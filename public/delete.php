<?php
//
// Description
// -----------
// This function will mark a business as deleted, but not remove any information.
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
function ciniki_businesses_lock($ciniki) {
	//
	// Find all the required and optional arguments
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/prepareArgs.php');
	$rc = ciniki_core_prepareArgs($ciniki, 'no', array(
		'id'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No business specified'), 
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$args = $rc['args'];

	//
	// Check access 
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/businesses/private/checkAccess.php');
	$rc = ciniki_businesses_checkAccess($ciniki, $args['id'], 'ciniki.businesses.delete');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbQuoteRequestArg.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbUpdate.php');
	$strsql = "UPDATE ciniki_businesses SET status = 60 "
		. "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['id']) . "'";
	return ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.businesses');
}
?>
