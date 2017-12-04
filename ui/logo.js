//
//
function ciniki_tenants_logo() {
    //
    // The panel
    //
    this.settings = null;

    this.init = function() {
        this.edit = new M.panel('Tenant Logo',
            'ciniki_tenants_logo', 'edit',
            'mc', 'narrow', 'sectioned', 'ciniki.tenants.logo.edit');

        //
        // Setup the form
        //
        this.edit.data = {'image_id':0};
        this.edit.sections = {
            '_image':{'label':'Logo', 'type':'imageform', 'fields':{
                'image_id':{'label':'', 'hidelabel':'yes', 'controls':'all', 'type':'image_id'},
                }},
            '_save':{'label':'', 'buttons':{
                'save':{'label':'Save', 'fn':'M.ciniki_tenants_logo.saveLogo();'},
                'delete':{'label':'Delete', 'fn':'M.ciniki_tenants_logo.deleteLogo();'},
                }},
        };
        this.edit.fieldValue = function(s, i, d) { return this.data[i]; }
        this.edit.addDropImage = function(iid) {
            this.setFieldValue('image_id', iid);
            return true;
        };
        this.edit.deleteImage = function(fid) {
            this.setFieldValue('image_id', 0);
            return true;
        };
        this.edit.addClose('Cancel');
    }

    this.start = function(cb, appPrefix) {
        //
        // Create the app container if it doesn't exist, and clear it out
        // if it does exist.
        //
        var appContainer = M.createContainer(appPrefix, 'ciniki_tenants_logo', 'yes');
        if( appContainer == null ) {
            alert('App Error');
            return false;
        } 

        // Get the detail for the users preferences.  
        var rsp = M.api.getJSONCb('ciniki.tenants.logoGet', 
            {'tnid':M.curTenantID}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_tenants_logo.edit;
                p.data.image_id = rsp.logo_id;
                p.refresh();
                p.show(cb);
            });
    }

    // 
    // Submit the form
    //
    this.saveLogo = function() {
        var c = this.edit.serializeForm('no');  
        if( c != '' ) {
            var rsp = M.api.postJSONCb('ciniki.tenants.logoSave', 
                {'tnid':M.curTenantID}, c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    M.ciniki_tenants_logo.edit.close();
                });
        } else {
            this.edit.close();
        }
    }

    this.deleteLogo = function() {
        if( confirm("Are you sure you want to remove the logo from your tenant?") ) {
            var rsp = M.api.getJSONCb('ciniki.tenants.logoDelete', 
                {'tnid':M.curTenantID}, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    M.ciniki_tenants_logo.edit.close();
                });
        }
    }
}

