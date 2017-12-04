<?php
//
// Description
// -----------
// This function will lookup the client plan in the database, and return the tenant id.
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_tenants_planList($ciniki) {
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
    // Check access to tnid as owner
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'checkAccess');
    $ac = ciniki_tenants_checkAccess($ciniki, $args['tnid'], 'ciniki.tenants.planList');
    if( $ac['stat'] != 'ok' ) {
        return $ac;
    }

    //
    // Query the database for the plan
    //
    $strsql = "SELECT id, name, monthly, trial_days, "
        . "IF((flags&0x01)=0x01, 'yes', 'no') AS ispublic "
        . "FROM ciniki_tenant_plans "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "ORDER BY sequence "
        . "";

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbRspQuery');
    return ciniki_core_dbRspQuery($ciniki, $strsql, 'ciniki.tenants', 'plans', 'plan', array('stat'=>'ok', 'plans'=>array()));
}
?>
