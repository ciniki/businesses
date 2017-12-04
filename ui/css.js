//
// This class will display the form to allow admins and tenant owners to 
// change the details of their tenant
//
function ciniki_tenants_css() {
    this.settings = null;

    this.init = function() {
        this.settings = new M.panel('Ciniki Manage CSS',
            'ciniki_tenants_css', 'settings',
            'mc', 'medium', 'sectioned', 'ciniki.tenant.css');
        this.settings.sections = {
            'general':{'label':'Custom CSS for ciniki-manage', 'fields':{
                'ciniki.manage.css':{'label':'', 'hidelabel':'yes', 'type':'textarea'},
                }},
            };
        this.settings.fieldValue = function(s, i, d) { 
            if( this.data != null && this.data[i] != null ) {
                return this.data[i]; 
            } 
            return '';
        }
        this.settings.fieldHistoryArgs = function(s, i) {
            return {'method':'ciniki.tenants.getDetailHistory', 'args':{'tnid':M.curTenantID, 'field':i}};
        }
        this.settings.addButton('save', 'Save', 'M.ciniki_tenants_css.save();');
        this.settings.addClose('Cancel');
    }

    this.start = function(cb, appPrefix) {
        //
        // Create the app container if it doesn't exist, and clear it out
        // if it does exist.
        //
        var appContainer = M.createContainer(appPrefix, 'ciniki_tenants_css', 'yes');
        if( appContainer == null ) {
            alert('App Error');
            return false;
        } 
        
        //
        // Get the detail for the tenant.  
        //
        var rsp = M.api.getJSONCb('ciniki.tenants.getDetails', 
            {'tnid':M.curTenantID, 'keys':'ciniki'}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_tenants_css.settings;
                p.data = rsp.details;
                p.refresh();
                p.show(cb);
            });
    }

    // 
    // Submit the form
    //
    this.save = function() {
        // Serialize the form data into a string for posting
        var c = this.settings.serializeForm('no');
        if( c != '' ) {
            var rsp = M.api.postJSONCb('ciniki.tenants.updateDetails', 
                {'tnid':M.curTenantID}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_tenants_css.settings.close();
            });
        } else {
            this.settings.close();
        }
    }
}
