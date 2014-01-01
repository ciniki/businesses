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
				//
				// Setup the list of modules into the form fields
				// 
				p.sections = {'modules':{'label':'', 'hidelabel':'yes', 'fields':{}}};
				p.data = rsp.modules;	
				if( M.curBusiness.modules['ciniki.atdo'] != null ) {
					p.sections.modules.fields['ciniki.atdo'] = {
						'label':'Atdo', 'type':'flags', 'join':'no', 'flags':{
							'1':{'name':'Appointments'},
							'2':{'name':'Tasks'},
							'4':{'name':'FAQ'},
							'5':{'name':'Notes'},
							'6':{'name':'Messages'},
						}};
				}
				if( M.curBusiness.modules['ciniki.mail'] != null ) {
					p.sections.modules.fields['ciniki.mail'] = {
						'label':'Mail', 'type':'flags', 'join':'no', 'flags':{
							'1':{'name':'Mailings'},
							'2':{'name':'Alerts'},
						}};
				}
				if( M.curBusiness.modules['ciniki.courses'] != null ) {
					p.sections.modules.fields['ciniki.courses'] = {
						'label':'Courses', 'type':'flags', 'join':'no', 'flags':{
							'1':{'name':'Course Codes'},
							'2':{'name':'Instructors'},
							'3':{'name':'Registrations'},
							'4':{'name':'Course Files'},
						}};
				}
				// The events and customers modules must both be enabled to allow for event registrations
				if( M.curBusiness.modules['ciniki.events'] != null 
					&& M.curBusiness.modules['ciniki.customers'] != null ) {
					p.sections.modules.fields['ciniki.events'] = {
						'label':'Events', 'type':'flags', 'join':'no', 'flags':{
							'1':{'name':'Registrations'},
							'2':{'name':'Online Registrations'},
						}};
				}
				if( M.curBusiness.modules['ciniki.exhibitions'] != null ) {
					p.sections.modules.fields['ciniki.exhibitions'] = {
						'label':'Exhibitions', 'type':'flags', 'join':'no', 'flags':{
							'1':{'name':'Exhibitors'},
							'2':{'name':'Sponsors'},
							'3':{'name':'Tour'},
						}};
				}
				if( M.curBusiness.modules['ciniki.web'] != null ) {
					p.sections.modules.fields['ciniki.web'] = {
						'label':'Web', 'type':'flags', 'join':'no', 'flags':{
							'1':{'name':'Custom Pages'},
						}};
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
