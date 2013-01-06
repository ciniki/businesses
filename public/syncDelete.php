<?php
//
// Description
// -----------
// This method will remove a syncronization from the local server, and also
// the remote server.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id: 			The ID of the business to lock.
// sync_id:					The ID of the sync to remove.
//
// Returns
// -------
// <rsp stat="ok" />
//
function ciniki_businesses_syncDelete($ciniki) {
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
	$rc = ciniki_businesses_checkAccess($ciniki, $args['business_id'], 'ciniki.businesses.syncDelete');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDelete');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');

	//
	// Create transaction
	//
	$rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.businesses');
	if( $rc['stat'] != 'ok' ) { 
		return $rc;
	}

	//
	// Grab the sync information
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncLoad');
	$rc = ciniki_core_syncLoad($ciniki, $args['business_id'], $args['sync_id']);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$sync = $rc['sync'];

	//
	// Delete from remote server, if possible
	// Don't error out if there's a problem, just finish and return message.
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncRequest');
	$remote_rc = ciniki_core_syncRequest($ciniki, $sync, array('method'=>'ciniki.core.delete'));
	if( $rc['stat'] != 'ok' ) {
		$remote_rc = $rc;
	}

	//
	// Delete from local server
	//
	$strsql = "DELETE FROM ciniki_business_syncs "
		. "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['sync_id']) . "' "
		. "AND business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "";
	$rc = ciniki_core_dbDelete($ciniki, $strsql, 'ciniki.customers');
	if( $rc['stat'] != 'ok' ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.businesses');
		return $rc;
	}

	//
	// Commit transaction
	//
	$rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.businesses');
	if( $rc['stat'] != 'ok' ) { 
		return $rc;
	}   

	return array('stat'=>'ok');
}
?>
