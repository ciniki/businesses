<?php
//
// Description
// -----------
// This method will return the history from the business module.
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_businesss_sync_historyList($ciniki, $sync, $business_id, $args) {
	//
	// Check the args
	//
	if( !isset($args['type']) ||
		($args['type'] != 'partial' && $args['type'] != 'full' && $args['type'] != 'incremental') ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'906', 'msg'=>'No type specified'));
	}
	if( $args['type'] == 'incremental' 
		&& (!isset($args['since_uts']) || $args['since_uts'] == '') ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'907', 'msg'=>'No timestamp specified'));
	}

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQueryList2');

	//
	// Prepare the query to fetch the list
	//
	$strsql = "SELECT uuid, UNIX_TIMESTAMP(log_date) AS log_date "	
		. "FROM ciniki_business_history "
		. "WHERE ciniki_business_history.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "AND (table_name = 'ciniki_business_users' OR table_name = 'ciniki_business_user_details') "
		. "";
	if( $args['type'] == 'incremental' ) {
		$strsql .= "AND UNIX_TIMESTAMP(ciniki_business_history.log_date) >= '" . ciniki_core_dbQuote($ciniki, $args['since_uts']) . "' ";
	}
	$strsql .= "ORDER BY log_date "
		. "";
	$rc = ciniki_core_dbQueryList2($ciniki, $strsql, 'ciniki.businesss', 'history');
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'908', 'msg'=>'Unable to get list', 'err'=>$rc['err']));
	}

	if( !isset($rc['history']) ) {
		return array('stat'=>'ok', 'history'=>array());
	}

//error_log(print_r($rc['history'], true));
	return array('stat'=>'ok', 'history'=>$rc['history']);
}
?>
