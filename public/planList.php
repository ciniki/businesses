<?php
//
// Description
// -----------
// This function will lookup the client plan in the database, and return the business id.
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_businesses_planList($ciniki) {
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
    // Check access to business_id as owner
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'checkAccess');
    $ac = ciniki_businesses_checkAccess($ciniki, $args['business_id'], 'ciniki.businesses.planList');
    if( $ac['stat'] != 'ok' ) {
        return $ac;
    }

    //
    // Query the database for the plan
    //
    $strsql = "SELECT id, name, monthly, trial_days, "
        . "IF((flags&0x01)=0x01, 'yes', 'no') AS ispublic "
        . "FROM ciniki_business_plans "
        . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "ORDER BY sequence "
        . "";

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbRspQuery');
    return ciniki_core_dbRspQuery($ciniki, $strsql, 'ciniki.businesses', 'plans', 'plan', array('stat'=>'ok', 'plans'=>array()));
}
?>
