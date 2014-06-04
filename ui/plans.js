//
// The app to manage businesses plans for a business
//
function ciniki_businesses_plans() {
	
	this.planFlags = {
		'1':{'name':'Public'},
		};
	
	this.init = function() {
		this.menu = new M.panel('Plans',
			'ciniki_businesses_plans', 'menu',
			'mc', 'medium', 'sectioned', 'ciniki.businesses.plans.menu');
		this.menu.data = {};
		this.menu.sections = {
			'plans':{'label':'', 'type':'simplegrid', 'num_cols':3,
				'headerValues':['Plan','Monthly','Trial'],
				},
			'_buttons':{'label':'', 'buttons':{
				'_add':{'label':'Add Plan', 'fn':'M.ciniki_businesses_plans.showEdit(\'M.ciniki_businesses_plans.showMenu();\',0);'},
				}},
		};
		this.menu.noData = function(s) { return 'No plans added'; }
		this.menu.sectionData = function(s) { return this.data; }
		this.menu.cellValue = function(s, i, j, d) {
			if( j == 0 ) {
				if( d.plan.ispublic == 'yes' ) {
					return d.plan.name + ' (public)';
				}
				return d.plan.name;
			} else if( j == 1 ) { 
				return d.plan.monthly; 
			} else if( j == 2 ) { 
				return d.plan.trial_days; 
			}
		}
		this.menu.rowFn = function(s, i, d) {
			return 'M.ciniki_businesses_plans.showEdit(\'M.ciniki_businesses_plans.showMenu();\',\'' + d.plan.id + '\');';
		};
		this.menu.addButton('add', 'Add', 'M.ciniki_businesses_plans.showEdit(\'M.ciniki_businesses_plans.showMenu();\',0);');
		this.menu.addClose('Back');

		this.edit = new M.panel('Edit Plan',
			'ciniki_businesses_plans', 'edit',
			'mc', 'medium', 'sectioned', 'ciniki.businesses.plans.edit');
		this.edit.data = {'status':'1'};
		this.edit.sections = {
			'info':{'label':'', 'fields':{
				'name':{'label':'Name', 'type':'text'},
				'flags':{'label':'', 'type':'flags', 'join':'yes', 'flags':this.planFlags},
				'sequence':{'label':'Sequence', 'type':'text', 'size':'small'},
				'monthly':{'label':'Monthly', 'type':'text', 'size':'small'},
				'trial_days':{'label':'Trial', 'type':'text', 'size':'small'},
				}},
			'_modules':{'label':'Modules', 'fields':{
				'modules':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'small'},
				}},
			'_description':{'label':'Description', 'fields':{
				'description':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'medium'},
				}},
			'_buttons':{'label':'', 'buttons':{
				'save':{'label':'Save', 'fn':'M.ciniki_businesses_plans.savePlan();'},
				'delete':{'label':'Delete', 'fn':'M.ciniki_businesses_plans.removePlan();'},
				}},
			};
		this.edit.fieldValue = function(s, i, d) { return this.data[i]; }
		this.edit.fieldHistoryArgs = function(s, i) {
			return {'method':'ciniki.businesses.planHistory', 'args':{'business_id':M.curBusinessID, 
				'plan_id':M.ciniki_businesses_plans.edit.plan_id, 'field':i}};
		}
		this.edit.addButton('save', 'Save', 'M.ciniki_businesses_plans.savePlan();');
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
		var appContainer = M.createContainer(ap, 'ciniki_businesses_plans', 'yes');
		if( appContainer == null ) {
			alert('App Error');
			return false;
		} 

		this.showMenu(cb);
	}

	this.showMenu = function(cb) {
		var rsp = M.api.getJSONCb('ciniki.businesses.planList', 
			{'business_id':M.curBusinessID}, function(rsp) {
				if( rsp.stat != 'ok' ) {
					M.api.err(rsp);
					return false;
				}
				var p = M.ciniki_businesses_plans.menu;
				p.data = rsp.plans;
				p.refresh();
				p.show(cb);
			});
	}

	this.showEdit = function(cb, did) {
		this.edit.reset();
		if( did != null ) {
			this.edit.plan_id = did;
		}
		if( this.edit.plan_id > 0 ) {
			this.edit.sections._buttons.buttons.delete.visible = 'yes';
			var rsp = M.api.getJSONCb('ciniki.businesses.planGet', 
				{'business_id':M.curBusinessID, 'plan_id':this.edit.plan_id}, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					var p = M.ciniki_businesses_plans.edit;
					p.data = rsp.plan;
					p.refresh();
					p.show(cb);
				});
		} else {
			this.edit.reset();
			this.edit.sections._buttons.buttons.delete.visible = 'no';
			this.edit.data = {};
			this.edit.refresh();
			this.edit.show(cb);
		}
	};

	this.savePlan = function() {
		if( this.edit.plan_id > 0 ) {
			var c = this.edit.serializeForm('no');
			if( c != '' ) {
				var rsp = M.api.postJSONCb('ciniki.businesses.planUpdate', 
					{'business_id':M.curBusinessID, 'plan_id':M.ciniki_businesses_plans.edit.plan_id}, c, function(rsp) {
						if( rsp.stat != 'ok' ) {
							M.api.err(rsp);
							return false;
						} 
						M.ciniki_businesses_plans.edit.close();
					});
			} else {
				this.edit.close();
			}
		} else {
			var c = this.edit.serializeForm('yes');
			var rsp = M.api.postJSONCb('ciniki.businesses.planAdd', 
				{'business_id':M.curBusinessID}, c, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					} 
					M.ciniki_businesses_plans.edit.close();
				});
		}
	};

	this.removePlan = function() {
		if( confirm("Are you sure you want to remove the plan '" + this.edit.data.name + "' ?") ) {
			var rsp = M.api.getJSONCb('ciniki.businesses.planDelete', 
				{'business_id':M.curBusinessID, 'plan_id':M.ciniki_businesses_plans.edit.plan_id}, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					M.ciniki_businesses_plans.edit.close();
				});
		}
	}
};
