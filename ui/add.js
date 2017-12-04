//
// This class will display the form to allow admins and tenant owners to 
// change the details of their tenant
//
function ciniki_tenants_add() {

    this.add = new M.panel('Add tenant', 'ciniki_tenants_add', 'add', 'mc', 'medium', 'sectioned', 'ciniki.tenants.add');
    this.add.data = null;
    this.add.sections = {
        'general':{'label':'General', 'fields':{
            'plan_id':{'label':'Plan', 'type':'select', 'options':{}},
            'payment_type':{'label':'Payment', 'type':'select', 'options':{'yearlycheque':'Yearly Cheque', 'monthlypaypal':'Monthly Paypal'}},
            'tenant.name':{'label':'Name', 'type':'text'},
            'tenant.category':{'label':'Category', 'type':'text'},
            'tenant.sitename':{'label':'Sitename', 'type':'text'},
            'tenant.tagline':{'label':'Tagline', 'type':'text'},
            }},
        'owner':{'label':'Owner', 'fields':{
            'owner.name.first':{'label':'First Name', 'type':'text', 'onchangeFn':'M.ciniki_tenants_add.add.updateContact'},
            'owner.name.last':{'label':'Last Name', 'type':'text', 'onchangeFn':'M.ciniki_tenants_add.add.updateContact'},
            'owner.name.display':{'label':'Display Name', 'type':'text'},
            'owner.email.address':{'label':'Email', 'type':'text', 'onchangeFn':'M.ciniki_tenants_add.add.updateEmail'},
            'owner.username':{'label':'Username', 'type':'text', 'onchangeFn':'M.ciniki_tenants_add.add.checkUsername'},
            'owner.password':{'label':'Password', 'type':'text'},
            }},
        'contact':{'label':'Contact', 'fields':{
            'contact.person.name':{'label':'Name', 'type':'text'},
            'contact.email.address':{'label':'Email', 'type':'text'},
            'contact.phone.number':{'label':'Phone', 'type':'text'},
            'contact.cell.number':{'label':'Cell', 'type':'text'},
            'contact.tollfree.number':{'label':'Tollfree', 'type':'text'},
            'contact.fax.number':{'label':'Fax', 'type':'text'},
            }},
        'address':{'label':'Address', 'fields':{
            'contact.address.street1':{'label':'Street', 'type':'text'},
            'contact.address.street2':{'label':'Street', 'type':'text'},
            'contact.address.city':{'label':'City', 'type':'text'},
            'contact.address.province':{'label':'Province', 'type':'text'},
            'contact.address.postal':{'label':'Postal', 'type':'text'},
            'contact.address.country':{'label':'Country', 'type':'text'},
            }},
        'modules':{'label':'Modules', 'fields':{}},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_tenants_add.add.save();'},
            }},
        };
    this.add.fieldValue = function(s, i, d) { 
        if( s == 'modules' ) { return 0; }
        return ''; 
    }
    this.add.updateContact = function(s, fid) {
        var f = this.formValue('owner.name.first');
        var l = this.formValue('owner.name.last');
        if( f != '' && l != '' ) {
            this.setFieldValue('contact.person.name', f + ' ' + l);
            this.setFieldValue('owner.name.display', f + ' ' + l[0]);
        } else if( f != '' ) {
            this.setFieldValue('contact.person.name', f);
            this.setFieldValue('owner.name.display', f);
        } else if( l != '' ) {
            this.setFieldValue('contact.person.name', l);
        }
    }
    this.add.updateEmail = function(s, fid) {
        this.setFieldValue('contact.email.address', this.formValue('owner.email.address'));
    }
    this.add.checkUsername = function(s, fid) {
        M.api.getJSONBgCb('ciniki.users.checkUsernameAvailable', 
            {'tnid':0, 'username':this.formValue('owner.username')}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                if( rsp.exists == 'yes' ) {
                    alert('Username taken');
                }
            });
    }
    this.add.open = function(cb) {
        M.api.getJSONCb('ciniki.tenants.getModules', {'tnid':0, 'plans':'yes'}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_tenants_add.add;
            //
            // Setup the list of modules into the form fields
            // 
            p.sections.modules.fields = {};
            for(i in rsp.modules) {
                p.sections.modules.fields[rsp.modules[i].package + '.' + rsp.modules[i].name] = {
                    'id':rsp.modules[i].name, 'label':rsp.modules[i].label, 'type':'toggle', 'toggles':{'0':' Off ', '1':' On '},
                    };
            }
            p.sections.general.fields.plan_id.options = {'0':'None'};
            if( rsp.plans != null ) {
                for(i in rsp.plans) {
                    p.sections.general.fields.plan_id.options[rsp.plans[i].plan.id] = rsp.plans[i].plan.name;
                }
            }
            p.show(cb);
        })
    }
    this.add.save = function() {
        // Serialize the form data into a string for posting
        var c = this.serializeFormSection('yes', 'general')
            + this.serializeFormSection('yes', 'contact')
            + this.serializeFormSection('yes', 'address')
            + this.serializeFormSection('yes', 'owner');
        if( document.getElementById(this.panelUID + '_tenant.name').value == '' ) {
            alert("You must specify a tenant name.");
            return false;
        }
        if( c == '' ) {
            alert("No changes to save");
            return false;
        } 
        if( c != '' ) {
            M.api.postJSONCb('ciniki.tenants.add', {}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }

                var tnid = rsp.id;
                if( M.ciniki_tenants_add.add.formValue('plan_id') == 0 ) {
                    var c = M.ciniki_tenants_add.add.serializeFormSection('no', 'modules');
                    M.api.postJSONCb('ciniki.tenants.updateModules', {'tnid':tnid}, c, function(rsp) {
                        if( rsp.stat != 'ok' ) {
                            M.api.err(rsp);
                            return false;
                        }
                        M.ciniki_tenants_add.add.close();
                    });
                } else {
                    M.ciniki_tenants_add.add.close();
                }
            });
        } else {
            this.close();
        }
    }
    this.add.addButton('save', 'Save', 'M.ciniki_tenants_add.add.save();');
    this.add.addClose('Cancel');

    this.start = function(cb, appPrefix) {
        //
        // Create the app container if it doesn't exist, and clear it out
        // if it does exist.
        //
        var appContainer = M.createContainer(appPrefix, 'ciniki_tenants_add', 'yes');
        if( appContainer == null ) {
            alert('App Error');
            return false;
        } 

        this.add.open(cb);
    }
}
