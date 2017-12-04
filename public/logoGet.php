<?php
//
// Description
// -----------
// This method will return the ID of the image of the tenant logo.
//
// Arguments
// ---------
// tnid:         The ID of the tenant to get the logo for.
//
// Returns
// -------
// <rsp stat="ok" logo_id="32" />
//
function ciniki_tenants_logoGet($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),     
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];
    
    //
    // Check access 
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'checkAccess');
    $rc = ciniki_tenants_checkAccess($ciniki, $args['tnid'], 'ciniki.tenants.logoGet');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the busines logo_id
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
    $strsql = "SELECT logo_id FROM ciniki_tenants "
        . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.tenants', 'tenant');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['tenant']['logo_id']) ) {
        $logo_id = $rc['tenant']['logo_id'];
    } else {
        $logo_id = 0;
    }

    return array('stat'=>'ok', 'logo_id'=>$logo_id);

//  ciniki_core_loadMethod($ciniki, 'ciniki', 'images', 'private', 'getImage');
//  return ciniki_images_getImage($ciniki, $args['tnid'], $logo_id, $args['version'], $args['maxlength']);
}
?>
