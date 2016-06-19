//
// This file contains the UI to setup the intl settings for the business.
//
function ciniki_businesses_apis() {
    this.init = function() {
        this.main = new M.panel('Dropbox',
            'ciniki_businesses_apis', 'main',
            'mc', 'narrow', 'sectioned', 'ciniki.businesses.apis.main');
        this.main.data = {};
        this.main.sections = {
            'apis':{'label':'Connected Services', 'type':'simplegrid', 'num_cols':2,
                'cellClasses':['','alignright'],
                },
        };
        this.main.fieldHistoryArgs = function(s, i) {
            return {'method':'ciniki.businesses.getDetailHistory', 'args':{'business_id':M.curBusinessID, 'field':i}};
        }
        this.main.sectionData = function(s) { return this.data[s]; }
        this.main.cellValue = function(s, i, j, d) {
            if( j == 0 ) {
                return d.name;
            }
            if( j == 1 ) {
                if( d.setup == 'yes' ) {
                    return '<button onclick="event.stopPropagation(); M.ciniki_businesses_apis.disconnectService(\'' + i + '\');">Disconnect</button>';
                } else {
                    return '<button onclick="event.stopPropagation(); M.ciniki_businesses_apis.connectService(\'' + i + '\');">Connect</button>';
                }
            }
        };
        this.main.addButton('save', 'Save', 'M.ciniki_businesses_apis.saveIntl();');
        this.main.addClose('Back');
    }

    this.start = function(cb, appPrefix) {
        //
        // Create the app container if it doesn't exist, and clear it out
        // if it does exist.
        //
        var appContainer = M.createContainer(appPrefix, 'ciniki_businesses_apis', 'yes');
        if( appContainer == null ) {
            alert('App Error');
            return false;
        } 
        
        this.showMain(cb);
    }

    this.showMain = function(cb) {
        M.api.getJSONCb('ciniki.businesses.settingsAPIsGet', 
            {'business_id':M.curBusinessID}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_businesses_apis.main;
                p.data = rsp;
                p.refresh();
                p.show(cb);
            });
    };

    this.connectService = function(service) {
        if( service == 'dropbox' ) {
            M.cookieSet('api_key',M.api.key,1);
            M.cookieSet('auth_token',M.api.token,1);
            M.cookieSet('business_id',M.curBusinessID,1);
            M.cookieSet('dropbox',this.main.data.apis.dropbox.csrf,1);
            window.open('https://www.dropbox.com/1/oauth2/authorize?client_id=' + this.main.data.apis.dropbox.appkey + '&response_type=code&redirect_uri=' + encodeURIComponent(this.main.data.apis.dropbox.redirect) + '&state=' + this.main.data.apis.dropbox.csrf, '_blank');
            // Close the main panel so the user must open it again to see new status.
            this.main.close();
        } else {
            alert('Unknown service');
        }
    };

    this.disconnectService = function(service) {
        if( service == 'dropbox' ) {
            var c = 'apis-dropbox-access-token=';
            M.api.postJSONCb('ciniki.businesses.settingsAPIsUpdate', 
                {'business_id':M.curBusinessID}, c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    var p = M.ciniki_businesses_apis.main;
                    p.data = rsp;
                    p.refresh();
                    p.show();
                });
        } else {
            alert('Unknown service');
        }
    }
}
