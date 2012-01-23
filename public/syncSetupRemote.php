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
function ciniki_businesses_syncSetupRemote($ciniki) {
	//
	// Find all the required and optional arguments
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/prepareArgs.php');
	$rc = ciniki_core_prepareArgs($ciniki, 'no', array(
		'business_uuid'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No business specified'), 
		'type'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No business specified'), 
		'name'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No business specified'), 
		'url'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No business specified'), 
		'uuid'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No business specified'), 
		'public_key'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No business specified'), 
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$args = $rc['args'];

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbInsert');

	//
	// Lookup the business id
	//
	$strsql = "SELECT id "
		. "FROM ciniki_businesses "
		. "WHERE ciniki_businesses.uuid = '" . ciniki_core_dbQuote($ciniki, $args['business_uuid']) . "' "
		. "";
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'businesses', 'business');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['business']) || !isset($rc['business']['id']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'524', 'msg'=>'Access denied'));
	}
	$args['business_id']  = $rc['business']['id'];

	//
	// Check access 
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/businesses/private/checkAccess.php');
	$rc = ciniki_businesses_checkAccess($ciniki, $args['business_id'], 'ciniki.businesses.syncInfo');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	//
	// Start transaction
	//
	$rc = ciniki_core_dbTransactionStart($ciniki, 'businesses');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	// 
	// Create private/public keys
	//
	$k = openssl_pkey_new();
	openssl_pkey_export($k, $private_str);
	$p = openssl_pkey_get_details($k);
	$public_str = $p['key'];

	//
	// Add sync to the database, with status of unknown (0)
	//
	$flags = 0;
	if( $args['type'] == 'push' ) {
		$flags = 0x01;
	} else if( $args['type'] == 'pull' ) {
		$flags = 0x02;
	}
	$strsql = "INSERT INTO ciniki_business_syncs (business_id, flags, status, "
		. "local_private_key, remote_name, remote_uuid, remote_url, remote_public_key, "
		. "date_added, last_updated) VALUES ("
		. "'" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. ", '" . ciniki_core_dbQuote($ciniki, $flags) . "' "
		. ", 0 "
		. ", '" . ciniki_core_dbQuote($ciniki, $private_str) . "' "
		. ", '" . ciniki_core_dbQuote($ciniki, $args['name']) . "' "
		. ", '" . ciniki_core_dbQuote($ciniki, $args['uuid']) . "' "
		. ", '" . ciniki_core_dbQuote($ciniki, $args['url']) . "' "
		. ", '" . ciniki_core_dbQuote($ciniki, $args['public_key']) . "' "
		. ", UTC_TIMESTAMP(), UTC_TIMESTAMP() "
		. ")"; 
	$rc = ciniki_core_dbInsert($ciniki, $strsql, 'businesses');
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'525', 'msg'=>'Unable to add remote sync', 'err'=>$rc['err']));
	}

	//
	// Create transaction
	//
	$rc = ciniki_core_dbTransactionCommit($ciniki, 'businesses');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	
	return array('stat'=>'ok', 'public_key'=>$public_str, 'sync_url'=>$ciniki['config']['core']['sync.url']);
}
?>
