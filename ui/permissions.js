//
//
function ciniki_businesses_permissions() {
	this.permissions = null;

	this.init = function() {
		this.permissions = new M.panel('Permissions',
			'ciniki_businesses_permissions', 'permissions',
			'mc', 'medium', 'sectioned', 'ciniki.businesses.permissions');
		this.permissions.sections = {'f':{'label':'', 'fields':{}}};
		this.permissions.data = {};
		this.permissions.noData = function() { return 'No modules have been activated.'; }
		this.permissions.fieldValue = function(s, i, d) { 
			if( this.data[i].module != null && this.data[i].module.ruleset != null ) {
				return this.data[i].module.ruleset; 
			}
		}
		// 
		// This function will fetch the history for the field from the API
		//
		this.permissions.fieldHistoryArgs = function(s, i) {
			var fid = i;
			for(j in this.data) {
				if( this.data[j].module.name == i ) {
					fid = j;
					break;
				}
			}
			return {'method':'ciniki.businesses.getModuleRulesetHistory', 'args':{'business_id':M.curBusinessID, 
				'field':this.data[fid].module.name}};
		}
		this.permissions.addButton('save', 'Save', 'M.ciniki_businesses_permissions.save();');
		this.permissions.addClose('Cancel');

	}

	this.start = function(cb, appPrefix) {
		//
		// Create the app container if it doesn't exist, and clear it out
		// if it does exist.
		//
		var appContainer = M.createContainer(appPrefix, 'ciniki_businesses_permissions', 'yes');
		if( appContainer == null ) {
			alert('App Error');
			return false;
		} 

		//
		// Get the detail for the user.  Do this for each request, to make sure
		// we have the current data.  If the user switches businesses, then we
		// want this data reloaded.
		//
		var rsp = M.api.getJSONCb('ciniki.businesses.getModuleRulesets', 
			{'business_id':M.curBusinessID}, function(rsp) {
				if( rsp.stat != 'ok' ) {
					M.api.err(rsp);
					return false;
				}
				var p = M.ciniki_businesses_permissions.permissions;
				var count = 0;
				for(i in rsp.modules) {
					var m = rsp.modules[i].module;
					if( m != null && m.name != undefined && m.rulesets != null && m.rulesets.length > 0 ) {
						//
						// For each specified ruleset, setup the form field to accept the selection
						//
						p.data[m.name] = rsp.modules[i];
						p.sections.f.fields[m.name] = {'label':m.label,
							'type':'select', 'id':m.name, 'options':m.rulesets, 
							'complex_options':{'subname':'ruleset', 'value':'id', 'name':'label'}};
						count++;
					}
				}
				p.refresh();
				p.show(cb);
			});
	}

//	this.toggleFieldHelp = function(formID, field) {
//		M.setHelpURL('', 'ciniki.businesses.permissions.' + field);	
//		M.showHelpContent('ciniki.businesses.permissions.' + field, content);
//	}

//	this.showFieldHelp = function(field) {
		//
		// Find the module selected, and setup the help information on the rulesets
		//
//		var content = '';
//		for(i in this.modules) {
//			var m = this.modules[i]['module'];
//			if( m['name'] == field ) {
//				content += '<p>The following rulesets are available for the ' + m['label'] + ' module.</p>';
//				for(j in m['rulesets']) {
//					var r = m.rulesets[j].ruleset;
//					content += '<h3>' + r.label + '</h3>'
//						+ '<p>' + r.description + '</p>';
//				}
//			}
//		}
//		M.toggleHelp('ciniki.businesses.permissions.' + this.permissions.data[field]['module']['name']);
//	}
	
	// 
	// Submit the form
	//
	this.save = function() {
		// Serialize the form data into a string for posting
		var c = this.permissions.serializeForm('no');
		for(i in this.modules) {
			
		}
		var rsp = M.api.postJSONCb('ciniki.businesses.updateModuleRulesets', 
			{'business_id':M.curBusinessID}, c, function(rsp) {
				if( rsp.stat != 'ok' ) {
					M.api.err(rsp);
					return false;
				}
				M.ciniki_businesses_permissions.permissions.close();
			});
	}
}
