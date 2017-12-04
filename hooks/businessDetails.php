<?php
//
// Description
// -----------
// This function will get detail values for a tenant.
//
// Info
// ----
// Status: beta
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant to get the details for.
// keys:                The comma delimited list of keys to lookup values for.
//
// Returns
// -------
// <details>
//      <tenant name='' tagline='' />
// </details>
//
function ciniki_tenants_hooks_tenantDetails($ciniki, $tnid, $args) {
    $rsp = array('stat'=>'ok', 'details'=>array());

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDetailsQuery');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDetailsQueryDash');

    //
    // Get the tenant name and tagline
    //
    $strsql = "SELECT name, sitename, tagline, logo_id FROM ciniki_tenants "
        . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' ";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.tenants', 'tenant');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['tenant']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.tenants.1', 'msg'=>'Unable to get tenant details'));
    }
    $rsp['details']['name'] = $rc['tenant']['name'];
    $rsp['details']['sitename'] = $rc['tenant']['sitename'];
    $rsp['details']['tagline'] = $rc['tenant']['tagline'];
    $rsp['details']['logo_id'] = $rc['tenant']['logo_id'];

    //
    // Get the social media information for the tenant
    //
    $rc = ciniki_core_dbDetailsQuery($ciniki, 'ciniki_tenant_details', 'tnid', $tnid, 'ciniki.tenants', 'contact', 'contact');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['contact']) ) {
        foreach($rc['contact'] as $contact_key => $contact) {
            $rsp['details'][$contact_key] = $contact;
        }
    }

    //
    // Check if web module is enabled, and determine the web address
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'checkModuleAccess');
    $rc = ciniki_tenants_checkModuleAccess($ciniki, $tnid, 'ciniki', 'web');
    if( $rc['stat'] == 'ok' ) {
        //
        // Lookup the web address
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'lookupTenantURL');
        $rc = ciniki_web_lookupTenantURL($ciniki, $tnid);
        if( $rc['stat'] == 'ok' ) {
            // Remove the http from the url
            $rsp['details']['contact-website-url'] = preg_replace('/http:\/\/www\./', '', $rc['url']);
            $rsp['details']['domain-base-url'] = preg_replace('/http:\/\/www\./', '', $rc['url']);
            $rsp['details']['ssl-domain-base-url'] = preg_replace('/https?\/\/www\./', '', $rc['secure_url']);
        }
    }
    
    return $rsp;
}
?>
