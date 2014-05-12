//
// This file contains the UI to setup the intl settings for the business.
//
function ciniki_businesses_intl() {
	this.init = function() {
		this.main = new M.panel('Localization',
			'ciniki_businesses_intl', 'main',
			'mc', 'medium', 'sectioned', 'ciniki.businesses.intl.main');
		this.main.data = {};
		this.main.sections = {
//			'info':{'label':'', 'type':'htmlcontent'},
			'locale':{'label':'', 'fields':{
				'intl-default-locale':{'label':'Locale', 'type':'select', 'options':{}},
				}},
			'currency':{'label':'', 'fields':{
				'intl-default-currency':{'label':'Currency', 'type':'select', 'options':{}},
				}},
			'timezone':{'label':'', 'fields':{
				'intl-default-timezone':{'label':'Time Zone', 'type':'select', 'options':{}},
				}},
			'measurement':{'label':'', 'fields':{
				'intl-default-distance-units':{'label':'Distance Units', 'type':'select', 'options':{}},
				}},
			'_save':{'label':'', 'buttons':{
				'save':{'label':'Save', 'fn':'M.ciniki_businesses_intl.saveIntl();'},
				}},
		};
		this.main.fieldHistoryArgs = function(s, i) {
			return {'method':'ciniki.businesses.getDetailHistory', 'args':{'business_id':M.curBusinessID, 'field':i}};
		}
		this.main.fieldValue = function(s, i, d) { return this.data[i]; }
		this.main.addButton('save', 'Save', 'M.ciniki_businesses_intl.saveIntl();');
		this.main.addClose('Cancel');
	}

	this.start = function(cb, appPrefix) {
		//
		// Create the app container if it doesn't exist, and clear it out
		// if it does exist.
		//
		var appContainer = M.createContainer(appPrefix, 'ciniki_businesses_intl', 'yes');
		if( appContainer == null ) {
			alert('App Error');
			return false;
		} 
		
		//
		// Load details
		//
		var rsp = M.api.getJSONCb('ciniki.businesses.settingsIntlGet', {'business_id':M.curBusinessID}, 
			function(rsp) {
				if( rsp.stat != 'ok' ) {
					M.api.err(rsp);
					return false;
				} 
				var p = M.ciniki_businesses_intl.main;
				p.data = rsp.settings;
				p.sections.locale.fields['intl-default-locale'].options = {};
				for(i in rsp.locales) {
					p.sections.locale.fields['intl-default-locale'].options[rsp.locales[i].locale.id] = rsp.locales[i].locale.name;
				}
				p.sections.currency.fields['intl-default-currency'].options = {};
				for(i in rsp.currencies) {
					p.sections.currency.fields['intl-default-currency'].options[rsp.currencies[i].currency.id] = rsp.currencies[i].currency.name;
				}
				p.sections.timezone.fields['intl-default-timezone'].options = {};
				for(i in rsp.timezones) {
					p.sections.timezone.fields['intl-default-timezone'].options[rsp.timezones[i].timezone.id] = rsp.timezones[i].timezone.id;
				}
				p.sections.measurement.fields['intl-default-distance-units'].options = {};
				for(i in rsp.distanceunits) {
					p.sections.measurement.fields['intl-default-distance-units'].options[rsp.distanceunits[i].unit.id] = rsp.distanceunits[i].unit.name;
				}
				p.refresh();
				p.show(cb);
		});
	}

	// 
	// Submit the form
	//
	this.saveIntl = function() {
		// Serialize the form data into a string for posting
		var c = this.main.serializeForm('no');
		if( c != '' ) {
			var rsp = M.api.postJSONCb('ciniki.businesses.settingsIntlUpdate', 
				{'business_id':M.curBusinessID}, c, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					M.ciniki_businesses_intl.main.close();
				});
		} else {
			this.main.close();
		}
	}
}
