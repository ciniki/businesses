<?php
//
// Description
// -----------
// Return the list of tenant owners.
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_tenants_hooks_tenantOwners(&$ciniki, $tnid, $args) {

    //
    // Select the owners only
    //
    $strsql = "SELECT ciniki_tenant_users.user_id, "
        . "ciniki_tenant_users.status, "
        . "ciniki_users.email, "
        . "ciniki_users.username, "
        . "ciniki_users.firstname, "
        . "ciniki_users.lastname, "
        . "ciniki_users.display_name "
        . "FROM ciniki_tenant_users, ciniki_users "
        . "WHERE ciniki_tenant_users.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND ciniki_tenant_users.package = 'ciniki' "
        . "AND (ciniki_tenant_users.permission_group = 'owners') "
        . "AND ciniki_tenant_users.status = 10 "      // Active owners only
        . "AND ciniki_tenant_users.user_id = ciniki_users.id "
        . "AND ciniki_users.status < 11 "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.tenants', array(
        array('container'=>'users', 'fname'=>'user_id',
            'fields'=>array('user_id', 'firstname', 'lastname', 'display_name', 'email')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.tenants.2', 'msg'=>'Unable to get list of owners', 'err'=>$rc['err']));
    }
    if( !isset($rc['users']) ) {
        return array('stat'=>'ok', 'users'=>array());
    }

    return array('stat'=>'ok', 'users'=>$rc['users']);
}
?>
