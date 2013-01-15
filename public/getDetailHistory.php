<?php
//
// Description
// -----------
// This function will get the history of a field from the ciniki_core_change_logs table.
// This allows the user to view what has happened to a data element, and if they
// choose, revert to a previous version.
//
// Info
// ----
// Status: beta
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
//	<history>
//		<action date="2011/02/03 00:03:00" value="Value field set to" user_id="1" />
//		...
//	</history>
//	<users>
//		<user id="1" name="users.display_name" />
//		...
//	</users>
//
function ciniki_businesses_getDetailHistory($ciniki) {
	//
	// Find all the required and optional arguments
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
	$rc = ciniki_core_prepareArgs($ciniki, 'no', array(
		'business_id'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No business specified'), 
		'field'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No user specified'), 
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$args = $rc['args'];
	
	//
	// Check access to business_id as owner, or sys admin
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'checkAccess');
	$ac = ciniki_businesses_checkAccess($ciniki, $args['business_id'], 'ciniki.businesses.getDetailHistory');
	if( $ac['stat'] != 'ok' ) {
		return $ac;
	}

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbGetModuleHistory');
	if( $args['field'] == 'business.name' ) {
		return ciniki_core_dbGetModuleHistory($ciniki, 'ciniki.businesses', 'ciniki_business_history', $args['business_id'], 
			'ciniki_businesses', '', 'name');
	} elseif( $args['field'] == 'business.category' ) {
		return ciniki_core_dbGetModuleHistory($ciniki, 'ciniki.businesses', 'ciniki_business_history', $args['business_id'], 
			'ciniki_businesses', '', 'category');
	} elseif( $args['field'] == 'business.sitename' ) {
		return ciniki_core_dbGetModuleHistory($ciniki, 'ciniki.businesses', 'ciniki_business_history', $args['business_id'], 
			'ciniki_businesses', '', 'sitename');
	} elseif( $args['field'] == 'business.tagline' ) {
		return ciniki_core_dbGetModuleHistory($ciniki, 'ciniki.businesses', 'ciniki_business_history', $args['business_id'], 
			'ciniki_businesses', '', 'tagline');
	}

	return ciniki_core_dbGetModuleHistory($ciniki, 'ciniki.businesses', 'ciniki_business_history', $args['business_id'], 
		'ciniki_business_details', $args['field'], 'detail_value');
}
?>
