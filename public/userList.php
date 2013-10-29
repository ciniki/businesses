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
		'id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$args = $rc['args'];

	//
	// Check access 
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'checkAccess');
	$rc = ciniki_businesses_checkAccess($ciniki, $args['id'], 'ciniki.businesses.userList');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	//
	// Get the list of users who have access to this business
	//
	$strsql = "SELECT ciniki_business_users.user_id, "
		. "ciniki_users.firstname, ciniki_users.lastname, ciniki_users.display_name, ciniki_users.email, "
		. "CONCAT_WS('.', ciniki_business_users.package, ciniki_business_users.permission_group) AS permission_group "
		. "FROM ciniki_business_users, ciniki_users "
		. "WHERE ciniki_business_users.business_id = '" . ciniki_core_dbQuote($ciniki, $args['id']) . "' " 
		. "AND ciniki_business_users.status = 10 "
		. "AND ciniki_business_users.user_id = ciniki_users.id "
		. "ORDER BY permission_group "
		. "";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.businesses', array(
		array('container'=>'groups', 'fname'=>'permission_group', 'name'=>'group', 'fields'=>array('permission_group')),
		array('container'=>'users', 'fname'=>'user_id', 'name'=>'user', 'fields'=>array('user_id', 'firstname', 'lastname', 'display_name', 'email')),
		));

	return $rc;
}
?>
