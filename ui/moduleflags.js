//
//
function ciniki_businesses_moduleflags() {
	this.modules = null;

	this.init = function() {
		this.modules = new M.panel('Modules',
			'ciniki_businesses_moduleflags', 'modules',
			'mc', 'medium', 'sectioned', 'ciniki.businesses.moduleflags.modules');
		this.modules.data = {};
		this.modules.fieldValue = function(s, i, d) { return this.data[i].flags; }
		this.modules.fieldHistoryArgs = function(s, i) {
			return {'method':'ciniki.businesses.getModuleFlagsHistory', 'args':{'business_id':M.curBusinessID, 'field':i}};
		}
		this.modules.addButton('save', 'Save', 'M.ciniki_businesses_moduleflags.save();');
		this.modules.addClose('Cancel');
	}

	this.start = function(cb, appPrefix) {
		//
		// Create the app container if it doesn't exist, and clear it out
		// if it does exist.
		//
		var appContainer = M.createContainer(appPrefix, 'ciniki_businesses_moduleflags', 'yes');
		if( appContainer == null ) {
			alert('App Error');
			return false;
		} 

		
		//
		// Get the detail for the user.  Do this for each request, to make sure
		// we have the current data.  If the user switches businesses, then we
		// want this data reloaded.
		//
		var rsp = M.api.getJSONCb('ciniki.businesses.getModuleFlags', 
			{'business_id':M.curBusinessID}, function(rsp) {
				if( rsp.stat != 'ok' ) {
					M.api.err(rsp);
					return false;
				}
				var p = M.ciniki_businesses_moduleflags.modules;
				p.sections = {};
				//
				// Setup the list of modules into the form fields
				// 
//				p.sections = {'modules':{'label':'', 'hidelabel':'yes', 'fields':{}}};
				p.data = rsp.modules;	
				if( M.curBusiness.modules['ciniki.atdo'] != null ) {
					p.sections['ciniki.atdo'] = {'label':'Atdo', 'fields':{
						'ciniki.atdo':{'label':'', 'hidelabel':'yes', 'type':'flags', 'join':'no', 'flags':{
							'1':{'name':'Appointments'},
							'2':{'name':'Tasks'},
							'4':{'name':'FAQ'},
							'5':{'name':'Notes'},
							'6':{'name':'Messages'},
						}}}};
				}
				if( M.curBusiness.modules['ciniki.customers'] != null ) {
					p.sections['ciniki.customers'] = {'label':'Customers', 'fields':{
						'ciniki.customers':{'label':'', 'hidelabel':'yes', 'type':'flags', 'join':'no', 'flags':{
							'1':{'name':'Customers'},
							'2':{'name':'Members'},
							'3':{'name':'Member Categories'},
						}}}};
				}
				if( M.curBusiness.modules['ciniki.blog'] != null ) {
					p.sections['ciniki.blog'] = {'label':'Blog', 'fields':{
						'ciniki.blog':{'label':'', 'hidelabel':'yes', 'type':'flags', 'join':'no', 'flags':{
							'1':{'name':'Public Blog'},
							'2':{'name':'Public Categories'},
							'3':{'name':'Public Tags'},
//							'5':{'name':'Customer Blog'},
//							'6':{'name':'Customer Categories'},
//							'7':{'name':'Customer Tags'},
							'9':{'name':'Member Blog'},
							'10':{'name':'Member Categories'},
							'11':{'name':'Member Tags'},
						}}}};
				}
				if( M.curBusiness.modules['ciniki.products'] != null ) {
					var pflags = {'1':{'name':'Similar Products'}};
					if( M.curBusiness.modules['ciniki.recipes'] != null ) {
						pflags['2'] = {'name':'Recommended Recipes'};
					}
					pflags['3'] = {'name':'Inventory'};
					pflags['4'] = {'name':'Suppliers'};
					p.sections['ciniki.products'] = {'label':'Products', 'fields':{
						'ciniki.products':{'label':'', 'hidelabel':'yes', 'type':'flags', 'join':'no', 
							'flags':pflags
						}}};
				}
				if( M.curBusiness.modules['ciniki.sapos'] != null ) {
					p.sections['ciniki.sapos'] = {'label':'Accounting', 'fields':{
						'ciniki.sapos':{'label':'', 'hidelabel':'yes', 'type':'flags', 'join':'no', 'flags':{
							'1':{'name':'Invoices'},
							'2':{'name':'Expenses'},
							'3':{'name':'Quick Invoices'},
							'4':{'name':'Shopping Cart'},
							'5':{'name':'POS'},
							'6':{'name':'Purchase Orders'},
							'7':{'name':'Shipping'},
							'8':{'name':'Manufacturing'},
							'9':{'name':'Mileage'},
						}}}};
				}
				if( M.curBusiness.modules['ciniki.mail'] != null ) {
					p.sections['ciniki.mail'] = {'label':'Mail', 'fields':{
						'ciniki.mail':{'label':'', 'hidelabel':'yes', 'type':'flags', 'join':'no', 'flags':{
							'1':{'name':'Mailings'},
							'2':{'name':'Alerts'},
						}}}};
				}
				if( M.curBusiness.modules['ciniki.courses'] != null ) {
					p.sections['ciniki.courses'] = {'label':'Courses', 'fields':{
						'ciniki.courses':{'label':'', 'hidelabel':'yes', 'type':'flags', 'join':'no', 'flags':{
							'1':{'name':'Course Codes'},
							'2':{'name':'Instructors'},
							'3':{'name':'Course Prices'},
							'4':{'name':'Course Files'},
							'7':{'name':'Registrations'},
							'8':{'name':'Online Registrations'},
						}}}};
				}
				// The events and customers modules must both be enabled to allow for event registrations
				if( M.curBusiness.modules['ciniki.events'] != null 
					&& M.curBusiness.modules['ciniki.customers'] != null ) {
					p.sections['ciniki.events'] = {'label':'Events', 'fields':{
						'ciniki.events':{'label':'', 'hidelabel':'yes', 'type':'flags', 'join':'no', 'flags':{
							'1':{'name':'Registrations'},
							'2':{'name':'Online Registrations'},
						}}}};
				}
				if( M.curBusiness.modules['ciniki.exhibitions'] != null ) {
					p.sections['ciniki.exhibitions'] = {'label':'Exhibitions', 'fields':{
						'ciniki.exhibitions':{'label':'', 'hidelabel':'yes', 'type':'flags', 'join':'no', 'flags':{
							'1':{'name':'Exhibitors'},
							'2':{'name':'Sponsors'},
							'3':{'name':'Tour'},
						}}}};
				}
				if( M.curBusiness.modules['ciniki.sponsors'] != null ) {
					p.sections['ciniki.sponsors'] = {'label':'Sponsors', 'fields':{
						'ciniki.sponsors':{'label':'Web', 'hidelabel':'yes', 'type':'flags', 'join':'no', 'flags':{
							'1':{'name':'Levels'},
						}}}};
				}
				if( M.curBusiness.modules['ciniki.marketing'] != null ) {
					p.sections['ciniki.marketing'] = {'label':'Marketing', 'fields':{
						'ciniki.marketing':{'label':'Marketing', 'hidelabel':'yes', 'type':'flags', 'join':'no', 'flags':{
							'1':{'name':'Features'},
							'2':{'name':'Feature Categories'},
						}}}};
				}
				if( M.curBusiness.modules['ciniki.info'] != null ) {
					p.sections['ciniki.info'] = {'label':'Business Information', 'fields':{
						'ciniki.info':{'label':'Web', 'hidelabel':'yes', 'type':'flags', 'join':'no', 'flags':{
							'1':{'name':'About'},
							'2':{'name':'Artist Statement'},
							'3':{'name':'CV'},
							'4':{'name':'Awards'},
							'5':{'name':'History'},
							'6':{'name':'Donations'},
							'7':{'name':'Membership'},
							'8':{'name':'Board of Directors'},
							'9':{'name':'Facilities'},
							'10':{'name':'Exhibition Application'},
						}}}};
				}
				if( M.curBusiness.modules['ciniki.web'] != null ) {
					p.sections['ciniki.web'] = {'label':'Web', 'fields':{
						'ciniki.web':{'label':'Web', 'hidelabel':'yes', 'type':'flags', 'join':'no', 'flags':{
							'1':{'name':'Custom Pages'},
						}}}};
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
			var rsp = M.api.postJSONCb('ciniki.businesses.updateModuleFlags', 
				{'business_id':M.curBusinessID}, c, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					M.ciniki_businesses_moduleflags.modules.close();
				});
		} else {
			this.modules.close();
		}
	}
}
