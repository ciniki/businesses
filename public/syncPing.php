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

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncLoad');
	$rc = ciniki_core_syncLoad($ciniki, $args['business_id'], $args['sync_id']);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$sync = $rc['sync'];

	//
	// Make the request
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncRequest');
	$rc = ciniki_core_syncRequest($ciniki, $sync, array('method'=>'ciniki.core.ping'));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	return array('stat'=>'ok');	
}
?>
