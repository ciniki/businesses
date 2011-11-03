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
function ciniki_businesses_removeOwner($ciniki) {
	//
	// Find all the required and optional arguments
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbQuote.php');
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
	$ac = ciniki_businesses_checkAccess($ciniki, $args['business_id'], 'ciniki.businesses.removeOwner');
	if( $ac['stat'] != 'ok' ) {
		return $ac;
	}

	//
	// No need for transactions, on one statement
	//

	//
	// Remove the user from the business_users table
	//
	$strsql = "DELETE FROM business_users "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND user_id = '" . ciniki_core_dbQuote($ciniki, $args['user_id']) . "'";
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbDelete.php');
	$rc = ciniki_core_dbDelete($ciniki, $strsql, 'businesses');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	if( $rc['num_affected_rows'] > 0 ) {
		return array('stat'=>'ok');
	}

	return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'156', 'msg'=>'Unable to remove owner'));
}
?>
