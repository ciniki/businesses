//
// This file contains the UI functions to manage Business Syncronization
//
function ciniki_businesses_sync() {
    this.users = null;
    this.user = null;

    this.init = function() {
        this.syncs = new M.panel('Business Syncronizations',
            'ciniki_businesses_sync', 'syncs',
            'mc', 'medium', 'sectioned', 'ciniki.businesses.sync.syncs');
        this.syncs.sections = {
            'info':{'label':'Local System', 'list':{
                'name':{'label':'System Name', 'value':'unknown'},
                'uuid':{'label':'Business UUID', 'value':'unknown'},
                'local_url':{'label':'System URL', 'value':M.api.url},
                'api_key':{'label':'System Key', 'value':M.api.key},    //FIXME: Should be sync api_key, not manage one.
                }},
            'synclist':{'label':'Syncronizations', 'type':'simplegrid',
                'headerValues':['Remote System', 'Type', 'Incremental', 'Partial', 'Full'], 'num_cols':5, 'sortable':'yes',
                'cellClasses':['multiline', '', 'multiline', 'multiline', 'multiline'],
                'noData':'No current syncronizations',
                'data':null,
                },
            '_add':{'label':'', 'buttons':{
                'add':{'label':'Add Syncronization', 'fn':'M.ciniki_businesses_sync.showAdd();'},
                'numbers':{'label':'Check Table Numbers', 'fn':'M.ciniki_businesses_sync.showRowCounts(\'M.ciniki_businesses_sync.showInfo();\');'},
            }},
        };
        this.syncs.listLabel = function(s, i, d) { return d.label; };
        this.syncs.listValue = function(s, i, d) { return d.value; };
        this.syncs.cellValue = function(s, i, j, d) { 
            switch(j) {
                case 0: return '<span class="multitext">' + d.sync.remote_name + '</span><span class="subtext">' + d.sync.status_text + '</span>';
                case 1: return d.sync.type;
                case 2: return (d.sync.last_sync!='')?'<span class="maintext">'+d.sync.last_sync.replace(/ ([0-9]+:[0-9]+ ..)/,'</span><span class="subtext">$1')+'</span>':'never';
                case 3: return (d.sync.last_partial!='')?'<span class="maintext">'+d.sync.last_partial.replace(/ ([0-9]+:[0-9]+ ..)/,'</span><span class="subtext">$1')+'</span>':'never';
                case 4: return (d.sync.last_full!='')?'<span class="maintext">'+d.sync.last_full.replace(/ ([0-9]+:[0-9]+ ..)/,'</span><span class="subtext">$1')+'</span>':'never';
            }
        };
        this.syncs.rowFn = function(s, i, d) { return 'M.ciniki_businesses_sync.showSync(\'M.ciniki_businesses_sync.showInfo();\',\'' + d.sync.id + '\');'; };
        this.syncs.noData = function() { return 'No syncronizations'; };
        this.syncs.addButton('add', 'Add', 'M.ciniki_businesses_sync.showAdd();');
        this.syncs.addClose('Back');

        //
        // Setup the panel to show the details of an sync
        //
        this.sync = new M.panel('Business Sync',
            'ciniki_businesses_sync', 'sync',
            'mc', 'medium', 'sectioned', 'ciniki.businesses.syncs.sync');
        this.sync.data = null;
        this.sync.sections = {
            'info':{'label':'', 'list':{
                'remote_name':{'label':'Remote', 'value':'unknown'},
                'remote_uuid':{'label':'Business UUID', 'value':'unknown'},
                'remote_url':{'label':'Sync URL', 'value':'unknown'},
                'type':{'label':'Type', 'value':'unknown'},
                'status_text':{'label':'Status', 'value':'unknown'},
                'date_added':{'label':'Added', 'value':'unknown'},
                'last_updated':{'label':'Updated', 'value':'unknown'},
                'last_sync':{'label':'Last Incremental', 'value':'unknown'},
                'last_partial':{'label':'Last Partial', 'value':'unknown'},
                'last_full':{'label':'Last Full', 'value':'unknown'},
                }},
            '_buttons':{'label':'', 'visible':'yes', 'buttons':{
                'ping':{'label':'Ping', 'fn':''},
                'check':{'label':'Check', 'fn':''},
                'sync_incremental':{'label':'Run Incremental', 'visible':'no'},
                'sync_partial':{'label':'Run Partial', 'visible':'no'},
                'sync_full':{'label':'Run Full (slow)', 'visible':'no'},
//              'stopstart':{'label':'Pause', 'visible':'no'},
                }},
// FIXME: Add any errors that have been found
//          'errors':{'label':'Errors', 'list':{
//              }},
        };
        this.sync.listLabel = function(s, i, d) { return d.label; }
        this.sync.listValue = function(s, i, d) { return this.data[i]; }
        this.sync.addButton('edit', 'Edit', 'M.ciniki_businesses_sync.showEdit(\'M.ciniki_businesses_sync.showSync();\',M.ciniki_businesses_sync.sync.sync_id);');
        this.sync.addClose('Back');

        //
        // The panel to display the add form
        //
        this.add = new M.panel('Add Sync',
            'ciniki_businesses_sync', 'add',
            'mc', 'medium', 'sectioned', 'ciniki.businesses.syncs.edit');
        this.add.data = null;
        this.add.sections = {
            '':{'label':'Remote Information', 'fields':{
                'remote_name':{'label':'System Name', 'value':'unknown', 'type':'text'},
                'remote_uuid':{'label':'Business UUID', 'value':'unknown', 'type':'text'},
                'type':{'label':'Type', 'type':'multitoggle', 'toggles':{'push':'Push', 'pull':'Pull', 'bi':'Bi'}},
            }},
            '_auth':{'label':'Remote Account', 'fields':{
                'json_api':{'label':'json API', 'value':'http://instance.ciniki.ca/ciniki-json.php', 'type':'text'},
                'remote_key':{'label':'Remote Key', 'value':'http://instance.ciniki.ca/ciniki-json.php', 'type':'text'},
                'username':{'label':'Username', 'type':'text'},
                'password':{'label':'Password', 'type':'password'},
            }},
            '_go':{'label':'', 'buttons':{
                'setup':{'label':'Initialize Sync', 'fn':'M.ciniki_businesses_sync.setupSync();'},
            }},
        };
        this.add.fieldValue = function(s, i, d) {
            if( i == 'type' ) { return 'push'; }
            return '';
        };
        this.add.addClose('Back');

        //
        // The panel to display the edit form
        //
        this.edit = new M.panel('Edit Sync',
            'ciniki_businesses_sync', 'edit',
            'mc', 'medium', 'sectioned', 'ciniki.businesses.syncs.edit');
        this.edit.data = null;
        this.edit.sections = {
            '':{'label':'Remote Information', 'fields':{
                'remote_name':{'label':'System Name', 'value':'unknown', 'type':'text'},
                'remote_uuid':{'label':'Business UUID', 'value':'unknown', 'type':'text', 'editable':'no'},
                'type':{'label':'Type', 'type':'multitoggle', 'toggles':{'push':'Push', 'pull':'Pull', 'bi':'Bi'}},
                'status':{'label':'Status', 'type':'multitoggle', 'toggles':{'10':'Active', '60':'Suspended'}},
            }},
            '_go':{'label':'', 'buttons':{
                'save':{'label':'Save', 'fn':'M.ciniki_businesses_sync.saveSync();'},
                'delete':{'label':'Delete', 'fn':'M.ciniki_businesses_sync.deleteSync();'},
            }},
        };
        this.edit.fieldValue = function(s, i, d) {
            return this.data[i];
        };
        this.edit.addButton('save', 'Save', 'M.ciniki_businesses_sync.saveSync();');
        this.edit.addClose('Back');

        //
        // The numbers panel shows tables from each sync, and the item counts
        // for the business.  This helps with debugging problems.
        //
        this.rowcounts = new M.panel('Table Numbers',
            'ciniki_businesses_sync', 'rowcounts',
            'mc', 'medium', 'sectioned', 'ciniki.businesses.syncs.rowcounts');
        this.rowcounts.data = null;
        this.rowcounts.sections = {};
        this.rowcounts.cellValue = function(s, i, j, d) {
            switch(j) {
                case 0: return d.table.name;
                case 1: return d.table.rows;
                default: return d.table['sync-' + this.syncs[j-2].sync.id];
            }
        };
        this.rowcounts.rowStyle = function(s, i, d) {
            if( d.table.flagged != null && d.table.flagged == 'yes' ) {
                return 'background: #ffdddd;';
            }
            return '';
        };
        this.rowcounts.sectionData = function(s) { return this.data[s].module.tables; }
        this.rowcounts.addClose('Back');
    }

    this.start = function(cb, appPrefix, aG) {
        args = {};
        if( aG != null ) {
            args = eval(aG);
        }
        //
        // Create the app container if it doesn't exist, and clear it out
        // if it does exist.
        //
        var appContainer = M.createContainer(appPrefix, 'ciniki_businesses_sync', 'yes');
        if( appContainer == null ) {
            alert('App Error');
            return false;
        } 

        if( args != null && args.business != null && args.business != '' ) {
            M.curBusinessID = args.business;
        }
        if( args != null && args.sync != null && args.sync != '' ) {
            this.showSync(cb, args.sync);
        } else {
            this.showInfo(cb);
        }
    }

    this.showInfo = function(cb) {
        this.syncs.sections.synclist.data = null;
        // 
        // Get the sync information for this server and business
        //
        var rsp = M.api.getJSONCb('ciniki.businesses.syncInfo', 
            {'business_id':M.curBusinessID}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_businesses_sync.syncs;
                p.sections.info.list.name.value = rsp.name;
                p.sections.info.list.uuid.value = rsp.uuid;
                p.sections.synclist.data = rsp.syncs;
                p.refresh();
                p.show(cb);
            });
    }

    this.showSync = function(cb, id) {
        if( id != null ) {
            this.sync.sync_id = id;
        }

        // 
        // Get the sync information for this server and business
        //
        var rsp = M.api.getJSONCb('ciniki.businesses.syncDetails', 
            {'business_id':M.curBusinessID, 'sync_id':this.sync.sync_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_businesses_sync.sync;
                p.data = rsp.sync;

                // Show or hide sync button depending on type of sync
                p.sections._buttons.buttons.ping.fn = 'M.ciniki_businesses_sync.syncPing(\'' + p.sync_id + '\');';
                if( rsp.sync.status == 10 ) {
                    p.sections._buttons.buttons.sync_incremental.visible = 'yes';
                    p.sections._buttons.buttons.sync_incremental.fn = 'M.ciniki_businesses_sync.syncNow(\'' + p.sync_id + '\',\'incremental\');';
                    p.sections._buttons.buttons.sync_partial.visible = 'yes';
                    p.sections._buttons.buttons.sync_partial.fn = 'M.ciniki_businesses_sync.syncNow(\'' + p.sync_id + '\',\'partial\');';
                    p.sections._buttons.buttons.sync_full.visible = 'yes';
                    p.sections._buttons.buttons.sync_full.fn = 'M.ciniki_businesses_sync.syncNow(\'' + p.sync_id + '\',\'full\');';
                    p.sections._buttons.buttons.check.visible = 'yes';
                    p.sections._buttons.buttons.check.fn = 'M.ciniki_businesses_sync.syncCheck(\'' + p.sync_id + '\');';
                } else {
                    p.sections._buttons.buttons.sync_incremental.visible = 'no';
                    p.sections._buttons.buttons.sync_partial.visible = 'no';
                    p.sections._buttons.buttons.sync_full.visible = 'no';
                    p.sections._buttons.buttons.check.visible = 'no';
                }

                p.refresh();
                p.show(cb);
            });
    }


    this.showAdd = function() {
        this.add.cb = 'M.ciniki_businesses_sync.showInfo();';
        this.add.show();
    }

    this.setupSync = function() {
        var c = this.add.serializeForm('yes');
        var rsp = M.api.postJSONCb('ciniki.businesses.syncSetupLocal', 
            {'business_id':M.curBusinessID}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_businesses_sync.add.close();
            });
    }

    this.syncPing = function(id) {
        // 
        // Get the sync information for this server and business
        //
        var rsp = M.api.getJSONCb('ciniki.businesses.syncPing', 
            {'business_id':M.curBusinessID, 'sync_id':id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                alert('ok');
            });
    }

    this.syncCheck = function(id) {
        // 
        // Get the sync information for this server and business
        //
        var rsp = M.api.getJSONCb('ciniki.businesses.syncCheck', 
            {'business_id':M.curBusinessID, 'sync_id':id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                alert('Systems are ok to sync');
            });
    }

    this.syncNow = function(id, type) {
        // 
        // Get the sync information for this server and business
        //
        var rsp = M.api.getJSONCb('ciniki.businesses.syncNow', 
            {'business_id':M.curBusinessID, 'sync_id':id, 'type':type}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_businesses_sync.showSync(null, id);
            });
    };

    this.showEdit = function(cb, id) {
        this.edit.reset();
        if( id != null ) {
            this.edit.sync_id = id;
        }

        var rsp = M.api.getJSONCb('ciniki.businesses.syncDetails', 
            {'business_id':M.curBusinessID, 'sync_id':this.edit.sync_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_businesses_sync.edit;
                p.data = rsp.sync;
                p.refresh();
                p.show(cb);
            });
    };

    this.saveSync = function() {
        var c = this.edit.serializeForm('no');

        if( c != '' ) {
            var rsp = M.api.postJSONCb('ciniki.businesses.syncUpdate', 
                {'business_id':M.curBusinessID, 'sync_id':M.ciniki_businesses_sync.sync.sync_id}, c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    } 
                    M.ciniki_businesses_sync.edit.close();
                });
        } else {
            this.edit.close();
        }
    };

    this.deleteSync = function() {
        if( confirm("Are you sure you want to remove syncronization?  It will be automatically removed from the remote server.") ) {
            var rsp = M.api.getJSONCb('ciniki.businesses.syncDelete', 
                {'business_id':M.curBusinessID, 'sync_id':M.ciniki_businesses_sync.edit.sync_id}, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    M.ciniki_businesses_sync.sync.close();
                });
        }
    };

    this.showRowCounts = function(cb) {
        var rsp = M.api.getJSONCb('ciniki.businesses.syncCheckRowCounts', 
            {'business_id':M.curBusinessID}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                } 
                var p = M.ciniki_businesses_sync.rowcounts;
                var nc = 2 + rsp.syncs.length;
                p.sections = {};
                for(i in rsp.modules) {
                    p.sections[i] = {'label':rsp.modules[i].module.name, 
                        'type':'simplegrid', 'num_cols':nc,
                        'headerValues':['Table','Local'],
                        'cellClasses':['',''],
                        };
                    for(j in rsp.syncs) {
                        p.sections[i].headerValues[Number(j)+2] = rsp.syncs[j].sync.name;
                    }
                }
                p.syncs = rsp.syncs;
                p.data = rsp.modules;
                p.show(cb);
            });
    };
}
