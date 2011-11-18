<?php
//
// Description
// -----------
// This function will add an owner from a business.
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
// user_id:				The ID of the user to be added.
//
// Returns
// -------
// <rsp stat='ok' id='1' />
//
function ciniki_businesses_addOwner($ciniki) {
	//
	// Find all the required and optional arguments
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/prepareArgs.php');
	$rc = ciniki_core_prepareArgs($ciniki, 'no', array(
		'business_id'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No business specified'), 
		'user_id'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No user specified'), 
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$args = $rc['args'];
	
	//
	// Check access to business_id as owner, or sys admin
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/businesses/private/checkAccess.php');
	$ac = ciniki_businesses_checkAccess($ciniki, $args['business_id'], 'ciniki.businesses.addOwner');
	if( $ac['stat'] != 'ok' ) {
		return $ac;
	}

	//
	// Don't need a transaction, there's only 1 statement which will either succeed or fail.
	//

	//
	// Remove the user from the business_users table
	//
	$strsql = "INSERT INTO ciniki_business_users (business_id, user_id, groups, type, status, date_added, last_updated) VALUES ("
		. "'" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['user_id']) . "', "
		. "1, 1, 1, UTC_TIMESTAMP(), UTC_TIMESTAMP())";
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbInsert.php');
	$rc = ciniki_core_dbInsert($ciniki, $strsql, 'businesses');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	if( $rc['num_affected_rows'] > 0 ) {
		return array('stat'=>'ok', 'id'=>$rc['insert_id']);
	}
	return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'166', 'msg'=>'Unable to add owner'));
}
?>
