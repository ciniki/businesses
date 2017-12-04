//
// This class will display the form to allow admins and tenant owners to 
// change the details of their tenant
//
function ciniki_tenants_social() {
    this.init = function() {
        this.main = new M.panel('Social Media',
            'ciniki_tenants_social', 'main',
            'mc', 'medium', 'sectioned', 'ciniki.tenants.social.main');
        this.main.data = {};
        this.main.sections = {
            'facebook':{'label':'Facebook', 'fields':{
                'social-facebook-url':{'label':'Page URL', 'type':'text'},
                }},
            'twitter':{'label':'Twitter', 'fields':{
                'social-twitter-tenant-name':{'label':'Tenant Name', 'type':'text'},
                'social-twitter-username':{'label':'Username', 'type':'text'},
                }},
            'flickr':{'label':'Flickr', 'fields':{
                'social-flickr-url':{'label':'Photostream URL', 'type':'text'},
                }},
            'linkedin':{'label':'Linked In', 'fields':{
                'social-linkedin-url':{'label':'URL', 'type':'text'},
                }},
            'etsy':{'label':'Etsy', 'fields':{
                'social-etsy-url':{'label':'Shop URL', 'type':'text'},
                }},
            'pinterest':{'label':'Pinterest', 'fields':{
                'social-pinterest-username':{'label':'Username', 'type':'text'},
                }},
            'tumblr':{'label':'Tumblr', 'fields':{
                'social-tumblr-username':{'label':'Username', 'type':'text'},
                }},
            'instagram':{'label':'Instagram', 'fields':{
                'social-instagram-username':{'label':'Username', 'type':'text'},
                }},
            'youtube':{'label':'Youtube', 'fields':{
                'social-youtube-url':{'label':'URL', 'type':'text'},
                }},
            'vimeo':{'label':'Vimeo', 'fields':{
                'social-vimeo-url':{'label':'URL', 'type':'text'},
                }},
            '_save':{'label':'', 'buttons':{
                'save':{'label':'Save', 'fn':'M.ciniki_tenants_social.saveSocial();'},
                }},
        };
        //
        // Callback for the field history
        //
        this.main.fieldHistoryArgs = function(s, i) {
            return {'method':'ciniki.tenants.getDetailHistory', 'args':{'tnid':M.curTenantID, 'field':i}};
        }
        this.main.fieldValue = function(s, i, d) { return this.data[i]; }
        this.main.addButton('save', 'Save', 'M.ciniki_tenants_social.saveSocial();');
        this.main.addClose('Cancel');
    }

    this.start = function(cb, appPrefix) {
        //
        // Create the app container if it doesn't exist, and clear it out
        // if it does exist.
        //
        var appContainer = M.createContainer(appPrefix, 'ciniki_tenants_social', 'yes');
        if( appContainer == null ) {
            alert('App Error');
            return false;
        } 

        //
        // Setup hint for twitter tenant name
        //
        M.ciniki_tenants_social.main.sections.twitter.fields['social-twitter-tenant-name'].hint = M.curTenant.name;
        
        //
        // Load details
        //
        this.main.cb = cb;
        var rsp = M.api.getJSONCb('ciniki.tenants.getDetails', {'tnid':M.curTenantID, 'keys':'social'}, 
            function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                } 
                M.ciniki_tenants_social.main.data = rsp.details;
                M.ciniki_tenants_social.main.show();
        });
        
    }

    // 
    // Submit the form
    //
    this.saveSocial = function() {
        // Serialize the form data into a string for posting
        var c = this.main.serializeForm('no');
        if( c != '' ) {
            var rsp = M.api.postJSONCb('ciniki.tenants.updateDetails', 
                {'tnid':M.curTenantID}, c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    M.ciniki_tenants_social.main.close();
                });
        } else {
            this.main.close();
        }
    }
}
