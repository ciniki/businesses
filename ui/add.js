//
// This class will display the form to allow admins and business owners to 
// change the details of their business
//
function ciniki_businesses_add() {

	this.init = function() {
		this.add = new M.panel('Add business',
			'ciniki_businesses_add', 'add',
			'mc', 'medium', 'sectioned', 'ciniki.businesses.add');
		this.add.data = null;
		this.add.sections = {
			'general':{'label':'General', 'fields':{
				'business.name':{'label':'Name', 'type':'text'},
				'business.category':{'label':'Category', 'type':'text'},
				'business.sitename':{'label':'Sitename', 'type':'text'},
				'business.tagline':{'label':'Tagline', 'type':'text'},
				}},
			'contact':{'label':'Contact', 'fields':{
				'contact.person.name':{'label':'Name', 'type':'text'},
				'contact.phone.number':{'label':'Phone', 'type':'text'},
				'contact.tollfree.number':{'label':'Tollfree', 'type':'text'},
				'contact.fax.number':{'label':'Fax', 'type':'text'},
				'contact.email.address':{'label':'Email', 'type':'text'},
				}},
			'owner':{'label':'Owner', 'fields':{
				'owner.name.first':{'label':'First Name', 'type':'text'},
				'owner.name.last':{'label':'Last Name', 'type':'text'},
				'owner.name.display':{'label':'Display Name', 'type':'text'},
				'owner.email.address':{'label':'Email', 'type':'text'},
				'owner.username':{'label':'Username', 'type':'text'},
				'owner.password':{'label':'Password', 'type':'password'},
				}},
			'address':{'label':'Address', 'fields':{
				'contact.address.street1':{'label':'Street', 'type':'text'},
				'contact.address.street2':{'label':'Street', 'type':'text'},
				'contact.address.city':{'label':'City', 'type':'text'},
				'contact.address.province':{'label':'Province', 'type':'text'},
				'contact.address.postal':{'label':'Postal', 'type':'text'},
				'contact.address.country':{'label':'Country', 'type':'text'},
				}},
			'modules':{'label':'Modules', 'fields':{}},
			'_buttons':{'label':'', 'buttons':{
				'save':{'label':'Save', 'fn':'M.ciniki_businesses_add.save();'},
				}},
			};
		this.add.fieldValue = function(s, i, d) { 
			if( s == 'modules' ) { return 0; }
			return ''; 
		}
		this.add.addButton('save', 'Save', 'M.ciniki_businesses_add.save();');
		this.add.addClose('Cancel');
	}

	this.start = function(cb, appPrefix) {
		//
		// FIXME: set global help url
		//

		//
		// Create the app container if it doesn't exist, and clear it out
		// if it does exist.
		//
		var appContainer = M.createContainer(appPrefix, 'ciniki_businesses_add', 'yes');
		if( appContainer == null ) {
			alert('App Error');
			return false;
		} 


		//
		// Get the detail for the user.  Do this for each request, to make sure
		// we have the current data.  If the user switches businesses, then we
		// want this data reloaded.
		//
		var rsp = M.api.getJSONCb('ciniki.businesses.getModules', 
			{'business_id':0}, function(rsp) {
				if( rsp.stat != 'ok' ) {
					M.api.err(rsp);
					return false;
				}
				var p = M.ciniki_businesses_add.add;
				//
				// Setup the list of modules into the form fields
				// 
				p.sections.modules.fields = {};
				for(i in rsp.modules) {
					var m = rsp.modules[i].module;
					p.sections['modules']['fields'][m.package + '.' + m.name] = {
						'id':m.name, 'label':m.label, 'type':'toggle', 'toggles':{'0':' Off ', '1':' On '},
						};
				}
				p.show(cb);
			});
	}

	// 
	// Submit the form
	//
	this.save = function() {
		// Serialize the form data into a string for posting
		var c = this.add.serializeFormSection('yes', 'general')
			+ this.add.serializeFormSection('yes', 'contact')
			+ this.add.serializeFormSection('yes', 'address')
			+ this.add.serializeFormSection('yes', 'owner');
		if( document.getElementById(this.add.panelUID + '_business.name').value == '' ) {
			alert("You must specify a business name.");
			return false;
		}
		if( c == '' ) {
			alert("No changes to save");
			return false;
		} 
		if( c != '' ) {
			M.api.postJSONCb('ciniki.businesses.add', {}, c, function(rsp) {
				if( rsp.stat != 'ok' ) {
					M.api.err(rsp);
					return false;
				}

				var business_id = rsp.id;
				var c = M.ciniki_businesses_add.add.serializeFormSection('no', 'modules');
				var rsp = M.api.postJSONCb('ciniki.businesses.updateModules', 
					{'business_id':business_id}, c, function(rsp) {
						if( rsp.stat != 'ok' ) {
							M.api.err(rsp);
							return false;
						}
						M.ciniki_businesses_add.add.close();
					});
			});
		} else {
			this.add.close();
		}
	}
}

