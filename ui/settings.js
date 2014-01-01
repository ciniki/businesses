//
// This class will display the form to allow admins and business owners to 
// change the details of their business
//
function ciniki_businesses_settings() {

	this.init = function() {
		this.menu = new M.panel('Business Settings',
			'ciniki_businesses_settings', 'menu',
			'mc', 'narrow', 'sectioned', 'ciniki.businesses.settings.menu');
		this.menu.addClose('Back');
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
		var appContainer = M.createContainer('mc', 'ciniki_businesses_settings', 'yes');
		if( appContainer == null ) {
			alert('App Error');
			return false;
		} 
		
		// 
		// Clear old menu
		//
		this.menu.reset();

		//
		// Setup the Business Settings 
		//
		this.menu.sections = {
			'':{'label':'', 'list':{
				'info':{'label':'Business Info', 'fn':'M.startApp(\'ciniki.businesses.info\', null, \'M.ciniki_businesses_settings.menu.show();\');'},
//				'logo':{'label':'Business Logo', 'fn':'M.startApp(\'ciniki.businesses.logo\', null, \'M.ciniki_businesses_settings.menu.show();\');'},
				'users':{'label':'Owners & Employees', 'fn':'M.startApp(\'ciniki.businesses.users\', null, \'M.ciniki_businesses_settings.menu.show();\');'},
				'permissions':{'label':'Permissions', 'visible':'no', 'fn':'M.startApp(\'ciniki.businesses.permissions\', null, \'M.ciniki_businesses_settings.menu.show();\');'},
				'social':{'label':'Social Media', 'fn':'M.startApp(\'ciniki.businesses.social\', null, \'M.ciniki_businesses_settings.menu.show();\');'},
				'intl':{'label':'Localization', 'fn':'M.startApp(\'ciniki.businesses.intl\', null, \'M.ciniki_businesses_settings.menu.show();\');'},
				'billing':{'label':'Billing', 'fn':'M.startApp(\'ciniki.businesses.billing\', null, \'M.ciniki_businesses_settings.menu.show();\');'},
				}}};
		if( M.curBusiness.modules['ciniki.artcatalog'] != null ) {
			this.menu.sections['']['list']['artcatalog'] = {'label':'Art Catalog', 'fn':'M.startApp(\'ciniki.artcatalog.settings\', null, \'M.ciniki_businesses_settings.menu.show();\');'};
		}
		if( M.curBusiness.modules['ciniki.customers'] != null ) {
			this.menu.sections['']['list']['customers'] = {'label':'Customers', 'fn':'M.startApp(\'ciniki.customers.settings\', null, \'M.ciniki_businesses_settings.menu.show();\');'};
		}
		if( M.curBusiness.modules['ciniki.bugs'] != null && (M.userPerms&0x01) == 0x01 ) {
			this.menu.sections['']['list']['bugs'] = {'label':'Bug Tracker', 'fn':'M.startApp(\'ciniki.bugs.settings\', null, \'M.ciniki_businesses_settings.menu.show();\');'};
			this.menu.sections[''].list.permissions.visible = 'yes';
		}
		if( M.curBusiness.modules['ciniki.questions'] != null && (M.userPerms&0x01) == 0x01 ) {
			this.menu.sections['']['list']['questions'] = {'label':'Questions', 'fn':'M.startApp(\'ciniki.questions.settings\', null, \'M.ciniki_businesses_settings.menu.show();\');'};
			this.menu.sections[''].list.permissions.visible = 'yes';
		}
		if( M.curBusiness.modules['ciniki.wineproduction'] != null ) {
			this.menu.sections['']['list']['wineproduction'] = {'label':'Wine Production', 'fn':'M.startApp(\'ciniki.wineproduction.settings\', null, \'M.ciniki_businesses_settings.menu.show();\');'};
			this.menu.sections[''].list.permissions.visible = 'yes';
		}
		if( M.curBusiness.modules['ciniki.subscriptions'] != null ) {
			this.menu.sections[''].list.permissions.visible = 'yes';
		}
		if( M.curBusiness.modules['ciniki.media'] != null ) {
			this.menu.sections[''].list.permissions.visible = 'yes';
		}
		if( M.curBusiness.modules['ciniki.atdo'] != null ) {
			this.menu.sections['']['list']['tasks'] = {'label':'Appointments, Tasks, Etc', 'fn':'M.startApp(\'ciniki.atdo.settings\',null,\'M.ciniki_businesses_settings.menu.show();\');'};
		}
		if( M.curBusiness.modules['ciniki.services'] != null ) {
			this.menu.sections['']['list']['services'] = {'label':'Services', 'fn':'M.startApp(\'ciniki.services.settings\',null,\'M.ciniki_businesses_settings.menu.show();\');'};
		}
		if( M.curBusiness.modules['ciniki.taxes'] != null ) {
			this.menu.sections['']['list']['taxes'] = {'label':'Taxes', 'fn':'M.startApp(\'ciniki.taxes.settings\',null,\'M.ciniki_businesses_settings.menu.show();\');'};
		}
		if( M.curBusiness.modules['ciniki.sapos'] != null ) {
			this.menu.sections['']['list']['sapos'] = {'label':'POS', 'fn':'M.startApp(\'ciniki.sapos.settings\',null,\'M.ciniki_businesses_settings.menu.show();\');'};
		}
		if( M.curBusiness.modules['ciniki.mail'] != null ) {
			this.menu.sections['']['list']['mail'] = {'label':'Mail', 'fn':'M.startApp(\'ciniki.mail.settings\',null,\'M.ciniki_businesses_settings.menu.show();\');'};
//			if( (M.userPerms&0x01) == 0x01 ) {
//				this.menu.sections['']['list']['mailtemplates'] = {'label':'Mail Templates', 'fn':'M.startApp(\'ciniki.mail.templates\',null,\'M.ciniki_businesses_settings.menu.show();\');'};
//			}
		}
		
		if( M.userID > 0 && (M.userPerms&0x01) == 0x01 ) {
			//
			// Setup the advanced section
			//
			this.menu.sections['advanced'] = {'label':'Advanced', 'list':{
					'integrityfix':{'label':'Database Integrity Fix', 'fn':'M.ciniki_businesses_settings.fixallintegrity();'},
				}};

			this.menu.sections['admin'] = {'label':'Admin', 'list':{
				'modules':{'label':'Modules', 'fn':'M.startApp(\'ciniki.businesses.modules\', null, \'M.ciniki_businesses_settings.menu.show();\');'},
				'moduleflags':{'label':'Module Flags', 'fn':'M.startApp(\'ciniki.businesses.moduleflags\', null, \'M.ciniki_businesses_settings.menu.show();\');'},
				'sync':{'label':'Syncronization', 'fn':'M.startApp(\'ciniki.businesses.sync\', null, \'M.ciniki_businesses_settings.menu.show();\');'},
				'CSS':{'label':'CSS', 'fn':'M.startApp(\'ciniki.businesses.css\', null, \'M.ciniki_businesses_settings.menu.show();\');'},
				'webdomains':{'label':'Domains', 'fn':'M.startApp(\'ciniki.businesses.domains\', null, \'M.ciniki_businesses_settings.menu.show();\');'},
				'assets':{'label':'Image Assets', 'fn':'M.startApp(\'ciniki.businesses.assets\', null, \'M.ciniki_businesses_settings.menu.show();\');'},
				'fixhistory':{'label':'Fix History', 'fn':'M.ciniki_businesses_settings.fixallhistory();'},
//				'fixhistory':{'label':'Fix History', 'fn':'M.startApp(\'ciniki.businesses.fixhistory\', null, \'M.ciniki_businesses_settings.menu.show();\');'},
				}};
			if( M.curBusinessID == M.masterBusinessID ) {
				this.menu.sections.admin.list['plans'] = {'label':'Plans', 'fn':'M.startApp(\'ciniki.businesses.plans\', null, \'M.ciniki_businesses_settings.menu.show();\');'};
			}
		}

		//
		// Show the settings menu
		//
		this.menu.show(cb);
	}

	this.fixallintegrity = function() {
		M.startLoad();
		this.dbfixintegrity();
	}

	this.dbfixintegrity = function() {
		if( this.fixintegrity('ciniki.businesses') == false ) {
			M.stopLoad();
			return false;
		}
		if( this.fixintegrity('ciniki.images') == false ) {
			M.stopLoad();
			return false;
		}
		var mods = [
			'ciniki.artcatalog',
			'ciniki.atdo',
			'ciniki.customers',
			'ciniki.events',
			'ciniki.exhibitions',
			'ciniki.gallery',
			'ciniki.images',
			'ciniki.links',
			'ciniki.products',
			'ciniki.projects',
			'ciniki.services',
			'ciniki.web',
			'ciniki.wineproduction',
			];
		for(i in mods) {
			if( M.curBusiness.modules[mods[i]] != null ) {
				if( this.fixintegrity(mods[i]) == false ) {
					M.stopLoad();
					return false;
				}
			}
		}
		M.stopLoad();
		alert('done');
	}

	this.fixintegrity = function(module) {
		var rsp = M.api.getJSON(module + '.dbIntegrityCheck', 
			{'business_id':M.curBusinessID, 'fix':'yes'});
		if( rsp.stat != 'ok' ) {
			M.api.err(rsp);
			return false;
		}
		return true;
	};

	this.fixallhistory = function() {
		if( this.fixhistory('ciniki.users') == false ) {
			return false;
		}
		if( this.fixhistory('ciniki.businesses') == false ) {
			return false;
		}
		if( this.fixhistory('ciniki.images') == false ) {
			return false;
		}
		var mods = [
			'ciniki.artcatalog',
			'ciniki.atdo',
			'ciniki.customers',
			'ciniki.events',
			'ciniki.exhibitions',
			'ciniki.gallery',
			'ciniki.images',
			'ciniki.links',
			'ciniki.products',
			'ciniki.projects',
			'ciniki.services',
			'ciniki.wineproduction',
			];
		for(i in mods) {
			if( M.curBusiness.modules[mods[i]] != null ) {
				if( this.fixhistory(mods[i]) == false ) {
					return false;
				}
			}
		}
		alert('done');
	}

	this.fixhistory = function(module) {
		var rsp = M.api.getJSON(module + '.historyFix', {'business_id':M.curBusinessID});
		if( rsp.stat != 'ok' ) {
			M.api.err(rsp);
			return false;
		}
		return true;
	};
}

