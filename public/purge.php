<?php
//
// Description
// -----------
// This method will purge all business data from the database
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:             The ID of the business to lock.
//
// Returns
// -------
// <rsp stat="ok" />
//
function ciniki_businesses_purge($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access 
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'checkAccess');
    $rc = ciniki_businesses_checkAccess($ciniki, $args['business_id'], 'ciniki.businesses.purge');
    if( $rc['stat'] != 'ok' && $rc['err']['code'] != 'ciniki.businesses.17' && $rc['err']['code'] != 'ciniki.businesses.18' ) {
        return $rc;
    }

    //
    // Make sure a sysadmin.  This check needs to be here because we ignore suspended/deleted 
    // businesses above, which then doesn't check perms.  Only sysadmins have access
    // to this method.
    //
    if( ($ciniki['session']['user']['perms'] & 0x01) != 0x01 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.businesses.59', 'msg'=>'Permission denied'));
    }

    //
    // Make sure a sysadmin is running this function. This has been checked in
    // the checkAccess function, but good idea to double check.
    //
    if( ($ciniki['session']['user']['perms'] & 0x01) != 0x01 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.businesses.60', 'msg'=>'You must be a sysadmin to purge a business'));
    }

    //
    // Get the business details
    //
    $strsql = "SELECT id, name, uuid, status, last_updated, "
        . "(UNIX_TIMESTAMP(UTC_TIMESTAMP()) - UNIX_TIMESTAMP(last_updated)) AS last_change "
        . "FROM ciniki_businesses "
        . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.businesses', 'business');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['business']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.businesses.61', 'msg'=>'Business not found'));
    }
    $business = $rc['business'];

    //
    // Check the business has been marked for deletion
    //
    if( $business['status'] != '60' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.businesses.62', 'msg'=>'Business has not been marked for deletion.'));
    }
    
    //
    // Check the business was last updated a week ago.  This means that businesses have to be marked
    // for deletion for 1 week before they can be purged
    //
    if( $business['last_change'] < (86400*7) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.businesses.63', 'msg'=>'Business has not been deleted for 1 week.'));
    }

    error_log("INFO[" . $business['id'] . "]: purging business - " . $business['name']);

//  error_log(print_r($business, true));
//  error_log($business['last_updated']);

    //
    // Go through the modules and delete all
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDelete');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'getModuleList');
    $rc = ciniki_core_getModuleList($ciniki);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $modules = $rc['modules'];

    foreach($modules as $module) {
        if( $module['package'] == 'ciniki'
            && ($module['name'] == 'core' || $module['name'] == 'businesses' || $module['name'] == 'users') ) {
            // Skip these modules
            continue;
        }
        $pkg = $module['package'];
        $mod = $module['name'];
        
        error_log("INFO[" . $business['id'] . "]: purging module $pkg.$mod");

        //
        // Load the objects files
        //
        $filename = $ciniki['config']['ciniki.core']['root_dir'] . "/$pkg-mods/$mod/private/objects.php";
        if( !file_exists($filename) ) {
            error_log("PURGE[" . $business['id'] . "]: $pkg.$mod - no objects.php");
            continue;
        }
        require_once($filename);
        $fn = "{$pkg}_{$mod}_objects";
        $rc = $fn($ciniki);
        if( $rc['stat'] != 'ok' ) {
            error_log("PURGE[" . $business['id'] . "]: $pkg.$mod - couldn't load objects.php");
            continue;
        }
        $objects = $rc['objects'];

        foreach($objects as $object) {
            if( isset($object['table']) ) {
                $strsql = "DELETE FROM " . $object['table'] . " "
                    . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business['id']) . "' "
                    . "";
                $rc = ciniki_core_dbDelete($ciniki, $strsql, "{$pkg}_{$mod}");
                if( $rc['stat'] != 'ok' ) {
                    return $rc;
                }
            }

            if( isset($object['history_table']) ) {
                // May be repeated, doesn't matter
                $strsql = "DELETE FROM " . $object['history_table'] . " "
                    . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business['id']) . "' "
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
    ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'hooks', 'storageDir');
    $rc = ciniki_businesses_hooks_storageDir($ciniki, $business['id'], array());
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.businesses.115', 'msg'=>'Unable to get storage directory', 'err'=>$rc['err']));
    }
    if( isset($rc['storage_dir']) ) {
        $storage_dir = $rc['storage_dir'];
        if( is_dir($storage_dir) ) {
            error_log("PURGE[" . $business['id'] . "]: Storage dir " . $storage_dir);
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'recursiveRmdir');
            $rc = ciniki_core_recursiveRmdir($ciniki, $storage_dir);
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.businesses.116', 'msg'=>'Unable to remove storage directory contents', 'err'=>$rc['err']));
            }
            if( !rmdir($storage_dir) ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.businesses.117', 'msg'=>'Unable to remove storage directory'));
            }
        }
    }

    //
    // Remove the cache directory
    //
    $cache_dir = $ciniki['config']['ciniki.core']['cache_dir'] . '/' . $business['uuid'][0] . '/' . $business['uuid'];
    if( is_dir($cache_dir) ) {
        $rc = ciniki_core_recursiveRmdir($ciniki, $cache_dir);
        error_log("PURGE[" . $business['id'] . "]: Cache dir " . $cache_dir);
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'recursiveRmdir');
        $rc = ciniki_core_recursiveRmdir($ciniki, $cache_dir);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.businesses.116', 'msg'=>'Unable to remove cache directory contents', 'err'=>$rc['err']));
        }
        if( !rmdir($cache_dir) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.businesses.117', 'msg'=>'Unable to remove cache directory'));
        }
    }

    //
    // Remove the web cache directory
    //
    $cache_dir = $ciniki['config']['ciniki.core']['modules_dir'] . '/web/cache/' . $business['uuid'][0] . '/' . $business['uuid'];
    if( is_dir($cache_dir) ) {
        $rc = ciniki_core_recursiveRmdir($ciniki, $cache_dir);
        error_log("PURGE[" . $business['id'] . "]: web cache dir " . $cache_dir);
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'recursiveRmdir');
        $rc = ciniki_core_recursiveRmdir($ciniki, $cache_dir);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.businesses.116', 'msg'=>'Unable to remove web cache directory contents', 'err'=>$rc['err']));
        }
        if( !rmdir($cache_dir) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.businesses.117', 'msg'=>'Unable to remove web cache directory'));
        }
    }

    //
    // Remove core error logs
    //
    $strsql = "DELETE FROM ciniki_core_api_logs "
        . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business['id']) . "' "
        . "";
    $rc = ciniki_core_dbDelete($ciniki, $strsql, "ciniki.businesses");
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    $strsql = "DELETE FROM ciniki_core_error_logs "
        . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business['id']) . "' "
        . "";
    $rc = ciniki_core_dbDelete($ciniki, $strsql, "ciniki.businesses");
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }


    //
    // Remove from businesses module
    //
    $strsql = "DELETE FROM ciniki_business_details "
        . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business['id']) . "' "
        . "";
    $rc = ciniki_core_dbDelete($ciniki, $strsql, "ciniki.businesses");
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    $strsql = "DELETE FROM ciniki_business_domains "
        . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business['id']) . "' "
        . "";
    $rc = ciniki_core_dbDelete($ciniki, $strsql, "ciniki.businesses");
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    $strsql = "DELETE FROM ciniki_business_modules "
        . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business['id']) . "' "
        . "";
    $rc = ciniki_core_dbDelete($ciniki, $strsql, "ciniki.businesses");
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    $strsql = "DELETE FROM ciniki_business_subscriptions "
        . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business['id']) . "' "
        . "";
    $rc = ciniki_core_dbDelete($ciniki, $strsql, "ciniki.businesses");
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // FIXME: Add uuidmaps and uuidissues when business_id is added
    //

    $strsql = "DELETE FROM ciniki_business_syncs "
        . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business['id']) . "' "
        . "";
    $rc = ciniki_core_dbDelete($ciniki, $strsql, "ciniki.businesses");
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    $strsql = "DELETE FROM ciniki_business_user_details "
        . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business['id']) . "' "
        . "";
    $rc = ciniki_core_dbDelete($ciniki, $strsql, "ciniki.businesses");
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    $strsql = "DELETE FROM ciniki_business_users "
        . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business['id']) . "' "
        . "";
    $rc = ciniki_core_dbDelete($ciniki, $strsql, "ciniki.businesses");
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    $strsql = "DELETE FROM ciniki_businesses "
        . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $business['id']) . "' "
        . "";
    $rc = ciniki_core_dbDelete($ciniki, $strsql, "ciniki.businesses");
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    $strsql = "DELETE FROM ciniki_business_history "
        . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business['id']) . "' "
        . "";
    $rc = ciniki_core_dbDelete($ciniki, $strsql, "ciniki.businesses");
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    error_log("INFO[" . $business['id'] . "]: purged - " . $business['name']);

    return array('stat'=>'ok');
}
?>
