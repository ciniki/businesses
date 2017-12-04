//
// This class will display the form to allow admins and tenant owners to 
// change the details of their tenant
//
function ciniki_tenants_main() {
    this.tenants = null;
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
        // Build the menus for the tenant, based on what they have access to
        //
        this.menu = new M.panel('Tenant Menu', 'ciniki_tenants_main', 'menu', 'mc', 'medium', 'sectioned', 'ciniki.tenants.main.menu');
        this.menu.data = {};
        this.menu.liveSearchCb = function(s, i, value) {
            if( this.sections[s].search != null && value != '' ) {
                var sargs = (this.sections[s].search.args != null ? this.sections[s].search.args : []);
                sargs['tnid'] = M.curTenantID;
                sargs['start_needle'] = encodeURIComponent(value);
                sargs['limit'] = 10;
                var container = this.sections[s].search.container;
                M.api.getJSONBgCb(this.sections[s].search.method, sargs, function(rsp) {
                    M.ciniki_tenants_main.menu.liveSearchShow(s, null, M.gE(M.ciniki_tenants_main.menu.panelUID + '_' + s), rsp[container]);
                });
                return true;
            }
        };
        this.menu.liveSearchResultClass = function(s, f, i, j, d) {
            if( this.sections[s].search != null ) {
                if( this.sections[s].search.cellClasses != null && this.sections[s].search.cellClasses[j] != null ) {
                    return this.sections[s].search.cellClasses[j];
                }
                return '';
            }
            return '';
        };
        this.menu.liveSearchResultValue = function(s, f, i, j, d) {
            if( this.sections[s].search != null && this.sections[s].search.cellValues != null ) {
                return eval(this.sections[s].search.cellValues[j]);
            }
            return '';
        }
        this.menu.liveSearchResultRowFn = function(s, f, i, j, d) { 
            if( this.sections[s].search != null ) {
                if( this.sections[s].search.edit != null ) {
                    var args = '';
                    for(var i in this.sections[s].search.edit.args) {
                        args += (args != '' ? ', ':'') + '\'' + i + '\':' + eval(this.sections[s].search.edit.args[i]);
                    }
                    return 'M.startApp(\'' + this.sections[s].search.edit.method + '\',null,\'M.ciniki_tenants_main.showMenu();\',\'mc\',{' + args + '});';
                } 
                return null;
            }
            return null;
        };
        this.menu.liveSearchResultRowStyle = function(s, f, i, d) {
            if( this.sections[s].search.rowStyle != null ) {
                return eval(this.sections[s].search.rowStyle);
            }
            return '';
        };
        this.menu.liveSearchSubmitFn = function(s, search_str) {
            if( this.sections[s].search != null && this.sections[s].search.submit != null ) {
                var args = {};
                for(var i in this.sections[s].search.submit.args) {
                    args[i] = eval(this.sections[s].search.submit.args[i]);
                }
                M.startApp(this.sections[s].search.submit.method,null,'M.ciniki_tenants_main.showMenu();','mc',args);
            }
        };
        this.menu.liveSearchResultCellFn = function(s, f, i, j, d) {
//            if( this.sections[s].search != null ) {
//                if( this.sections[s].search.cellFns != null && this.sections[s].search.cellFns[j] != null ) {
//                    return eval(this.sections[s].search.cellFns[j]);
//                }
//                return '';
//            }
            // FIXME: This needs to move into hooks/uiSettings
            if( this.sections[s].id == 'calendars' ) {
                if( j == 0 && d.appointment.start_ts > 0 ) {
                    return 'M.startApp(\'ciniki.calendars.main\',null,\'M.ciniki_tenants_main.showMenu();\',\'mc\',{\'date\':\'' + d.appointment.date + '\'});';
                }
                if( d.appointment.module == 'ciniki.wineproduction' ) {
                    return 'M.startApp(\'ciniki.wineproduction.main\',null,\'M.ciniki_tenants_main.showMenu();\',\'mc\',{\'appointment_id\':\'' + d.appointment.id + '\'});';
                }
                if( d.appointment.module == 'ciniki.atdo' ) {
                    return 'M.startApp(\'ciniki.atdo.main\',null,\'M.ciniki_tenants_main.showMenu();\',\'mc\',{\'atdo_id\':\'' + d.appointment.id + '\'});';
                }
            }
            return '';
        };
        this.menu.liveSearchResultCellColour = function(s, f, i, j, d) {
            if( this.sections[s].search != null ) {
                if( this.sections[s].search.cellColours != null && this.sections[s].search.cellColours[j] != null ) {
                    return eval(this.sections[s].search.cellColours[j]);
                }
                return '';
            }
            return '';
        };

        this.menu.cellValue = function(s, i, j, d) {
            if( s == '_tasks' ) {
                switch (j) {
                    case 0: return '<span class="icon">' + M.curTenant.atdo.priorities[d.task.priority] + '</span>';
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
                if( d.task.status != 'closed' ) { return 'background: ' + M.curTenant.atdo.settings['tasks.priority.' + d.task.priority]; }
                else { return 'background: ' + M.curTenant.atdo.settings['tasks.status.60']; }
            }
            if( d != null && d.task != null ) {
                if( d.task.status != 'closed' ) { return 'background: ' + M.curTenant.atdo.settings['tasks.priority.' + d.task.priority]; }
                else { return 'background: ' + M.curTenant.atdo.settings['tasks.status.60']; }
            }
            return '';
        };
        this.menu.rowFn = function(s, i, d) {
            return 'M.startApp(\'ciniki.atdo.main\',null,\'M.ciniki_tenants_main.showMenu();\',\'mc\',{\'atdo_id\':\'' + d.task.id + '\'});';
        };
        this.menu.sectionData = function(s) {
            if( s == '_tasks' ) { return this.data._tasks; }
            return this.sections[s].list;
        }
    }

    this.start = function(cb, ap, aG) {
        args = {};
        if( aG != null ) { args = eval(aG); }

        //
        // Create the app container if it doesn't exist, and clear it out
        // if it does exist.
        //
        var appContainer = M.createContainer('mc', 'ciniki_tenants_main', 'yes');
        if( appContainer == null ) {
            alert('App Error');
            return false;
        } 

        //
        // Get the tnid to be opened
        //
        if( args.id != null && args.id != '' ) {
            this.openTenant(cb, args.id);
        } else {
            alert('Tenant not found');
            return false;
        }
    }

    //
    // Open a tenant for the specified ID
    //
    this.openTenant = function(cb, id) {
        if( id != null ) {
            M.curTenantID = id;
            // (re)set the tenant object
            delete M.curTenant;
            M.curTenant = {'id':id};
        }
        if( M.curTenantID == null ) {
            alert('Invalid tenant');
        }

        //
        // Reset all buttons
        //
        this.menu.leftbuttons = {};
        this.menu.rightbuttons = {};

        //
        // If both callbacks are null, then this is the root of the menu system
        //
        M.menuHome = this.menu;
        if( cb == null ) {
            // 
            // Add the buttons required on home menu
            //
            this.menu.addButton('account', 'Account', 'M.startApp(\'ciniki.users.main\',null,\'M.home();\');');
            this.menu.addLeftButton('logout', 'Logout', 'M.logout();');
            if( M.userID > 0 && (M.userPerms&0x01) == 0x01 ) {
                this.menu.addLeftButton('sysadmin', 'Admin', 'M.startApp(\'ciniki.sysadmin.main\',null,\'M.home();\');');
            }
//          M.menuHome = this.menu;
        } else {
            this.menu.addClose('Back');
            if( typeof(Storage) !== 'undefined' ) {
                localStorage.setItem("lastTenantID", M.curTenantID);
            }
        }
        this.menu.cb = cb;

        this.openTenantSettings();
    }

    this.openTenantSettings = function() {
        // 
        // Get the list of owners and employees for the tenant
        //
        M.api.getJSONCb('ciniki.tenants.getUserSettings', {'tnid':M.curTenantID}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            M.ciniki_tenants_main.openTenantFinish(rsp);
        });
    }

    this.openTenantFinish = function(rsp) {
        // 
        // Setup menu name
        //
        M.curTenant.name = rsp.name;

        //
        // Setup CSS
        //
        if( rsp.css != null && rsp.css != '' ) {
            M.gE('tenant_colours').innerHTML = rsp.css;
        } else {
            M.gE('tenant_colours').innerHTML = M.defaultTenantColours;
        }

        //
        // Setup employees
        //
        M.curTenant.employees = {};
        var ct = 0;
        for(i in rsp.users) {
            M.curTenant.employees[rsp.users[i].user.id] = rsp.users[i].user.display_name;
            ct++;
        }
        M.curTenant.numEmployees = ct;

        // 
        // Setup tenant permissions for the user
        //
        M.curTenant.permissions = {};
        M.curTenant.permissions = rsp.permissions;

        // 
        // Setup the settings for activated modules
        //
        if( rsp.settings != null && rsp.settings['ciniki.bugs'] != null ) {
            M.curTenant.bugs = {};
            M.curTenant.bugs.priorities = {'10':'<span class="icon">Q</span>', '30':'<span class="icon">W</span>', '50':'<span class="icon">E</span>'};
            if( M.size == 'compact' ) {
                M.curTenant.bugs.priorityText = {'10':'<span class="icon">Q</span>', '30':'<span class="icon">W</span>', '50':'<span class="icon">E</span>'};
            } else {
                M.curTenant.bugs.priorityText = {'10':'<span class="icon">Q</span> Low', '30':'<span class="icon">W</span> Medium', '50':'<span class="icon">E</span> High'};
            }
            M.curTenant.bugs.settings = rsp.settings['ciniki.bugs'];
        }
        if( rsp.settings != null && rsp.settings['ciniki.atdo'] != null ) {
            M.curTenant.atdo = {};
            M.curTenant.atdo.priorities = {'10':'<span class="icon">Q</span>', '30':'<span class="icon">W</span>', '50':'<span class="icon">E</span>'};
            if( M.size == 'compact' ) {
                M.curTenant.atdo.priorityText = {'10':'<span class="icon">Q</span>', '30':'<span class="icon">W</span>', '50':'<span class="icon">E</span>'};
            } else {
                M.curTenant.atdo.priorityText = {'10':'<span class="icon">Q</span> Low', '30':'<span class="icon">W</span> Medium', '50':'<span class="icon">E</span> High'};
            }
            M.curTenant.atdo.settings = rsp.settings['ciniki.atdo'];
        }
        if( rsp.settings != null && rsp.settings['ciniki.customers'] != null ) {
            M.curTenant.customers = {'settings':rsp.settings['ciniki.customers']};
        }
        if( rsp.settings != null && rsp.settings['ciniki.taxes'] != null ) {
            M.curTenant.taxes = {'settings':rsp.settings['ciniki.taxes']};
        }
        if( rsp.settings != null && rsp.settings['ciniki.services'] != null ) {
            M.curTenant.services = {'settings':rsp.settings['ciniki.services']};
        }
        if( rsp.settings != null && rsp.settings['ciniki.mail'] != null ) {
            M.curTenant.mail = {'settings':rsp.settings['ciniki.mail']};
        }
        if( rsp.settings != null && rsp.settings['ciniki.artcatalog'] != null ) {
            M.curTenant.artcatalog = {'settings':rsp.settings['ciniki.artcatalog']};
        }
        if( rsp.settings != null && rsp.settings['ciniki.sapos'] != null ) {
            M.curTenant.sapos = {'settings':rsp.settings['ciniki.sapos']};
        }
        if( rsp.settings != null && rsp.settings['ciniki.products'] != null ) {
            M.curTenant.products = {'settings':rsp.settings['ciniki.products']};
        }
        if( rsp.settings != null ) {
            if( M.curTenant.settings == null ) {
                M.curTenant.settings = {};
            }
            if( rsp.settings['googlemapsapikey'] != null && rsp.settings['googlemapsapikey'] != '' ) {
                M.curTenant.settings.googlemapsapikey = rsp.settings['googlemapsapikey'];
            }
            if( rsp.settings['uiAppOverrides'] != null && rsp.settings['uiAppOverrides'] != '' ) {
                M.curTenant.settings.uiAppOverrides = rsp.settings['uiAppOverrides'];
            }
        }
        if( rsp.intl != null ) {
            M.curTenant.intl = rsp.intl;
        }

        var modules = {};
        for(i in rsp.modules) {
            modules[rsp.modules[i].module.name] = rsp.modules[i].module;
            if( rsp.settings != null && rsp.settings[rsp.modules[i].module.name] != null ) {
                modules[rsp.modules[i].module.name].settings = rsp.settings[rsp.modules[i].module.name];
            }
        }
        M.curTenant.modules = modules;

        //
        // FIXME: Check if tenant is suspended status, and display message
        //

        //
        // Show the menu, which loads modules and display up to date message counts, etc.
        //
        this.showMenuFinish(rsp, 'yes');
    };

    //
    // This function is called upon return from opening a main menu item
    //
    this.showMenu = function() {
        //
        // Get the list of modules (along with other information that's not required)
        //
        M.api.getJSONCb('ciniki.tenants.getUserSettings', {'tnid':M.curTenantID}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            M.ciniki_tenants_main.openTenantFinish(rsp, 'no');
        });
    }

    this.showMenuFinish = function(r, autoopen) {
        this.menu.title = M.curTenant.name;
        //
        // If sysadmin, or tenant owner
        //
        if( M.userID > 0 && ( (M.userPerms&0x01) == 0x01 || M.curTenant.permissions.owners != null || M.curTenant.permissions.resellers != null )) {
            this.menu.addButton('settings', 'Settings', 'M.startApp(\'ciniki.tenants.settings\',null,\'M.ciniki_tenants_main.openTenantSettings();\');');
            M.curTenant.settings_menu_items = r.settings_menu_items;
        }

        var c = 0;
        var join = -1;  // keep track of how many are already joined together

        var perms = M.curTenant.permissions;

        //
        // Check that the module is turned on for the tenant, and the user has permissions to the module
        //

        this.menu.sections = {};
        var tenant_possession = 'our';
        var g = 0;
        var menu_search = 0;

        //
        // Build the main menu from the items supplied
        //
        if( r.menu_items != null ) {
            // Get the number of search items
            for(var i in r.menu_items) {
                if( r.menu_items[i].search != null ) {
                    menu_search++
                }
            }
            if( menu_search < 2 ) {
                menu_search = 0;
            }
            for(var i in r.menu_items) {
                var item = {'label':r.menu_items[i].label};
                if( r.menu_items[i].edit != null ) {
                    var args = '';
                    if( r.menu_items[i].edit.args != null ) {
                        for(var j in r.menu_items[i].edit.args) {
                            args += (args != '' ? ', ':'') + '\'' + j + '\':' + eval(r.menu_items[i].edit.args[j]);
                        }
                        item.fn = 'M.startApp(\'' + r.menu_items[i].edit.app + '\',null,\'M.ciniki_tenants_main.showMenu();\',\'mc\',{' + args + '});';
                    } else {
                        item.fn = 'M.startApp(\'' + r.menu_items[i].edit.app + '\',null,\'M.ciniki_tenants_main.showMenu();\');';
                    }
                } else if( r.menu_items[i].fn != null ) {
                    item.fn = r.menu_items[i].fn;
                }
                if( r.menu_items[i].count != null ) {
                    item.count = r.menu_items[i].count;
                }
                if( r.menu_items[i].add != null && menu_search > 0 ) {
                    var args = '';
                    for(var j in r.menu_items[i].add.args) {
                        args += (args != '' ? ', ':'') + '\'' + j + '\':' + eval(r.menu_items[i].add.args[j]);
                    }
                    item.addFn = 'M.startApp(\'' + r.menu_items[i].add.app + '\',null,\'M.ciniki_tenants_main.showMenu();\',\'mc\',{' + args + '});';
                }

                if( r.menu_items[i].search != null && menu_search > 0 ) {
                    item.search = r.menu_items[i].search;
                    if( r.menu_items[i].id != null ) {
                        item.id = r.menu_items[i].id;
                    }
                    item.type = 'livesearchgrid';
                    item.searchlabel = item.label;
                    item.aside = 'yes';
                    item.label = '';
                    item.livesearchcols = item.search.cols;
                    item.noData = item.search.noData;
                    if( item.search.headerValues != null ) {
                        item.headerValues = item.search.headerValues;
                    }
                    if( r.menu_items[i].search.searchtype != null && r.menu_items[i].search.searchtype != '' ) {
                        item.livesearchtype = r.menu_items[i].search.searchtype;
                    }
                    this.menu.sections[c++] = item;
                    menu_search = 1;
                }
                else if( r.menu_items[i].subitems != null ) {
                    item.aside = 'yes';
                    item.list = {};
                    for(var j in r.menu_items[i].subitems) {
                        var args = '';
                        for(var k in r.menu_items[i].subitems[j].edit.args) {
                            args += (args != '' ? ', ':'') + '\'' + k + '\':' + eval(r.menu_items[i].subitems[j].edit.args[k]);
                        }
                        item.list[j] = {'label':r.menu_items[i].subitems[j].label, 'fn':'M.startApp(\'' + r.menu_items[i].subitems[j].edit.app + '\',null,\'M.ciniki_tenants_main.showMenu();\',\'mc\',{' + args + '});'};
                        if( r.menu_items[i].subitems[j].count != null ) {
                            item.list[j].count = r.menu_items[i].subitems[j].count;
                        }
                    }
                    this.menu.sections[c] = item;
                    menu_search = 0;
                    join = 0;
                    c++;
                    this.menu.sections[c] = {'label':'Menu', 'aside':'yes', 'list':{}};
                }
                else if( join > -1 ) {
                    this.menu.sections[c].list['item_' + i] = item;
                    join++;
//                    this.menu.sections[c].list['item_' + i] = {'label':r.menu_items[i].label, 'fn':fn};
                } else {
                    this.menu.sections[c++] = {'label':'', 'aside':'yes', 'list':{'_':item}};
//                    this.menu.sections[c++] = {'label':'', 'list':{'_':{'label':r.menu_items[i].label, 'fn':fn}}};
                }
                if( c > 4 && join < 0 ) {
                    join = 0;
                    this.menu.sections[c] = {'label':' &nbsp; ', 'aside':'yes', 'list':{}};
                }
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

        // Set size of menu based on contents
        if( menu_search == 1 ) {
            this.menu.size = 'medium';
        } else {
            this.menu.size = 'narrow';
        }
        //
        // Check if there should be a task list displayed
        // FIXME: Change to background load
        //
        if( M.curTenant.modules['ciniki.atdo'] != null && M.curTenant.atdo != null
            && M.curTenant.atdo.settings['tasks.ui.mainmenu.category.'+M.userID] != null 
            && M.curTenant.atdo.settings['tasks.ui.mainmenu.category.'+M.userID] != ''
            && (perms.owners != null || perms.employees != null || perms.resellers != null || (M.userPerms&0x01) == 1) 
            ) {
            this.menu.data._tasks = {};
            this.menu.sections._tasks = {'label':'Tasks', 'visible':'hidden', 'type':'simplegrid', 'num_cols':3,
                'headerValues':['', 'Task', 'Due'],
                'cellClasses':['multiline aligncenter', 'multiline', 'multiline'],
                'noData':'No tasks found',
                };
            M.api.getJSONCb('ciniki.atdo.tasksList', {'tnid':M.curTenant.id,
                'category':M.curTenant.atdo.settings['tasks.ui.mainmenu.category.'+M.userID], 'assigned':'yes', 'status':'open'},
                function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    var p = M.ciniki_tenants_main.menu;
                    if( rsp.categories[0] != null && rsp.categories[0].category != null ) {
                        p.data._tasks = rsp.categories[0].category.tasks;
                        p.sections._tasks.visible = 'yes';
                        p.refreshSection('_tasks');
                        p.size = 'medium mediumaside';
                        M.gE(p.panelUID).children[0].className = 'medium mediumaside';
                    }
                });
        }

        //
        // Check if add to home screen should be shown
        //
//      if( M.device == 'ipad' && !window.navigator.standalone ) {
//          this.menu.sections.addtohomescreen = {'label':'', 'list':{
//              'add':{'label':'Download App', 'fn':''},
//              }},
//      }

        this.menu.refresh();
        this.menu.show();
    }
}
