<?php
//
// Description
// -----------
// This method will return the ID of the image of the business logo.
//
// Arguments
// ---------
// business_id:         The ID of the business to get the logo for.
//
// Returns
// -------
// <rsp stat="ok" logo_id="32" />
//
function ciniki_businesses_logoGet($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'),     
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];
    
    //
    // Check access 
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'checkAccess');
    $rc = ciniki_businesses_checkAccess($ciniki, $args['business_id'], 'ciniki.businesses.logoGet');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the busines logo_id
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
    $strsql = "SELECT logo_id FROM ciniki_businesses "
        . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.businesses', 'business');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['business']['logo_id']) ) {
        $logo_id = $rc['business']['logo_id'];
    } else {
        $logo_id = 0;
    }

    return array('stat'=>'ok', 'logo_id'=>$logo_id);

//  ciniki_core_loadMethod($ciniki, 'ciniki', 'images', 'private', 'getImage');
//  return ciniki_images_getImage($ciniki, $args['business_id'], $logo_id, $args['version'], $args['maxlength']);
}
?>
