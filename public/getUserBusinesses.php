<?php
//
// Description
// -----------
// This function will return the list of tenants which the user has access to.
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
//      <tenant id='4592' name='Temporary Test Tenant' />
//      <tenant id='20719' name='Old Test Tenant' />
// </tenants>
//
function ciniki_tenants_getUserTenants($ciniki) {
    //
    // Any authenticated user has access to this function, so no need to check permissions
    //

    //
    // Check access to tnid as owner, or sys admin
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'checkAccess');
    $ac = ciniki_tenants_checkAccess($ciniki, 0, 'ciniki.tenants.getUserTenants');
    if( $ac['stat'] != 'ok' ) {
        return $ac;
    }

    // 
    // Check the database for user and which tenants they have access to.  If they
    // are a ciniki-manage, they have access to all tenants.
    // Link to the tenant_users table to grab the groups the user belongs to for that tenant.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    if( ($ciniki['session']['user']['perms'] & 0x01) == 0x01 ) {
        //
        // Check if there is a debug file of action to do on login
        //
        if( file_exists($ciniki['config']['ciniki.core']['root_dir'] . '/loginactions.js') ) {
            $login_actions = file_get_contents($ciniki['config']['ciniki.core']['root_dir'] . '/loginactions.js'); 
        }

        $strsql = "SELECT ciniki_tenants.category, "
            . "ciniki_tenants.id, "
            . "ciniki_tenants.name "
            . "FROM ciniki_tenants "
            . "ORDER BY category, ciniki_tenants.status, ciniki_tenants.name "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
        $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.tenants', array(
            array('container'=>'categories', 'fname'=>'category', 'name'=>'category',
                'fields'=>array('name'=>'category')),
            array('container'=>'tenants', 'fname'=>'id', 'name'=>'tenant',
                'fields'=>array('id', 'name')),
            ));

        if( isset($login_actions) && $login_actions != '' ) {
            $rc['loginActions'] = $login_actions;
        }

        return $rc;
    } else {
        $strsql = "SELECT DISTINCT ciniki_tenants.id, name "
            . "FROM ciniki_tenant_users, ciniki_tenants "
            . "WHERE ciniki_tenant_users.user_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "' "
            . "AND ciniki_tenant_users.status = 10 "
            . "AND ciniki_tenant_users.tnid = ciniki_tenants.id "
            . "AND ciniki_tenants.status < 60 "  // Allow suspended tenants to be listed, so user can login and update billing/unsuspend
            . "ORDER BY ciniki_tenant_users.permission_group, ciniki_tenants.name ";
//      $strsql = "SELECT DISTINCT id, name, ciniki_tenant_users.permission_group, "
//          . "d1.detail_value AS css "
//          . "FROM ciniki_tenant_users, ciniki_tenants "
//          . "LEFT JOIN ciniki_tenant_details AS d1 ON (ciniki_tenants.id = d1.tnid AND d1.detail_key = 'ciniki.manage.css') "
//          . "WHERE ciniki_tenant_users.user_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "' "
//          . "AND ciniki_tenant_users.status = 1 "
//          . "AND ciniki_tenant_users.tnid = ciniki_tenants.id "
//          . "AND ciniki_tenants.status < 60 "  // Allow suspended tenants to be listed, so user can login and update billing/unsuspend
//          . "ORDER BY ciniki_tenants.name ";
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbRspQuery');
    $rc = ciniki_core_dbRspQuery($ciniki, $strsql, 'ciniki.tenants', 'tenants', 'tenant', array('stat'=>'fail', 'err'=>array('code'=>'ciniki.tenants.50', 'msg'=>'No tenants found')));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    return $rc;
}
?>
