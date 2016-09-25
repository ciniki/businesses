//
// This class will display the form to allow admins and business owners to 
// change the details of their business
//
function ciniki_businesses_settings() {

    this.menu = new M.panel('Business Settings', 'ciniki_businesses_settings', 'menu', 'mc', 'narrow', 'sectioned', 'ciniki.businesses.settings.menu');
    this.menu.addClose('Back');

    this.start = function(cb, ap, aG) {
        args = {};
        if( aG != null ) { args = eval(aG); }

        //
        // Create the app container if it doesn't exist, and clear it out
        // if it does exist.
        //
        var appContainer = M.createContainer('mc', 'ciniki_businesses_settings', 'yes');
        if( appContainer == null ) {
            alert('App Error');
            return false;
        } 
        
        // 
        // Clear old menu
        //
        this.menu.reset();

        //
        // Setup the Business Settings 
        //
        this.menu.sections = {
            '':{'label':'', 'aside':'yes', 'list':{
                'info':{'label':'Business Info', 'fn':'M.startApp(\'ciniki.businesses.info\', null, \'M.ciniki_businesses_settings.menu.show();\');'},
                'users':{'label':'Owners & Employees', 'fn':'M.startApp(\'ciniki.businesses.users\', null, \'M.ciniki_businesses_settings.menu.show();\');'},
                'permissions':{'label':'Permissions', 'visible':'no', 'fn':'M.startApp(\'ciniki.businesses.permissions\', null, \'M.ciniki_businesses_settings.menu.show();\');'},
                'social':{'label':'Social Media', 'fn':'M.startApp(\'ciniki.businesses.social\', null, \'M.ciniki_businesses_settings.menu.show();\');'},
                'intl':{'label':'Localization', 'fn':'M.startApp(\'ciniki.businesses.intl\', null, \'M.ciniki_businesses_settings.menu.show();\');'},
                'billing':{'label':'Billing', 'fn':'M.startApp(\'ciniki.businesses.billing\', null, \'M.ciniki_businesses_settings.menu.show();\');'},
                }}};
        //
        // FIXME: Move into bugs settings
        //
        if( (M.curBusiness.modules['ciniki.bugs'] != null && (M.userPerms&0x01) == 0x01) 
            || M.curBusiness.modules['ciniki.wineproduction'] != null
            ) {
            this.menu.sections[''].list.permissions.visible = 'yes';
        }

        if( M.curBusiness.modules['ciniki.artcatalog'] != null && M.modFlagSet('ciniki.businesses', 0x020000) == 'yes' ) {
            this.menu.sections['']['list']['backups'] = {'label':'Backups', 'fn':'M.startApp(\'ciniki.businesses.backups\', null, \'M.ciniki_businesses_settings.menu.show();\');'};
        }

        //
        // Check for settings_menu_items
        //
        if( M.curBusiness.settings_menu_items != null ) {
            this.menu.sections['modules'] = {'label':'', 'aside':'yes', 'list':{}};
            for(var i in M.curBusiness.settings_menu_items) {
                var item = {'label':M.curBusiness.settings_menu_items[i].label};
                if( M.curBusiness.settings_menu_items[i].edit != null ) {
                    var args = '';
                    if( M.curBusiness.settings_menu_items[i].edit.args != null ) {
                        for(var j in M.curBusiness.settings_menu_items[i].edit.args) {
                            args += (args != '' ? ', ':'') + '\'' + j + '\':' + eval(M.curBusiness.settings_menu_items[i].edit.args[j]);
                        }
                        item.fn = 'M.startApp(\'' + M.curBusiness.settings_menu_items[i].edit.app + '\',null,\'M.ciniki_businesses_settings.menu.show();\',\'mc\',{' + args + '});';
                    } else {
                        item.fn = 'M.startApp(\'' + M.curBusiness.settings_menu_items[i].edit.app + '\',null,\'M.ciniki_businesses_settings.menu.show();\');';
                    }
                }
                this.menu.sections.modules.list[i] = item;
            }
        }
    
        //
        // Advaned options for Sysadmins or resellers
        //
        if( M.userID > 0 && ((M.userPerms&0x01) == 0x01 || M.curBusiness.permissions.resellers != null) ) {
            //
            // Setup the advanced section for resellers and admins
            //
            this.menu.sections['advanced'] = {'label':'Admin', 'list':{
                'modules':{'label':'Modules', 'fn':'M.startApp(\'ciniki.businesses.modules\', null, \'M.ciniki_businesses_settings.menu.show();\');'},
                'moduleflags':{'label':'Module Flags', 'fn':'M.startApp(\'ciniki.businesses.moduleflags\', null, \'M.ciniki_businesses_settings.menu.show();\');'},
            }};
            if( (M.curBusiness.modules['ciniki.directory'] != null && (M.curBusiness.modules['ciniki.directory'].flags&0x01) > 0)
                || (M.curBusiness.modules['ciniki.artistprofiles'] != null && (M.curBusiness.modules['ciniki.artistprofiles'].flags&0x01) > 0)
                ) {
                this.menu.sections['advanced']['list']['apis'] = {'label':'Connected Services', 'fn':'M.startApp(\'ciniki.businesses.apis\',null,\'M.ciniki_businesses_settings.menu.show();\');'};
            }

//            this.menu.sections['advanced'] = {'label':'Advanced', 'list':{
//                    'integrityfix':{'label':'Database Integrity Fix', 'fn':'M.ciniki_businesses_settings.fixallintegrity();'},
//                }};
            if( M.curBusiness.modules['ciniki.products'] != null ) {
                this.menu.sections['advanced']['list']['products'] = {'label':'Products', 'fn':'M.startApp(\'ciniki.products.types\',null,\'M.ciniki_businesses_settings.menu.show();\');'};
            }
            this.menu.size = 'narrow narrowaside';

            //
            // Setup the sysadmin only options
            //
            if( M.userID > 0 && (M.userPerms&0x01) == 0x01 ) {
                this.menu.sections['admin'] = {'label':'SysAdmin', 'list':{
                    'sync':{'label':'Syncronization', 'fn':'M.startApp(\'ciniki.businesses.sync\', null, \'M.ciniki_businesses_settings.menu.show();\');'},
                    'CSS':{'label':'CSS', 'fn':'M.startApp(\'ciniki.businesses.css\', null, \'M.ciniki_businesses_settings.menu.show();\');'},
                    'webdomains':{'label':'Domains', 'fn':'M.startApp(\'ciniki.businesses.domains\', null, \'M.ciniki_businesses_settings.menu.show();\');'},
                    'assets':{'label':'Image Assets', 'fn':'M.startApp(\'ciniki.businesses.assets\', null, \'M.ciniki_businesses_settings.menu.show();\');'},
                    'fixhistory':{'label':'Fix History', 'fn':'M.ciniki_businesses_settings.fixallhistory();'},
                    'checkimages':{'label':'Check Image Storage', 'fn':'M.ciniki_businesses_settings.checkimagestorage("no");'},
                    'checkimagesclean':{'label':'Check Image Storage & Clean DB', 'fn':'M.ciniki_businesses_settings.checkimagestorage("yes");'},
                    'moveproductfiles':{'label':'Check Product Files', 'fn':'M.ciniki_businesses_settings.moveproductstorage("no");'},
                    'moveproductfilesclean':{'label':'Check Product Files & Clean DB', 'fn':'M.ciniki_businesses_settings.moveproductstorage("yes");'},
    //              'fixhistory':{'label':'Fix History', 'fn':'M.startApp(\'ciniki.businesses.fixhistory\', null, \'M.ciniki_businesses_settings.menu.show();\');'},
                    }};
                if( M.curBusiness.modules['ciniki.artclub'] != null ) {
                    this.menu.sections.admin.list['movemembers'] = {'label':'Move Members', 'fn':'M.ciniki_businesses_settings.movemembers();'};
                }
                if( M.curBusinessID == M.masterBusinessID ) {
                    this.menu.sections.admin.list['plans'] = {'label':'Plans', 'fn':'M.startApp(\'ciniki.businesses.plans\', null, \'M.ciniki_businesses_settings.menu.show();\');'};
                }
            }
        }

        //
        // Show the settings menu
        //
        this.menu.show(cb);
    }



    this.fixallintegrity = function() {
        M.startLoad();
        this.dbfixintegrity();
    }

    this.dbfixintegrity = function() {
        if( this.fixintegrity('ciniki.businesses') == false ) {
            M.stopLoad();
            return false;
        }
        if( this.fixintegrity('ciniki.images') == false ) {
            M.stopLoad();
            return false;
        }
        var mods = [
            'ciniki.artcatalog',
            'ciniki.atdo',
            'ciniki.customers',
            'ciniki.events',
            'ciniki.exhibitions',
            'ciniki.gallery',
            'ciniki.images',
            'ciniki.links',
            'ciniki.products',
            'ciniki.projects',
            'ciniki.services',
            'ciniki.web',
            'ciniki.wineproduction',
            ];
        for(i in mods) {
            if( M.curBusiness.modules[mods[i]] != null ) {
                if( this.fixintegrity(mods[i]) == false ) {
                    M.stopLoad();
                    return false;
                }
            }
        }
        M.stopLoad();
        alert('done');
    }

    this.fixintegrity = function(module) {
        var rsp = M.api.getJSON(module + '.dbIntegrityCheck', 
            {'business_id':M.curBusinessID, 'fix':'yes'});
        if( rsp.stat != 'ok' ) {
            M.api.err(rsp);
            return false;
        }
        return true;
    };

    this.fixallhistory = function() {
        if( this.fixhistory('ciniki.users') == false ) {
            return false;
        }
        if( this.fixhistory('ciniki.businesses') == false ) {
            return false;
        }
        if( this.fixhistory('ciniki.images') == false ) {
            return false;
        }
        var mods = [
            'ciniki.artcatalog',
            'ciniki.atdo',
            'ciniki.customers',
            'ciniki.events',
            'ciniki.exhibitions',
            'ciniki.gallery',
            'ciniki.images',
            'ciniki.links',
            'ciniki.products',
            'ciniki.projects',
            'ciniki.services',
            'ciniki.wineproduction',
            ];
        for(i in mods) {
            if( M.curBusiness.modules[mods[i]] != null ) {
                if( this.fixhistory(mods[i]) == false ) {
                    return false;
                }
            }
        }
        alert('done');
    }

    this.fixhistory = function(module) {
        var rsp = M.api.getJSON(module + '.historyFix', {'business_id':M.curBusinessID});
        if( rsp.stat != 'ok' ) {
            M.api.err(rsp);
            return false;
        }
        return true;
    };

    this.movemembers = function(module) {
        var rsp = M.api.getJSON('ciniki.artclub.memberCopyToCustomers', {'business_id':M.curBusinessID});
        if( rsp.stat != 'ok' ) {
            alert('failed');
            M.api.err(rsp);
            return false;
        }
        return true;
    };

    this.checkimagestorage = function(clear) {
        M.api.getJSONCb('ciniki.images.dbCheckImageStorage', {'business_id':M.curBusinessID, 'clear':clear}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            alert('All images in storage');        
        });
    };
    this.moveproductstorage = function(clear) {
        M.api.getJSONCb('ciniki.products.dbMoveFileStorage', {'business_id':M.curBusinessID, 'clear':clear}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            alert('All files in storage');        
        });
    };
}

