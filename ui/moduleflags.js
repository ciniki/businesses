//
//
function ciniki_tenants_moduleflags() {
    this.modules = null;

    this.modules = new M.panel('Modules', 'ciniki_tenants_moduleflags', 'modules', 'mc', 'medium', 'sectioned', 'ciniki.tenants.moduleflags.modules');
    this.modules.data = {};
    this.modules.fieldValue = function(s, i, d) { return this.data[i].flags; }
    this.modules.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.tenants.getModuleFlagsHistory', 'args':{'tnid':M.curTenantID, 'field':i}};
    }
    this.modules.open = function(cb) {
        M.api.getJSONCb('ciniki.tenants.getModuleFlags', {'tnid':M.curTenantID}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_tenants_moduleflags.modules;
            p.sections = {};
            //
            // Setup the list of modules into the form fields
            // 
            p.data = rsp.modules;   
            for(i in rsp.modules) {
                if( rsp.modules[i].available_flags != null ) {
                    var flags = {};
                    for(j in rsp.modules[i].available_flags) {
                        flags[rsp.modules[i].available_flags[j].flag.bit] =
                            {'name':rsp.modules[i].available_flags[j].flag.name};
                    }
                    p.sections[i] = {
                        'label':rsp.modules[i].proper_name,
                        'fields':{}};
                    p.sections[i].fields[i] = {'label':'',
                        'hidelabel':'yes', 'type':'flags', 'join':'no', 'flags':flags
                    };
                }
            }
            p.refresh();
            p.show(cb);
        });
    }
    this.modules.save = function() {
        var c = this.serializeForm('no');
        if( c != '' ) {
            M.api.postJSONCb('ciniki.tenants.updateModuleFlags', {'tnid':M.curTenantID}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_tenants_moduleflags.modules.close();
            });
        } else {
            this.close();
        }
    }
    this.modules.addButton('save', 'Save', 'M.ciniki_tenants_moduleflags.modules.save();');
    this.modules.addClose('Cancel');

    this.start = function(cb, appPrefix) {
        //
        // Create the app container if it doesn't exist, and clear it out
        // if it does exist.
        //
        var appContainer = M.createContainer(appPrefix, 'ciniki_tenants_moduleflags', 'yes');
        if( appContainer == null ) {
            alert('App Error');
            return false;
        } 

        this.modules.open(cb);
    }
}
