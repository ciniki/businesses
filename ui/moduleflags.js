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
