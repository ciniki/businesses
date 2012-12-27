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
function ciniki_businesses_userRemove($ciniki) {
	//
	// Find all the required and optional arguments
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
	$rc = ciniki_core_prepareArgs($ciniki, 'no', array(
		'business_id'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No business specified'), 
		'user_id'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No user specified'), 
		'package'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No package specified'), 
		'permission_group'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No permission specified'), 
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
	// No need for transactions, on one statement
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');

	//
	// Grab the business_user_id
	//
	$strsql = "SELECT id FROM ciniki_business_users "
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

	//
	// Remove the user from the business_users table
	//
	$strsql = "DELETE FROM ciniki_business_users "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND user_id = '" . ciniki_core_dbQuote($ciniki, $args['user_id']) . "'"
		. "AND package = '" . ciniki_core_dbQuote($ciniki, $args['package']) . "'"
		. "AND permission_group = '" . ciniki_core_dbQuote($ciniki, $args['permission_group']) . "'"
		. "";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDelete');
	$rc = ciniki_core_dbDelete($ciniki, $strsql, 'ciniki.businesses');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.businesses', 'ciniki_business_history', $args['business_id'], 
		3, 'ciniki_business_users', $business_user_id, '*', '');

	if( $rc['num_affected_rows'] < 1 ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'156', 'msg'=>'Unable to remove user'));
	}

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

	return array('stat'=>'ok');
}
?>
