<?php
//
// Description
// -----------
// This method will update the intl settings for a business.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:			The ID of the business to get the intl settings for.
//
// Returns
// -------
// <settings intl-default-locale="en_US"
//
function ciniki_businesses_settingsIntlUpdate($ciniki) {
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
	$ac = ciniki_businesses_checkAccess($ciniki, $args['business_id'], 'ciniki.businesses.settingsIntlUpdate');
	if( $ac['stat'] != 'ok' ) {
		return $ac;
	}

	//
	// Turn off autocommit
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbInsert');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
	$rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.businesses');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	//
	// FIXME: Add check that the values submitted are valid
	//

	//
	// Allowed business detail keys 
	//
	$allowed_keys = array(
		'intl-default-locale',
		'intl-default-currency',
		'intl-default-timezone',
		);
	foreach($ciniki['request']['args'] as $arg_name => $arg_value) {
		if( in_array($arg_name, $allowed_keys) ) {
			$strsql = "INSERT INTO ciniki_business_details (business_id, "
				. "detail_key, detail_value, date_added, last_updated) "
				. "VALUES ('" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "'"
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
			ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.businesses', 'ciniki_business_history', 
				$args['business_id'], 2, 'ciniki_business_details', $arg_name, 'detail_value', $arg_value);
			$ciniki['syncqueue'][] = array('push'=>'ciniki.businesses.details', 
				'args'=>array('id'=>$arg_name));
		}
	}

	//
	// Commit the transaction
	//
	$rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.businesses');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	return array('stat'=>'ok');
}
?>
