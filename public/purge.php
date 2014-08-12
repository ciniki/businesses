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
// business_id: 			The ID of the business to lock.
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
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$modules = $rc['modules'];

	//
	// Make sure a sysadmin is running this function. This has been checked in
	// the checkAccess function, but good idea to double check.
	//
	if( ($ciniki['session']['user']['perms'] & 0x01) != 0x01 ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1915', 'msg'=>'You must be a sysadmin to purge a business'));
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
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1916', 'msg'=>'Business not found'));
	}
	$business = $rc['business'];

	//
	// Check the business has been marked for deletion
	//
	if( $business['status'] != '60' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1917', 'msg'=>'Business has not been marked for deletion.'));
	}
	
	//
	// Check the business was last updated a week ago.  This means that businesses have to be marked
	// for deletion for 1 week before they can be purged
	//
	if( $business['last_change'] < (86400*7) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1918', 'msg'=>'Business has not been deleted for 1 week.'));
	}

	error_log("INFO[" . $business['id'] . "]: purging business - " . $business['name']);

//	error_log(print_r($business, true));
//	error_log($business['last_updated']);

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
			&& ($module['name'] == 'core' || $module['name'] == 'businesses') ) {
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
