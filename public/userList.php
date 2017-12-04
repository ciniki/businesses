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
function ciniki_tenants_userList($ciniki) {
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
    $rc = ciniki_tenants_checkAccess($ciniki, $args['tnid'], 'ciniki.tenants.userList');
    // Ignore error that module isn't enabled, tenants is on by default.
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $modules = $rc['modules'];

    //
    // Get the flags for this module and send back the permission groups available
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'flags');
    $rc = ciniki_tenants_flags($ciniki, $modules);
    $flags = $rc['flags'];

    $rsp = array('stat'=>'ok', 'permission_groups'=>array(
        'ciniki.owners'=>array('name'=>'Owners'),
        ));
    if( isset($modules['ciniki.tenants']) && ($modules['ciniki.tenants']['flags']&0x01) == 1 ) {
        $rsp['permission_groups']['ciniki.employees'] = array('name'=>'Employees');
    }
    if( isset($modules['ciniki.tenants']) ) {
        foreach($flags as $flag) {
            $flag = $flag['flag'];
            if( isset($flag['group']) 
                && ($modules['ciniki.tenants']['flags']&pow(2, $flag['bit']-1)) > 0 
                ) {
                $rsp['permission_groups'][$flag['group']] = array('name'=>$flag['name']);
            }
        }
    }
    if( ($ciniki['session']['user']['perms'] & 0x01) == 0x01 ) {
        $rsp['permission_groups']['ciniki.resellers'] = array('name'=>'Resellers');
    }

    //
    // Get the list of users who have access to this tenant
    //
    $strsql = "SELECT ciniki_tenant_users.user_id, "
        . "ciniki_users.firstname, ciniki_users.lastname, ciniki_users.display_name, ciniki_users.email, "
        . "ciniki_tenant_users.eid, "
        . "CONCAT_WS('.', ciniki_tenant_users.package, ciniki_tenant_users.permission_group) AS permission_group "
        . "FROM ciniki_tenant_users, ciniki_users "
        . "WHERE ciniki_tenant_users.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' " 
        . "AND ciniki_tenant_users.status = 10 "
        . "AND ciniki_tenant_users.user_id = ciniki_users.id "
        . "ORDER BY permission_group "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.tenants', array(
        array('container'=>'groups', 'fname'=>'permission_group', 'name'=>'group', 
            'fields'=>array('permission_group')),
        array('container'=>'users', 'fname'=>'user_id', 'name'=>'user', 
            'fields'=>array('user_id', 'eid', 'firstname', 'lastname', 'display_name', 'email')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['groups']) ) {
        $rsp['groups'] = $rc['groups'];
    } else {
        $rsp['groups'] = array();
    }

    return $rsp;
}
?>
