//
// The app to manage businesses domains for a business
//
function ciniki_businesses_domains() {
	
	this.domainFlags = {
		'1':{'name':'Primary'},
		};
	this.domainStatus = {
		'1':'Active',
		'50':'Suspended',
		'60':'Deleted',
		};
	
	this.init = function() {
		//
		// Main menu
		//
		this.menu = new M.panel('Web Domains',
			'ciniki_businesses_domains', 'menu',
			'mc', 'medium', 'sectioned', 'ciniki.businesses.domains.menu');
		this.menu.data = {};
		this.menu.sections = {
			'domains':{'label':'', 'type':'simplegrid', 'num_cols':1,
				'headerValues':null,
				'cellClasses':['multiline'],
				},
		};
		this.menu.noData = function(s) { return 'No domains added'; }
		this.menu.sectionData = function(s) { return this.data; }
		this.menu.cellValue = function(s, i, j, d) {
			var primary = '';
			if( d.domain.isprimary == 'yes' ) {
				primary = ' (primary)';
			}
			var managed = '';
			if( d.domain.managed_by != '' ) {
				managed = ' - ' + d.domain.managed_by;
			}
			return '<span class="maintext">' + d.domain.domain + primary + '</span><span class="subtext">' + d.domain.expiry_date + managed + '</span>';
		}
		this.menu.rowFn = function(s, i, d) {
			return 'M.ciniki_businesses_domains.showEdit(\'M.ciniki_businesses_domains.showMenu();\',\'' + d.domain.id + '\');';
		};
		this.menu.addButton('add', 'Add', 'M.ciniki_businesses_domains.showEdit(\'M.ciniki_businesses_domains.showMenu();\',0);');
		this.menu.addClose('Back');

		//
		// Edit panel
		//
		this.edit = new M.panel('Edit Domain',
			'ciniki_businesses_domains', 'edit',
			'mc', 'medium', 'sectioned', 'ciniki.businesses.domains.edit');
		this.edit.data = {'status':'1'};
		this.edit.sections = {
			'info':{'label':'', 'fields':{
				'domain':{'label':'Domain/Site', 'type':'text'},
				'flags':{'label':'', 'type':'flags', 'join':'yes', 'flags':this.domainFlags},
				'status':{'label':'Status', 'type':'multitoggle', 'toggles':this.domainStatus},
				'expiry_date':{'label':'Expiry', 'type':'date'},
				'managed_by':{'label':'Managed', 'type':'text'},
				}},
			'_buttons':{'label':'', 'buttons':{
				'save':{'label':'Save', 'fn':'M.ciniki_businesses_domains.saveDomain();'},
				'delete':{'label':'Delete', 'fn':'M.ciniki_businesses_domains.removeDomain();'},
				}},
			};
		this.edit.fieldValue = function(s, i, d) { return this.data[i]; }
		this.edit.fieldHistoryArgs = function(s, i) {
			return {'method':'ciniki.businesses.domainHistory', 'args':{'business_id':M.curBusinessID, 
				'domain_id':M.ciniki_businesses_domains.edit.domain_id, 'field':i}};
		}
		this.edit.addButton('save', 'Save', 'M.ciniki_businesses_domains.saveDomain();');
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
		var appContainer = M.createContainer(ap, 'ciniki_businesses_domains', 'yes');
		if( appContainer == null ) {
			alert('App Error');
			return false;
		} 

		if( args != null && args.business != null && args.business != '' ) {
			M.curBusinessID = args.business;
		}
		if( args != null && args.domain != null && args.domain != '' ) {
			this.showEdit(cb, args.domain);
		} else {
			this.showMenu(cb);
		}
	}

	this.showMenu = function(cb) {
		var rsp = M.api.getJSONCb('ciniki.businesses.domainList', 
			{'business_id':M.curBusinessID}, function(rsp) {
				if( rsp.stat != 'ok' ) {
					M.api.err(rsp);
					return false;
				}
				var p = M.ciniki_businesses_domains.menu;
				p.data = rsp.domains;
				p.refresh();
				p.show(cb);
			});
	}

	this.showEdit = function(cb, did) {
		this.edit.reset();
		if( did != null ) {
			this.edit.domain_id = did;
		}
		if( this.edit.domain_id > 0 ) {
			this.edit.sections._buttons.buttons.delete.visible = 'yes';
			var rsp = M.api.getJSONCb('ciniki.businesses.domainGet', 
				{'business_id':M.curBusinessID, 'domain_id':this.edit.domain_id}, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					var p = M.ciniki_businesses_domains.edit;
					p.data = rsp.domain;
					p.refresh();
					p.show(cb);
				});
		} else {
			this.edit.sections._buttons.buttons.delete.visible = 'no';
			this.edit.data = {};
			this.edit.refresh();
			this.edit.show(cb);
		}
	};

	this.saveDomain = function() {
		if( this.edit.domain_id > 0 ) {
			var c = this.edit.serializeForm('no');
			if( c != '' ) {
				var rsp = M.api.postJSONCb('ciniki.businesses.domainUpdate', 
					{'business_id':M.curBusinessID, 'domain_id':this.edit.domain_id}, c, function(rsp) {
						if( rsp.stat != 'ok' ) {
							M.api.err(rsp);
							return false;
						} 
						M.ciniki_businesses_domains.edit.close();
					});
			} else {
				this.edit.close();
			}
		} else {
			var c = this.edit.serializeForm('yes');
			var rsp = M.api.postJSONCb('ciniki.businesses.domainAdd', 
				{'business_id':M.curBusinessID}, c, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					} 
					M.ciniki_businesses_domains.edit.close();
				});
		}
	};

	this.removeDomain = function() {
		if( confirm("Are you sure you want to remove the domain '" + this.edit.data.domain + "' ?") ) {
			var rsp = M.api.getJSONCb('ciniki.businesses.domainDelete', 
				{'business_id':M.curBusinessID, 'domain_id':this.edit.domain_id}, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					M.ciniki_businesses_domains.edit.close();
				});
		}
	}
};
