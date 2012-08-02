<?php
//
// Description
// -----------
// This method will sync the business information
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
function ciniki_businesses_syncNow($ciniki) {
	//
	// Find all the required and optional arguments
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/prepareArgs.php');
	$rc = ciniki_core_prepareArgs($ciniki, 'no', array(
		'business_id'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No business specified'), 
		'sync_id'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No sync specified'), 
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$args = $rc['args'];

	//
	// Check access 
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/businesses/private/checkAccess.php');
	$rc = ciniki_businesses_checkAccess($ciniki, $args['business_id'], 'ciniki.businesses.syncNow');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	require_once($ciniki['config']['core']['modules_dir'] . '/users/private/datetimeFormat.php');	
	$datetime_format = ciniki_users_datetimeFormat($ciniki);

	//
	// Get the sync information
	//
	$strsql = "SELECT id, business_id, flags, status, "
		. "local_private_key, remote_name, remote_url, remote_uuid, remote_public_key, "
		. "UNIX_TIMESTAMP(last_sync) AS last_sync "
		. "FROM ciniki_business_syncs "
		. "WHERE ciniki_business_syncs.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' " 
		. "";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.businesses', 'sync');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	
	return array('stat'=>'ok', 'name'=>$ciniki['config']['core']['sync.name'], 'uuid'=>$uuid, 'local_url'=>$ciniki['config']['core']['sync.url'], 'syncs'=>$rc['syncs']);
}
?>
