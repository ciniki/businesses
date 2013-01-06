<?php
//
// Description
// -----------
// This method will return the information about a syncronization. 
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:		The ID of the business to get the sync information for.
// sync_id:			The ID of the syncronization to get the information for.
//
// Returns
// -------
// <rsp stat="ok" />
//
function ciniki_businesses_syncDetails($ciniki) {
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
	$rc = ciniki_businesses_checkAccess($ciniki, $args['business_id'], 'ciniki.businesses.syncDetails');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'datetimeFormat');
	$datetime_format = ciniki_users_datetimeFormat($ciniki);

	//
	// Get the information for the syncronization
	//
	$strsql = "SELECT id, business_id, flags, status, "
		. "remote_name, remote_url, remote_uuid, "
		. "DATE_FORMAT(date_added, '" . ciniki_core_dbQuote($ciniki, $datetime_format) . "') as date_added, "
		. "DATE_FORMAT(last_updated, '" . ciniki_core_dbQuote($ciniki, $datetime_format) . "') as last_updated, "
		. "DATE_FORMAT(last_sync, '" . ciniki_core_dbQuote($ciniki, $datetime_format) . "') as last_sync, "
		. "DATE_FORMAT(last_partial, '" . ciniki_core_dbQuote($ciniki, $datetime_format) . "') as last_partial, "
		. "DATE_FORMAT(last_full, '" . ciniki_core_dbQuote($ciniki, $datetime_format) . "') as last_full "
		. "FROM ciniki_business_syncs "
		. "WHERE ciniki_business_syncs.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' " 
		. "AND ciniki_business_syncs.id = '" . ciniki_core_dbQuote($ciniki, $args['sync_id']) . "' " 
		. "";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.businesses', 'sync');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['sync']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'506', 'msg'=>'Unable to find syncronization'));
	}

	if( ($rc['sync']['flags']&0x03) == 0x03 ) {
		$rc['sync']['type'] = 'bi';
	} elseif( ($rc['sync']['flags']&0x01) == 0x01 ) {
		$rc['sync']['type'] = 'push';
	} elseif( ($rc['sync']['flags']&0x02) == 0x02 ) {
		$rc['sync']['type'] = 'pull';
	}

	if( !isset($rc['sync']['last_sync']) ) {
		$rc['sync']['last_sync'] = 'never';
	}

	if( $rc['sync']['status'] == 10 ) {
		$rc['sync']['status_text'] = 'active';
//	} elseif( $rc['sync']['status_text'] == 20 ) {
//		$rc['sync']['status'] = 'paused';
	} elseif( $rc['sync']['status'] == 60 ) {
		$rc['sync']['status_text'] = 'suspended';
	} else {
		$rc['sync']['status_text'] = 'unknown';
	}
	
	return array('stat'=>'ok', 'sync'=>$rc['sync']); 
}
?>
