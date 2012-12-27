<?php
//
// Description
// -----------
// This method will ping the remote server, using full encryption to make sure
// the two systems can talk to each other.
//
// Returns
// -------
// <rsp stat="ok" />
//
function ciniki_businesses_syncPing($ciniki) {
	//
	// Find all the required and optional arguments
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
	$rc = ciniki_core_prepareArgs($ciniki, 'no', array(
		'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
		'sync_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Sync'), 
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$args = $rc['args'];

	//
	// Check access 
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'checkAccess');
	$rc = ciniki_businesses_checkAccess($ciniki, $args['business_id'], 'ciniki.businesses.syncPing');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	//
	// Get the sync information required to send the request
	//
	$strsql = "SELECT ciniki_businesses.id, ciniki_businesses.uuid AS local_uuid, local_private_key, "
		. "remote_name, remote_uuid, remote_url, remote_public_key "
		. "FROM ciniki_businesses, ciniki_business_syncs "
		. "WHERE ciniki_businesses.id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND ciniki_businesses.id = ciniki_business_syncs.business_id "
		. "AND ciniki_business_syncs.id = '" . ciniki_core_dbQuote($ciniki, $args['sync_id']) . "' "
		. "";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.businesses', 'sync');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['sync']) || !is_array($rc['sync']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'546', 'msg'=>'Invalid sync'));
	}
	$sync = $rc['sync'];
	$sync['type'] = 'business';

	//
	// Make the request
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncRequest');
	$rc = ciniki_core_syncRequest($ciniki, $sync, array('action'=>'ping'));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	return array('stat'=>'ok');	
}
?>
