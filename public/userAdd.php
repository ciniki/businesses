<?php
//
// Description
// -----------
// This method will add an existing user to a business with permissions.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:			The ID of the business to get the users for.
// user_id:				The ID of the user to be added.
// package:				The package to be used in combination with the permission group.
// permission_group:	The permission group the user is a part of.
//
// Returns
// -------
// <rsp stat='ok' id='1' />
//
function ciniki_businesses_userAdd(&$ciniki) {
	//
	// Find all the required and optional arguments
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
	$rc = ciniki_core_prepareArgs($ciniki, 'no', array(
		'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
		'user_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'User'), 
		'package'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Package'), 
		'permission_group'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Permissions'),
//			'validlist'=>array('owners', 'employees'), 'name'=>'Permissions'), 
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$args = $rc['args'];
	
	//
	// Check access to business_id as owner, or sys admin
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'checkAccess');
	$ac = ciniki_businesses_checkAccess($ciniki, $args['business_id'], 'ciniki.businesses.userAdd');
	if( $ac['stat'] != 'ok' ) {
		return $ac;
	}
	$modules = $ac['modules'];

	//
	// Get the flags for this module and send back the permission groups available
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'flags');
	$rc = ciniki_businesses_flags($ciniki, $modules);
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
				&& ($modules['ciniki.businesses']['flags']&pow(2, $flag['bit']-1)) > 0 
				) {
				$found = 'yes';
				break;
			}
		}
        if( ($ciniki['session']['user']['perms'] & 0x01) == 0x01 && $args['permission_group'] == 'resellers' ) {
            $found = 'yes';
        }
		if( $found == 'no' ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1841', 'msg'=>'Invalid permissions'));
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
		. "FROM ciniki_business_users "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND user_id = '" . ciniki_core_dbQuote($ciniki, $args['user_id']) . "' "
		. "AND package = '" . ciniki_core_dbQuote($ciniki, $args['package']) . "' "
		. "AND permission_group = '" . ciniki_core_dbQuote($ciniki, $args['permission_group']) . "' "
		. "";
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.businesses', 'user');
	if( $rc['stat'] != 'ok' ) {	
		return $rc;
	}
	
	if( isset($rc['user']) && $rc['user']['user_id'] == $args['user_id'] ) {
		$business_user_id = $rc['user']['id'];
		if( $rc['user']['status'] != '10' ) {
			$strsql = "UPDATE ciniki_business_users SET "
				. "status = '10', "
				. "last_updated = UTC_TIMESTAMP() "
				. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
				. "AND user_id = '" . ciniki_core_dbQuote($ciniki, $args['user_id']) . "' "
				. "AND package = '" . ciniki_core_dbQuote($ciniki, $args['package']) . "' "
				. "AND permission_group = '" . ciniki_core_dbQuote($ciniki, $args['permission_group']) . "' "
				. "";
			ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
			$rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.businesses');
			if( $rc['stat'] != 'ok' ) {
				return $rc;
			}
			$db_updated = 1;
			ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.businesses', 'ciniki_business_history', 
				$args['business_id'], 2, 'ciniki_business_users', $business_user_id, 'status', '10'); 
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
		$rc = ciniki_core_dbUUID($ciniki, 'ciniki.businesses');
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		$uuid = $rc['uuid'];

		//
		// Remove the user from the business_users table
		//
		$strsql = "INSERT INTO ciniki_business_users (uuid, business_id, user_id, "
			. "package, permission_group, status, date_added, last_updated) VALUES ("
			. "'" . ciniki_core_dbQuote($ciniki, $uuid) . "', "
			. "'" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "', "
			. "'" . ciniki_core_dbQuote($ciniki, $args['user_id']) . "', "
			. "'" . ciniki_core_dbQuote($ciniki, $args['package']) . "', "
			. "'" . ciniki_core_dbQuote($ciniki, $args['permission_group']) . "', "
			. "10, UTC_TIMESTAMP(), UTC_TIMESTAMP())";
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbInsert');
		$rc = ciniki_core_dbInsert($ciniki, $strsql, 'ciniki.businesses');
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( $rc['num_affected_rows'] < 1 ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'512', 'msg'=>'Unable to add user to the business'));
		}
		$business_user_id = $rc['insert_id'];

		ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.businesses', 'ciniki_business_history', 
			$args['business_id'], 1, 'ciniki_business_users', $business_user_id, 
			'uuid', $uuid); 
		ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.businesses', 'ciniki_business_history', 
			$args['business_id'], 1, 'ciniki_business_users', $business_user_id, 
			'package', $args['package']); 
		ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.businesses', 'ciniki_business_history', 
			$args['business_id'], 1, 'ciniki_business_users', $business_user_id, 
			'permission_group', $args['permission_group']); 
		ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.businesses', 'ciniki_business_history', 
			$args['business_id'], 1, 'ciniki_business_users', $business_user_id, 'status', '10'); 
	}

	//
	// Get the list of businesses this user is part of, and replicate that user for that business
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'updateModuleChangeDate');
	ciniki_businesses_updateModuleChangeDate($ciniki, $args['business_id'], 'ciniki', 'businesses');

	$ciniki['syncqueue'][] = array('push'=>'ciniki.businesses.user', 
		'args'=>array('id'=>$business_user_id));

	return array('stat'=>'ok', 'id'=>$business_user_id);
}
?>
