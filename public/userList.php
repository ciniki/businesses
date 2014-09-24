<?php
//
// Description
// -----------
// This method will return a list of the users who have permissions within a business.
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
function ciniki_businesses_userList($ciniki) {
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
	$rc = ciniki_businesses_checkAccess($ciniki, $args['business_id'], 'ciniki.businesses.userList');
	// Ignore error that module isn't enabled, businesses is on by default.
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$modules = $rc['modules'];

	//
	// Get the flags for this module and send back the permission groups available
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'flags');
	$rc = ciniki_businesses_flags($ciniki, $modules);
	$flags = $rc['flags'];

	$rsp = array('stat'=>'ok', 'permission_groups'=>array(
		'ciniki.owners'=>array('name'=>'Owners'),
		'ciniki.employees'=>array('name'=>'Employees'),
		));
	if( isset($modules['ciniki.businesses']) ) {
		foreach($flags as $flag) {
			$flag = $flag['flag'];
			if( isset($flag['group']) 
				&& ($modules['ciniki.businesses']['flags']&pow(2, $flag['bit']-1)) > 0 
				) {
				$rsp['permission_groups'][$flag['group']] = array('name'=>$flag['name']);
			}
		}
	}

	//
	// Get the list of users who have access to this business
	//
	$strsql = "SELECT ciniki_business_users.user_id, "
		. "ciniki_users.firstname, ciniki_users.lastname, ciniki_users.display_name, ciniki_users.email, "
		. "ciniki_business_users.eid, "
		. "CONCAT_WS('.', ciniki_business_users.package, ciniki_business_users.permission_group) AS permission_group "
		. "FROM ciniki_business_users, ciniki_users "
		. "WHERE ciniki_business_users.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' " 
		. "AND ciniki_business_users.status = 10 "
		. "AND ciniki_business_users.user_id = ciniki_users.id "
		. "ORDER BY permission_group "
		. "";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.businesses', array(
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
