<?php
//
// Description
// -----------
// This function will suspend a business.
//
// Arguments
// ---------
// api_key:
// auth_token:
// id: 			The ID of the business to archive.
//
// Returns
// -------
// <rsp stat="ok" />
//
function ciniki_businesses_suspend($ciniki) {
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
	$rc = ciniki_businesses_checkAccess($ciniki, $args['id'], 'ciniki.businesses.suspend');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbQuoteRequestArg.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbUpdate.php');
	$strsql = "UPDATE ciniki_businesses SET status = 50 "
		. "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['id']) . "'";
	// Update the log
    require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbAddModuleHistory.php');
	ciniki_core_dbAddModuleHistory($ciniki, 'businesses', 'ciniki_business_history', $args['business_id'], 
		2, 'ciniki_businesses', $args['business_id'], 'status', $args[$field]);

	return ciniki_core_dbUpdate($ciniki, $strsql, 'businesses');
}
?>
