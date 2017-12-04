<?php
//
// Description
// -----------
// This method will return a list of the users who have permissions within a tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:             The ID of the tenant to lock.
//
// Returns
// -------
// <rsp stat="ok" />
//
function ciniki_tenants_userDetails($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'user_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'User'), 
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access 
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'checkAccess');
    $rc = ciniki_tenants_checkAccess($ciniki, $args['tnid'], 'ciniki.tenants.userDetails');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get details for a user
    //
    $strsql = "SELECT ciniki_tenant_users.user_id, ciniki_tenant_users.eid, ciniki_users.username, "
        . "ciniki_users.firstname, ciniki_users.lastname, "
        . "ciniki_users.email, ciniki_users.display_name, "
        . "ciniki_tenant_user_details.detail_key, ciniki_tenant_user_details.detail_value "
        . "FROM ciniki_tenant_users "
        . "LEFT JOIN ciniki_users ON (ciniki_tenant_users.user_id = ciniki_users.id ) "
        . "LEFT OUTER JOIN ciniki_tenant_user_details ON (ciniki_tenant_users.tnid = ciniki_tenant_user_details.tnid "
            . "AND ciniki_tenant_users.user_id = ciniki_tenant_user_details.user_id ) "
        . "WHERE ciniki_tenant_users.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND ciniki_tenant_users.user_id = '" . ciniki_core_dbQuote($ciniki, $args['user_id']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.tenants', array(
        array('container'=>'users', 'fname'=>'user_id', 'name'=>'user', 
            'fields'=>array('user_id', 'eid', 'firstname', 'lastname', 'username', 'email', 'display_name'),
            'details'=>array('detail_key'=>'detail_value'),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['users'][0]['user']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.tenants.99', 'msg'=>'Unable to find user'));
    }

    $user = $rc['users'][0]['user'];

    //
    // Check if the tenant is active and the module is enabled
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'checkModuleAccess');
    $rc = ciniki_tenants_checkModuleAccess($ciniki, $args['tnid'], 'ciniki', 'web');
    if( $rc['stat'] == 'ok' ) {
        $strsql = "SELECT detail_key, detail_value "
            . "FROM ciniki_web_settings "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND detail_key LIKE 'page-contact-user-%-" . ciniki_core_dbQuote($ciniki, $args['user_id']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.web', 'user');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        foreach($rc['rows'] as $row) {
            $user[$row['detail_key']] = $row['detail_value'];
        }
    }

    return array('stat'=>'ok', 'user'=>$user);
}
?>
