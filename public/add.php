<?php
//
// Description
// -----------
// This function will add a new business.  You must be a sys admin to 
// be authorized to add a business.
//
// Arguments
// ---------
// api_key:
// auth_token:
// 
//
// Returns
// -------
// <rsp stat='ok' id='new business id' />
//
function ciniki_businesses_add($ciniki) {
	//
	// Find all the required and optional arguments
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/prepareArgs.php');
	$rc = ciniki_core_prepareArgs($ciniki, 'no', array(
		'business.name'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No business name specified'), 
		'business.sitename'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'errmsg'=>'No business sitename specified'), 
		'business.tagline'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'errmsg'=>'No business tagline specified'), 
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$args = $rc['args'];

	//
	// Check access to business_id as owner, or sys admin
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/businesses/private/checkAccess.php');
	$ac = ciniki_businesses_checkAccess($ciniki, 0, 'ciniki.businesses.add');
	if( $ac['stat'] != 'ok' ) {
		return $ac;
	}

	//
	// Check the sitename is proper format
	//
	if( preg_match('/[^a-z0-9\-_]/', $args['business.sitename']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'162', 'msg'=>'Illegal characters in sitename.  It can only contain lowercase letters, numbers, underscores (_) or dash (-)'));
	}
	
	//
	// Load required functions
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbQuote.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbInsert.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbAddModuleHistory.php');

	//
	// Turn off autocommit
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbTransactionStart.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbTransactionRollback.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbTransactionCommit.php');
	$rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.businesses');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	//
	// Add the business to the database
	//
	$strsql = "INSERT INTO ciniki_businesses (uuid, name, sitename, tagline, status, date_added, last_updated) VALUES ("
		. "UUID(), "
		. "'" . ciniki_core_dbQuote($ciniki, $args['business.name']) . "' "
		. ", '" . ciniki_core_dbQuote($ciniki, $args['business.sitename']) . "' "
		. ", '" . ciniki_core_dbQuote($ciniki, $args['business.tagline']) . "' "
		. ", 1, UTC_TIMESTAMP(), UTC_TIMESTAMP())";
	$rc = ciniki_core_dbInsert($ciniki, $strsql, 'ciniki.businesses');
	if( $rc['stat'] != 'ok' ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.businesses');
		return $rc;
	}
	if( !isset($rc['insert_id']) || $rc['insert_id'] < 1 ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.businesses');
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'159', 'msg'=>'Unable to add business'));
	}
	$business_id = $rc['insert_id'];
	ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.businesses', 'ciniki_business_history', $business_id, 
		1, 'ciniki_businesses', $business_id, 'name', $args['business.name']);
	ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.businesses', 'ciniki_business_history', $business_id, 
		1, 'ciniki_businesses', $business_id, 'tagline', $args['business.tagline']);
	ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.businesses', 'ciniki_business_history', $business_id, 
		1, 'ciniki_businesses', $business_id, 'sitename', $args['business.sitename']);
	ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.businesses', 'ciniki_business_history', $business_id, 
		1, 'ciniki_businesses', $business_id, 'status', '1');

	if( $business_id < 1 ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.businesses');
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'161', 'msg'=>'Unable to add business'));
	}

	//
	// Allowed business detail keys 
	//
	$allowed_keys = array(
		'contact.address.street1',
		'contact.address.street2',
		'contact.address.city',
		'contact.address.province',
		'contact.address.postal',
		'contact.address.country',
		'contact.person.name',
		'contact.phone.number',
		'contact.tollfree.number',
		'contact.fax.number',
		'contact.email.address',
		);
	foreach($ciniki['request']['args'] as $arg_name => $arg_value) {
		if( in_array($arg_name, $allowed_keys) ) {
			$strsql = "INSERT INTO ciniki_business_details (business_id, detail_key, detail_value, date_added, last_updated) "
				. "VALUES ('" . ciniki_core_dbQuote($ciniki, $business_id) . "', "
				. "'" . ciniki_core_dbQuote($ciniki, $arg_name) . "', "
				. "'" . ciniki_core_dbQuote($ciniki, $arg_value) . "', "
				. "UTC_TIMESTAMP(), UTC_TIMESTAMP()) ";
			$rc = ciniki_core_dbInsert($ciniki, $strsql, 'ciniki.businesses');
			if( $rc['stat'] != 'ok' ) {
				ciniki_core_dbTransactionRollback($ciniki, 'ciniki.businesses');
				return $rc;
			}
			ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.businesses', 'ciniki_business_history', $business_id, 
				1, 'ciniki_business_details', $arg_name, 'detail_value', $arg_value);
		}
	}

	$rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.businesses');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	return array('stat'=>'ok', 'id'=>$business_id);
}
?>
