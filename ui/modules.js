//
//
function ciniki_businesses_modules() {
    this.modules = null;

    this.init = function() {
        this.modules = new M.panel('Modules',
            'ciniki_businesses_modules', 'modules',
            'mc', 'medium', 'sectioned', 'ciniki.businesses.modules');
        this.modules.sections = {};
        this.modules.fieldValue = function(s, i, d) { return this.data[i].status; }
        this.modules.fieldHistoryArgs = function(s, i) {
            return {'method':'ciniki.businesses.getModuleHistory', 'args':{'business_id':M.curBusinessID, 'field':i}};
        }
        this.modules.addButton('save', 'Save', 'M.ciniki_businesses_modules.save();');
        this.modules.addClose('Cancel');
    }

    this.start = function(cb, appPrefix) {
        //
        // Create the app container if it doesn't exist, and clear it out
        // if it does exist.
        //
        var appContainer = M.createContainer(appPrefix, 'ciniki_businesses_modules', 'yes');
        if( appContainer == null ) {
            alert('App Error');
            return false;
        } 
        
        //
        // Get the detail for the user.  Do this for each request, to make sure
        // we have the current data.  If the user switches businesses, then we
        // want this data reloaded.
        //
        var rsp = M.api.getJSONCb('ciniki.businesses.getModules', 
            {'business_id':M.curBusinessID}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_businesses_modules.modules;
                p.data = {};
                //
                // Setup the list of modules into the form fields
                // 
                p.sections = {
                    'modules':{'label':'', 'hidelabel':'yes', 'fields':{}},
                    };
                for(i in rsp['modules']) {
                    var m = rsp['modules'][i]['module'];
                    p.data[m.package + '.' + m.name] = rsp.modules[i].module;
                    p.sections.modules.fields[m.package + '.' + m.name] = {
                        'id':m.name, 'label':m.label, 'type':'toggle', 'toggles':{'0':' Off ', '1':' On '},
                        };
                }

                p.show(cb);
            });
    }

    // 
    // Submit the form
    //
    this.save = function() {
        // Serialize the form data into a string for posting
        var c = this.modules.serializeForm('no');
        if( c != '' ) {
            var rsp = M.api.postJSONCb('ciniki.businesses.updateModules', 
                {'business_id':M.curBusinessID}, c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    M.ciniki_businesses_modules.modules.close();
                });
        } else {
            this.modules.close();
        }
    }
}
