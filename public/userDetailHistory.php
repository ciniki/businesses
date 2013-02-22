<?php
//
// Description
// -----------
// This function will get the history of a field from the ciniki_core_change_logs table.
// This allows the user to view what has happened to a data element, and if they
// choose, revert to a previous version.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:			The ID of the business to get the details for.
// key:					The detail key to get the history for.
//
// Returns
// -------
//
function ciniki_businesses_userDetailHistory($ciniki) {
	//
	// Find all the required and optional arguments
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
	$rc = ciniki_core_prepareArgs($ciniki, 'no', array(
		'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
		'user_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'User'), 
		'field'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Field'), 
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$args = $rc['args'];
	
	//
	// Check access to business_id as owner, or sys admin
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'checkAccess');
	$rc = ciniki_businesses_checkAccess($ciniki, $args['business_id'], 'ciniki.businesses.userDetailHistory');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	$strsql = "SELECT id FROM ciniki_business_user_details "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND user_id = '" . ciniki_core_dbQuote($ciniki, $args['user_id']) . "' "
		. "AND detail_key = '" . ciniki_core_dbQuote($ciniki, $args['field']) . "' "
		. "";
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.businesses', 'detail');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( isset($rc['detail']) ) {
		$detail_id = $rc['detail']['id'];
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbGetModuleHistory');
		return ciniki_core_dbGetModuleHistory($ciniki, 'ciniki.businesses', 
			'ciniki_business_history', $args['business_id'], 
			'ciniki_business_user_details', $detail_id, 'detail_value', '');
	} 

	return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'999', 'msg'=>'Unable to find any history'));
}
?>
