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
function ciniki_businesses_syncInfo($ciniki) {
	//
	// Find all the required and optional arguments
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/prepareArgs.php');
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
	$rc = ciniki_businesses_checkAccess($ciniki, $args['business_id'], 'ciniki.businesses.syncInfo');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	require_once($ciniki['config']['core']['modules_dir'] . '/users/private/datetimeFormat.php');	
	$datetime_format = ciniki_users_datetimeFormat($ciniki);

	//
	// Get the local business information
	//
	$strsql = "SELECT uuid "
		. "FROM ciniki_businesses "
		. "WHERE ciniki_businesses.id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' " 
		. "";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.businesses', 'business');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['business']) ) {	
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'523', 'msg'=>'Unable to get business information'));
	}
	$uuid = $rc['business']['uuid'];

	//
	// Get the list of syncs setup for this business
	//
	$strsql = "SELECT id, business_id, flags, status, "
		. "remote_name, remote_url, remote_uuid, "
		. "DATE_FORMAT(last_sync, '" . ciniki_core_dbQuote($ciniki, $datetime_format) . "') as last_sync "
		. "FROM ciniki_business_syncs "
		. "WHERE ciniki_business_syncs.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' " 
		. "";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbRspQuery');
	$rc = ciniki_core_dbRspQuery($ciniki, $strsql, 'ciniki.businesses', 'syncs', 'sync', array('stat'=>'ok', 'syncs'=>array()));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	foreach($rc['syncs'] as $i => $sync) {
		if( ($sync['sync']['flags']&0x01) == 0x01 ) {
			$rc['syncs'][$i]['sync']['type'] = 'push';
		} elseif( ($sync['sync']['flags']&0x02) == 0x02 ) {
			$rc['syncs'][$i]['sync']['type'] = 'pull';
		} elseif( ($sync['sync']['flags']&0x03) == 0x03 ) {
			$rc['syncs'][$i]['sync']['type'] = 'bi';
		}
	}
	
	return array('stat'=>'ok', 'name'=>$ciniki['config']['core']['sync.name'], 'uuid'=>$uuid, 'local_url'=>$ciniki['config']['core']['sync.url'], 'syncs'=>$rc['syncs']);
}
?>
