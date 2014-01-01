//
// This app is a placeholder
//
function ciniki_businesses_assets() {
	//
	// Panels
	//
	this.settings = null;
	this.toggleOptions = {'off':'Off', 'on':'On'};

	this.init = function() {
		//
		// sites panel
		//
		this.settings = new M.panel('title',
			'ciniki_businesses_assets', 'settings',
			'mc', 'medium', 'sectioned', 'ciniki.businesses.assets');
		this.settings.sections = {
			'sites':{'name':'', 'list':{}},
			};
		// FIXME: Change title
		this.settings.sectionLabel = function(i, d) { return 'Image Assets'; }
		this.settings.noData = function() { return '**  placeholder ** <br/><br/>this is where the settings for configuring the business\'s assets will go.'; }
		this.settings.addClose('Back');
	}

	//
	// Arguments:
	// aG - The arguments to be parsed into args
	//
	this.start = function(cb, appPrefix, aG) {
		args = {};
		if( aG != null ) {
			args = eval(aG);
		}

		//
		// Create the app container if it doesn't exist, and clear it out
		// if it does exist.
		//
		var appContainer = M.createContainer(appPrefix, 'ciniki_businesses_assets', 'yes');
		if( appContainer == null ) {
			alert('App Error');
			return false;
		} 

		var rsp = M.api.getJSONCb('ciniki.businesses.brokenonpurpose', 
			{'business_id':M.curBusinessID}, function(rsp) {
				if( rsp.stat != 'ok' ) {
					M.api.err(rsp);
					return false;
				}
				this.site_id = args.id;
				this.name = args.name;
				this.settings.title = args.name;
				this.settings.show(cb);
			});
	}
}
