<?php
//
// Description
// -----------
// This method will return a list of owners and employee's for a tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:             The ID of the tenant to lock.
//
// Returns
// -------
// <users>
//  <user id="1" display_name="Andrew" />
// </users>
//
function ciniki_tenants_employees($ciniki) {
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
    $rc = ciniki_tenants_checkAccess($ciniki, $args['tnid'], 'ciniki.tenants.employees');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the list of users who have access to this tenant
    //
    $strsql = "SELECT ciniki_tenant_users.user_id AS id, ciniki_users.display_name "
        . "FROM ciniki_tenant_users, ciniki_users "
        . "WHERE ciniki_tenant_users.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND ciniki_tenant_users.status = 10 "
        . "AND ciniki_tenant_users.user_id = ciniki_users.id "
        . "ORDER BY display_name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbRspQuery');
    $rc = ciniki_core_dbRspQuery($ciniki, $strsql, 'ciniki.tenants', 'users', 'user', array('stat'=>'ok', 'users'=>array()));

    return $rc;
}
?>
