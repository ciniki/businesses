//
// This is the main app for the tenants module
//
function ciniki_tenants_reports() {
    //
    // The panel to list the report
    //
    this.menu = new M.panel('report', 'ciniki_tenants_reports', 'menu', 'mc', 'medium', 'sectioned', 'ciniki.tenants.reports.menu');
    this.menu.data = {};
    this.menu.nplist = [];
    this.menu.sections = {
//        'search':{'label':'', 'type':'livesearchgrid', 'livesearchcols':1,
//            'cellClasses':[''],
//            'hint':'Search report',
//            'noData':'No report found',
//            },
        'reports':{'label':'Reports', 'type':'simplegrid', 'num_cols':4,
            'headerValues':['Title', 'Frequency', 'Next Date'],
            'noData':'No report',
            'addTxt':'Add Reports',
            'addFn':'M.ciniki_tenants_reports.report.open(\'M.ciniki_tenants_reports.menu.open();\',0,null);'
            },
    }
/*    this.menu.liveSearchCb = function(s, i, v) {
        if( s == 'search' && v != '' ) {
            M.api.getJSONBgCb('ciniki.tenants.reportSearch', {'tnid':M.curTenantID, 'start_needle':v, 'limit':'25'}, function(rsp) {
                M.ciniki_tenants_reports.menu.liveSearchShow('search',null,M.gE(M.ciniki_tenants_reports.menu.panelUID + '_' + s), rsp.reports);
                });
        }
    }
    this.menu.liveSearchResultValue = function(s, f, i, j, d) {
        return d.name;
    }
    this.menu.liveSearchResultRowFn = function(s, f, i, j, d) {
        return 'M.ciniki_tenants_reports.report.open(\'M.ciniki_tenants_reports.menu.open();\',\'' + d.id + '\');';
    } */
    this.menu.cellValue = function(s, i, j, d) {
        if( s == 'reports' ) {
            switch(j) {
                case 0: return d.title;
                case 1: return d.frequency_text;
                case 2: return d.next_date;
            }
        }
    }
    this.menu.rowFn = function(s, i, d) {
        if( s == 'reports' ) {
            return 'M.ciniki_tenants_reports.report.open(\'M.ciniki_tenants_reports.menu.open();\',\'' + d.id + '\',M.ciniki_tenants_reports.report.nplist);';
        }
    }
    this.menu.open = function(cb) {
        M.api.getJSONCb('ciniki.tenants.reportList', {'tnid':M.curTenantID}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_tenants_reports.menu;
            p.data = rsp;
            p.nplist = (rsp.nplist != null ? rsp.nplist : null);
            p.refresh();
            p.show(cb);
        });
    }
    this.menu.addClose('Back');

    //
    // The panel to edit Reports
    //
    this.report = new M.panel('Reports', 'ciniki_tenants_reports', 'report', 'mc', 'medium mediumaside', 'sectioned', 'ciniki.tenants.main.report');
    this.report.data = null;
    this.report.report_id = 0;
    this.report.nplist = [];
    this.report.sections = {};
    this.report.cellValue = function(s, i, j, d) {
        switch(j) {
            case 0: return d.name;
            case 1: return '<button onclick="event.stopPropagation(); M.ciniki_tenants_reports.report.addBlock(\'' + d.ref + '\'); return false;">Add</button>';
        }
    }
    this.report.fieldValue = function(s, i, d) { return this.data[i]; }
    this.report.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.tenants.reportHistory', 'args':{'tnid':M.curTenantID, 'report_id':this.report_id, 'field':i}};
    }
    this.report.addBlock = function(b) {
        this.save('this.open(null,null,null,\'' + b + '\');');
    }
    this.report.open = function(cb, rid, list, block) {
        if( rid != null ) { this.report_id = rid; }
        if( list != null ) { this.nplist = list; }
        var args = {'tnid':M.curTenantID, 'report_id':this.report_id};
        if( block != null ) {
            args['addblock'] = block;
        }
        M.api.getJSONCb('ciniki.tenants.reportGet', args, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_tenants_reports.report;
            p.data = rsp.report;
            p.data.availableblocks = rsp.blocks;
            for(var i in p.data.availableblocks) {
                p.data.availableblocks[i].ref = i;
            }
            // Setup the sections each time this is loaded
            p.sections = {
                'general':{'label':'', 'aside':'yes', 'fields':{
                    'title':{'label':'Title', 'required':'yes', 'type':'text'},
                    'frequency':{'label':'Frequency', 'required':'yes', 'default':'30', 'type':'toggle', 'toggles':{'10':'Daily', '30':'Weekly'}},
                    }},
                '_next':{'label':'Next Run', 'aside':'yes', 'fields':{
                    'next_date':{'label':'Date', 'required':'yes', 'type':'date'},
                    'next_time':{'label':'Time', 'required':'yes', 'type':'text', 'size':'small'},
                    }},
                '_users':{'label':'Users', 'aside':'yes', 'fields':{
                    'user_ids':{'label':'', 'hidelabel':'yes', 'type':'idlist', 'list':rsp.users},
                    }},
                '_buttons':{'label':'', 'aside':'yes', 'buttons':{
                    'save':{'label':'Save', 'fn':'M.ciniki_tenants_reports.report.save();'},
                    'pdf':{'label':'Download PDF', 'fn':'M.ciniki_tenants_reports.report.downloadPDF();'},
                    'testemail':{'label':'Send Test Email', 'fn':'M.ciniki_tenants_reports.report.save("M.ciniki_tenants_reports.report.emailTestPDF();");'},
                    'delete':{'label':'Delete', 'visible':function() {return M.ciniki_tenants_reports.report.report_id>0?'yes':'no';}, 'fn':'M.ciniki_tenants_reports.report.remove();'},
                    }},
                };
            // 
            // Setup the blocks and their options to be edited
            //
            p.num_blocks = 0;
            if( rsp.report.blocks != null ) {
                var c = 0;
                for(var i in rsp.report.blocks) {
                    if( rsp.blocks[rsp.report.blocks[i].block_ref] != null ) {
                        var b = rsp.blocks[rsp.report.blocks[i].block_ref];
                        var bid = 'block_' + rsp.report.blocks[i].id;
                        p.sections[bid] = {'label':'', 'field_id':rsp.report.blocks[i].id, 'fields':{}};
                        //
                        // Add the title and sequence fields
                        //
                        p.sections[bid].fields[bid + '_title'] = {'label':'Title', 'type':'text'};
                        p.data[bid + '_title'] = rsp.report.blocks[i].title;
                        p.sections[bid].fields[bid + '_sequence'] = {'label':'Order', 'type':'text', 'size':'small'};
                        p.data[bid + '_sequence'] = rsp.report.blocks[i].sequence;
                        for(var j in b.options) {
                            //
                            // Add the field to the section
                            //
                            p.sections[bid].fields[bid + '_' + j] = b.options[j];
                            //
                            // Setup the data values for this option
                            //
                            if( rsp.report.blocks[i].options[j] != null ) {
                                p.data[bid + '_' + j] = rsp.report.blocks[i].options[j];
                            } else {
                                p.data[bid + '_' + j] = '';
                            }
                        }
                        c++;
                    }
                }
                p.num_blocks = c;
            }
            p.sections['availableblocks'] = {'label':'Add more sections', 'type':'simplegrid', 'num_cols':2, 
                'cellClasses':['', 'multiline alignright'],
                };

            p.refresh();
            p.show(cb);
        });
    }
    this.report.downloadPDF = function() {
        this.save("M.api.openPDF('ciniki.tenants.reportPDF', {'tnid':" + M.curTenantID + ", 'report_id':" + this.report_id + "});");
    }
    this.report.emailTestPDF = function() {
        M.api.getJSONCb('ciniki.tenants.reportPDF', {'tnid':M.curTenantID, 'report_id':this.report_id, 'email':'test'}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            alert('Email sent');
        });
    }
    this.report.save = function(cb) {
        if( cb == null ) { cb = 'M.ciniki_tenants_reports.report.close();'; }
        if( !this.checkForm() ) { return false; }
        if( this.report_id > 0 ) {
            var c = this.serializeForm('no');
            if( c != '' ) {
                M.api.postJSONCb('ciniki.tenants.reportUpdate', {'tnid':M.curTenantID, 'report_id':this.report_id}, c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    eval(cb);
                });
            } else {
                eval(cb);
            }
        } else {
            var c = this.serializeForm('yes');
            M.api.postJSONCb('ciniki.tenants.reportAdd', {'tnid':M.curTenantID}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_tenants_reports.report.report_id = rsp.id;
                eval(cb);
            });
        }
    }
    this.report.remove = function() {
        if( confirm('Are you sure you want to remove report?') ) {
            M.api.getJSONCb('ciniki.tenants.reportDelete', {'tnid':M.curTenantID, 'report_id':this.report_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_tenants_reports.report.close();
            });
        }
    }
    this.report.nextButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.report_id) < (this.nplist.length - 1) ) {
            return 'M.ciniki_tenants_reports.report.save(\'M.ciniki_tenants_reports.report.open(null,' + this.nplist[this.nplist.indexOf('' + this.report_id) + 1] + ');\');';
        }
        return null;
    }
    this.report.prevButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.report_id) > 0 ) {
            return 'M.ciniki_tenants_reports.report.save(\'M.ciniki_tenants_reports.report_id.open(null,' + this.nplist[this.nplist.indexOf('' + this.report_id) - 1] + ');\');';
        }
        return null;
    }
    this.report.addButton('save', 'Save', 'M.ciniki_tenants_reports.report.save();');
    this.report.addClose('Cancel');
    this.report.addButton('next', 'Next');
    this.report.addLeftButton('prev', 'Prev');

    //
    // Start the app
    // cb - The callback to run when the user leaves the main panel in the app.
    // ap - The application prefix.
    // ag - The app arguments.
    //
    this.start = function(cb, ap, ag) {
        args = {};
        if( ag != null ) {
            args = eval(ag);
        }
        
        //
        // Create the app container
        //
        var ac = M.createContainer(ap, 'ciniki_tenants_reports', 'yes');
        if( ac == null ) {
            alert('App Error');
            return false;
        }
        
        this.menu.open(cb);
    }
}
