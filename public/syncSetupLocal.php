<?php
//
// Description
// -----------
// This method will return the information about the local server, and the list of replications
// connected to this server.
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
function ciniki_businesses_syncSetupLocal($ciniki) {
	//
	// Find all the required and optional arguments
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/prepareArgs.php');
	$rc = ciniki_core_prepareArgs($ciniki, 'no', array(
		'business_id'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No business specified'), 
		'remote_name'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No name specified'), 
		'remote_uuid'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No remote uuid specified'), 
		'type'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No sync type specified'), 
		'json_api'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No json API specified'), 
		'username'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No remote username specified'), 
		'password'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No remote password specified'), 
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$args = $rc['args'];

	//
	// Check access 
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/businesses/private/checkAccess.php');
	$rc = ciniki_businesses_checkAccess($ciniki, $args['business_id'], 'ciniki.businesses.syncSetupLocal');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbTransactionStart.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbTransactionRollback.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbTransactionCommit.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbQuote.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbUpdate.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbAddChangeLog.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/cinikiAPIAuth.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/cinikiAPIGet.php');

	//
	// Get the business uuid
	//
	$strsql = "SELECT uuid "
		. "FROM ciniki_businesses "
		. "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "";
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'businesses', 'business');
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
	if( !isset($rc['business']) || !isset($rc['business']['uuid']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'521', 'msg'=>'Internal error'));
	}
	$business_uuid  = $rc['business']['uuid'];

	//
	// Create transaction
	//
	$rc = ciniki_core_dbTransactionStart($ciniki, 'businesses');
	if( $rc['stat'] != 'ok' ) { 
		return $rc;
	}   

	//
	// auth against remote machine
	//
	$rc = ciniki_core_cinikiAPIAuth($ciniki, $args['json_api'], $args['username'], $args['password']);
	if( $rc['stat'] != 'ok' ) { 
		return $rc;
	}
	$api = $rc['api'];

	//
	// create local private/public keys
	//
	$k = openssl_pkey_new();
	openssl_pkey_export($k, $private_str);
	$p = openssl_pkey_get_details($k);
	$public_str = $p['key'];

	//
	// call syncSetupRemote
	//
	if( $args['type'] == 'push' ) {
		$remote_type = 'pull';
		$flags = 0x01;
	else if( $args['type'] == 'pull' ) {
		$remote_type = 'push';
		$flags = 0x02;
	} else {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'520', 'msg'=>'The type must be push or pull'));
	}
	$remote_args = array(
		'business_uuid'=>$args['business_uuid'],
		'type'=>$remote_type,
		'name'=>$ciniki['config']['core']['sync.name'],
		'url'=>$ciniki['config']['core']['sync.url'],
		'uuid'=>$business_uuid,
		'public_key'=>$public_str,
	);
	$rc = ciniki_core_cinikiAPIGet($ciniki, $api, 'ciniki.businesses.syncSetupRemote', $remote_args);
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'526', 'msg'=>'Unable to add remote sync', 'err'=>$rc['err']));
	}

	//
	// Add sync to local
	//
	$strsql = "INSERT INTO ciniki_business_syncs (business_id, flags, status, "
		. "local_private_key, remote_name, remote_uuid, remote_url, remote_public_key, "
		. "date_added, last_updated) VALUES ("
		. "'" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. ", '" . ciniki_core_dbQuote($ciniki, $flags) . "' "
		. ", 10 " 
		. ", '" . ciniki_core_dbQuote($ciniki, $private_str) . "' "
		. ", ' . ciniki_core_dbQuote($ciniki, $args['remote_name']) . "' "
		. ", ' . ciniki_core_dbQuote($ciniki, $args['remote_uuid']) . "' "
		. ", ' . ciniki_core_dbQuote($ciniki, $rc['sync_url']) . "' "
		. ", ' . ciniki_core_dbQuote($ciniki, $rc['public_key']) . "' "
		. ", UTC_TIMESTAMP(), UTC_TIMESTAMP() "
		. ")"; 
	$rc = ciniki_core_dbInsert($ciniki, $strsql, 'businesses');
	if( $rc['stat'] != 'ok' ) { 
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'527', 'msg'=>'Unable to add remote sync', 'err'=>$rc['err']));
	}

	//
	// call syncActivateRemote
	//
	$remote_args = array(
		'business_uuid'=>$args['business_uuid'],
		'url'=>$ciniki['config']['core']['sync.url'],
		'uuid'=>$business_uuid,
	);
	$rc = ciniki_core_cinikiAPIGet($ciniki, $api, 'ciniki.businesses.syncActivateRemote', $remote_args);
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'528', 'msg'=>'Unable to add remote sync', 'err'=>$rc['err']));
	}

	$rc = ciniki_core_cinikiAPIGet($ciniki, $api, 'ciniki.users.logout', array());
	// Ignore response

	//
	// Commit transaction
	//
	$rc = ciniki_core_dbTransactionCommit($ciniki, 'businesses');
	if( $rc['stat'] != 'ok' ) { 
		return $rc;
	}   

	return array('stat'=>'ok');
}
?>
