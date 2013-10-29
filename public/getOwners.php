<?php
//
// Description
// -----------
// This function will retrieve the users who are owners or employee's 
// of the specified business.  No customers will be returned in this query,
// unless they are also an owner or employee of this business.
//
// *note* Only business owners are implemented, employee's will be in the future.
//
// Info
// ----
// Status: beta
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:			The ID of the business to get the users for.
//
// Returns
// -------
// <users>
//		<user id='1' email='' firstname='' lastname='' display_name='' />
// </users>
//
function ciniki_businesses_getOwners($ciniki) {
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
	$ac = ciniki_businesses_checkAccess($ciniki, $args['business_id'], 'ciniki.businesses.getOwners');
	if( $ac['stat'] != 'ok' ) {
		return $ac;
	}

	//
	// Query for the business users
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
	$strsql = "SELECT user_id FROM ciniki_business_users "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND type = 1 ";
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.businesses', 'business');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( $rc['num_rows'] < 1 ) {
		return array('stat'=>'ok', 'users'=>array());
	}

	$user_id_list = array();
	$strsql = "SELECT id, email, firstname, lastname, display_name FROM ciniki_users "
		. "WHERE id IN (";
	$comma = '';
	foreach($rc['rows'] as $i) {
		$strsql .= $comma . ciniki_core_dbQuote($ciniki, $i['user_id']);
		$comma = ', ';
	}
	$strsql .= ") ORDER BY lastname, firstname";
	
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbRspQuery');
	return ciniki_core_dbRspQuery($ciniki, $strsql, 'ciniki.users', 'users', 'user', array('stat'=>'ok', 'users'=>array()));
}
?>
