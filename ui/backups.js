//
// This class will display the form to allow admins and business owners to 
// change the details of their business
//
function ciniki_businesses_backups() {
	this.init = function() {
		this.main = new M.panel('Backups',
			'ciniki_businesses_backups', 'main',
			'mc', 'narrow', 'sectioned', 'ciniki.businesses.backups.main');
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
				return "<button onclick=\"event.stopPropagation(); M.ciniki_businesses_backups.downloadBackup(\'" + d.backup.id + "\'); return false;\">Download</button>"
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
		var appContainer = M.createContainer(appPrefix, 'ciniki_businesses_backups', 'yes');
		if( appContainer == null ) {
			alert('App Error');
			return false;
		} 
	
		this.showMain(cb);
	}

	this.showMain = function(cb) {
		M.api.getJSONCb('ciniki.businesses.backupList', {'business_id':M.curBusinessID}, function(rsp) {
			if( rsp.stat != 'ok' ) {
				M.api.err(rsp);
				return false;
			}
			var p = M.ciniki_businesses_backups.main;
			p.data = {'backups':rsp.backups};
			p.refresh();
			p.show(cb);
		});
	}

	this.downloadBackup = function(bid) {
		M.api.openFile('ciniki.businesses.backupDownload', {'business_id':M.curBusinessID, 'backup_id':bid});
	}
}
