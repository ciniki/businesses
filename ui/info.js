//
// This class will display the form to allow admins and business owners to 
// change the details of their business
//
function ciniki_businesses_info() {

    //
    // Edit the business information
    //
    this.info = new M.panel('Business Information', 'ciniki_businesses_info', 'info', 'mc', 'medium', 'sectioned', 'ciniki.businesses.info');
    this.info.sections = {
        'general':{'label':'General', 'fields':{
            'business.name':{'label':'Name', 'type':'text'},
            'business.category':{'label':'Category', 'active':function() { return ((M.userPerms&0x01)==1?'yes':'no');}, 'type':'text', 'livesearch':'yes', 'livesearchempty':'yes'},
            'business.sitename':{'label':'Sitename', 'active':function() { return ((M.userPerms&0x01)==1?'yes':'no');}, 'type':'text', 'livesearch':'yes', 'livesearchempty':'yes'},
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
    this.info.liveSearchCb = function(s, i, value) {
        if( i == 'business.category' ) {
            M.api.getJSONBgCb('ciniki.businesses.searchCategory', {'business_id':M.curBusinessID, 'start_needle':value, 'limit':15}, function(rsp) {
                M.ciniki_businesses_info.info.liveSearchShow(s, i, M.gE(M.ciniki_businesses_info.info.panelUID + '_' + i), rsp.results);
            });
        }
    };
    this.info.liveSearchResultValue = function(s, f, i, j, d) {
        if( f == 'business.category' && d.result != null ) { return d.result.name; }
        return '';
    };
    this.info.liveSearchResultRowFn = function(s, f, i, j, d) { 
        if( f == 'business.category' && d.result != null ) {
            return 'M.ciniki_businesses_info.info.updateField(\'' + s + '\',\'' + f + '\',\'' + escape(d.result.name) + '\');';
        }
    };
    this.info.updateField = function(s, fid, result) {
        M.gE(this.panelUID + '_' + fid).value = unescape(result);
        this.removeLiveSearch(s, fid);
    };
    this.info.open = function(cb) {
        M.api.getJSONCb('ciniki.businesses.getDetails', {'business_id':M.curBusinessID, 'keys':'business,contact'}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_businesses_info.info;
            p.data = rsp.details;
            p.show(cb);
        });
    }
    this.info.save = function() {
        // Serialize the form data into a string for posting
        var c = this.serializeForm('no');
        if( c != '' ) {
            M.api.postJSONCb('ciniki.businesses.updateDetails', {'business_id':M.curBusinessID}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_businesses_info.info.close();
            });
        } else {
            this.close();
        }
    }
    this.info.addButton('save', 'Save', 'M.ciniki_businesses_info.info.save();');
    this.info.addClose('Cancel');

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
    
        this.info.open(cb);
    }
}
