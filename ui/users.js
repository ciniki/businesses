//
//
function ciniki_businesses_users() {
	this.users = null;
	this.user = null;

	this.userFlags = {
		'1':{'name':'Name'},
		'2':{'name':'Title'},
		'3':{'name':'Phone'},
		'4':{'name':'Cell'},
		'5':{'name':'Fax'},
		'6':{'name':'Email'},
		'7':{'name':'Bio'},
		};

	this.init = function() {
		this.users = new M.panel('Business Owners',
			'ciniki_businesses_users', 'users',
			'mc', 'medium', 'sectioned', 'ciniki.businesses.users');
		this.users.data = {};
		this.users.sections = {};
		this.users.cellValue = function(s, i, j, d) { return d.user.firstname + ' ' + d.user.lastname; }	
		this.users.rowFn = function(s, i, d) { return 'M.ciniki_businesses_users.showEdit(\'M.ciniki_businesses_users.showUsers();\',\'' + s + '\',\'' + d.user.user_id + '\');'; }
		this.users.noData = function() { return 'No users'; }
		this.users.sectionData = function(s) { return this.data[s]; }
		this.users.addClose('Back');

		//
		// Edit user details
		//
		this.edit = new M.panel('User Details',
			'ciniki_businesses_users', 'edit',
			'mc', 'medium', 'sectioned', 'ciniki.businesses.users.edit');
		this.edit.data = {};
		this.edit.user_package = '';
		this.edit.user_permission_group = '';
		this.edit.sections = {
			'info':{'label':'Login', 'list':{
				'firstname':{'label':'First', 'type':'noedit'},
				'lastname':{'label':'Last', 'type':'noedit'},
				'username':{'label':'Username', 'type':'noedit'},
				'email':{'label':'Email', 'type':'noedit'},
				'display_name':{'label':'Display', 'type':'noedit'},
				}},
			'details':{'label':'Contact Info', 'type':'simpleform', 'fields':{
				'employee.title':{'label':'Title', 'type':'text'},
				'contact.phone.number':{'label':'Phone', 'type':'text'},
				'contact.cell.number':{'label':'Cell', 'type':'text'},
				'contact.fax.number':{'label':'Fax', 'type':'text'},
				'contact.email.address':{'label':'Email', 'type':'text'},
//				'employee-twitter':{'label':'Email', 'type':'text'},
				}},
//			'_web':{'label':'Web Options', 'visible':'no', 'type':'simpleform', 'fields':{
//				}},
			'_image':{'label':'Image', 'fields':{
				'employee-bio-image':{'label':'', 'type':'image_id', 'controls':'all', 'hidelabel':'yes', 'history':'no'},
				}},
			'_image_caption':{'label':'', 'visible':'no', 'fields':{
				'employee-bio-image-caption':{'label':'Caption', 'type':'text'},
				}},
			'_content':{'label':'Biography', 'fields':{
				'employee-bio-content':{'label':'', 'hidelabel':'yes', 'hint':'', 'type':'textarea', 'size':'large'},
				}},
			'_buttons':{'label':'', 'buttons':{
				'save':{'label':'Save', 'fn':'M.ciniki_businesses_users.saveDetails();'},
				'delete':{'label':'Remove',},
				}},
			};
		this.edit.listLabel = function(s, i, d) { return d.label; }
		this.edit.listValue = function(s, i, d) { return this.data[i]; }
		this.edit.fieldValue = function(s, i, d) { return this.data[i]; }
		this.edit.fieldHistoryArgs = function(s, i) {
			if( i.match(/page-contact-user-display-flags/) ) {
				return {'method':'ciniki.web.pageSettingsHistory', 'args':{'business_id':M.curBusinessID, 'field':i}};
			} else {
				return {'method':'ciniki.businesses.userDetailHistory', 'args':{'business_id':M.curBusinessID, 
					'user_id':this.user_id, 'field':i}};
			}
		}
		this.edit.addDropImage = function(iid) {
			this.setFieldValue('employee-bio-image', iid);
			return true;
		};
		this.edit.deleteImage = function(fid) {
			this.setFieldValue('employee-bio-image', 0);
			return true;
		};
		this.edit.addButton('save', 'Save', 'M.ciniki_businesses_users.saveDetails();');
		this.edit.addClose('Cancel');
	}

	this.start = function(cb, appPrefix) {
		//
		// Create the app container if it doesn't exist, and clear it out
		// if it does exist.
		//
		var appContainer = M.createContainer(appPrefix, 'ciniki_businesses_users', 'yes');
		if( appContainer == null ) {
			alert('App Error');
			return false;
		} 
		
//		this.users.sections = {
//			'ciniki.owners':{'label':'Owners', 'type':'simplegrid', 'num_cols':1, 
//				'headerValues':null,
//				'cellClasses':[''],
//				'addTxt':'Add Owner',
//				'addFn':'M.startApp(\'ciniki.users.add\', null, \'M.ciniki_businesses_users.addOwner(data);\');',
//				},
//			'ciniki.employees':{'label':'Employees', 'type':'simplegrid', 'num_cols':1, 
//				'headerValues':null,
//				'cellClasses':[''],
//				'addTxt':'Add Employee',
//				'addFn':'M.startApp(\'ciniki.users.add\', null, \'M.ciniki_businesses_users.addEmployee(data);\');',
//				},
//		};
//		if( M.curBusiness.modules['ciniki.businesses'] != null ) {
//			if( (M.curBusiness.modules['ciniki.businesses'].flags&0x04) > 0 ) {
//				this.users.sections['ciniki.warehouse'].visible = 'yes';
//			} else {
//				this.users.sections['ciniki.warehouse'].visible = 'no';
//			}
//			if( (M.curBusiness.modules['ciniki.businesses'].flags&0x08) > 0 ) {
//				this.users.sections['ciniki.marketing'].visible = 'yes';
//			} else {
//				this.users.sections['ciniki.marketing'].visible = 'no';
//			}
//		}

		this.showUsers(cb);
	}

	this.showUsers = function(cb) {
		//
		// Get the detail for the user.  Do this for each request, to make sure
		// we have the current data.  If the user switches businesses, then we
		// want this data reloaded.
		//
		M.api.getJSONCb('ciniki.businesses.userList', {'business_id':M.curBusinessID}, function(rsp) {
			if( rsp.stat != 'ok' ) {
				M.api.err(rsp);
				return false;
			}
			var p = M.ciniki_businesses_users.users;
			p.reset();
			p.data = [];

			// Add the user lists into the proper sections
			p.sections = {};
			for(i in rsp.permission_groups) {
				p.sections[i] = {'label':rsp.permission_groups[i].name,
					'type':'simplegrid',
					'num_cols':1,
					'headerValues':null,
					'cellClasses':[''],
					'addTxt':'Add',
					'addFn':'M.ciniki_businesses_users.showAdd(\'' + i + '\');',
					};
			}
			for(i in rsp.groups) {
				if( p.sections[rsp.groups[i].group.permission_group] != null ) {
					p.data[rsp.groups[i].group.permission_group] = rsp.groups[i].group.users;
				}
			}
			p.refresh();
			p.show(cb);
		});
	}

	this.showEdit = function(cb, s, uid, mod) {
		if( uid != null ) { this.edit.user_id = uid; }
		if( s != null ) { 
			var g = s.split('.');
			this.edit.package = g[0];
			this.edit.permission_group = g[1];
		}
		this.edit.sections._buttons.buttons.delete.fn = 'M.ciniki_businesses_users.removeUser(' + this.edit.user_id + ');';
		M.api.getJSONCb('ciniki.businesses.userDetails', 
			{'business_id':M.curBusinessID, 'user_id':this.edit.user_id}, function(rsp) {
				if( rsp.stat != 'ok' ) {
					M.api.err(rsp);
					return false;
				}
				var p = M.ciniki_businesses_users.edit;
				if( M.curBusiness.modules['ciniki.web'] != null ) {
					p.sections._image.visible = 'yes';
					p.sections._content.visible = 'yes';
					if( rsp.user['employee-bio-image-caption'] != null && rsp.user['employee-bio-image-caption'] != '' ) {
						p.sections._image_caption.visible = 'yes';
						p.sections._image_caption.fields['employee-bio-image-caption'].active = 'yes';
					} else {
						p.sections._image_caption.visible = 'no';
						p.sections._image_caption.fields['employee-bio-image-caption'].active = 'no';
					}
				} else {
		//			p.sections._web.visible = 'no';
					p.sections._image.visible = 'no';
					p.sections._image_caption.visible = 'no';
					p.sections._content.visible = 'no';
				}
				p.data = rsp.user;
				p.refresh();
				p.show(cb);
			});
	}

	this.saveDetails = function() {
		var c = this.edit.serializeForm('no');
		if( c != '' ) {
			var rsp = M.api.postJSONCb('ciniki.businesses.userUpdateDetails', 
				{'business_id':M.curBusinessID, 'user_id':this.edit.user_id}, c, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					M.ciniki_businesses_users.showUsers();
				});
		} else {
			this.edit.close();	
		}
	}

	this.showAdd = function(s) {
		var g = s.split('.');
		this.cur_package = g[0];
		this.cur_permission_group = g[1];
		M.startApp('ciniki.users.add',null,'M.ciniki_businesses_users.addUser(data);');
	};

	// 
	// Submit the form
	//
	this.addUser = function(data) {
		if( data != null && data.id > 0 ) {
			var rsp = M.api.getJSONCb('ciniki.businesses.userAdd', 
				{'business_id':M.curBusinessID, 'user_id':data.id, 
				'package':this.cur_package, 'permission_group':this.cur_permission_group}, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					M.ciniki_businesses_users.showUsers();
				});
		} else {
			M.ciniki_businesses_users.showUsers();
		}
//		return false;
	}

	this.removeUser = function(id) {
		if( id != null && id > 0 ) {
			if( confirm('Are you sure you want to remove this user as an Owner?') ) {
				var rsp = M.api.getJSONCb('ciniki.businesses.userRemove', 
					{'business_id':M.curBusinessID, 'user_id':id, 
					'package':this.edit.package, 'permission_group':this.edit.permission_group}, function(rsp) {
						if( rsp.stat != 'ok' ) {
							M.api.err(rsp);
							return false;
						}
						M.ciniki_businesses_users.showUsers();
					});
			}
		}
		return false;
	}
}
