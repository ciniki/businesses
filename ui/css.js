//
// This class will display the form to allow admins and business owners to 
// change the details of their business
//
function ciniki_businesses_css() {
    this.settings = null;

    this.init = function() {
        this.settings = new M.panel('Ciniki Manage CSS',
            'ciniki_businesses_css', 'settings',
            'mc', 'medium', 'sectioned', 'ciniki.business.css');
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
            return {'method':'ciniki.businesses.getDetailHistory', 'args':{'business_id':M.curBusinessID, 'field':i}};
        }
        this.settings.addButton('save', 'Save', 'M.ciniki_businesses_css.save();');
        this.settings.addClose('Cancel');
    }

    this.start = function(cb, appPrefix) {
        //
        // Create the app container if it doesn't exist, and clear it out
        // if it does exist.
        //
        var appContainer = M.createContainer(appPrefix, 'ciniki_businesses_css', 'yes');
        if( appContainer == null ) {
            alert('App Error');
            return false;
        } 
        
        //
        // Get the detail for the business.  
        //
        var rsp = M.api.getJSONCb('ciniki.businesses.getDetails', 
            {'business_id':M.curBusinessID, 'keys':'ciniki'}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_businesses_css.settings;
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
            var rsp = M.api.postJSONCb('ciniki.businesses.updateDetails', 
                {'business_id':M.curBusinessID}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_businesses_css.settings.close();
            });
        } else {
            this.settings.close();
        }
    }
}
