<?php
//
// Description
// -----------
// This function will verify the business is active, and the module is active.
//
// Arguments
// ---------
// ciniki:
// business_id:         The business ID to check the session user against.
// method:              The requested method.
//
// Returns
// -------
// <rsp stat='ok' />
//
function ciniki_businesses_hooks_getActiveModules($ciniki, $business_id, $args) {
    //
    // Check if the module is enabled for this business, don't really care about the ruleset
    //
    $strsql = "SELECT ciniki_businesses.status AS business_status, "
        . "ciniki_business_modules.status AS module_status, "
        . "CONCAT_WS('.', ciniki_business_modules.package, ciniki_business_modules.module) AS module_id, "
        . "(flags&0xFFFFFFFF) as flags, (flags&0xFFFFFFFF00000000)>>32 as flags2, ruleset "
        . "FROM ciniki_businesses, ciniki_business_modules "
        . "WHERE ciniki_businesses.id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
        . "AND ciniki_businesses.id = ciniki_business_modules.business_id "
        . "AND ciniki_business_modules.status = 1 "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashIDQuery');
    $rc = ciniki_core_dbHashIDQuery($ciniki, $strsql, 'ciniki.businesses', 'modules', 'module_id');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['modules']) ) {
        return array('stat'=>'ok', 'modules'=>array());
    }
    return $rc;
}
?>
