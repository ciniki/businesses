<?php
//
// Description
// -----------
// This method will add an existing user to a tenant with permissions.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant to get the users for.
// user_id:             The ID of the user to be added.
// package:             The package to be used in combination with the permission group.
// permission_group:    The permission group the user is a part of.
//
// Returns
// -------
// <rsp stat='ok' id='1' />
//
function ciniki_tenants_userAdd(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'user_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'User'), 
        'package'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Package'), 
        'permission_group'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Permissions'),
//          'validlist'=>array('owners', 'employees'), 'name'=>'Permissions'), 
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];
    
    //
    // Check access to tnid as owner, or sys admin
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'checkAccess');
    $ac = ciniki_tenants_checkAccess($ciniki, $args['tnid'], 'ciniki.tenants.userAdd');
    if( $ac['stat'] != 'ok' ) {
        return $ac;
    }
    $modules = $ac['modules'];

    //
    // Get the flags for this module and send back the permission groups available
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'flags');
    $rc = ciniki_tenants_flags($ciniki, $modules);
    $flags = $rc['flags'];

    //
    // Check the permission group is valid
    //
    if( $args['permission_group'] != 'owners' && $args['permission_group'] != 'employees' ) {
        $found = 'no';
        foreach($flags as $flag) {
            $flag = $flag['flag'];
            // Make sure permission_group is enabled, and
            if( $flag['group'] = $args['permission_group'] . '.' . $args['package']
                && ($modules['ciniki.tenants']['flags']&pow(2, $flag['bit']-1)) > 0 
                ) {
                $found = 'yes';
                break;
            }
        }
        if( ($ciniki['session']['user']['perms'] & 0x01) == 0x01 && $args['permission_group'] == 'resellers' ) {
            $found = 'yes';
        }
        if( $found == 'no' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.tenants.96', 'msg'=>'Invalid permissions'));
        }
    }

    //
    // Don't need a transaction, there's only 1 statement which will either succeed or fail.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');

    $db_updated = 0;

    //
    // Check if user already exists, and we need to change status
    //
    $strsql = "SELECT id, user_id, status "
        . "FROM ciniki_tenant_users "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND user_id = '" . ciniki_core_dbQuote($ciniki, $args['user_id']) . "' "
        . "AND package = '" . ciniki_core_dbQuote($ciniki, $args['package']) . "' "
        . "AND permission_group = '" . ciniki_core_dbQuote($ciniki, $args['permission_group']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.tenants', 'user');
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }
    
    if( isset($rc['user']) && $rc['user']['user_id'] == $args['user_id'] ) {
        $tenant_user_id = $rc['user']['id'];
        if( $rc['user']['status'] != '10' ) {
            $strsql = "UPDATE ciniki_tenant_users SET "
                . "status = '10', "
                . "last_updated = UTC_TIMESTAMP() "
                . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "AND user_id = '" . ciniki_core_dbQuote($ciniki, $args['user_id']) . "' "
                . "AND package = '" . ciniki_core_dbQuote($ciniki, $args['package']) . "' "
                . "AND permission_group = '" . ciniki_core_dbQuote($ciniki, $args['permission_group']) . "' "
                . "";
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
            $rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.tenants');
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            $db_updated = 1;
            ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.tenants', 'ciniki_tenant_history', 
                $args['tnid'], 2, 'ciniki_tenant_users', $tenant_user_id, 'status', '10'); 
        }
    } 

    //
    // If the user and package-permission_group doesn't already exist, add
    //
    else {
        //
        // Get a new UUID
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUUID');
        $rc = ciniki_core_dbUUID($ciniki, 'ciniki.tenants');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $uuid = $rc['uuid'];

        //
        // Remove the user from the tenant_users table
        //
        $strsql = "INSERT INTO ciniki_tenant_users (uuid, tnid, user_id, "
            . "package, permission_group, status, date_added, last_updated) VALUES ("
            . "'" . ciniki_core_dbQuote($ciniki, $uuid) . "', "
            . "'" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "', "
            . "'" . ciniki_core_dbQuote($ciniki, $args['user_id']) . "', "
            . "'" . ciniki_core_dbQuote($ciniki, $args['package']) . "', "
            . "'" . ciniki_core_dbQuote($ciniki, $args['permission_group']) . "', "
            . "10, UTC_TIMESTAMP(), UTC_TIMESTAMP())";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbInsert');
        $rc = ciniki_core_dbInsert($ciniki, $strsql, 'ciniki.tenants');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( $rc['num_affected_rows'] < 1 ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.tenants.97', 'msg'=>'Unable to add user to the tenant'));
        }
        $tenant_user_id = $rc['insert_id'];

        ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.tenants', 'ciniki_tenant_history', 
            $args['tnid'], 1, 'ciniki_tenant_users', $tenant_user_id, 
            'uuid', $uuid); 
        ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.tenants', 'ciniki_tenant_history', 
            $args['tnid'], 1, 'ciniki_tenant_users', $tenant_user_id, 
            'package', $args['package']); 
        ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.tenants', 'ciniki_tenant_history', 
            $args['tnid'], 1, 'ciniki_tenant_users', $tenant_user_id, 
            'permission_group', $args['permission_group']); 
        ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.tenants', 'ciniki_tenant_history', 
            $args['tnid'], 1, 'ciniki_tenant_users', $tenant_user_id, 'status', '10'); 
    }

    //
    // Get the list of tenants this user is part of, and replicate that user for that tenant
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'updateModuleChangeDate');
    ciniki_tenants_updateModuleChangeDate($ciniki, $args['tnid'], 'ciniki', 'tenants');

    $ciniki['syncqueue'][] = array('push'=>'ciniki.tenants.user', 
        'args'=>array('id'=>$tenant_user_id));

    return array('stat'=>'ok', 'id'=>$tenant_user_id);
}
?>
