<?php
//
// Description
// -----------
// This function will remove an owner from a tenant.
//
// Info
// ----
// Status: beta
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant to get the users for.
// user_id:             The ID of the user to be removed.
//
// Returns
// -------
// <rsp stat='ok' />
//
function ciniki_tenants_userRemove(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'user_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'User'), 
        'package'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Package'), 
        'permission_group'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Permissions'), 
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner, or sys admin
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'checkAccess');
    $ac = ciniki_tenants_checkAccess($ciniki, $args['tnid'], 'ciniki.tenants.userRemove');
    if( $ac['stat'] != 'ok' ) {
        return $ac;
    }

    //
    // Grab the tenant_user_id
    //
    $strsql = "SELECT ciniki_tenant_users.id "
        . "FROM ciniki_tenant_users "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND user_id = '" . ciniki_core_dbQuote($ciniki, $args['user_id']) . "'"
        . "AND package = '" . ciniki_core_dbQuote($ciniki, $args['package']) . "'"
        . "AND permission_group = '" . ciniki_core_dbQuote($ciniki, $args['permission_group']) . "'"
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.tenants', 'user');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['user']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.tenants.100', 'msg'=>'Unable to remove user.'));
    }
    $tenant_user_id = $rc['user']['id'];

    // Required functions
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');

    //
    // Turn off autocommit
    //
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.tenants');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Remove the user from the tenant_users table
    //
//  $strsql = "DELETE FROM ciniki_tenant_users "
//      . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
//      . "AND user_id = '" . ciniki_core_dbQuote($ciniki, $args['user_id']) . "'"
//      . "AND package = '" . ciniki_core_dbQuote($ciniki, $args['package']) . "'"
//      . "AND permission_group = '" . ciniki_core_dbQuote($ciniki, $args['permission_group']) . "'"
//      . "";
//  ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDelete');
//  $rc = ciniki_core_dbDelete($ciniki, $strsql, 'ciniki.tenants');
    $strsql = "UPDATE ciniki_tenant_users "
        . "SET status = 60 "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND user_id = '" . ciniki_core_dbQuote($ciniki, $args['user_id']) . "'"
        . "AND package = '" . ciniki_core_dbQuote($ciniki, $args['package']) . "'"
        . "AND permission_group = '" . ciniki_core_dbQuote($ciniki, $args['permission_group']) . "'"
        . "";
    $rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.tenants');
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.tenants');
        return $rc;
    }
    if( $rc['num_affected_rows'] < 1 ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.tenants');
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.tenants.101', 'msg'=>'Unable to remove user'));
    }

    //
    // Remote user details from ciniki_tenant_user_details
    //
//  $strsql = "DELETE FROM ciniki_tenant_user_details "
//      . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
//      . "AND user_id = '" . ciniki_core_dbQuote($ciniki, $args['user_id']) . "' "
//      . "";
//  $rc = ciniki_core_dbDelete($ciniki, $strsql, 'ciniki.tenants');
//  if( $rc['stat'] != 'ok' ) {
//      ciniki_core_dbTransactionRollback($ciniki, 'ciniki.tenants');
//      return $rc;
//  }
    
//  ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.tenants', 'ciniki_tenant_history', $args['tnid'], 
//      3, 'ciniki_tenant_users', $args['user_id'] . '.' . $args['package'] . '.' . $args['permission_group'], '*', '');
    ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.tenants', 'ciniki_tenant_history', 
        $args['tnid'], 2, 'ciniki_tenant_users', $tenant_user_id, 'status', '60');

    //
    // Commit the changes
    //
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.tenants');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Update the last_updated for the user, so replication catches the changes
    //
//  $strsql = "UPDATE ciniki_tenant_users SET last_updated = UTC_TIMESTAMP() "
//      . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
//      . "AND user_id = '" . ciniki_core_dbQuote($ciniki, $args['user_id']) . "' "
//      . "";
//  $rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.users');
//  if( $rc['stat'] != 'ok' ) {
//      return $rc;
//  }

    //
    // Get the list of tenants this user is part of, and replicate that user for that tenant
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'updateModuleChangeDate');
    ciniki_tenants_updateModuleChangeDate($ciniki, $args['tnid'], 'ciniki', 'tenants');
    $ciniki['syncqueue'][] = array('push'=>'ciniki.tenants.user', 
        'args'=>array('id'=>$tenant_user_id));

    return array('stat'=>'ok');
}
?>
