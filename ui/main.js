//
// This class will display the form to allow admins and business owners to 
// change the details of their business
//
function ciniki_businesses_main() {
	this.businesses = null;
	this.menu = null;

	this.statusOptions = {
		'10':'Ordered',
		'20':'Started',
		'25':'SG Ready',
		'30':'Racked',
		'40':'Filtered',
		'60':'Bottled',
		'100':'Removed',
		'*':'Unknown',
		};

	this.init = function() {
		//
		// Build the menus for the business, based on what they have access to
		//
		this.menu = new M.panel('Business Menu',
			'ciniki_businesses_main', 'menu', 
			'mc', 'medium', 'sectioned', 'ciniki.businesses.main.menu');
		this.menu.data = {};
		this.menu.liveSearchCb = function(s, i, value) {
			if( this.sections[s].id == 'wineproduction' && value != '' ) {
				M.api.getJSONBgCb('ciniki.wineproduction.searchQuick', {'business_id':M.curBusinessID, 'start_needle':value, 'limit':'10'}, 
					function(rsp) { 
						M.ciniki_businesses_main.menu.liveSearchShow(s, null, M.gE(M.ciniki_businesses_main.menu.panelUID + '_' + s), rsp.orders); 
					});
				return true;
			}
			else if( this.sections[s].id == 'calendars' && value != '' ) {
				M.api.getJSONBgCb('ciniki.calendars.search', {'business_id':M.curBusinessID, 'start_needle':value, 'limit':'10'}, 
					function(rsp) { 
						M.ciniki_businesses_main.menu.liveSearchShow(s, null, M.gE(M.ciniki_businesses_main.menu.panelUID + '_' + s), rsp.appointments); 
					}); 
				return true;
			}
			else if( this.sections[s].id == 'customers' && value != '' ) {
				M.api.getJSONBgCb('ciniki.customers.searchQuick', {'business_id':M.curBusinessID, 'start_needle':value, 'limit':'10'}, 
					function(rsp) { 
						M.ciniki_businesses_main.menu.liveSearchShow(s, null, M.gE(M.ciniki_businesses_main.menu.panelUID + '_' + s), rsp.customers); 
					}); 
				return true;
			}
			else if( this.sections[s].id == 'members' && value != '' ) {
				M.api.getJSONBgCb('ciniki.customers.searchQuick', {'business_id':M.curBusinessID, 'start_needle':value, 'limit':'10', 'member_status':'10'}, 
					function(rsp) { 
						M.ciniki_businesses_main.menu.liveSearchShow(s, null, M.gE(M.ciniki_businesses_main.menu.panelUID + '_' + s), rsp.customers); 
					}); 
				return true;
			}
			else if( this.sections[s].id == 'dealers' && value != '' ) {
				M.api.getJSONBgCb('ciniki.customers.searchQuick', {'business_id':M.curBusinessID, 'start_needle':value, 'limit':'10', 'dealer_status':'10'}, 
					function(rsp) { 
						M.ciniki_businesses_main.menu.liveSearchShow(s, null, M.gE(M.ciniki_businesses_main.menu.panelUID + '_' + s), rsp.customers); 
					}); 
				return true;
			}
			else if( this.sections[s].id == 'distributors' && value != '' ) {
				M.api.getJSONBgCb('ciniki.customers.searchQuick', {'business_id':M.curBusinessID, 'start_needle':value, 'limit':'10', 'distributor_status':'10'}, 
					function(rsp) { 
						M.ciniki_businesses_main.menu.liveSearchShow(s, null, M.gE(M.ciniki_businesses_main.menu.panelUID + '_' + s), rsp.customers); 
					}); 
				return true;
			}
			else if( this.sections[s].id == 'tasks' && value != '' ) {
				M.api.getJSONBgCb('ciniki.atdo.tasksSearchQuick', {'business_id':M.curBusinessID, 'start_needle':value, 'limit':'10'}, 
					function(rsp) { 
						M.ciniki_businesses_main.menu.liveSearchShow(s, null, M.gE(M.ciniki_businesses_main.menu.panelUID + '_' + s), rsp.tasks); 
					}); 
				return true;
			}
			else if( this.sections[s].id == 'products' && value != '' ) {
				M.api.getJSONBgCb('ciniki.products.productSearch', {'business_id':M.curBusinessID, 
					'start_needle':value, 'status':1, 'limit':'10', 'reserved':'yes'}, 
					function(rsp) { 
						M.ciniki_businesses_main.menu.liveSearchShow(s, null, M.gE(M.ciniki_businesses_main.menu.panelUID + '_' + s), rsp.products); 
					}); 
				return true;
			}
			else if( this.sections[s].id == 'artcatalog' && value != '' ) {
				M.api.getJSONBgCb('ciniki.artcatalog.searchQuick', {'business_id':M.curBusinessID, 
					'start_needle':value, 'limit':'10'}, function(rsp) { 
						M.ciniki_businesses_main.menu.liveSearchShow(s, null, M.gE(M.ciniki_businesses_main.menu.panelUID + '_' + s), rsp.items); 
					}); 
				return true;
			}
			else if( this.sections[s].id == 'sapos' && value != '' ) {
				M.api.getJSONBgCb('ciniki.sapos.invoiceSearch', {'business_id':M.curBusinessID, 
					'start_needle':value, 'sort':'reverse', 'limit':'10'}, function(rsp) { 
						M.ciniki_businesses_main.menu.liveSearchShow(s, null, M.gE(M.ciniki_businesses_main.menu.panelUID + '_' + s), rsp.invoices); 
					}); 
				return true;
			}
			else if( this.sections[s].id == 'sapos_orders' && value != '' ) {
				M.api.getJSONBgCb('ciniki.sapos.invoiceSearch', {'business_id':M.curBusinessID, 
					'start_needle':value, 'sort':'reverse', 'limit':'10'}, function(rsp) { 
						M.ciniki_businesses_main.menu.liveSearchShow(s, null, M.gE(M.ciniki_businesses_main.menu.panelUID + '_' + s), rsp.invoices); 
					}); 
				return true;
			}
		};
		this.menu.liveSearchResultClass = function(s, f, i, j, d) {
			if( this.sections[s].id == 'wineproduction' ) {
				if( j > 2 ) { return 'multiline aligncenter'; }
				return 'multiline';
			}
			else if( this.sections[s].id == 'calendars' ) {
				if( j == 0 ) { return 'multiline slice_0'; }
				return 'schedule_appointment';
			}
			else if( this.sections[s].id == 'customers' || this.sections[s].id == 'members'
				|| this.sections[s].id == 'dealers' || this.sections[s].id == 'distributors' ) {
				return '';
			}
			else if( this.sections[s].id == 'tasks' ) {
				return this.sections[s].cellClasses[j];
			}
			else if( this.sections[s].id == 'artcatalog' ) {
				return this.sections[s].cellClasses[j];
			}
			return '';
		};
		this.menu.liveSearchResultValue = function(s, f, i, j, d) {
			if( this.sections[s].id == 'wineproduction' ) {
				switch(j) {
					case 0: return "<span class='maintext'>" + d.order.invoice_number + "</span>" + "<span class='subtext'>" + M.ciniki_businesses_main.statusOptions[d.order.status] + "</span>";
					case 1: return "<span class='maintext'>" + d.order.wine_name + "</span>" + "<span class='subtext'>" + d.order.customer_name + "</span>";
					case 2: return "<span class='maintext'>" + d.order.wine_type + "</span>" + "<span class='subtext'>" + d.order.kit_length + "&nbsp;weeks</span>";
				}
				// Only other possibility is a date
				var dt = d.order[this.sections[s].dataMaps[j]];
				// Check for missing filter date, and try to take a guess
				if( dt != null && dt != '' ) {
					return dt.replace(/(...)\s([0-9]+),\s([0-9][0-9][0-9][0-9])/, "<span class='maintext'>$1<\/span><span class='subtext'>$2<\/span>");
				} else {
					return '';
				}
			}
			// Appointments and calendars both return the same format
			else if( this.sections[s].id == 'calendars' ) {
				if( j == 0 ) { 
					if( d.appointment.start_ts == 0 ) { 
						return 'unscheduled';
					}   
					if( d.appointment.allday == 'yes' ) { 
						return d.appointment.start_date.split(/ [0-9]+:/)[0];
					}   
					return '<span class="maintext">' + d.appointment.start_date.split(/ [0-9]+:/)[0] + '</span><span class="subtext">' + d.appointment.start_date.split(/, [0-9][0-9][0-9][0-9] /)[1] + '</span>';
				} else if( j == 1 ) { 
					var t = '';
					if( d.appointment.secondary_colour != null && d.appointment.secondary_colour != '' ) {
						//t += '<span class="colourswatch" style="background-color:' + d.appointment.secondary_colour + '">&nbsp;</span> '
						t += '<span class="colourswatch" style="background-color:' + d.appointment.secondary_colour + '">';
						if( d.appointment.secondary_colour_text != null && d.appointment.secondary_colour_text != '' ) { t += d.appointment.secondary_colour_text; }
						else { t += '&nbsp;'; }
						t += '</span> '
					}
					t += d.appointment.subject;
					if( d.appointment.secondary_text != null && d.appointment.secondary_text != '' ) {
						t += ' <span class="secondary">' + d.appointment.secondary_text + '</span>';
					}
					return t;
				}
			}
			else if( this.sections[s].id == 'tasks' ) {
				switch(j) {
					case 0: return M.curBusiness.atdo.priorities[d.task.priority];
					case 1: return '<span class="maintext">' + d.task.subject + '</span><span class="subtext">' + d.task.assigned_users + '&nbsp;</span>';
					case 2: return '<span class="maintext">' + d.task.due_date + '</span><span class="subtext">' + d.task.due_time + '</span>';
				}
				return '';
			}
			else if( this.sections[s].id == 'customers' ) {
				switch(j) {
					case 0: return d.customer.display_name;
					case 1: return d.customer.status_text;
				}
			}
			else if( this.sections[s].id == 'members' 
				|| this.sections[s].id == 'dealers' 
				|| this.sections[s].id == 'distributors'
				) {
				return d.customer.display_name;
			}
			else if( this.sections[s].id == 'products' ) {
//				return (d.product.category!=''?d.product.category:'Uncategorized') + ' - ' + d.product.name;
				switch(j) {
					case 0: return d.product.name;
//					case 1: return d.product.inventory_current_num + (d.product.inventory_reserved!=null?' <span class="subdue">[' + d.product.inventory_reserved + ']</span>':'');
					case 1: return d.product.inventory_current_num + ((d.product.inventory_reserved!=null&&parseFloat(d.product.inventory_current_num)>=0)?' <span class="subdue">[' + d.product.inventory_reserved + ']</span>':'');
				}
			}
			else if( this.sections[s].id == 'artcatalog' ) {
				if( j == 0 ) {
					if( d.item.image != null && d.item.image != '' ) {
						return '<img width="75px" height="75px" src=\'' + d.item.image + '\' />';
					} else if( d.item.image_id > 0 ) {
						return '<img width="75px" height="75px" src=\'' + M.api.getBinaryURL('ciniki.artcatalog.getImage', {'business_id':M.curBusinessID, 'image_id':d.item.image_id, 'version':'thumbnail', 'maxwidth':'75'}) + '\' />';
					} else {
						return '<img width="75px" height="75px" src=\'/ciniki-mods/core/ui/themes/default/img/noimage_75.jpg\' />';
					}
				} else if( j == 1 ) {
					var sold = '';
					var price = '<b>Price</b>: ';
					var media = '';
					var size = '';
					if( d.item.sold == 'yes' ) { sold = ' <b>SOLD</b>'; }
					if( d.item.price != '' ) {
						if( d.item.price[0] != '$' ) { price += '$' + d.item.price; }
						else { price += d.item.price; }
					}
					if( d.item.type == 1 ) {
						return '<span class="maintext">' + d.item.name + '</span><span class="subtext"><b>Media</b>: ' + d.item.media + ', <b>Size</b>: ' + d.item.size + ', <b>Framed</b>: ' + d.item.framed_size + ', ' + price + sold + '</span>'; 
					} else if( d.item.type == 2 ) {
						return '<span class="maintext">' + d.item.name + '</span><span class="subtext">' + price + sold + '</span>'; 
					} else if( d.item.type == 3 ) {
						return '<span class="maintext">' + d.item.name + '</span><span class="subtext"><b>Size</b>: ' + d.item.size + ', ' + price + sold + '</span>'; 
					} else if( d.item.type == 3 ) {
						return '<span class="maintext">' + d.item.name + '</span><span class="subtext">' + price + sold + '</span>'; 
					}
				} else if( j == 2 ) {
					return '<span class="maintext">' + d.item.catalog_number + '</span><span class="subtext">' + d.item.location + '</span>';
				}
			}
			else if( this.sections[s].id == 'sapos' || this.sections[s].id == 'sapos_orders' ) {
				switch (j) {
					case 0: return d.invoice.invoice_number;
					case 1: return d.invoice.invoice_date;
					case 2: return d.invoice.customer_display_name;
					case 3: return d.invoice.total_amount_display;
					case 4: return d.invoice.status_text;
				}
			}
			return '';
		}
		this.menu.liveSearchResultRowFn = function(s, f, i, j, d) { 
			if( this.sections[s].id == 'wineproduction' ) {
				return 'M.startApp(\'ciniki.wineproduction.main\',null,\'M.ciniki_businesses_main.showMenu();\',\'mc\',{\'order_id\':' + d.order.id + '})';
			}
			else if( this.sections[s].id == 'customers' ) {
				return 'M.startApp(\'ciniki.customers.main\',null,\'M.ciniki_businesses_main.showMenu();\',\'mc\',{\'customer_id\':' + d.customer.id + '})';
			}
			else if( this.sections[s].id == 'members' ) {
				return 'M.startApp(\'ciniki.customers.members\',null,\'M.ciniki_businesses_main.showMenu();\',\'mc\',{\'customer_id\':' + d.customer.id + '})';
			}
			else if( this.sections[s].id == 'dealers' ) {
				return 'M.startApp(\'ciniki.customers.dealers\',null,\'M.ciniki_businesses_main.showMenu();\',\'mc\',{\'customer_id\':' + d.customer.id + '})';
			}
			else if( this.sections[s].id == 'distributors' ) {
				return 'M.startApp(\'ciniki.customers.distributors\',null,\'M.ciniki_businesses_main.showMenu();\',\'mc\',{\'customer_id\':' + d.customer.id + '})';
			}
			else if( this.sections[s].id == 'tasks' ) {
				return 'M.startApp(\'ciniki.atdo.main\',null,\'M.ciniki_businesses_main.showMenu();\',\'mc\',{\'atdo_id\':' + d.task.id + '})';
			}
			else if( this.sections[s].id == 'products' ) {
//				return 'M.startApp(\'ciniki.products.winekits\',null,\'M.ciniki_businesses_main.showMenu();\',\'mc\',{\'product_id\':' + d.product.id + '})';
				return 'M.startApp(\'ciniki.products.product\',null,\'M.ciniki_businesses_main.showMenu();\',\'mc\',{\'product_id\':\'' + d.product.id + '\'});';
			}
			else if( this.sections[s].id == 'artcatalog' ) {
				return 'M.startApp(\'ciniki.artcatalog.main\',null,\'M.ciniki_businesses_main.showMenu();\',\'mc\',{\'artcatalog_id\':' + d.item.id + '})';
			}
			else if( this.sections[s].id == 'sapos' || this.sections[s].id == 'sapos_orders' ) {
				return 'M.startApp(\'ciniki.sapos.invoice\',null,\'M.ciniki_businesses_main.showMenu();\',\'mc\',{\'invoice_id\':' + d.invoice.id + '})';
			}
			return null;
		};
		this.menu.liveSearchResultRowStyle = function(s, f, i, d) {
			if( this.sections[s].id == 'customers' ) {
				if( M.curBusiness.customers.settings['ui-colours-customer-status-' + d.customer.status] != null ) {
					return 'background: ' + M.curBusiness.customers.settings['ui-colours-customer-status-' + d.customer.status];
				}
			}
			if( this.sections[s].id == 'tasks' ) {
				if( d.task.status != 'closed' ) { return 'background: ' + M.curBusiness.atdo.settings['tasks.priority.' + d.task.priority]; }
				else { return 'background: ' + M.curBusiness.atdo.settings['tasks.status.60']; }
			}
			return '';
		};
		this.menu.liveSearchSubmitFn = function(s, search_str) {
			if( this.sections[s].id == 'wineproduction' ) {
				M.startApp('ciniki.wineproduction.main',null,'M.ciniki_businesses_main.showMenu();','mc',{'search': search_str});
			}
			else if( this.sections[s].id == 'calendars' ) {
				M.startApp('ciniki.calendars.main',null,'M.ciniki_businesses_main.showMenu();','mc',{'search': search_str});
			}
			else if( this.sections[s].id == 'customers' ) {
				M.startApp('ciniki.customers.main',null,'M.ciniki_businesses_main.showMenu();','mc',{'search': search_str,'type':'customers'});
			}
			else if( this.sections[s].id == 'members' ) {
				M.startApp('ciniki.customers.main',null,'M.ciniki_businesses_main.showMenu();','mc',{'search': search_str,'type':'members'});
			}
			else if( this.sections[s].id == 'dealers' ) {
				M.startApp('ciniki.customers.main',null,'M.ciniki_businesses_main.showMenu();','mc',{'search': search_str,'type':'dealers'});
			}
			else if( this.sections[s].id == 'distributors' ) {
				M.startApp('ciniki.customers.main',null,'M.ciniki_businesses_main.showMenu();','mc',{'search': search_str,'type':'distributors'});
			}
			else if( this.sections[s].id == 'tasks' ) {
				M.startApp('ciniki.atdo.main',null,'M.ciniki_businesses_main.showMenu();','mc',{'tasksearch': search_str});
			}
			else if( this.sections[s].id == 'products' ) {
				M.startApp('ciniki.products.main',null,'M.ciniki_businesses_main.showMenu();','mc',{'search': search_str});
			}
		};
		this.menu.liveSearchResultCellFn = function(s, f, i, j, d) {
			if( this.sections[s].id == 'calendars' ) {
				if( j == 0 && d.appointment.start_ts > 0 ) {
					return 'M.startApp(\'ciniki.calendars.main\',null,\'M.ciniki_businesses_main.showMenu();\',\'mc\',{\'date\':\'' + d.appointment.date + '\'});';
				}
				if( d.appointment.module == 'ciniki.wineproduction' ) {
					return 'M.startApp(\'ciniki.wineproduction.main\',null,\'M.ciniki_businesses_main.showMenu();\',\'mc\',{\'appointment_id\':\'' + d.appointment.id + '\'});';
				}
				if( d.appointment.module == 'ciniki.atdo' ) {
					return 'M.startApp(\'ciniki.atdo.main\',null,\'M.ciniki_businesses_main.showMenu();\',\'mc\',{\'atdo_id\':\'' + d.appointment.id + '\'});';
				}
			}
			return '';
		};
		this.menu.liveSearchResultCellColour = function(s, f, i, j, d) {
			if( this.sections[s].id == 'calendars' && j == 1 ) { 
				if( d.appointment != null && d.appointment.colour != null && d.appointment.colour != '' ) {
					return d.appointment.colour;
				}
				return '#77ddff';
			}
	//		if( this.sections[s].id == 'tasks' && j == 1 ) { 
	//			if( d.appointment != null && d.appointment.colour != null && d.appointment.colour != '' ) {
	//				return d.appointment.colour;
	//			}
	//			return '#aaddff';
	//		}
			return '';
		};

		this.menu.cellValue = function(s, i, j, d) {
			if( s == '_tasks' ) {
				switch (j) {
					case 0: return '<span class="icon">' + M.curBusiness.atdo.priorities[d.task.priority] + '</span>';
					case 1: 
						var pname = '';
						if( d.task.project_name != null && d.task.project_name != '' ) {
							pname = ' <span class="subdue">[' + d.task.project_name + ']</span>';
						}
						return '<span class="maintext">' + d.task.subject + pname + '</span><span class="subtext">' + d.task.assigned_users + '&nbsp;</span>';
					case 2: return '<span class="maintext">' + d.task.due_date + '</span><span class="subtext">' + d.task.due_time + '</span>';
				}
			}
		};
		this.menu.rowStyle = function(s, i, d) {
			if( s == '_tasks' ) {
				if( d.task.status != 'closed' ) { return 'background: ' + M.curBusiness.atdo.settings['tasks.priority.' + d.task.priority]; }
				else { return 'background: ' + M.curBusiness.atdo.settings['tasks.status.60']; }
			}
			if( d != null && d.task != null ) {
				if( d.task.status != 'closed' ) { return 'background: ' + M.curBusiness.atdo.settings['tasks.priority.' + d.task.priority]; }
				else { return 'background: ' + M.curBusiness.atdo.settings['tasks.status.60']; }
			}
			return '';
		};
		this.menu.rowFn = function(s, i, d) {
			return 'M.startApp(\'ciniki.atdo.main\',null,\'M.ciniki_businesses_main.showMenu();\',\'mc\',{\'atdo_id\':\'' + d.task.id + '\'});';
		};
		this.menu.sectionData = function(s) {
			if( s == '_tasks' ) { return this.data._tasks; }
			return this.sections[s].list;
		}
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
		var appContainer = M.createContainer('mc', 'ciniki_businesses_main', 'yes');
		if( appContainer == null ) {
			alert('App Error');
			return false;
		} 

		//
		// Get the business_id to be opened
		//
		if( args.id != null && args.id != '' ) {
			this.openBusiness(cb, args.id);
		} else {
			alert('Business not found');
			return false;
		}
	}

	//
	// Open a business for the specified ID
	//
	this.openBusiness = function(cb, id) {
		if( id != null ) {
			M.curBusinessID = id;
			// (re)set the business object
			delete M.curBusiness;
			M.curBusiness = {'id':id};
		}
		if( M.curBusinessID == null ) {
			alert('Invalid business');
		}

		//
		// Reset all buttons
		//
		this.menu.leftbuttons = {};
		this.menu.rightbuttons = {};

		//
		// If both callbacks are null, then this is the root of the menu system
		//
		if( cb == null ) {
			// 
			// Add the buttons required on home menu
			//
			this.menu.addButton('account', 'Account', 'M.startApp(\'ciniki.users.main\',null,\'M.ciniki_businesses_main.menu.show();\');');
			this.menu.addLeftButton('logout', 'Logout', 'M.logout();');
			if( M.userID > 0 && (M.userPerms&0x01) == 0x01 ) {
				this.menu.addLeftButton('sysadmin', 'Admin', 'M.startApp(\'ciniki.sysadmin.main\',null,\'M.menuHome.show();\');');
			}
			M.menuHome = this.menu;
		} else {
			this.menu.addClose('Back');
		}
		this.menu.cb = cb;

		this.openBusinessSettings();
	}

	this.openBusinessSettings = function() {
		// 
		// Get the list of owners and employees for the business
		//
		M.api.getJSONCb('ciniki.businesses.getUserSettings', 
			{'business_id':M.curBusinessID}, function(rsp) {
				if( rsp.stat != 'ok' ) {
					M.api.err(rsp);
					return false;
				}
				M.ciniki_businesses_main.openBusinessFinish(rsp);
			});
	}

	this.openBusinessFinish = function(rsp) {
		// 
		// Setup menu name
		//
		M.curBusiness.name = rsp.name;

		//
		// Setup CSS
		//
		if( rsp.css != null && rsp.css != '' ) {
			M.gE('business_colours').innerHTML = rsp.css;
		} else {
			M.gE('business_colours').innerHTML = M.defaultBusinessColours;
		}

		//
		// Setup employees
		//
		M.curBusiness.employees = {};
		var ct = 0;
		for(i in rsp.users) {
			M.curBusiness.employees[rsp.users[i].user.id] = rsp.users[i].user.display_name;
			ct++;
		}
		M.curBusiness.numEmployees = ct;

		// 
		// Setup business permissions for the user
		//
		M.curBusiness.permissions = {};
		for(i in rsp.permissions) {
			M.curBusiness.permissions[rsp.permissions[i].group.name] = rsp.permissions[i].group;
		}

		// 
		// Setup the settings for activated modules
		//
		if( rsp.settings != null && rsp.settings['ciniki.bugs'] != null ) {
			M.curBusiness.bugs = {};
			M.curBusiness.bugs.priorities = {'10':'<span class="icon">Q</span>', '30':'<span class="icon">W</span>', '50':'<span class="icon">E</span>'};
			if( M.size == 'compact' ) {
				M.curBusiness.bugs.priorityText = {'10':'<span class="icon">Q</span>', '30':'<span class="icon">W</span>', '50':'<span class="icon">E</span>'};
			} else {
				M.curBusiness.bugs.priorityText = {'10':'<span class="icon">Q</span> Low', '30':'<span class="icon">W</span> Medium', '50':'<span class="icon">E</span> High'};
			}
			M.curBusiness.bugs.settings = rsp.settings['ciniki.bugs'];
		}
		if( rsp.settings != null && rsp.settings['ciniki.atdo'] != null ) {
			M.curBusiness.atdo = {};
			M.curBusiness.atdo.priorities = {'10':'<span class="icon">Q</span>', '30':'<span class="icon">W</span>', '50':'<span class="icon">E</span>'};
			if( M.size == 'compact' ) {
				M.curBusiness.atdo.priorityText = {'10':'<span class="icon">Q</span>', '30':'<span class="icon">W</span>', '50':'<span class="icon">E</span>'};
			} else {
				M.curBusiness.atdo.priorityText = {'10':'<span class="icon">Q</span> Low', '30':'<span class="icon">W</span> Medium', '50':'<span class="icon">E</span> High'};
			}
			M.curBusiness.atdo.settings = rsp.settings['ciniki.atdo'];
		}
		if( rsp.settings != null && rsp.settings['ciniki.customers'] != null ) {
			M.curBusiness.customers = {'settings':rsp.settings['ciniki.customers']};
		}
		if( rsp.settings != null && rsp.settings['ciniki.taxes'] != null ) {
			M.curBusiness.taxes = {'settings':rsp.settings['ciniki.taxes']};
		}
		if( rsp.settings != null && rsp.settings['ciniki.services'] != null ) {
			M.curBusiness.services = {'settings':rsp.settings['ciniki.services']};
		}
		if( rsp.settings != null && rsp.settings['ciniki.mail'] != null ) {
			M.curBusiness.mail = {'settings':rsp.settings['ciniki.mail']};
		}
		if( rsp.settings != null && rsp.settings['ciniki.artcatalog'] != null ) {
			M.curBusiness.artcatalog = {'settings':rsp.settings['ciniki.artcatalog']};
		}
		if( rsp.settings != null && rsp.settings['ciniki.sapos'] != null ) {
			M.curBusiness.sapos = {'settings':rsp.settings['ciniki.sapos']};
		}
		if( rsp.settings != null && rsp.settings['ciniki.taxes'] != null ) {
			M.curBusiness.taxes = {'settings':rsp.settings['ciniki.taxes']};
		}
		if( rsp.settings != null && rsp.settings['ciniki.products'] != null ) {
			M.curBusiness.products = {'settings':rsp.settings['ciniki.products']};
		}
		if( rsp.settings != null && rsp.settings['googlemapsapikey'] != null && rsp.settings['googlemapsapikey'] != '' ) {
			M.curBusiness.settings = {'googlemapsapikey':rsp.settings['googlemapsapikey']};
		}
		if( rsp.intl != null ) {
			M.curBusiness.intl = rsp.intl;
		}

		var modules = {};
		for(i in rsp.modules) {
			modules[rsp.modules[i].module.name] = rsp.modules[i].module;
			if( rsp.settings != null && rsp.settings[rsp.modules[i].module.name] != null ) {
				modules[rsp.modules[i].module.name].settings = rsp.settings[rsp.modules[i].module.name];
			}
		}
		M.curBusiness.modules = modules;

		//
		// FIXME: Check if business is suspended status, and display message
		//

		//
		// Show the menu, which loads modules and display up to date message counts, etc.
		//
		this.showMenuFinish(rsp, 'yes');
	};
	
	this.showMenu = function() {
		// FIXME: Remove, no longer needed (ACTUALLY still used on return)
		//
		// Get the modules enabled for the business, along with message counts, etc.
		//
		var r = M.api.getJSONCb('ciniki.businesses.getUserModules', 
			{'business_id':M.curBusiness.id}, function(r) {
				if( r.stat != 'ok' ) {
					M.api.err(r);
					return false;
				}
				M.ciniki_businesses_main.showMenuFinish(r, 'no');
		});
	}

	this.showMenuFinish = function(r, autoopen) {
		this.menu.title = M.curBusiness.name;
		//
		// If sysadmin, or business owner
		//
		if( M.userID > 0 && 
			( (M.userPerms&0x01) == 0x01 || M.curBusiness.permissions.owners != null )
			) {
			this.menu.addButton('settings', 'Settings', 'M.startApp(\'ciniki.businesses.settings\',null,\'M.ciniki_businesses_main.openBusinessSettings();\');');
		}

		var c = 0;
		var join = -1;	// keep track of how many are already joined together

		var perms = M.curBusiness.permissions;

		//
		// Check that the module is turned on for the business, and the user has permissions to the module
		//

		this.menu.sections = {};
		var menu_search = 0;
		if( M.curBusiness.modules['ciniki.exhibitions'] != null && r.exhibitions != null 
			&& (perms.owners != null || perms.employees != null || (M.userPerms&0x01) == 1) ) {
			for(i in r.exhibitions) {
				this.menu.sections[c] = {'label':r.exhibitions[i].exhibition.name, 'list':{}};
//					'gallery':{'label':'Gallery', 
//						'fn':'M.startApp(\'ciniki.exhibitions.images\',null,\'M.ciniki_businesses_main.showMenu();\',\'mc\',{\'exhibition_id\':\'' + r.exhibitions[i].exhibition.id + '\'});'},
				if( r.exhibitions[i].exhibition['use-exhibitors'] == 'yes' ) {
					this.menu.sections[c].list['exhibitors'] = {'label':'Exhibitors', 
						'fn':'M.startApp(\'ciniki.exhibitions.participants\',null,\'M.ciniki_businesses_main.showMenu();\',\'mc\',{\'exhibition_id\':\'' + r.exhibitions[i].exhibition.id + '\',\'exhibitors\':\'yes\'});'};
				}
				if( r.exhibitions[i].exhibition['use-tour'] == 'yes' ) {
					this.menu.sections[c].list['tourexhibitors'] = {'label':'Tour Exhibitors', 
						'fn':'M.startApp(\'ciniki.exhibitions.participants\',null,\'M.ciniki_businesses_main.showMenu();\',\'mc\',{\'exhibition_id\':\'' + r.exhibitions[i].exhibition.id + '\',\'tour\':\'yes\'});'};
				}
				if( r.exhibitions[i].exhibition['use-sponsors'] == 'yes' ) {
					this.menu.sections[c].list['sponsors'] = {'label':'Sponsors', 
						'fn':'M.startApp(\'ciniki.exhibitions.participants\',null,\'M.ciniki_businesses_main.showMenu();\',\'mc\',{\'exhibition_id\':\'' + r.exhibitions[i].exhibition.id + '\',\'sponsors\':\'yes\'});'};
				}
//					'contacts':{'label':'Contacts', 
//						'fn':'M.startApp(\'ciniki.exhibitions.participants\',null,\'M.ciniki_businesses_main.showMenu();\',\'mc\',{\'exhibition_id\':\'' + r.exhibitions[i].exhibition.id + '\',\'contacts\':\'yes\'});'},
//				}};
				c++;
				join = 0;
			}
			if( join == 0 ) {
				this.menu.sections[c] = {'label':'Menu', 'list':{}};
			}
		}
		// Art Catalog
		if( M.curBusiness.modules['ciniki.artcatalog'] != null 
			&& (perms.owners != null || perms.employees != null || (M.userPerms&0x01) == 1) ) {
			if( M.curBusiness.modules['ciniki.sapos'] != null ) {
				this.menu.sections[c] = {'label':'', 'id':'artcatalog', 'searchlabel':'Art Catalog', 
					'type':'livesearchgrid', 'livesearchcols':3, 'hint':'',
					'headerValues':null,
					'cellClasses':['thumbnail','multiline','multiline'],
					'noData':'No art found',
					'addFn':'M.startApp(\'ciniki.artcatalog.main\',null,\'M.ciniki_businesses_main.showMenu();\',\'mc\',{\'artcatalog_id\':0});',
					'fn':'M.startApp(\'ciniki.artcatalog.main\',null,\'M.ciniki_businesses_main.showMenu();\',\'mc\',{});',
				};
				menu_search = 1;
				c++;
			} else {
				this.menu.sections[c++] = {'label':'', 'list':{
					'_':{'label':'Art Catalog', 'fn':'M.startApp(\'ciniki.artcatalog.main\', null, \'M.ciniki_businesses_main.showMenu();\');'}}};
			}
		}
		// Wine production module, all owners, employees and wine production group
		if( M.curBusiness.modules['ciniki.wineproduction'] != null 
			&& (perms.owners != null || perms.employees != null || (M.userPerms&0x01) == 1) ) {
			this.menu.sections[c] = {'label':'', 'id':'wineproduction', 'searchlabel':'Wine Production', 'type':'livesearchgrid', 'livesearchcols':8, 'hint':'',
				'headerValues':['INV#', 'Wine', 'Type', 'BD', 'OD', 'SD', 'RD', 'FD'],
				'dataMaps':['invoice_number', 'wine_and_customer', 'wine_type_and_length', 'bottling_date', 'order_date', 'start_date', 'racking_date', 'filtering_date'],
				'noData':'No active orders found',
				'addFn':'M.startApp(\'ciniki.wineproduction.main\', null, \'M.ciniki_businesses_main.showMenu();\',\'mc\',{\'add\':\'yes\'});',
				'fn':'M.startApp(\'ciniki.wineproduction.main\', null, \'M.ciniki_businesses_main.showMenu();\');',
			};
			menu_search = 1;
			c++;
		}

		if( M.curBusiness.modules['ciniki.calendars'] != null 
			&& (perms.owners != null || perms.employees != null || (M.userPerms&0x01) == 1) ) {
			var cal_dt = new Date();
			var cal_date = cal_dt.getFullYear() + '-' + (cal_dt.getMonth()+1) + '-' + cal_dt.getDate();
			this.menu.sections[c] = {'label':'', 'id':'calendars', 'searchlabel':'Calendars', 'type':'livesearchgrid', 
				'livesearchtype':'appointments', 'livesearchcols':2, 'hint':'',
				'headerValues':null,
				'noData':'No appointments found',
				'addFn':'M.startApp(\'ciniki.atdo.main\', null, \'M.ciniki_businesses_main.showMenu();\',\'mc\',{\'add\':\'appointment\'});',
				'fn':'M.startApp(\'ciniki.calendars.main\', null, \'M.ciniki_businesses_main.showMenu();\',\'mc\',{\'date\':\'' + cal_date + '\'});',
			};
			menu_search = 1;
			c++;
		}

		//
		// Order management menu
		//
		if( M.curBusiness.modules['ciniki.sapos'] != null 
			&& (M.curBusiness.modules['ciniki.sapos'].flags&0x60) > 0 // Order or Shipping enabled
			&& (perms.owners != null || perms.employees != null || (M.userPerms&0x01) == 1)) {
			this.menu.sections[c] = {'label':'', 'id':'sapos_orders', 'searchlabel':'Orders', 'type':'livesearchgrid', 
				'livesearchcols':5, 'hint':'',
				'headerValues':['Invoice #','Date','Customer','Amount','Status'],
				'cellClasses':['','',''],
				'noData':'No orders found',
				'addFn':'M.startApp(\'ciniki.sapos.invoice\', null, \'M.ciniki_businesses_main.showMenu();\',\'mc\',{\'customer_id\':\'0\',\'invoice_type\':\'40\'});',
				'fn':'M.startApp(\'ciniki.sapos.orders\', null, \'M.ciniki_businesses_main.showMenu();\',\'mc\',{});',
			};
			menu_search = 1;
			c++;
		}

		//
		// Simple Accounting/POS/Invoicing/Expenses module
		//
		if( M.curBusiness.modules['ciniki.sapos'] != null 
			&& (perms.owners != null || perms.employees != null || (M.userPerms&0x01) == 1) ) {
			this.menu.sections[c] = {'label':'', 'id':'sapos', 'searchlabel':'Accounting', 'type':'livesearchgrid', 
				'livesearchcols':5, 'hint':'',
				'headerValues':['Invoice #','Date','Customer','Amount','Status'],
				'cellClasses':['','',''],
				'noData':'No invoices found',
				'addFn':'M.startApp(\'ciniki.sapos.invoice\', null, \'M.ciniki_businesses_main.showMenu();\',\'mc\',{});',
				'fn':'M.startApp(\'ciniki.sapos.main\', null, \'M.ciniki_businesses_main.showMenu();\',\'mc\',{});',
			};
			if( (M.curBusiness.modules['ciniki.sapos'].flags&0x04) > 0 ) {
				this.menu.sections[c].addFn = 'M.startApp(\'ciniki.sapos.qi\', null, \'M.ciniki_businesses_main.showMenu();\',\'mc\',{});';
				}
			menu_search = 1;
			c++;
		}

		// Products module, all owners and employees and Products group
		if( M.curBusiness.modules['ciniki.products'] != null 
			&& (perms.owners != null || perms.employees != null || (M.userPerms&0x01) == 1) ) { 
			this.menu.sections[c] = {'label':'', 'id':'products', 'searchlabel':'Products', 
				'type':'livesearchgrid', 
				'livesearchcols':1, 'hint':'',
				'headerValues':null,
				'noData':'No products found',
				'fn':'M.startApp(\'ciniki.products.main\', null, \'M.ciniki_businesses_main.showMenu();\');',
				'addFn':'M.startApp(\'ciniki.products.edit\',null,\'M.ciniki_businesses_main.showMenu();\',\'mc\',{\'product_id\':0});',
			};
			if( (M.curBusiness.modules['ciniki.products'].flags&0x04) > 0 ) {
				this.menu.sections[c].livesearchcols = 2;
				this.menu.sections[c].headerValues = ['Product', 'Inv [Rsv]'];
			} else {
				this.menu.sections[c].livesearchcols = 1;
				this.menu.sections[c].headerValues = null;
			}
			menu_search = 1;
			c++;
		}

		// Customer module, all owners and employees
		// Add a space to the label, to create a separate section appearance
		if( M.curBusiness.modules['ciniki.customers'] != null 
			&& (M.curBusiness.modules['ciniki.customers'].flags&0x01) > 0 
			&& (perms.owners != null || perms.employees != null || perms.salesreps != null || (M.userPerms&0x01) == 1) ) {
			var label = 'Customers';
			if( M.curBusiness.customers != null 
				&& M.curBusiness.customers.settings['ui-labels-customers'] != null
				&& M.curBusiness.customers.settings['ui-labels-customers'] != ''
				) {
				label = M.curBusiness.customers.settings['ui-labels-customers'];
			}
			if( menu_search == 1 ) {
				this.menu.sections[c] = {'label':'', 'id':'customers', 'searchlabel':label, 'type':'livesearchgrid', 
					'livesearchcols':2, 'hint':'',
					'headerValues':['Customer', 'Status'],
					'noData':'No ' + label + ' found',
					'addFn':'M.startApp(\'ciniki.customers.edit\', null, \'M.ciniki_businesses_main.showMenu();\',\'mc\',{\'customer_id\':0});',
					'fn':'M.startApp(\'ciniki.customers.main\', null, \'M.ciniki_businesses_main.showMenu();\');',
				};
				c++;
			} else {
				this.menu.sections[c++] = {'label':'', 'list':{
					'_':{'label':label, 'fn':'M.startApp(\'ciniki.customers.main\', null, \'M.ciniki_businesses_main.showMenu();\');'}}};
			}
		}
		//
		// Members
		//
		if( M.curBusiness.modules['ciniki.customers'] != null 
			&& (M.curBusiness.modules['ciniki.customers'].flags&0x02) > 0 
			&& (perms.owners != null || perms.employees != null || (M.userPerms&0x01) == 1) ) {
			var label = 'Members';
			if( M.curBusiness.customers != null && M.curBusiness.customers.settings['ui-labels-members'] != null) {
				label = M.curBusiness.customers.settings['ui-labels-members'];
			}
			if( menu_search == 1 ) {
				this.menu.sections[c] = {'label':'', 'id':'members', 'searchlabel':label, 'type':'livesearchgrid', 
					'livesearchcols':1, 'hint':'',
					'headerValues':null,
					'noData':'No ' + label + ' found',
					'addFn':'M.startApp(\'ciniki.customers.edit\', null, \'M.ciniki_businesses_main.showMenu();\',\'mc\',{\'customer_id\':0,\'member\':\'yes\'});',
					'fn':'M.startApp(\'ciniki.customers.members\', null, \'M.ciniki_businesses_main.showMenu();\');',
				};
				c++;
			} else {
				this.menu.sections[c++] = {'label':'', 'list':{
					'_':{'label':label, 'fn':'M.startApp(\'ciniki.customers.members\', null, \'M.ciniki_businesses_main.showMenu();\');'}}};
			}
		}
		// Dealers
		if( M.curBusiness.modules['ciniki.customers'] != null 
			&& (M.curBusiness.modules['ciniki.customers'].flags&0x10) > 0 
			&& (perms.owners != null || perms.employees != null || (M.userPerms&0x01) == 1) ) {
			var label = 'Dealers';
			if( M.curBusiness.customers != null && M.curBusiness.customers.settings['ui-labels-dealers'] != null) {
				label = M.curBusiness.customers.settings['ui-labels-dealers'];
			}
			if( menu_search == 1 ) {
				this.menu.sections[c] = {'label':'', 'id':'dealers', 'searchlabel':label, 
					'type':'livesearchgrid', 'livesearchcols':1, 'hint':'',
					'headerValues':null,
					'noData':'No ' + label + ' found',
					'addFn':'M.startApp(\'ciniki.customers.edit\', null, \'M.ciniki_businesses_main.showMenu();\',\'mc\',{\'customer_id\':0,\'dealer\':\'yes\'});',
					'fn':'M.startApp(\'ciniki.customers.dealers\', null, \'M.ciniki_businesses_main.showMenu();\');',
				};
				c++;
			} else {
				this.menu.sections[c++] = {'label':'', 'list':{
					'_':{'label':label, 'fn':'M.startApp(\'ciniki.customers.dealers\', null, \'M.ciniki_businesses_main.showMenu();\');'}}};
			}
		}

		// Distributors
		if( M.curBusiness.modules['ciniki.customers'] != null 
			&& (M.curBusiness.modules['ciniki.customers'].flags&0x100) > 0 
			&& (perms.owners != null || perms.employees != null || (M.userPerms&0x01) == 1) ) {
			var label = 'Distributors';
			if( M.curBusiness.customers != null && M.curBusiness.customers.settings['ui-labels-distributors'] != null) {
				label = M.curBusiness.customers.settings['ui-labels-distributors'];
			}
			if( menu_search == 1 ) {
				this.menu.sections[c] = {'label':'', 'id':'distributors', 'searchlabel':label, 
					'type':'livesearchgrid', 'livesearchcols':1, 'hint':'',
					'headerValues':null,
					'noData':'No ' + label + ' found',
					'addFn':'M.startApp(\'ciniki.customers.edit\', null, \'M.ciniki_businesses_main.showMenu();\',\'mc\',{\'customer_id\':0,\'distributor\':\'yes\'});',
					'fn':'M.startApp(\'ciniki.customers.distributors\', null, \'M.ciniki_businesses_main.showMenu();\');',
				};
				c++;
			} else {
				this.menu.sections[c++] = {'label':'', 'list':{
					'_':{'label':label, 'fn':'M.startApp(\'ciniki.customers.distributors\', null, \'M.ciniki_businesses_main.showMenu();\');'}}};
			}
		}

		// Bugs/Features/Questions
		if( M.curBusiness.modules['ciniki.bugs'] != null 
			&& (perms.owners != null || perms.employees != null || (M.userPerms&0x01) == 1) ) { 
			if( join > -1 ) {
				this.menu.sections[c].list.bugs = {
					'label':'Bug Tracking', 'fn':'M.startApp(\'ciniki.bugs.main\', null, \'M.ciniki_businesses_main.showMenu();\');'};
				join++;
			} else {
				this.menu.sections[c++] = {'label':'', 'list':{
					'_':{'label':'Bug Tracking', 'fn':'M.startApp(\'ciniki.bugs.main\', null, \'M.ciniki_businesses_main.showMenu();\');'}}};
			}
		}

		// Check if the remaining sections should be joined together as one section
		// to balance the menu
		if( c > 4 && join < 0 ) {
			join = 0;
			this.menu.sections[c] = {'label':' &nbsp; ', 'list':{}};
		}

		// Art Gallery
		if( M.curBusiness.modules['ciniki.artgallery'] != null 
			&& (perms.owners != null || perms.employees != null || (M.userPerms&0x01) == 1) ) {
			this.menu.sections[c++] = {'label':'', 'list':{
				'_':{'label':'Exhibitions', 'fn':'M.startApp(\'ciniki.artgallery.exhibitions\', null, \'M.ciniki_businesses_main.showMenu();\');'}}};
		}

		// Courses module
		if( M.curBusiness.modules['ciniki.courses'] != null 
			&& (perms.owners != null || perms.employees != null || (M.userPerms&0x01) == 1) ) {
			this.menu.sections[c++] = {'label':'', 'list':{
				'_':{'label':'Courses', 'fn':'M.startApp(\'ciniki.courses.offerings\', null, \'M.ciniki_businesses_main.showMenu();\');'}}};
		}
		// Classes module
		if( M.curBusiness.modules['ciniki.classes'] != null 
			&& (perms.owners != null || perms.employees != null || (M.userPerms&0x01) == 1) ) {
			this.menu.sections[c++] = {'label':'', 'list':{
				'_':{'label':'Classes', 'fn':'M.startApp(\'ciniki.classes.main\', null, \'M.ciniki_businesses_main.showMenu();\');'}}};
		}
		
		// Journal
		if( M.curBusiness.modules['ciniki.journal'] != null 
			&& (perms.owners != null || perms.employees != null || (M.userPerms&0x01) == 1) ) {
			this.menu.sections[c] = {'label':'', 'id':'journal', 'searchlabel':'Journal', 'type':'livesearchgrid', 
				'livesearchcols':1, 'hint':'',
				'headerValues':null,
				'noData':'No tasks found',
				'addFn':'M.startApp(\'ciniki.journal.main\', null, \'M.ciniki_businesses_main.showMenu();\',\'mc\',{\'add\':\'yes\'});',
				'fn':'M.startApp(\'ciniki.journal.main\', null, \'M.ciniki_businesses_main.showMenu();\');',
			};
			menu_search = 1;
			c++;
		}

		// services module, all owners and employees and Products group
		if( M.curBusiness.modules['ciniki.services'] != null 
			&& (perms.owners != null || perms.employees != null || (M.userPerms&0x01) == 1) ) { 
//			this.menu.sections[c++] = {'label':'', 'list':{
//				'_':{'label':'Services', 'fn':'M.startApp(\'ciniki.services.main\', null, \'M.ciniki_businesses_main.showMenu();\');'}}};
			this.menu.sections[c] = {'label':'', 'id':'services', 'searchlabel':'Services', 'type':'livesearchgrid', 
				'livesearchcols':1, 'hint':'', 'hidesearch':'yes',
				'headerValues':null,
				'noData':'No services found',
				'addFn':'M.startApp(\'ciniki.services.quickadd\', null, \'M.ciniki_businesses_main.showMenu();\');',
				'fn':'M.startApp(\'ciniki.services.main\', null, \'M.ciniki_businesses_main.showMenu();\');',
			};
			c++;
		}

		// ATDO - Tasks
		if( M.curBusiness.modules['ciniki.atdo'] != null 
			&& (M.curBusiness.modules['ciniki.atdo'].flags&0x02) > 0 
			&& (perms.owners != null || perms.employees != null || (M.userPerms&0x01) == 1) ) {
			this.menu.sections[c] = {'label':'', 'id':'tasks', 'searchlabel':'Tasks', 
				'type':'livesearchgrid', 
				'livesearchcols':3, 'hint':'',
				'headerValues':['','Task','Due'],
				'cellClasses':['multiline aligncenter','multiline','multiline'],
				'count':M.curBusiness.modules['ciniki.atdo'].task_count,
				'noData':'No tasks found',
				'addFn':'M.startApp(\'ciniki.atdo.main\', null, \'M.ciniki_businesses_main.showMenu();\',\'mc\',{\'add\':\'task\'});',
				'fn':'M.startApp(\'ciniki.atdo.main\', null, \'M.ciniki_businesses_main.showMenu();\',\'mc\',{\'tasks\':\'yes\'});',
			};
			menu_search = 1;
			c++;
		}

		// Check if the remaining sections should be joined together as one section
		// to balance the menu
		if( c > 4 && join < 0 ) {
			join = 0;
			this.menu.sections[c] = {'label':' &nbsp; ', 'list':{}};
		}

		// Wine production schedule
		if( M.curBusiness.modules['ciniki.wineproduction'] != null 
			&& (perms.owners != null || perms.employees != null || (M.userPerms&0x01) == 1) ) {
			if( join > -1 ) {
				this.menu.sections[c].list.wineproductionschedule = {
					'label':'Production Schedule', 'fn':'M.startApp(\'ciniki.wineproduction.main\',null,\'M.ciniki_businesses_main.showMenu();\',\'mc\',{\'schedule\':\'today\'});'};
				join++;
			} else {
				this.menu.sections[c++] = {'label':'', 'list':{
					'_':{'label':'Production Schedule', 'fn':'M.startApp(\'ciniki.wineproduction.main\', null, \'M.ciniki_businesses_main.showMenu();\',\'mc\',{\'schedule\':\'today\'});'}}};
			}
		}

		// Check if the remaining sections should be joined together as one section
		// to balance the menu
		if( c > 4 && join < 0 ) {
			join = 0;
			this.menu.sections[c] = {'label':' &nbsp; ', 'list':{}};
		}

		// Projects module
		if( M.curBusiness.modules['ciniki.projects'] != null 
			&& (perms.owners != null || perms.employees != null || (M.userPerms&0x01) == 1) ) {
			if( join > -1 ) {
				this.menu.sections[c].list.projects = {
					'label':'Projects', 'fn':'M.startApp(\'ciniki.projects.main\', null, \'M.ciniki_businesses_main.showMenu();\');'};
				join++;
			} else {
				this.menu.sections[c++] = {'label':'', 'list':{
					'_':{'label':'Projects', 'fn':'M.startApp(\'ciniki.projects.main\', null, \'M.ciniki_businesses_main.showMenu();\');'}}};
			}
		}

		// Blog
		if( M.curBusiness.modules['ciniki.blog'] != null 
			&& (perms.owners != null || perms.employees != null || (M.userPerms&0x01) == 1) ) { 
			if( (M.curBusiness.modules['ciniki.blog'].flags&0x01) > 0 ) {
				if( join > -1 ) {
					this.menu.sections[c].list.blog = {'label':'Blog', 'fn':'M.startApp(\'ciniki.blog.main\', null, \'M.ciniki_businesses_main.showMenu();\',\'mc\',{\'blogtype\':\'blog\'});'};
					join++;
				} else {
					this.menu.sections[c++] = {'label':'', 'list':{
						'_':{'label':'Blog', 'fn':'M.startApp(\'ciniki.blog.main\', null, \'M.ciniki_businesses_main.showMenu();\',\'mc\',{\'blogtype\':\'blog\'});'}}};
				}
			}
			if( (M.curBusiness.modules['ciniki.blog'].flags&0x0100) > 0 ) {
				if( join > -1 ) {
					this.menu.sections[c].list.blog = {'label':'Member News', 'fn':'M.startApp(\'ciniki.blog.main\', null, \'M.ciniki_businesses_main.showMenu();\',\'mc\',{\'blogtype\':\'memberblog\'});'};
					join++;
				} else {
					this.menu.sections[c++] = {'label':'', 'list':{
						'_':{'label':'Member News', 'fn':'M.startApp(\'ciniki.blog.main\', null, \'M.ciniki_businesses_main.showMenu();\',\'mc\',{\'blogtype\':\'memberblog\'});'}}};
				}
			}
		}

		//
		// Members
		//
		if( M.curBusiness.modules['ciniki.customers'] != null 
			&& (M.curBusiness.modules['ciniki.customers'].flags&0x02) > 0 
			&& M.curBusiness.modules['ciniki.membersonly'] != null
			) {
			if( join > -1 ) {
				this.menu.sections[c].list.membersonly = {'label':'Members Only', 'fn':'M.startApp(\'ciniki.membersonly.pages\', null, \'M.ciniki_businesses_main.showMenu();\',\'mc\',{});'};
				join++;
			} else {
				this.menu.sections[c++] = {'label':'', 'list':{
					'_':{'label':'Members Only', 'fn':'M.startApp(\'ciniki.membersonly.pages\', null, \'M.ciniki_businesses_main.showMenu();\',\'mc\',{});'}}};
			}
		}

		// Exhibitions
		if( M.curBusiness.modules['ciniki.exhibitions'] != null 
			&& (perms.owners != null || perms.employees != null || (M.userPerms&0x01) == 1) ) {
			if( join > -1 ) {
				this.menu.sections[c].list.exhibitions = {
					'label':'Exhibitions', 
					'fn':'M.startApp(\'ciniki.exhibitions.main\', null, \'M.ciniki_businesses_main.showMenu();\');',
					};
//				this.menu.sections[c].list['contacts'] = {
//					'label':'Contacts', 
//					'fn':'M.startApp(\'ciniki.exhibitions.contacts\', null, \'M.ciniki_businesses_main.showMenu();\');',
//					};
				join++;
			} else {
				this.menu.sections[c++] = {'label':'', 'list':{
					'_':{'label':'Exhibitions', 
					'fn':'M.startApp(\'ciniki.exhibitions.main\', null, \'M.ciniki_businesses_main.showMenu();\');',}}
					};
//				this.menu.sections[c++] = {'label':'', 'list':{
//					'_':{'label':'Contacts', 
//					'fn':'M.startApp(\'ciniki.exhibitions.contacts\', null, \'M.ciniki_businesses_main.showMenu();\');',}}
//					};
			}
		}

		// Check if the remaining sections should be joined together as one section
		// to balance the menu
		if( c > 4 && join < 0 ) {
			join = 0;
			this.menu.sections[c] = {'label':' &nbsp; ', 'list':{}};
		}

		// ATDO - Messages/Notes/FAQ
		if( M.curBusiness.modules['ciniki.atdo'] != null 
			&& (perms.owners != null || perms.employees != null || (M.userPerms&0x01) == 1) ) {
			if( join > -1 ) {
				if( (M.curBusiness.modules['ciniki.atdo'].flags&0x20) > 0 ) {
					this.menu.sections[c].list.messages = {
						'label':'Messages',
						'fn':'M.startApp(\'ciniki.atdo.main\', null, \'M.ciniki_businesses_main.showMenu();\',\'mc\',{\'messages\':\'yes\'});',
						'count':M.curBusiness.modules['ciniki.atdo'].message_count,
						};
					join++;
				}
				if( (M.curBusiness.modules['ciniki.atdo'].flags&0x10) > 0 ) {
					this.menu.sections[c].list.notes = {
						'label':'Notes',
							'fn':'M.startApp(\'ciniki.atdo.main\', null, \'M.ciniki_businesses_main.showMenu();\',\'mc\',{\'notes\':\'yes\'});',
							'count':M.curBusiness.modules['ciniki.atdo'].notes_count,
						};
					join++;
				}
				if( (M.curBusiness.modules['ciniki.atdo'].flags&0x08) > 0 ) {
					this.menu.sections[c].list.faq = {
						'label':'FAQ', 
						'fn':'M.startApp(\'ciniki.atdo.main\', null, \'M.ciniki_businesses_main.showMenu();\',\'mc\',{\'faq\':\'yes\'});',
						};
					join++;
				}
			} else {
				if( (M.curBusiness.modules['ciniki.atdo'].flags&0x20) > 0 ) {
					this.menu.sections[c++] = {'label':'', 'list':{
						'_':{'label':'Messages',
							'fn':'M.startApp(\'ciniki.atdo.main\', null, \'M.ciniki_businesses_main.showMenu();\',\'mc\',{\'messages\':\'yes\'});',
							'count':M.curBusiness.modules['ciniki.atdo'].message_count,
							}}
						};
				}
				if( (M.curBusiness.modules['ciniki.atdo'].flags&0x10) > 0 ) {
					this.menu.sections[c++] = {'label':'', 'list':{
						'_':{'label':'Notes',
							'fn':'M.startApp(\'ciniki.atdo.main\', null, \'M.ciniki_businesses_main.showMenu();\',\'mc\' ,{\'notes\':\'yes\'});',
							'count':M.curBusiness.modules['ciniki.atdo'].notes_count,
							}}
						};
				}
				if( (M.curBusiness.modules['ciniki.atdo'].flags&0x08) > 0 ) {
					this.menu.sections[c++] = {'label':'', 'list':{
						'_':{'label':'FAQ', 'fn':'M.startApp(\'ciniki.atdo.main\', null, \'M.ciniki_businesses_main.showMenu();\',\'mc\',{\'faq\':\'yes\'});'}}};
				}
			}
		}

		// Check if the remaining sections should be joined together as one section
		// to balance the menu
		if( c > 4 && join < 0 ) {
			join = 0;
			this.menu.sections[c] = {'label':' &nbsp; ', 'list':{}};
		}

		// Subscriptions module, all owners and employees and Subscriptions group
		if( M.curBusiness.modules['ciniki.subscriptions'] != null 
			&& (perms.owners != null || perms.employees != null || (M.userPerms&0x01) == 1) ) {
			if( join > -1 ) {
				this.menu.sections[c].list.subscriptions = {
					'label':'Subscriptions', 'fn':'M.startApp(\'ciniki.subscriptions.main\', null, \'M.ciniki_businesses_main.showMenu();\');'};
				join++;
			} else {
				this.menu.sections[c++] = {'label':'', 'list':{
					'_':{'label':'Subscriptions', 'fn':'M.startApp(\'ciniki.subscriptions.main\', null, \'M.ciniki_businesses_main.showMenu();\');'}}};
			}
		}

		if( M.curBusiness.modules['ciniki.media'] != null 
			&& (perms.owners != null || perms.employees != null || (M.userPerms&0x01) == 1) ) { 
			if( join > -1 ) {
				this.menu.sections[c].list.media = {'label':'Media', 'fn':'M.startApp(\'ciniki.media.main\', null, \'M.ciniki_businesses_main.showMenu();\');'};
				join++;
			} else {
				this.menu.sections[c++] = {'label':'', 'list':{
					'_':{'label':'Media', 'fn':'M.startApp(\'ciniki.media.main\', null, \'M.ciniki_businesses_main.showMenu();\');'}}};
			}
		}
		// Check if the remaining sections should be joined together as one section
		// to balance the menu
		if( c > 4 && join < 0 ) {
			join = 0;
			this.menu.sections[c] = {'label':' &nbsp; ', 'list':{}};
		}

		if( M.curBusiness.modules['ciniki.events'] != null 
			&& (perms.owners != null || perms.employees != null || (M.userPerms&0x01) == 1) ) { 
			if( join > -1 ) {
				this.menu.sections[c].list.events = {'label':'Events', 'fn':'M.startApp(\'ciniki.events.main\', null, \'M.ciniki_businesses_main.showMenu();\');'};
				join++;
			} else {
				this.menu.sections[c++] = {'label':'', 'list':{
					'_':{'label':'Events', 'fn':'M.startApp(\'ciniki.events.main\', null, \'M.ciniki_businesses_main.showMenu();\');'}}};
			}
		}
		if( M.curBusiness.modules['ciniki.workshops'] != null 
			&& (perms.owners != null || perms.employees != null || (M.userPerms&0x01) == 1) ) { 
			if( join > -1 ) {
				this.menu.sections[c].list.workshops = {'label':'Workshops', 'fn':'M.startApp(\'ciniki.workshops.main\', null, \'M.ciniki_businesses_main.showMenu();\');'};
				join++;
			} else {
				this.menu.sections[c++] = {'label':'', 'list':{
					'_':{'label':'Workshops', 'fn':'M.startApp(\'ciniki.workshops.main\', null, \'M.ciniki_businesses_main.showMenu();\');'}}};
			}
		}
		// Check if the remaining sections should be joined together as one section
		// to balance the menu
		if( c > 4 && join < 0 ) {
			join = 0;
			this.menu.sections[c] = {'label':' &nbsp; ', 'list':{}};
		}

		if( M.curBusiness.modules['ciniki.marketplaces'] != null 
			&& (perms.owners != null || perms.employees != null || (M.userPerms&0x01) == 1) ) { 
			if( join > -1 ) {
				this.menu.sections[c].list.marketplaces = {'label':'Market Places', 'fn':'M.startApp(\'ciniki.marketplaces.main\', null, \'M.ciniki_businesses_main.showMenu();\');'};
				join++;
			} else {
				this.menu.sections[c++] = {'label':'', 'list':{
					'_':{'label':'Market Places', 'fn':'M.startApp(\'ciniki.marketplaces.main\', null, \'M.ciniki_businesses_main.showMenu();\');'}}};
			}
		}
		// Check if the remaining sections should be joined together as one section
		// to balance the menu
		if( c > 4 && join < 0 ) {
			join = 0;
			this.menu.sections[c] = {'label':' &nbsp; ', 'list':{}};
		}

		if( M.curBusiness.modules['ciniki.gallery'] != null 
			&& (perms.owners != null || perms.employees != null || (M.userPerms&0x01) == 1) ) { 
			if( join > -1 ) {
				this.menu.sections[c].list.gallery = {'label':'Gallery', 'fn':'M.startApp(\'ciniki.gallery.main\', null, \'M.ciniki_businesses_main.showMenu();\');'};
				join++;
			} else {
				this.menu.sections[c++] = {'label':'', 'list':{
					'_':{'label':'Gallery', 'fn':'M.startApp(\'ciniki.gallery.main\', null, \'M.ciniki_businesses_main.showMenu();\');'}}};
			}
		}
		if( M.curBusiness.modules['ciniki.recipes'] != null 
			&& (perms.owners != null || perms.employees != null || (M.userPerms&0x01) == 1) ) { 
			if( join > -1 ) {
				this.menu.sections[c].list.recipes = {
					'label':'Recipes', 'fn':'M.startApp(\'ciniki.recipes.main\', null, \'M.ciniki_businesses_main.showMenu();\');'};
				join++;
			} else {
				this.menu.sections[c++] = {'label':'', 'list':{
					'_':{'label':'Recipes', 'fn':'M.startApp(\'ciniki.recipes.main\', null, \'M.ciniki_businesses_main.showMenu();\');'}}};
			}
		}
		if( M.curBusiness.modules['ciniki.library'] != null 
			&& (perms.owners != null || perms.employees != null || (M.userPerms&0x01) == 1) ) { 
			if( join > -1 ) {
				this.menu.sections[c].list.library = {
					'label':'Library', 'fn':'M.startApp(\'ciniki.library.main\', null, \'M.ciniki_businesses_main.showMenu();\');'};
				join++;
			} else {
				this.menu.sections[c++] = {'label':'', 'list':{
					'_':{'label':'Library', 'fn':'M.startApp(\'ciniki.library.main\', null, \'M.ciniki_businesses_main.showMenu();\');'}}};
			}
		}
		if( M.curBusiness.modules['ciniki.toolbox'] != null 
			&& (perms.owners != null || perms.employees != null || (M.userPerms&0x01) == 1) ) { 
			if( join > -1 ) {
				this.menu.sections[c].list.toolbox = {
					'label':'Toolbox', 'fn':'M.startApp(\'ciniki.toolbox.excel\', null, \'M.ciniki_businesses_main.showMenu();\');'};
				join++;
			} else {
				this.menu.sections[c++] = {'label':'', 'list':{
					'_':{'label':'Toolbox', 'fn':'M.startApp(\'ciniki.toolbox.excel\', null, \'M.ciniki_businesses_main.showMenu();\');'}}};
			}
		}
		if( M.curBusiness.modules['ciniki.filedepot'] != null 
			&& (perms.owners != null || perms.employees != null || (M.userPerms&0x01) == 1) ) { 
			if( join > -1 ) {
				this.menu.sections[c].list.filedepot = {
					'label':'File Depot', 'fn':'M.startApp(\'ciniki.filedepot.main\', null, \'M.ciniki_businesses_main.showMenu();\');'
					};
				join++;
			} else {
				this.menu.sections[c++] = {'label':'', 'list':{
					'_':{'label':'File Depot', 'fn':'M.startApp(\'ciniki.filedepot.main\', null, \'M.ciniki_businesses_main.showMenu();\');'}}};
			}
		}

		if( M.curBusiness.modules['ciniki.systemdocs'] != null 
			&& (perms.owners != null || perms.employees != null || (M.userPerms&0x01) == 1) ) { 
			if( join > -1 ) {
				this.menu.sections[c].list.systemdocs = {'label':'System Documentation', 'fn':'M.startApp(\'ciniki.systemdocs.main\', null, \'M.ciniki_businesses_main.showMenu();\');'};
				join++;
			} else {
				this.menu.sections[c++] = {'label':'', 'list':{
					'_':{'label':'System Documentation', 'fn':'M.startApp(\'ciniki.systemdocs.main\', null, \'M.ciniki_businesses_main.showMenu();\');'}}};
				}
		}
		if( M.curBusiness.modules['ciniki.newsletters'] != null 
			&& (perms.owners != null || perms.employees != null || (M.userPerms&0x01) == 1) ) { 
			if( join > -1 ) {
				this.menu.sections[c].list.newsletters = {'label':'Newsletters', 'fn':'M.startApp(\'ciniki.newsletters.main\', null, \'M.ciniki_businesses_main.showMenu();\');'};
				join++;
			} else {
				this.menu.sections[c++] = {'label':'', 'list':{
					'_':{'label':'Newsletters', 'fn':'M.startApp(\'ciniki.newsletters.main\', null, \'M.ciniki_businesses_main.showMenu();\');'}}};
			}
		}
		if( (M.userPerms&0x01) == 0x01 &&  M.curBusiness.modules['ciniki.artclub'] != null 
			&& (perms.owners != null || perms.employees != null || (M.userPerms&0x01) == 1) ) { 
			if( join > -1 ) {
				this.menu.sections[c].list.artclub = {'label':'Club Info', 'fn':'M.startApp(\'ciniki.artclub.info\', null, \'M.ciniki_businesses_main.showMenu();\');'};
				join++;
			} else {
				this.menu.sections[c++] = {'label':'', 'list':{
					'_':{'label':'Club Info', 'fn':'M.startApp(\'ciniki.artclub.info\', null, \'M.ciniki_businesses_main.showMenu();\');'}}};
			}
		}
//		if( M.curBusiness.modules['ciniki.artgallery'] != null ) { 
//			if( join > -1 ) {
//				this.menu.sections[c].list.artgallery = {'label':'Gallery Info', 'fn':'M.startApp(\'ciniki.artgallery.info\', null, \'M.ciniki_businesses_main.showMenu();\');'};
//				join++;
//			} else {
//				this.menu.sections[c++] = {'label':'', 'list':{
//					'_':{'label':'Gallery Info', 'fn':'M.startApp(\'ciniki.artgallery.info\', null, \'M.ciniki_businesses_main.showMenu();\');'}}};
//			}
//		}

		if( M.curBusiness.modules['ciniki.mail'] != null && (M.curBusiness.modules['ciniki.mail'].flags&0x01) == 0x01 
			&& (perms.owners != null || perms.employees != null || (M.userPerms&0x01) == 1) ) { 
			if( join > -1 ) {
				this.menu.sections[c].list.mailings = {'label':'Mailings', 'fn':'M.startApp(\'ciniki.mail.mailings\', null, \'M.ciniki_businesses_main.showMenu();\');'};
				join++;
			} else {
				this.menu.sections[c++] = {'label':'', 'list':{
					'_':{'label':'Mailings', 'fn':'M.startApp(\'ciniki.mail.mailings\', null, \'M.ciniki_businesses_main.showMenu();\');'}}};
			}
		}
		if( M.curBusiness.modules['ciniki.donations'] != null ) {
			if( join > -1 ) {
				this.menu.sections[c].list.donations = {'label':'Donations', 'fn':'M.startApp(\'ciniki.donations.main\', null, \'M.ciniki_businesses_main.showMenu();\');'};
				join++;
			} else {
				this.menu.sections[c++] = {'label':'', 'list':{
					'_':{'label':'Donations', 'fn':'M.startApp(\'ciniki.donations.main\', null, \'M.ciniki_businesses_main.showMenu();\');'}}};
			}
		}
		if( M.curBusiness.modules['ciniki.surveys'] != null 
			&& (perms.owners != null || perms.employees != null || (M.userPerms&0x01) == 1) ) { 
			if( join > -1 ) {
				this.menu.sections[c].list.surveys = {'label':'Surveys', 'fn':'M.startApp(\'ciniki.surveys.main\', null, \'M.ciniki_businesses_main.showMenu();\');'};
				join++;
			} else {
				this.menu.sections[c++] = {'label':'', 'list':{
					'_':{'label':'Surveys', 'fn':'M.startApp(\'ciniki.surveys.main\', null, \'M.ciniki_businesses_main.showMenu();\');'}}};
			}
		}
		if( M.curBusiness.modules['ciniki.sponsors'] != null 
			&& (perms.owners != null || perms.employees != null || (M.userPerms&0x01) == 1) ) { 
			if( join > -1 ) {
				this.menu.sections[c].list.sponsors = {'label':'Sponsors', 'fn':'M.startApp(\'ciniki.sponsors.main\', null, \'M.ciniki_businesses_main.showMenu();\');'};
				join++;
			} else {
				this.menu.sections[c++] = {'label':'', 'list':{
					'_':{'label':'Sponsors', 'fn':'M.startApp(\'ciniki.sponsors.main\', null, \'M.ciniki_businesses_main.showMenu();\');'}}};
			}
		}
		if( M.curBusiness.modules['ciniki.directory'] != null 
			&& (perms.owners != null || perms.employees != null || (M.userPerms&0x01) == 1) ) { 
			if( join > -1 ) {
				this.menu.sections[c].list.directory = {'label':'Directory', 'fn':'M.startApp(\'ciniki.directory.main\', null, \'M.ciniki_businesses_main.showMenu();\');'};
				join++;
			} else {
				this.menu.sections[c++] = {'label':'', 'list':{
					'_':{'label':'Directory', 'fn':'M.startApp(\'ciniki.directory.main\', null, \'M.ciniki_businesses_main.showMenu();\');'}}};
			}
		}
		if( M.curBusiness.modules['ciniki.links'] != null 
			&& (perms.owners != null || perms.employees != null || (M.userPerms&0x01) == 1) ) { 
			if( join > -1 ) {
				this.menu.sections[c].list.links = {'label':'Links', 'fn':'M.startApp(\'ciniki.links.main\', null, \'M.ciniki_businesses_main.showMenu();\');'};
				join++;
			} else {
				this.menu.sections[c++] = {'label':'', 'list':{
					'_':{'label':'Links', 'fn':'M.startApp(\'ciniki.links.main\', null, \'M.ciniki_businesses_main.showMenu();\');'}}};
			}
		}
		if( M.curBusiness.modules['ciniki.info'] != null 
			&& (perms.owners != null || perms.employees != null || (M.userPerms&0x01) == 1) ) { 
			if( join > -1 ) {
				this.menu.sections[c].list.info = {'label':'Information', 'fn':'M.startApp(\'ciniki.info.main\', null, \'M.ciniki_businesses_main.showMenu();\');'};
				join++;
			} else {
				this.menu.sections[c++] = {'label':'', 'list':{
					'_':{'label':'Information', 'fn':'M.startApp(\'ciniki.info.main\', null, \'M.ciniki_businesses_main.showMenu();\');'}}};
			}
		}
		if( M.curBusiness.modules['ciniki.marketing'] != null 
			&& (perms.owners != null || perms.employees != null || (M.userPerms&0x01) == 1) ) { 
			if( join > -1 ) {
				this.menu.sections[c].list.marketing = {'label':'Marketing', 'fn':'M.startApp(\'ciniki.marketing.main\', null, \'M.ciniki_businesses_main.showMenu();\');'};
				join++;
			} else {
				this.menu.sections[c++] = {'label':'', 'list':{
					'_':{'label':'Marketing', 'fn':'M.startApp(\'ciniki.marketing.main\', null, \'M.ciniki_businesses_main.showMenu();\');'}}};
			}
		}
		if( M.curBusiness.modules['ciniki.web'] != null 
			&& (perms.owners != null || perms.employees != null || (M.userPerms&0x01) == 1) ) { 
			if( join > -1 ) {
				this.menu.sections[c].list.website = {'label':'Website', 'fn':'M.startApp(\'ciniki.web.main\', null, \'M.ciniki_businesses_main.showMenu();\');'};
				join++;
			} else {
				this.menu.sections[c++] = {'label':'', 'list':{
					'_':{'label':'Website', 'fn':'M.startApp(\'ciniki.web.main\', null, \'M.ciniki_businesses_main.showMenu();\');'}}};
			}
		}
		if( M.curBusiness.modules['ciniki.newsaggregator'] != null
			&& (perms.owners != null || perms.employees != null || (M.userPerms&0x01) == 1) ) { 
			if( join > -1 ) {
				this.menu.sections[c].list.newsaggregator = {'label':'News', 
					'fn':'M.startApp(\'ciniki.newsaggregator.main\', null, \'M.ciniki_businesses_main.showMenu();\');',
					'count':M.curBusiness.modules['ciniki.newsaggregator'].unread_count,
				};
				join++;
			} else {
				this.menu.sections[c++] = {'label':'', 'list':{
					'_':{'label':'News', 'fn':'M.startApp(\'ciniki.newsaggregator.main\', null, \'M.ciniki_businesses_main.showMenu();\');',
					'count':M.curBusiness.modules['ciniki.newsaggregator'].unread_count,
					}}};
			}
		}
		// Allow sysadmin access to click stats
		if( M.curBusiness.modules['ciniki.clicktracker'] != null && (M.userPerms&0x01 == 0x01)) { 
			if( join > -1 ) {
				this.menu.sections[c].list.clicktracker = {
					'label':'Click Tracker', 'fn':'M.startApp(\'ciniki.clicktracker.main\', null, \'M.ciniki_businesses_main.showMenu();\');'};
				join++;
			} else {
				this.menu.sections[c++] = {'label':'', 'list':{
					'_':{'label':'Click Tracker', 'fn':'M.startApp(\'ciniki.clicktracker.main\', null, \'M.ciniki_businesses_main.showMenu();\');'}}};
			}
		}

		//
		// Setup the auto split if long menu
		//
		if( join > 8 ) {
			this.menu.sections[c].as = 'yes';
		}

		//
		// Check if we should autoopen the submenu when there is only one menu item.
		//
		if( autoopen == 'yes' && c == 1 
			&& this.menu.sections[0].list != null 
			&& this.menu.sections[0].list._ != null 
			&& this.menu.sections[0].list._.fn != null ) {
			this.menu.autoopen = 'skipped';
			eval(this.menu.sections[0].list._.fn);
		} else {
			this.menu.autoopen = 'no';
		}

		//
		// Check if there should be a task list displayed
		//
		if( M.curBusiness.modules['ciniki.atdo'] != null && M.curBusiness.atdo != null
			&& M.curBusiness.atdo.settings['tasks.ui.mainmenu.category.'+M.userID] != null 
			&& M.curBusiness.atdo.settings['tasks.ui.mainmenu.category.'+M.userID] != ''
			&& (perms.owners != null || perms.employees != null || (M.userPerms&0x01) == 1) ) {
			var rsp = M.api.getJSON('ciniki.atdo.tasksList', {'business_id':M.curBusiness.id,
				'category':M.curBusiness.atdo.settings['tasks.ui.mainmenu.category.'+M.userID], 'assigned':'yes'});
			if( rsp.stat != 'ok' ) {
				M.api.err(rsp);
				return false;
			}
			if( rsp.categories[0] != null && rsp.categories[0].category != null ) {
				this.menu.data._tasks = rsp.categories[0].category.tasks;
				this.menu.sections._tasks = {'label':'Tasks', 'type':'simplegrid', 'num_cols':3,
					'headerValues':['', 'Task', 'Due'],
					'cellClasses':['multiline aligncenter', 'multiline', 'multiline'],
					'noData':'No tasks found',
					};
			}
		}

		// Set size of menu based on contents
		if( menu_search == 1 ) {
			this.menu.size = 'medium';
		} else {
			this.menu.size = 'narrow';
		}
		this.menu.refresh();
		this.menu.show();
	}
}
