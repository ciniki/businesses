<?php
//
// Description
// -----------
// This function will retrieve all the owners and their tenants.  This is
// only available to sys admins.
//
// Info
// ----
// Status: beta
//
// Arguments
// ---------
// api_key:
// auth_token:
//
// Returns
// -------
// <users>
//      <user id='' email='' firstname='' lastname='' display_name=''>
//          <tenants>
//              <tenant id='' name='' />
//          </tenants>
//      </user>
// </users>
//
function ciniki_tenants_getAllOwners($ciniki) {
    //
    // Check access to tnid as owner, or sys admin
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'checkAccess');
    $ac = ciniki_tenants_checkAccess($ciniki, 0, 'ciniki.tenants.getAllOwners');
    if( $ac['stat'] != 'ok' ) {
        return $ac;
    }

    $strsql = "SELECT ciniki_users.id AS user_id, email, firstname, lastname, display_name, "
        . "ciniki_tenants.id AS tnid, ciniki_tenants.name "
        . "FROM ciniki_users "
        . "LEFT JOIN ciniki_tenant_users ON (ciniki_users.id = ciniki_tenant_users.user_id "
            . "AND ciniki_tenant_users.status = 10) "
        . "LEFT JOIN ciniki_tenants ON (ciniki_tenant_users.tnid = ciniki_tenants.id) "
        . "ORDER BY ciniki_users.lastname, ciniki_users.firstname, ciniki_tenants.name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.tenants', array(
        array('container'=>'users', 'fname'=>'user_id', 'name'=>'user',
            'fields'=>array('id'=>'user_id', 'display_name', 'firstname', 'lastname', 'email')),
        array('container'=>'tenants', 'fname'=>'tnid', 'name'=>'tenant',
            'fields'=>array('id'=>'tnid', 'name')),
        ));
    return $rc;
}
?>
