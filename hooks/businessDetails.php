<?php
//
// Description
// -----------
// This function will get detail values for a business.
//
// Info
// ----
// Status: beta
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:         The ID of the business to get the details for.
// keys:                The comma delimited list of keys to lookup values for.
//
// Returns
// -------
// <details>
//      <business name='' tagline='' />
// </details>
//
function ciniki_businesses_hooks_businessDetails($ciniki, $business_id, $args) {
    $rsp = array('stat'=>'ok', 'details'=>array());

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDetailsQuery');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDetailsQueryDash');

    //
    // Get the business name and tagline
    //
    $strsql = "SELECT name, sitename, tagline, logo_id FROM ciniki_businesses "
        . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' ";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.businesses', 'business');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['business']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.businesses.1', 'msg'=>'Unable to get business details'));
    }
    $rsp['details']['name'] = $rc['business']['name'];
    $rsp['details']['sitename'] = $rc['business']['sitename'];
    $rsp['details']['tagline'] = $rc['business']['tagline'];
    $rsp['details']['logo_id'] = $rc['business']['logo_id'];

    //
    // Get the social media information for the business
    //
    $rc = ciniki_core_dbDetailsQuery($ciniki, 'ciniki_business_details', 'business_id', $business_id, 'ciniki.businesses', 'contact', 'contact');
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
    ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'checkModuleAccess');
    $rc = ciniki_businesses_checkModuleAccess($ciniki, $business_id, 'ciniki', 'web');
    if( $rc['stat'] == 'ok' ) {
        //
        // Lookup the web address
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'lookupBusinessURL');
        $rc = ciniki_web_lookupBusinessURL($ciniki, $business_id);
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
