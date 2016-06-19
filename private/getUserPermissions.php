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
function ciniki_businesses_getUserPermissions(&$ciniki, $business_id) {

    //
    // Get the list of permission_groups the user is a part of
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    $strsql = "SELECT permission_group "
        . "FROM ciniki_business_users "
        . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
        . "AND user_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "' "
        . "AND status = 10 "    // Active user
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQueryList');
    $rc = ciniki_core_dbQueryList($ciniki, $strsql, 'ciniki.businesses', 'groups', 'permission_group');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['groups']) ) {
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2022', 'msg'=>'Access denied'));
    }
    $groups = $rc['groups'];

    $perms = 0;
    if( in_array('owners', $groups) ) { $perms |= 0x01; }
    if( in_array('employees', $groups) ) { $perms |= 0x02; }
    if( in_array('salesreps', $groups) ) { $perms |= 0x04; }
    $ciniki['business']['user']['perms'] = $perms;

    //
    // Return the ruleset
    //
    return array('stat'=>'ok', 'perms'=>$perms);
}
?>
