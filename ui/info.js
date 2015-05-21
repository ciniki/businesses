//
// This class will display the form to allow admins and business owners to 
// change the details of their business
//
function ciniki_businesses_info() {
	this.info = null;

	this.init = function() {
		this.info = new M.panel('Business Information',
			'ciniki_businesses_info', 'info',
			'mc', 'medium', 'sectioned', 'ciniki.businesses.info');
		this.info.sections = {
			'general':{'label':'General', 'fields':{
				'business.name':{'label':'Name', 'type':'text'},
				'business.sitename':{'label':'Sitename', 'visible':'no', 'type':'text'},
				'business.tagline':{'label':'Tagline', 'type':'text'},
				}},
			'contact':{'label':'Contact', 'fields':{
				'contact.person.name':{'label':'Name', 'type':'text'},
				'contact.phone.number':{'label':'Phone', 'type':'text'},
				'contact.cell.number':{'label':'Cell', 'type':'text'},
				'contact.tollfree.number':{'label':'Tollfree', 'type':'text'},
				'contact.fax.number':{'label':'Fax', 'type':'text'},
				'contact.email.address':{'label':'Email', 'type':'text'},
				}},
			'address':{'label':'Address', 'fields':{
				'contact.address.street1':{'label':'Street', 'type':'text'},
				'contact.address.street2':{'label':'Street', 'type':'text'},
				'contact.address.city':{'label':'City', 'type':'text'},
				'contact.address.province':{'label':'Province', 'type':'text'},
				'contact.address.postal':{'label':'Postal', 'type':'text'},
				'contact.address.country':{'label':'Country', 'type':'text'},
				}}
			};
		this.info.fieldHistoryArgs = function(s, i) {
			return {'method':'ciniki.businesses.getDetailHistory', 'args':{'business_id':M.curBusinessID, 'field':i}};
		}
		this.info.fieldValue = function(s, i, d) { return this.data[i]; }
		this.info.addButton('save', 'Save', 'M.ciniki_businesses_info.save();');
		this.info.addClose('Cancel');
	}

	this.start = function(cb, appPrefix) {
		//
		// Create the app container if it doesn't exist, and clear it out
		// if it does exist.
		//
		var appContainer = M.createContainer(appPrefix, 'ciniki_businesses_info', 'yes');
		if( appContainer == null ) {
			alert('App Error');
			return false;
		} 
	
		this.info.sections.general.fields['business.sitename'].visible = (M.userPerms&0x01)==1?'yes':'no';

		//
		// Get the detail for the business.  
		//
		var rsp = M.api.getJSONCb('ciniki.businesses.getDetails', 
			{'business_id':M.curBusinessID, 'keys':'business,contact'}, function(rsp) {
				if( rsp.stat != 'ok' ) {
					M.api.err(rsp);
					return false;
				}
				var p = M.ciniki_businesses_info.info;
				p.data = rsp.details;
				p.show(cb);
			});
	}

	// 
	// Submit the form
	//
	this.save = function() {
		// Serialize the form data into a string for posting
		var c = this.info.serializeForm('no');
		if( c != '' ) {
			M.api.postJSONCb('ciniki.businesses.updateDetails', 
				{'business_id':M.curBusinessID}, c, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					M.ciniki_businesses_info.info.close();
				});
		} else {
			this.info.close();
		}
	}
}

