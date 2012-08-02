<?php
//
// Description
// -----------
// This method will set the logo image ID for a business.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:		The ID of the business to set the logo image ID.
// image_id:		The ID of the image in the ciniki images modules 
//					to be used as the business logo.
// 
// Example Return
// --------------
// <rsp stat="ok" logo_id="4" />
//
function ciniki_businesses_logoSave(&$ciniki) {
	//
	// Check args
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/prepareArgs.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbQuote.php');
	$rc = ciniki_core_prepareArgs($ciniki, 'no', array(
		'business_id'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No business specified'), 
		'image_id'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No image specified'), 
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$args = $rc['args'];

	//
	// Check access 
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/businesses/private/checkAccess.php');
	$rc = ciniki_businesses_checkAccess($ciniki, $args['business_id'], 'ciniki.businesses.logoSave');
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
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'747', 'msg'=>'Internal Error', 'err'=>$rc['err']));
	}   

	//
	// Update business with new image id
	//
	$strsql = "UPDATE ciniki_businesses SET logo_id = '" . ciniki_core_dbQuote($ciniki, $args['image_id']) . "' "
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
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'748', 'msg'=>'Unable to save logo', 'err'=>$rc['err']));
	}

	return array('stat'=>'ok', 'logo_id'=>$args['image_id']);
}
?>
