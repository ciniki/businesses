<?php
//
// Description
// -----------
// This method will purge all tenant data from the database
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
function ciniki_tenants_purge($ciniki) {
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
    $rc = ciniki_tenants_checkAccess($ciniki, $args['tnid'], 'ciniki.tenants.purge');
    if( $rc['stat'] != 'ok' && $rc['err']['code'] != 'ciniki.tenants.17' && $rc['err']['code'] != 'ciniki.tenants.18' ) {
        return $rc;
    }

    //
    // Make sure a sysadmin.  This check needs to be here because we ignore suspended/deleted 
    // tenants above, which then doesn't check perms.  Only sysadmins have access
    // to this method.
    //
    if( ($ciniki['session']['user']['perms'] & 0x01) != 0x01 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.tenants.59', 'msg'=>'Permission denied'));
    }

    //
    // Make sure a sysadmin is running this function. This has been checked in
    // the checkAccess function, but good idea to double check.
    //
    if( ($ciniki['session']['user']['perms'] & 0x01) != 0x01 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.tenants.60', 'msg'=>'You must be a sysadmin to purge a tenant'));
    }

    //
    // Get the tenant details
    //
    $strsql = "SELECT id, name, uuid, status, last_updated, "
        . "(UNIX_TIMESTAMP(UTC_TIMESTAMP()) - UNIX_TIMESTAMP(last_updated)) AS last_change "
        . "FROM ciniki_tenants "
        . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.tenants', 'tenant');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['tenant']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.tenants.61', 'msg'=>'Tenant not found'));
    }
    $tenant = $rc['tenant'];

    //
    // Check the tenant has been marked for deletion
    //
    if( $tenant['status'] != '60' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.tenants.62', 'msg'=>'Tenant has not been marked for deletion.'));
    }
    
    //
    // Check the tenant was last updated a week ago.  This means that tenants have to be marked
    // for deletion for 1 week before they can be purged
    //
    if( $tenant['last_change'] < (86400*7) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.tenants.63', 'msg'=>'Tenant has not been deleted for 1 week.'));
    }

    error_log("INFO[" . $tenant['id'] . "]: purging tenant - " . $tenant['name']);

//  error_log(print_r($tenant, true));
//  error_log($tenant['last_updated']);

    //
    // Go through the modules and delete all
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'recursiveRmdir');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDelete');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'getModuleList');
    $rc = ciniki_core_getModuleList($ciniki);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $modules = $rc['modules'];

    foreach($modules as $module) {
        if( $module['package'] == 'ciniki'
            && ($module['name'] == 'core' || $module['name'] == 'tenants' || $module['name'] == 'users') ) {
            // Skip these modules
            continue;
        }
        $pkg = $module['package'];
        $mod = $module['name'];
        
        error_log("INFO[" . $tenant['id'] . "]: purging module $pkg.$mod");

        //
        // Load the objects files
        //
        $filename = $ciniki['config']['ciniki.core']['root_dir'] . "/$pkg-mods/$mod/private/objects.php";
        if( !file_exists($filename) ) {
            error_log("PURGE[" . $tenant['id'] . "]: $pkg.$mod - no objects.php");
            continue;
        }
        require_once($filename);
        $fn = "{$pkg}_{$mod}_objects";
        $rc = $fn($ciniki);
        if( $rc['stat'] != 'ok' ) {
            error_log("PURGE[" . $tenant['id'] . "]: $pkg.$mod - couldn't load objects.php");
            continue;
        }
        $objects = $rc['objects'];

        foreach($objects as $object) {
            if( isset($object['table']) ) {
                $strsql = "DELETE FROM " . $object['table'] . " "
                    . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tenant['id']) . "' "
                    . "";
                $rc = ciniki_core_dbDelete($ciniki, $strsql, "{$pkg}_{$mod}");
                if( $rc['stat'] != 'ok' ) {
                    return $rc;
                }
            }

            if( isset($object['history_table']) ) {
                // May be repeated, doesn't matter
                $strsql = "DELETE FROM " . $object['history_table'] . " "
                    . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tenant['id']) . "' "
                    . "";
                $rc = ciniki_core_dbDelete($ciniki, $strsql, "{$pkg}_{$mod}");
                if( $rc['stat'] != 'ok' ) {
                    return $rc;
                }
            }
        }
    }

    //
    // Remove the storage directory
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'hooks', 'storageDir');
    $rc = ciniki_tenants_hooks_storageDir($ciniki, $tenant['id'], array());
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.tenants.115', 'msg'=>'Unable to get storage directory', 'err'=>$rc['err']));
    }
    if( isset($rc['storage_dir']) ) {
        $storage_dir = $rc['storage_dir'];
        if( is_dir($storage_dir) ) {
            error_log("PURGE[" . $tenant['id'] . "]: Storage dir " . $storage_dir);
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'recursiveRmdir');
            $rc = ciniki_core_recursiveRmdir($ciniki, $storage_dir);
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.tenants.116', 'msg'=>'Unable to remove storage directory contents', 'err'=>$rc['err']));
            }
            if( !rmdir($storage_dir) ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.tenants.117', 'msg'=>'Unable to remove storage directory'));
            }
        }
    }

    //
    // Remove the cache directory
    //
    $cache_dir = $ciniki['config']['ciniki.core']['cache_dir'] . '/' . $tenant['uuid'][0] . '/' . $tenant['uuid'];
    if( is_dir($cache_dir) ) {
        $rc = ciniki_core_recursiveRmdir($ciniki, $cache_dir);
        error_log("PURGE[" . $tenant['id'] . "]: Cache dir " . $cache_dir);
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'recursiveRmdir');
        $rc = ciniki_core_recursiveRmdir($ciniki, $cache_dir);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.tenants.116', 'msg'=>'Unable to remove cache directory contents', 'err'=>$rc['err']));
        }
        if( !rmdir($cache_dir) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.tenants.117', 'msg'=>'Unable to remove cache directory'));
        }
    }

    //
    // Remove the web cache directory
    //
    $cache_dir = $ciniki['config']['ciniki.core']['modules_dir'] . '/web/cache/' . $tenant['uuid'][0] . '/' . $tenant['uuid'];
    if( is_dir($cache_dir) ) {
        $rc = ciniki_core_recursiveRmdir($ciniki, $cache_dir);
        error_log("PURGE[" . $tenant['id'] . "]: web cache dir " . $cache_dir);
        $rc = ciniki_core_recursiveRmdir($ciniki, $cache_dir);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.tenants.116', 'msg'=>'Unable to remove web cache directory contents', 'err'=>$rc['err']));
        }
        if( !rmdir($cache_dir) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.tenants.117', 'msg'=>'Unable to remove web cache directory'));
        }
    }

    //
    // Remove core error logs
    //
    $strsql = "DELETE FROM ciniki_core_api_logs "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tenant['id']) . "' "
        . "";
    $rc = ciniki_core_dbDelete($ciniki, $strsql, "ciniki.tenants");
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    $strsql = "DELETE FROM ciniki_core_error_logs "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tenant['id']) . "' "
        . "";
    $rc = ciniki_core_dbDelete($ciniki, $strsql, "ciniki.tenants");
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }


    //
    // Remove from tenants module
    //
    $strsql = "DELETE FROM ciniki_tenant_details "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tenant['id']) . "' "
        . "";
    $rc = ciniki_core_dbDelete($ciniki, $strsql, "ciniki.tenants");
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    $strsql = "DELETE FROM ciniki_tenant_domains "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tenant['id']) . "' "
        . "";
    $rc = ciniki_core_dbDelete($ciniki, $strsql, "ciniki.tenants");
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    $strsql = "DELETE FROM ciniki_tenant_modules "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tenant['id']) . "' "
        . "";
    $rc = ciniki_core_dbDelete($ciniki, $strsql, "ciniki.tenants");
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    $strsql = "DELETE FROM ciniki_tenant_subscriptions "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tenant['id']) . "' "
        . "";
    $rc = ciniki_core_dbDelete($ciniki, $strsql, "ciniki.tenants");
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // FIXME: Add uuidmaps and uuidissues when tnid is added
    //

    $strsql = "DELETE FROM ciniki_tenant_syncs "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tenant['id']) . "' "
        . "";
    $rc = ciniki_core_dbDelete($ciniki, $strsql, "ciniki.tenants");
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    $strsql = "DELETE FROM ciniki_tenant_user_details "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tenant['id']) . "' "
        . "";
    $rc = ciniki_core_dbDelete($ciniki, $strsql, "ciniki.tenants");
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    $strsql = "DELETE FROM ciniki_tenant_users "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tenant['id']) . "' "
        . "";
    $rc = ciniki_core_dbDelete($ciniki, $strsql, "ciniki.tenants");
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    $strsql = "DELETE FROM ciniki_tenants "
        . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $tenant['id']) . "' "
        . "";
    $rc = ciniki_core_dbDelete($ciniki, $strsql, "ciniki.tenants");
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    $strsql = "DELETE FROM ciniki_tenant_history "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tenant['id']) . "' "
        . "";
    $rc = ciniki_core_dbDelete($ciniki, $strsql, "ciniki.tenants");
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    error_log("INFO[" . $tenant['id'] . "]: purged - " . $tenant['name']);

    return array('stat'=>'ok');
}
?>
