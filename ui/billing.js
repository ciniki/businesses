//
// The app to manage businesses domains for a business
//
function ciniki_businesses_billing() {

    this.script = null;

    this.statusOptions = {
        '0':'Unknown',
        '1':'Update required',
        '2':'Trial',
        '10':'Active',
        '11':'Free Subscription',
        '50':'Suspended',
        '60':'Cancelled',
        };
    this.paymentTypes = {
        '':'None',
        'paypal':'Paypal',
        'stripe':'Stripe',
        'cheque':'Cheque',
    };
    this.currencies = {
        'CAD':'CAD',
        'USD':'USD',
    };
    this.paymentFrequencies = {
        '10':'Monthly',
        '20':'Yearly',
    };

    //
    // The main menu/subscribe form
    //
    this.menu = new M.panel('Billing', 'ciniki_businesses_billing', 'menu', 'mc', 'medium', 'sectioned', 'ciniki.businesses.billing.menu');
    this.menu.subscription_id = 0;
    this.menu.business_id = 0;
    this.menu.data = {};
    this.menu.sections = {
        'info':{'label':'Subscription', 'list':{
            'status_text':{'label':'Status'},
            'payments':{'label':'Payments'},
            }},
        'subscription':{'label':'Subscription', 
            'visible':function() { return (M.ciniki_businesses_billing.menu.data != null && M.ciniki_businesses_billing.menu.data.status == 2) ? 'yes' : 'no'; }, 
            'fields':{
                'currency':{'label':'Currency', 'type':'toggle', 'toggles':this.currencies,
                    'onchange':'M.ciniki_businesses_billing.menu.updatePayments',
                    },
                'payment_frequency':{'label':'Frequency', 'type':'toggle', 'toggles':this.paymentFrequencies,
                    'onchange':'M.ciniki_businesses_billing.menu.updatePayments',
                    },
                'billing_email':{'label':'Billing Email', 'type':'text'},
            }},
        '_buttons':{'label':'', 'buttons':{
            'stripe':{'label':'Subscribe with Credit Card', 
                'visible':function() { return (M.ciniki_businesses_billing.menu.data != null && M.ciniki_businesses_billing.menu.data.status == 2) ? 'yes' : 'no'; }, 
                'fn':'M.ciniki_businesses_billing.menu.save(\'M.ciniki_businesses_billing.menu.paynow();\');'},
            'edit':{'label':'Edit', 
                'visible':function() { return (M.userPerms&0x01) == 0x01 ? 'yes': 'no'; }, 
                'fn':'M.ciniki_businesses_billing.edit.open(\'M.ciniki_businesses_billing.menu.open();\',M.ciniki_businesses_billing.menu.business_id);'},
            'delete':{'label':'Cancel Subscription', 
                'visible':function() { return (M.ciniki_businesses_billing.menu.data != null && M.ciniki_businesses_billing.menu.data.status == 10) ? 'yes' : 'no'; }, 
                'fn':'M.ciniki_businesses_billing.menu.cancelSubscription();'},
            }},
    }
    this.menu.listLabel = function(s, i, d) { return d.label; }
    this.menu.listValue = function(s, i, d) { 
        return this.data[i]; 
    }
    this.menu.fieldValue = function(s, i, d) { 
        if( i == 'billing_email' && this.data[i] == '' ) {
            return this.data.business_email;
        }
        return this.data[i]; 
    }
    this.menu.open = function(cb, bid) {
        if( bid != null ) { this.business_id = bid; }
        M.api.getJSONCb('ciniki.businesses.subscriptionInfo', {'business_id':this.business_id}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_businesses_billing.menu;
            p.data = rsp.subscription;
            if( p.data.status == 2 ) {
                p.stripeHandler = StripeCheckout.configure({
                    key: p.data.stripe_public_key,
                    image: 'https://s3.amazonaws.com/stripe-uploads/acct_104IPT4DnKptjnBBmerchant-icon-319979-logo.jpg',
                    locale: 'auto',
                    allowRememberMe: false,
                    token: function(token) {
                        var c = M.ciniki_businesses_billing.menu.serializeForm('no');
                        c += '&token=' + encodeURIComponent(token.id);
                        M.api.postJSONCb('ciniki.businesses.subscriptionStripeProcess', 
                            {'business_id':M.ciniki_businesses_billing.menu.business_id, 'action':'subscribe'}, c, function(rsp) {
                                if( rsp.stat != 'ok' ) {
                                    M.api.err(rsp);
                                    return false;
                                }
                                // Reopen this window to show new status, etc
                                M.ciniki_businesses_billing.menu.open()
                            });
                    },
                });
            }
            p.refresh();
            p.show(cb);
            p.updatePayments();
        });
    }
    this.menu.updatePayments = function(s, i) {
        var f = this.formValue('payment_frequency');
        if( f == 0 ) { f = this.data.payment_frequency; }
        var c = this.formValue('currency');
        if( c == 0 ) { c = this.data.currency; }
        if( f == 10 ) {
            this.data.payment_amount = this.data.monthly;
            this.data.payments = '$' + this.data.monthly + '/month (' + c + ')';
        } else if( f == 20 ) {
            this.data.payment_amount = this.data.yearly;
            this.data.payments = '$' + this.data.yearly + '/year (' + c + ')';
        }
        this.refreshSection('info');
    }
    this.menu.save = function(cb) {
        // Skip if not the correct status for updating
        if( this.data.status != 2 ) { 
            this.close(); 
            return true; 
        }
        if( cb == null ) { cb = 'M.ciniki_businesses_billing.menu.close();'; }
        var c = this.serializeForm('no');
        if( c != '' ) {
            console.log(c);
            M.api.postJSONCb('ciniki.businesses.subscriptionCustomerUpdate', {'business_id':this.business_id}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                eval(cb);
            });
        } else {
            eval(cb);
        }
    }
    this.menu.paynow = function() {
        var currency = this.formValue('currency');
        var payment_frequency = this.formValue('payment_frequency');
        this.stripeHandler.open({
            name: 'Ciniki',
            description: this.data.payments,
            zipCode: true,
            currency: currency,
            amount: parseFloat(this.data.payment_amount) * 100,
            });
    }
    this.menu.cancelSubscription = function() {
        if( confirm("Are you sure you want to cancel your subscription and close your account?") ) {
            M.api.getJSONCb('ciniki.businesses.subscriptionCancel', {'business_id':this.business_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_businesses_billing.menu.open();
            });
        }
    }
    this.menu.addLeftButton('back', 'Back', 'M.ciniki_businesses_billing.menu.save();');

    //
    // Edit form for sysadmins to change the billing
    //
    this.edit = new M.panel('Edit Billing', 'ciniki_businesses_billing', 'edit', 'mc', 'medium', 'sectioned', 'ciniki.businesses.billing.edit');
    this.edit.business_id = 0;
    this.edit.data = null;
    this.edit.sections = {
        'subscription':{'label':'Subscription', 'fields':{
            'status':{'label':'Status', 'type':'select', 'options':this.statusOptions},
            'currency':{'label':'Currency', 'type':'toggle', 'toggles':this.currencies},
            'monthly':{'label':'Monthly', 'type':'text', 'size':'small'},
            'yearly':{'label':'Yearly', 'type':'text', 'size':'small'},
            'payment_type':{'label':'Type', 'type':'toggle', 'toggles':this.paymentTypes},
            'payment_frequency':{'label':'Frequency', 'type':'toggle', 'toggles':this.paymentFrequencies},
            'last_payment_date':{'label':'Last Payment', 'type':'text', 'size':'medium'},
            'paid_until':{'label':'Paid Until', 'type':'date', 'size':'small'},
            }},
        '_trial':{'label':'Trial', 'fields':{
            'trial_start_date':{'label':'Start', 'type':'date', 'size':'small'},
            'trial_days':{'label':'Days', 'type':'text', 'size':'small'},
            }},
        '_notes':{'label':'Notes', 'fields':{
            'notes':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'medium'},
            }},
        '_save':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_businesses_billing.edit.save();'},
            }},
        };
    this.edit.fieldValue = function(s, i, d) { return this.data[i]; }
    this.edit.open = function(cb, bid) {
        if( bid != null ) { this.business_id = bid; }
        M.api.getJSONCb('ciniki.businesses.subscriptionInfo', {'business_id':this.business_id}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_businesses_billing.edit;
            p.data = rsp.subscription;
            p.subscription_id = rsp.subscription.id;
            if( p.data.currency == null ) {
                p.data.currency = 'USD';
            }
            if( p.data.trial_days == null ) {
                p.data.trial_days = '60';
            }
            p.refresh();
            p.show(cb);
        });
    }
    this.edit.save = function() {
        var c = this.serializeForm('no');
        if( c != '' ) {
            M.api.postJSONCb('ciniki.businesses.subscriptionUpdate', {'business_id':this.business_id}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_businesses_billing.edit.close();
            });
        } else {
            this.close();
        }
    } 
    this.edit.addButton('save', 'Save', 'M.ciniki_businesses_billing.edit.save();');
    this.edit.addClose('Cancel');

    //
    // Start the app
    //
    this.start = function(cb, ap, aG) {
        this.cb = cb;
        this.args = {};
        if( aG != null ) {
            this.args = eval(aG);
        }

        //
        // Create the app container if it doesn't exist, and clear it out
        // if it does exist.
        //
        var appContainer = M.createContainer(ap, 'ciniki_businesses_billing', 'yes');
        if( appContainer == null ) {
            alert('App Error');
            return false;
        } 

        //
        // Load the stripe library
        //
        if( this.script == null ) {
            M.startLoad();
            this.script = document.createElement('script');
            this.script.type = 'text/javascript';
            // this.script.src = 'https://js.stripe.com/v2/';
            this.script.src = 'https://checkout.stripe.com/checkout.js';
            var done = false;
            var head = document.getElementsByTagName('head')[0];
            this.script.onerror = function() {
                M.stopLoad();
                alert("Unable to load, please report this bug.");
            };

            // Attach handlers for all browsers

            this.script.onload = this.script.onreadystatechange = function() {
                M.stopLoad();
                if(!done&&(!this.readyState||this.readyState==="loaded"||this.readyState==="complete")){
                    done = true;
                 
                    M.ciniki_businesses_billing.startAfterStripe();
                   
                    // Handle memory leak in IE
    //                script.onload = script.onreadystatechange = null;
    //                if(head&&script.parentNode){
    //                    head.removeChild( script );
    //                }    
                }    
            };
            head.appendChild(this.script);
        } else {
            this.startAfterStripe();
        }
    }

    this.startAfterStripe = function() {
        if( this.args.business_id != null && this.args.business_id > 0 ) {
            this.menu.open(this.cb, this.args.business_id);
        } else {
            this.menu.open(this.cb, M.curBusinessID);
        }
    }
}
