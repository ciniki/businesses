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
function ciniki_businesses_userAdd($ciniki) {
	//
	// Find all the required and optional arguments
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/prepareArgs.php');
	$rc = ciniki_core_prepareArgs($ciniki, 'no', array(
		'business_id'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No business specified'), 
		'user_id'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No user specified'), 
		'package'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No package specified'), 
		'permission_group'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No permissions specified'), 
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$args = $rc['args'];
	
	//
	// Check access to business_id as owner, or sys admin
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/businesses/private/checkAccess.php');
	$ac = ciniki_businesses_checkAccess($ciniki, $args['business_id'], 'ciniki.businesses.userAdd');
	if( $ac['stat'] != 'ok' ) {
		return $ac;
	}

	//
	// Don't need a transaction, there's only 1 statement which will either succeed or fail.
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbAddModuleHistory.php');


	//
	// Remove the user from the business_users table
	//
	$strsql = "INSERT INTO ciniki_business_users (business_id, user_id, package, permission_group, status, date_added, last_updated) VALUES ("
		. "'" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['user_id']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['package']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['permission_group']) . "', "
		. "1, UTC_TIMESTAMP(), UTC_TIMESTAMP())";
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbInsert.php');
	$rc = ciniki_core_dbInsert($ciniki, $strsql, 'ciniki.businesses');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( $rc['num_affected_rows'] < 1 ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'512', 'msg'=>'Unable to add user to the business'));
	}
	$business_user_id = $rc['insert_id'];

	ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.businesses', 'ciniki_business_history', $args['business_id'], 
		1, 'ciniki_business_users', $business_user_id, 'user_id', $args['user_id']);
	ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.businesses', 'ciniki_business_history', $args['business_id'], 
		1, 'ciniki_business_users', $business_user_id, 'package', $args['package']);
	ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.businesses', 'ciniki_business_history', $args['business_id'], 
		1, 'ciniki_business_users', $business_user_id, 'permission_group', $args['permission_group']);
	ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.businesses', 'ciniki_business_history', $args['business_id'], 
		1, 'ciniki_business_users', $business_user_id, 'status', '1'); 

	//
	// Update the last_updated date so changes will be sync'd
	//
	$strsql = "UPDATE ciniki_businesses SET last_updated = UTC_TIMESTAMP() "
		. "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
	$rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.businesses');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}


	return array('stat'=>'ok', 'id'=>$business_user_id);
}
?>
