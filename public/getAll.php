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
// <tenants>
//      <tenant id='' name='' >
//          <users>
//              <user id='' email='' firstname='' lastname='' display_name='' />
//          </users>
//      </tenant>
// </tenants>
//
function ciniki_tenants_getAll($ciniki) {
    //
    // Check access to tnid as owner, or sys admin
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'checkAccess');
    $ac = ciniki_tenants_checkAccess($ciniki, 0, 'ciniki.tenants.getAll');
    if( $ac['stat'] != 'ok' ) {
        return $ac;
    }

    //
    // Query for tenants and users
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
    $strsql = "SELECT IF(ciniki_tenants.category='','Uncategorized', ciniki_tenants.category) AS category, "
        . "ciniki_tenants.id as tnid, ciniki_tenants.name, "
        . "ciniki_tenants.status AS status_text, "
        . "ciniki_tenant_users.user_id, "
        . "ciniki_users.display_name, ciniki_users.firstname, ciniki_users.lastname, ciniki_users.email "
        . "FROM ciniki_tenants "
        . "LEFT JOIN ciniki_tenant_users ON (ciniki_tenants.id = ciniki_tenant_users.tnid "
            . "AND ciniki_tenant_users.status = 10) "
        . "LEFT JOIN ciniki_users ON (ciniki_tenant_users.user_id = ciniki_users.id) "
        . "ORDER BY category, ciniki_tenants.name, ciniki_users.firstname, ciniki_users.lastname ";
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.tenants', array(
        array('container'=>'categories', 'fname'=>'category', 'name'=>'category',
            'fields'=>array('name'=>'category')),
        array('container'=>'tenants', 'fname'=>'tnid', 'name'=>'tenant',
            'fields'=>array('id'=>'tnid', 'name', 'status_text'),
            'maps'=>array('status_text'=>array('1'=>'Active', '50'=>'Suspended', '60'=>'Deleted'))),
        array('container'=>'users', 'fname'=>'user_id', 'name'=>'user',
            'fields'=>array('id'=>'user_id', 'display_name', 'firstname', 'lastname', 'email')),
        ));
    return $rc;
}
?>
