<?php
//
// Description
// -----------
// This method will remove the logo image from the business.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:		The ID of the business to delete the logo from.
// 
// Example Return
// --------------
// <rsp stat="ok" />
//
function ciniki_businesses_logoDelete(&$ciniki) {
	//
	// Check args
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/prepareArgs.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbQuote.php');
	$rc = ciniki_core_prepareArgs($ciniki, 'no', array(
		'business_id'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No business specified'), 
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$args = $rc['args'];

	//
	// Check access 
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/businesses/private/checkAccess.php');
	$rc = ciniki_businesses_checkAccess($ciniki, $args['business_id'], 'ciniki.businesses.logoDelete');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	//
	// Start transaction
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbTransactionStart.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbTransactionRollback.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbTransactionCommit.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbHashQuery.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbUpdate.php');
	$rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.businesses');
	if( $rc['stat'] != 'ok' ) { 
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'745', 'msg'=>'Internal Error', 'err'=>$rc['err']));
	}   

	//
	// Remove logo_id from business
	//
	$strsql = "UPDATE ciniki_businesses SET logo_id = 0 "
		. ", last_updated = UTC_TIMESTAMP() "
		. "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' ";
	$rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.businesses');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	
	//
	// Commit the transaction
	//
	$rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.businesses');
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'746', 'msg'=>'Unable to delete logo', 'err'=>$rc['err']));
	}

	return array('stat'=>'ok');
}
?>
