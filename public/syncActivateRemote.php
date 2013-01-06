<?php
//
// Description
// -----------
// This method is called by syncSetupLocal from the server
// initializating this sync.  
//
// Arguments
// ---------
//
// Returns
// -------
// <rsp stat="ok" />
//
function ciniki_businesses_syncActivateRemote($ciniki) {
	//
	// Find all the required and optional arguments
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
	$rc = ciniki_core_prepareArgs($ciniki, 'no', array(
		'business_uuid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
		'url'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'URL'), 
		'uuid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'UUID'), 
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
	$strsql = "SELECT ciniki_businesses.id, ciniki_business_syncs.id AS sync_id "
		. "FROM ciniki_businesses, ciniki_business_syncs "
		. "WHERE ciniki_businesses.uuid = '" . ciniki_core_dbQuote($ciniki, $args['business_uuid']) . "' "
		. "AND ciniki_businesses.id = ciniki_business_syncs.business_id "
		. "AND remote_url = '" . ciniki_core_dbQuote($ciniki, $args['url']) . "' "
		. "AND remote_uuid = '" . ciniki_core_dbQuote($ciniki, $args['uuid']) . "' "
		. "";
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.businesses', 'business');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['business']) || !isset($rc['business']['id']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'528', 'msg'=>'Access denied'));
	}
	$args['business_id']  = $rc['business']['id'];
	$args['sync_id']  = $rc['business']['sync_id'];

	//
	// Check access 
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'checkAccess');
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

	// Update the log
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
	ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.businesses', 'ciniki_business_history', $args['business_id'], 
		1, 'ciniki_business_syncs', $args['sync_id'], 'status', '10');

	return array('stat'=>'ok');
}
?>
