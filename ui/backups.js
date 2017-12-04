//
// This class will display the form to allow admins and tenant owners to 
// change the details of their tenant
//
function ciniki_tenants_backups() {
    this.init = function() {
        this.main = new M.panel('Backups',
            'ciniki_tenants_backups', 'main',
            'mc', 'narrow', 'sectioned', 'ciniki.tenants.backups.main');
        this.main.data = {};
        this.main.sections = {
            'backups':{'label':'Backups', 'type':'simplegrid', 'num_cols':2,
                'noData':'No backups available',
                },
            };
        this.main.sectionData = function(s) { return this.data[s]; }
        this.main.cellValue = function(s, i, j, d) {
            if( j == 0 ) {
                return d.backup.name;
            }
            if( j == 1 ) {
                return "<button onclick=\"event.stopPropagation(); M.ciniki_tenants_backups.downloadBackup(\'" + d.backup.id + "\'); return false;\">Download</button>"
            }
        };
        this.main.noData = function(s) {
            return this.sections[s].noData;
        }
        this.main.addClose('Cancel');
    }

    this.start = function(cb, appPrefix) {
        //
        // Create the app container if it doesn't exist, and clear it out
        // if it does exist.
        //
        var appContainer = M.createContainer(appPrefix, 'ciniki_tenants_backups', 'yes');
        if( appContainer == null ) {
            alert('App Error');
            return false;
        } 
    
        this.showMain(cb);
    }

    this.showMain = function(cb) {
        M.api.getJSONCb('ciniki.tenants.backupList', {'tnid':M.curTenantID}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_tenants_backups.main;
            p.data = {'backups':rsp.backups};
            p.refresh();
            p.show(cb);
        });
    }

    this.downloadBackup = function(bid) {
        M.api.openFile('ciniki.tenants.backupDownload', {'tnid':M.curTenantID, 'backup_id':bid});
    }
}
