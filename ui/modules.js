//
//
function ciniki_businesses_modules() {
    this.modules = null;

    this.modules = new M.panel('Modules', 'ciniki_businesses_modules', 'modules', 'mc', 'medium', 'sectioned', 'ciniki.businesses.modules');
    this.modules.sections = {
        'modules':{'label':'', 'hidelabel':'yes', 'fields':{}},
    }
    this.modules.fieldValue = function(s, i, d) { return this.data[i].status; }
    this.modules.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.businesses.getModuleHistory', 'args':{'business_id':M.curBusinessID, 'field':i}};
    }
    this.modules.open = function(cb) {
        M.api.getJSONCb('ciniki.businesses.getModules', {'business_id':M.curBusinessID}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_businesses_modules.modules;
            p.data = {};
            p.sections.modules.fields = {};
            for(i in rsp.modules) {
                p.data[rsp.modules[i].package + '.' + rsp.modules[i].name] = rsp.modules[i];
                p.sections.modules.fields[rsp.modules[i].package + '.' + rsp.modules[i].name] = {
                    'id':rsp.modules[i].name, 'label':rsp.modules[i].label, 'type':'toggle', 'toggles':{'0':' Off ', '1':' On '},
                    };
            }
            p.show(cb);
        });
    }
    this.modules.save = function() {
        // Serialize the form data into a string for posting
        var c = this.serializeForm('no');
        if( c != '' ) {
            M.api.postJSONCb('ciniki.businesses.updateModules', {'business_id':M.curBusinessID}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_businesses_modules.modules.close();
            });
        } else {
            this.close();
        }
    }
    this.modules.addButton('save', 'Save', 'M.ciniki_businesses_modules.modules.save();');
    this.modules.addClose('Cancel');

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
        this.modules.open(cb);    
    }
}
