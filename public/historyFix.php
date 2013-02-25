<?php
//
// Description
// -----------
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_businesses_historyFix($ciniki) {
	//
	// Find all the required and optional arguments
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
	$rc = ciniki_core_prepareArgs($ciniki, 'no', array(
		'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$args = $rc['args'];
	
	//
	// Check access to business_id as owner, or sys admin
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'checkAccess');
	$rc = ciniki_businesses_checkAccess($ciniki, $args['business_id'], 'ciniki.businesses.historyFix', 0);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDelete');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbFixTableHistory');

	//
	// Remove old incorrect formatted entries
	//
	$strsql = "DELETE FROM ciniki_business_history "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND table_name = 'ciniki_business_users' "
		. "AND table_field LIKE '%.%.%' "
		. "";
	$rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.businesses');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	$strsql = "DELETE FROM ciniki_business_history "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND table_name = 'ciniki_business_users' "
		. "AND table_key LIKE '%.%.%' "
		. "";
	$rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.businesses');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	$strsql = "DELETE FROM ciniki_business_history "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND table_name = 'ciniki_business_user_details' "
		. "AND table_field LIKE '%.%' "
		. "";
	$rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.businesses');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	// Remote entries with blank table_field
	$strsql = "DELETE FROM ciniki_business_history "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND table_name = 'ciniki_businesses' "
		. "AND table_field = '' "
		. "";
	$rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.businesses');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	

	//
	// Add the proper history for ciniki_business_users
	//
	$rc = ciniki_core_dbFixTableHistory($ciniki, 'ciniki.businesses', $args['business_id'],
		'ciniki_business_users', 'ciniki_business_history',
		array('uuid', 'user_id', 'package', 'permission_group', 'status'));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	$rc = ciniki_core_dbFixTableHistory($ciniki, 'ciniki.businesses', $args['business_id'],
		'ciniki_business_user_details', 'ciniki_business_history',
		array('uuid', 'user_id', 'detail_key', 'detail_value'));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	//
	// Check for items missing a UUID
	//
	$strsql = "UPDATE ciniki_business_history SET uuid = UUID() WHERE uuid = ''";
	$rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.businesses');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	return array('stat'=>'ok');
}
?>
