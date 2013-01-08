<?php
//
// Description
// -----------
// This function will remove an owner from a business.
//
// Info
// ----
// Status: beta
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:			The ID of the business to get the users for.
// user_id:				The ID of the user to be removed.
//
// Returns
// -------
// <rsp stat='ok' />
//
function ciniki_businesses_userRemove(&$ciniki) {
	//
	// Find all the required and optional arguments
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
	$rc = ciniki_core_prepareArgs($ciniki, 'no', array(
		'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
		'user_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'User'), 
		'package'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Package'), 
		'permission_group'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Permissions'), 
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$args = $rc['args'];

	//
	// Check access to business_id as owner, or sys admin
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'checkAccess');
	$ac = ciniki_businesses_checkAccess($ciniki, $args['business_id'], 'ciniki.businesses.userRemove');
	if( $ac['stat'] != 'ok' ) {
		return $ac;
	}

	//
	// Grab the business_user_id
	//
	$strsql = "SELECT ciniki_business_users.id "
		. "FROM ciniki_business_users "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND user_id = '" . ciniki_core_dbQuote($ciniki, $args['user_id']) . "'"
		. "AND package = '" . ciniki_core_dbQuote($ciniki, $args['package']) . "'"
		. "AND permission_group = '" . ciniki_core_dbQuote($ciniki, $args['permission_group']) . "'"
		. "";
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.businesses', 'user');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['user']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'155', 'msg'=>'Unable to remove user.'));
	}
	$business_user_id = $rc['user']['id'];

	// Required functions
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');

	//
	// Turn off autocommit
	//
	$rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.businesses');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	//
	// Remove the user from the business_users table
	//
//	$strsql = "DELETE FROM ciniki_business_users "
//		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
//		. "AND user_id = '" . ciniki_core_dbQuote($ciniki, $args['user_id']) . "'"
//		. "AND package = '" . ciniki_core_dbQuote($ciniki, $args['package']) . "'"
//		. "AND permission_group = '" . ciniki_core_dbQuote($ciniki, $args['permission_group']) . "'"
//		. "";
//	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDelete');
//	$rc = ciniki_core_dbDelete($ciniki, $strsql, 'ciniki.businesses');
	$strsql = "UPDATE ciniki_business_users "
		. "SET status = 60 "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND user_id = '" . ciniki_core_dbQuote($ciniki, $args['user_id']) . "'"
		. "AND package = '" . ciniki_core_dbQuote($ciniki, $args['package']) . "'"
		. "AND permission_group = '" . ciniki_core_dbQuote($ciniki, $args['permission_group']) . "'"
		. "";
	$rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.businesses');
	if( $rc['stat'] != 'ok' ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.businesses');
		return $rc;
	}
	if( $rc['num_affected_rows'] < 1 ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.businesses');
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'156', 'msg'=>'Unable to remove user'));
	}

	//
	// Remote user details from ciniki_business_user_details
	//
//	$strsql = "DELETE FROM ciniki_business_user_details "
//		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
//		. "AND user_id = '" . ciniki_core_dbQuote($ciniki, $args['user_id']) . "' "
//		. "";
//	$rc = ciniki_core_dbDelete($ciniki, $strsql, 'ciniki.businesses');
//	if( $rc['stat'] != 'ok' ) {
//		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.businesses');
//		return $rc;
//	}
	
//	ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.businesses', 'ciniki_business_history', $args['business_id'], 
//		3, 'ciniki_business_users', $args['user_id'] . '.' . $args['package'] . '.' . $args['permission_group'], '*', '');
	ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.businesses', 'ciniki_business_history', $args['business_id'], 
		2, 'ciniki_business_users', $args['user_id'] . '.' . $args['package'] . '.' . $args['permission_group'], 'status', '60');

	//
	// Commit the changes
	//
	$rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.businesses');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	//
	// Update the last_updated for the user, so replication catches the changes
	//
	$strsql = "UPDATE ciniki_business_users SET last_updated = UTC_TIMESTAMP() "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND user_id = '" . ciniki_core_dbQuote($ciniki, $args['user_id']) . "' "
		. "";
	$rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.users');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	//
	// Get the list of businesses this user is part of, and replicate that user for that business
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'updateModuleChangeDate');
	ciniki_businesses_updateModuleChangeDate($ciniki, $args['business_id'], 'ciniki', 'businesses');
	$ciniki['syncqueue'][] = array('method'=>'ciniki.businesses.user.push', 'args'=>array('id'=>$args['user_id']));

	return array('stat'=>'ok');
}
?>
