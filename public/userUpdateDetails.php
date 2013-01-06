<?php
//
// Description
// -----------
// This method will update the details for a user of a business. 
//
// Arguments
// ---------
// business_id:					The ID of the business to get the details for.
// user_id:						The ID of the user to set the details for.
// business.title:				(optional) The name to set for the user of the business.
// contact.phone.number:		(optional) The contact phone number for the user of the business.  
// contact.cell.number:			(optional) The cell number for the user of the business.
// contact.fax.number:			(optional) The fax number for the user of the business.
// contact.email.address:		(optional) The contact email address for the user of the business.
//
// Returns
// -------
// <rsp stat='ok' />
//
function ciniki_businesses_userUpdateDetails(&$ciniki) {
	//
	// Find all the required and optional arguments
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
	$rc = ciniki_core_prepareArgs($ciniki, 'no', array(
		'business_id'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No business specified'), 
		'user_id'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No user specified'), 
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$args = $rc['args'];
	
	//
	// Check access to business_id as owner
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'checkAccess');
	$ac = ciniki_businesses_checkAccess($ciniki, $args['business_id'], 'ciniki.businesses.userUpdateDetails');
	if( $ac['stat'] != 'ok' ) {
		return $ac;
	}

	// Required functions
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbInsert');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');

	//
	// Turn off autocommit
	//
	$rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.businesses');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	//
	// Allowed business user detail keys 
	//
	$allowed_keys = array(
		'employee.title',
		'contact.phone.number',
		'contact.cell.number',
		'contact.fax.number',
		'contact.email.address',
		);
	foreach($ciniki['request']['args'] as $arg_name => $arg_value) {
		if( in_array($arg_name, $allowed_keys) ) {
			$strsql = "INSERT INTO ciniki_business_user_details (business_id, user_id, detail_key, detail_value, date_added, last_updated) "
				. "VALUES ('" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "'"
				. ", '" . ciniki_core_dbQuote($ciniki, $args['user_id']) . "'"
				. ", '" . ciniki_core_dbQuote($ciniki, $arg_name) . "'"
				. ", '" . ciniki_core_dbQuote($ciniki, $arg_value) . "'"
				. ", UTC_TIMESTAMP(), UTC_TIMESTAMP()) "
				. "ON DUPLICATE KEY UPDATE detail_value = '" . ciniki_core_dbQuote($ciniki, $arg_value) . "' "
				. ", last_updated = UTC_TIMESTAMP() "
				. "";
			$rc = ciniki_core_dbInsert($ciniki, $strsql, 'ciniki.businesses');
			if( $rc['stat'] != 'ok' ) {
				ciniki_core_dbTransactionRollback($ciniki, 'ciniki.businesses');
				return $rc;
			}
			ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.businesses', 'ciniki_business_history', $args['business_id'], 
				2, 'ciniki_business_user_details', $args['user_id'], $arg_name, $arg_value);
		}
	}

	//
	// Check for web options
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'checkModuleAccess');
	$rc = ciniki_businesses_checkModuleAccess($ciniki, $args['business_id'], 'ciniki', 'web');
	if( $rc['stat'] == 'ok' ) {
		$field = 'page-contact-user-display-flags-' . $args['user_id'];
		if( isset($ciniki['request']['args'][$field]) && $ciniki['request']['args'][$field] != '') {
			// Update the web module
			$strsql = "INSERT INTO ciniki_web_settings (business_id, detail_key, detail_value, date_added, last_updated) "
				. "VALUES ('" . ciniki_core_dbQuote($ciniki, $ciniki['request']['args']['business_id']) . "'"
				. ", '" . ciniki_core_dbQuote($ciniki, $field) . "' "
				. ", '" . ciniki_core_dbQuote($ciniki, $ciniki['request']['args'][$field]) . "'"
				. ", UTC_TIMESTAMP(), UTC_TIMESTAMP()) "
				. "ON DUPLICATE KEY UPDATE detail_value = '" . ciniki_core_dbQuote($ciniki, $ciniki['request']['args'][$field]) . "' "
				. ", last_updated = UTC_TIMESTAMP() "
				. "";
			$rc = ciniki_core_dbInsert($ciniki, $strsql, 'ciniki.web');
			if( $rc['stat'] != 'ok' ) {
				ciniki_core_dbTransactionRollback($ciniki, 'ciniki.web');
				return $rc;
			}
			ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.web', 'ciniki_web_history', $args['business_id'], 
				2, 'ciniki_web_settings', $field, 'detail_value', $ciniki['request']['args'][$field]);
			//
			// Update the page-contact-user-display field
			// - this function will update last_change for web module.
			//
			ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'updateUserDisplay');
			$rc = ciniki_web_updateUserDisplay($ciniki, $args['business_id']);
			if( $rc['stat'] != 'ok' ) {
				ciniki_core_dbTransactionRollback($ciniki, 'ciniki.businesses');
				return $rc;
			}
		}
	}

	$rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.businesses');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	//
	// Update the last_updated for the business user
	//
	$strsql = "UPDATE ciniki_business_users SET last_updated = UTC_TIMESTAMP() "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND user_id = '" . ciniki_core_dbQuote($ciniki, $args['user_id']) . "' "
		. "";
	$rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.users');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	//
	// Get the list of businesses this user is part of, and replicate that user for that business
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'updateModuleChangeDate');
	ciniki_businesses_updateModuleChangeDate($ciniki, $args['business_id'], 'ciniki', 'businesses');
	$ciniki['syncqueue'][] = array('method'=>'ciniki.businesses.syncPushUser', 'args'=>array('id'=>$args['user_id']));

	return array('stat'=>'ok');
}
?>
