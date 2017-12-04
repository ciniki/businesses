<?php
//
// Description
// -----------
// This function will verify the tenant is active, and the module is active.
//
// Arguments
// ---------
// ciniki:
// tnid:         The tenant ID to check the session user against.
// method:              The requested method.
//
// Returns
// -------
// <rsp stat='ok' />
//
function ciniki_tenants_getUserPermissions(&$ciniki, $tnid) {

    //
    // Get the list of permission_groups the user is a part of
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    $strsql = "SELECT permission_group "
        . "FROM ciniki_tenant_users "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND user_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "' "
        . "AND status = 10 "    // Active user
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQueryList');
    $rc = ciniki_core_dbQueryList($ciniki, $strsql, 'ciniki.tenants', 'groups', 'permission_group');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['groups']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.tenants.21', 'msg'=>'Access denied'));
    }
    $groups = $rc['groups'];

    $perms = 0;
    if( in_array('owners', $groups) ) { $perms |= 0x01; }
    if( in_array('employees', $groups) ) { $perms |= 0x02; }
    if( in_array('salesreps', $groups) ) { $perms |= 0x04; }
    if( in_array('resellers', $groups) ) { $perms |= 0x100; }
    $ciniki['tenant']['user']['perms'] = $perms;

    //
    // Return the ruleset
    //
    return array('stat'=>'ok', 'perms'=>$perms);
}
?>
