<?php
//
// Description
// -----------
// This method is called by syncSetupLocal from the server
// initializating this sync.  
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
function ciniki_businesses_syncActivateRemote($ciniki) {
	//
	// Find all the required and optional arguments
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/prepareArgs.php');
	$rc = ciniki_core_prepareArgs($ciniki, 'no', array(
		'business_uuid'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No business specified'), 
		'url'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No url specified'), 
		'uuid'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No uuid specified'), 
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$args = $rc['args'];

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');

	//
	// Lookup the business id
	//
	$strsql = "SELECT id "
		. "FROM ciniki_businesses "
		. "WHERE ciniki_businesses.uuid = '" . ciniki_core_dbQuote($ciniki, $args['business_uuid']) . "' "
		. "";
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.businesses', 'business');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['business']) || !isset($rc['business']['id']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'528', 'msg'=>'Access denied'));
	}
	$args['business_id']  = $rc['business']['id'];

	//
	// Check access 
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/businesses/private/checkAccess.php');
	$rc = ciniki_businesses_checkAccess($ciniki, $args['business_id'], 'ciniki.businesses.syncActivateRemote');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	$strsql = "UPDATE ciniki_business_syncs SET status = 10 "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND remote_url = '" . ciniki_core_dbQuote($ciniki, $args['url']) . "' "
		. "AND remote_uuid = '" . ciniki_core_dbQuote($ciniki, $args['uuid']) . "' "
		. "";
	$rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.businesses');
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'529', 'msg'=>'Unable to add remote sync', 'err'=>$rc['err']));
	}
	$sync_id = $rc['insert_id'];

	// Update the log
    require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbAddModuleHistory.php');
	ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.businesses', 'ciniki_business_history', $args['business_id'], 
		1, 'ciniki_business_syncs', $sync_id, 'status', '10');

	return array('stat'=>'ok');
}
?>
