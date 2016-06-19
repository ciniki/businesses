//
// The app to manage businesses domains for a business
//
function ciniki_businesses_billing() {
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
        'cheque':'Cheque',
    };
    this.paymentFrequencies = {
        '10':'Monthly',
        '20':'Yearly',
    };
    this.init = function() {
        this.menu = new M.panel('Billing',
            'ciniki_businesses_billing', 'menu',
            'mc', 'medium', 'sectioned', 'ciniki.businesses.billing.menu');
        this.menu.data = {};
        this.menu.sections = {
            'subscription':{'label':'Subscription', 'type':'simplelist', 'list':{
                'status_text':{'label':'Status'},
                'currency':{'label':'Currency'},
                'monthly':{'label':'Monthly Amount'},
                'yearly':{'label':'Yearly Amount'},
                'trial':{'label':'Trial remaining'},
                'last_payment_date':{'label':'Last Payment'},
                }},
            '_edit':{'label':'', 'visible':'no', 'buttons':{
                'edit':{'label':'Edit Plan', 'fn':'M.ciniki_businesses_billing.showEdit(\'M.ciniki_businesses_billing.showMenu();\');'},
                }},
            '_paypal_button':{'label':'', 'visible':'no', 'buttons':{
                'subscribe':{'label':'Subscribe', 'fn':'paypal_form.submit();'},
                }},
            '_paypal':{'label':'', 'type':'html', 'visible':'no'},
            '_paypal_cancel':{'label':'', 'type':'html', 'visible':'no'},
            '_cancel_button':{'label':'', 'visible':'no', 'buttons':{
                'cancel':{'label':'Cancel Subscription', 'fn':'M.ciniki_businesses_billing.cancelSubscription();'},
                }},
        };
        this.menu.noData = function(s) { return 'No subscriptions added'; }
        // this.menu.sectionData = function(s) { return this.data[s]; }
        this.menu.listLabel = function(s, i, d) { return d.label; }
        this.menu.listValue = function(s, i, d) {
            if( s == 'subscription' ) {
                // You can only change the currency during the trial, before entering subcription information.
                if( i == 'currency' && (this.data.subscription.status == 2 || this.data.subscription.status == 60) ) {
                    if( this.data.subscription.currency == 'USD' ) {
                        return 'USD (Switch to <a href="javascript:M.ciniki_businesses_billing.changeCurrency(\'CAD\');">CAD</a>)';
                    } else if( this.data.subscription.currency == 'CAD' ) {
                        return 'CAD (Switch to <a href="javascript:M.ciniki_businesses_billing.changeCurrency(\'USD\');">USD</a>)';
                    }
                }
                switch (i) {
                    case 'monthly': return '$' + this.data.subscription.monthly + '/month';
                    case 'trial': return this.data.subscription.trial_remaining + ' days';
                }
                return this.data.subscription[i];
            }
        };
        this.menu.addClose('Back');

        //
        // Edit form for sysadmins to change the billing
        //
        this.edit = new M.panel('Edit Billing',
            'ciniki_businesses_billing', 'edit',
            'mc', 'medium', 'sectioned', 'ciniki.businesses.billing.edit');
        this.edit.data = null;
        this.edit.sections = {
            'subscription':{'label':'Subscription', 'fields':{
                'status':{'label':'Status', 'type':'select', 'options':this.statusOptions},
                'currency':{'label':'Currency', 'type':'text', 'size':'small'},
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
                'save':{'label':'Save', 'fn':'M.ciniki_businesses_billing.saveSubscription();'},
                }},
            };
        this.edit.fieldValue = function(s, i, d) { return this.data[i]; }
        this.edit.addButton('save', 'Save', 'M.ciniki_businesses_billing.saveSubscription();');
        this.edit.addClose('Cancel');
    }

    this.start = function(cb, ap, aG) {
        args = {};
        if( aG != null ) {
            args = eval(aG);
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

        if( args.business_id != null && args.business_id > 0 ) {
            M.curBusinessID = args.business_id;
            this.showEdit(cb);
        } else {
            this.showMenu(cb);
        }
    }

    this.showMenu = function(cb) {
        //
        // Set paypal buttons to invisible by default
        //
        this.menu.sections._paypal.visible = 'no';
        this.menu.sections._paypal_button.visible = 'no';
        this.menu.sections._paypal_cancel.visible = 'no';
        this.menu.sections._cancel_button.visible = 'no';

        //
        // Load domain list
        //
        var rsp = M.api.getJSONCb('ciniki.businesses.subscriptionInfo', 
            {'business_id':M.curBusinessID}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_businesses_billing.showMenuFinish(cb, rsp);
            });
    }

    this.showMenuFinish = function(cb, rsp) {
        this.menu.data = {'subscription':rsp.subscription};
        
        // Sysadmin, make edit button visible
        if( (M.userPerms&0x01) == 0x01 ) {
            this.menu.sections._edit.visible = 'yes';   
        } else {
            this.menu.sections._edit.visible = 'no';    
        }

        this.menu.sections.subscription.list.trial.visible = 'no';
        if( rsp.subscription.trial_remaining > 0 ) {
            this.menu.sections.subscription.list.trial.visible = 'yes';
        }

        if( (rsp.subscription.monthly > 0 && rsp.subscription.paypal_subscr_id == '') || rsp.subscription.status == 60 ) {
            //
            // Display subscribe button
            //
            this.menu.sections._paypal.visible = 'yes';
            this.menu.sections._paypal.html = '<form id="paypal_form" action="' + rsp.paypal.url + '" method="post">'
                + '<input type="hidden" name="cmd" value="_xclick-subscriptions">'
                + '<input type="hidden" name="business" value="' + rsp.paypal.business + '">'
                + '<input type="hidden" name="item_name" value="' + rsp.paypal.prefix + ' - ' + M.curBusiness.name + '">'
                + '<input type="hidden" name="item_number" value="' + rsp.subscription.uuid + '">'
                + '<input type="hidden" name="currency_code" value="' + rsp.subscription.currency + '">'
                + '';
            if( rsp.subscription.trial_remaining > 0 ) {
                this.menu.sections._paypal.html += '<input type="hidden" name="a1" value="0.00">'
                    + '<input type="hidden" name="p1" value="' + rsp.subscription.trial_remaining + '">'
                    + '<input type="hidden" name="t1" value="D">'
                    + '';
            }
            this.menu.sections._paypal.html += '<input type="hidden" name="a3" value="' + rsp.subscription.monthly + '">'
                + '<input type="hidden" name="p3" value="1">'
                + '<input type="hidden" name="t3" value="M">'
                + '<input type="hidden" name="src" value="1">'
                + '<input type="hidden" name="no_shipping" value="1">'
                + '<input type="hidden" name="notify_url" value="' + rsp.paypal.ipn + '">'
                + '</form>'
                + '';
            this.menu.sections._paypal_button.visible = 'yes';
            this.menu.sections._paypal_button.buttons.subscribe.label = 'Subscribe';
        }
        else if( (rsp.subscription.yearly > 0 && rsp.subscription.paypal_subscr_id == '') || rsp.subscription.status == 60 ) {
            //
            // Display subscribe button
            //
            this.menu.sections._paypal.visible = 'yes';
            this.menu.sections._paypal.html = '<form id="paypal_form" action="' + rsp.paypal.url + '" method="post">'
                + '<input type="hidden" name="cmd" value="_xclick-subscriptions">'
                + '<input type="hidden" name="business" value="' + rsp.paypal.business + '">'
                + '<input type="hidden" name="item_name" value="' + rsp.paypal.prefix + ' - ' + M.curBusiness.name + '">'
                + '<input type="hidden" name="item_number" value="' + rsp.subscription.uuid + '">'
                + '<input type="hidden" name="currency_code" value="' + rsp.subscription.currency + '">'
                + '';
            if( rsp.subscription.trial_remaining > 0 ) {
                this.menu.sections._paypal.html += '<input type="hidden" name="a1" value="0.00">'
                    + '<input type="hidden" name="p1" value="' + rsp.subscription.trial_remaining + '">'
                    + '<input type="hidden" name="t1" value="D">'
                    + '';
            }
            this.menu.sections._paypal.html += '<input type="hidden" name="a3" value="' + rsp.subscription.yearly + '">'
                + '<input type="hidden" name="p3" value="1">'
                + '<input type="hidden" name="t3" value="Y">'
                + '<input type="hidden" name="src" value="1">'
                + '<input type="hidden" name="no_shipping" value="1">'
                + '<input type="hidden" name="notify_url" value="' + rsp.paypal.ipn + '">'
                + '</form>'
                + '';
            this.menu.sections._paypal_button.visible = 'yes';
            this.menu.sections._paypal_button.buttons.subscribe.label = 'Subscribe';
        }

//
// Modifications in paypal do not work well.  New amount cannot be more than %20.  You cannot modify during trial, 
// or customer is billed right away.
// All modifications must be a cancel and new subscription. :(
//
//      else if( rsp.subscription.monthly > 0 && rsp.subscription.paypal_subscr_id != '' && rsp.subscription.status == 1 ) {
            //
            // Display modify button
            //
//          this.menu.sections._paypal.visible = 'yes';
//          this.menu.sections._paypal.html = '<form id="paypal_form" action="' + rsp.paypal.url + '" method="post">'
//              + '<input type="hidden" name="cmd" value="_xclick-subscriptions">'
//              + '<input type="hidden" name="business" value="' + rsp.paypal.business + '">'
//              + '<input type="hidden" name="item_name" value="' + rsp.paypal.prefix + ' - ' + M.curBusiness.name + '">'
//              + '<input type="hidden" name="item_number" value="' + rsp.subscription.uuid + '">'
//              + '<input type="hidden" name="currency_code" value="' + rsp.subscription.currency + '">'
//              + '<input type="hidden" name="subscr_id" value="' + rsp.subscription.paypal_subscr_id + '">'
//              + '<input type="hidden" name="a1" value="0">'
//              + '<input type="hidden" name="p1" value="' + rsp.subscription.trial_remaining + '">'
//              + '<input type="hidden" name="t1" value="D">'
//              + '<input type="hidden" name="a3" value="' + rsp.subscription.monthly + '">'
//              + '<input type="hidden" name="p3" value="1">'
//              + '<input type="hidden" name="t3" value="M">'
//              + '<input type="hidden" name="src" value="1">'
//              + '<input type="hidden" name="no_shipping" value="1">'
//              + '<input type="hidden" name="modify" value="2">'
//              + '<input type="hidden" name="notify_url" value="' + rsp.paypal.ipn + '">'
//              + '</form>'
//              + '';
//          this.menu.sections._paypal_button.visible = 'yes';
//          this.menu.sections._paypal_button.buttons.subscribe.label = 'Update Subscription';
//      }

        else if( rsp.subscription.monthly > 0 && rsp.subscription.paypal_subscr_id != '' 
            && (rsp.subscription.status == 10 || rsp.subscription.status == 1) ) {
            this.menu.sections._paypal_cancel.visible = 'yes';
            this.menu.sections._paypal_cancel.html = 'If you cancel your subscription, your business will be suspended and deleted after 30 days.';
            this.menu.sections._cancel_button.visible = 'yes';
            this.menu.sections._cancel_button.buttons.cancel.label = 'Cancel Subscription';
            
        }
        this.menu.refresh();
        this.menu.show(cb);
    }

    this.changeCurrency = function(currency) {
        var rsp = M.api.getJSONCb('ciniki.businesses.subscriptionChangeCurrency', 
            {'business_id':M.curBusinessID, 'currency':currency}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_businesses_billing.showMenu();
            });
    };

    this.cancelSubscription = function() {
        if( confirm("Are you sure you want to cancel your subscription?  This will suspend your business and you will no longer be able to make any changes.") ) {
            var rsp = M.api.getJSONCb('ciniki.businesses.subscriptionCancel', 
                {'business_id':M.curBusinessID}, function(rsp) { 
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    M.ciniki_businesses_billing.showMenu(); 
                });
        }
    }

    this.showEdit = function(cb, sid) {
        if( sid != null ) {
            this.edit.subscription_id = sid;
        }
        var rsp = M.api.getJSONCb('ciniki.businesses.subscriptionInfo', 
            {'business_id':M.curBusinessID}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_businesses_billing.edit;
                p.data = rsp.subscription;
                if( p.data.currency == null ) {
                    p.data.currency = 'USD';
                }
                if( p.data.trial_days == null ) {
                    p.data.trial_days = '60';
                }
                p.refresh();
                p.show(cb);
            });
    };

    this.saveSubscription = function() {
        var c = this.edit.serializeForm('no');
        if( c != '' ) {
            var rsp = M.api.postJSONCb('ciniki.businesses.subscriptionUpdate', 
                {'business_id':M.curBusinessID}, c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    M.ciniki_businesses_billing.edit.close();
                });
        } else {
            this.edit.close();
        }
    };
};
