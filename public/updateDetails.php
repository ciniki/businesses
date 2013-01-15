<?php
//
// Description
// -----------
// This function will take a list of details to be updated within the database.  The
// fields will be used for the contact information and business information
// on the Contact Page for the business.
//
// Info
// ----
// Status: beta
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:					The ID of the business to get the details for.
// business.name:				(optional) The name to set for the business.
// business.tagline:			(optional) The tagline for the website.  Used on website.
// contact.address.street1:		(optional) The address for the business.
// contact.address.street2:		(optional) The second address line for the business.
// contact.address.city:		(optional) The city for the business.
// contact.address.province:	(optional) The province for the business.
// contact.address.postal:		(optional) The postal code for the business.
// contact.address.country:		(optional) The county of the business.
// contact.person.name:			(optional) The contact person for the business.
// contact.phone.number:		(optional) The contact phone number for the business.  
// contact.tollfree.number:		(optional) The toll free number for the business.
// contact.fax.number:			(optional) The fax number for the business.
// contact.email.address:		(optional) The contact email address for the business.
//
// Returns
// -------
// <rsp stat='ok' />
//
function ciniki_businesses_updateDetails($ciniki) {
	//
	// Find all the required and optional arguments
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
	$rc = ciniki_core_prepareArgs($ciniki, 'no', array(
		'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
		'business.name'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Business Name'), 
		'business.category'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Category'), 
		'business.sitename'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Sitename'), 
		'business.tagline'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Tagline'), 
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$args = $rc['args'];
	
	//
	// Check access to business_id as owner
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'checkAccess');
	$ac = ciniki_businesses_checkAccess($ciniki, $args['business_id'], 'ciniki.businesses.updateDetails');
	if( $ac['stat'] != 'ok' ) {
		return $ac;
	}

	//
	// Check the sitename is proper format
	//
	if( isset($args['business.sitename']) && preg_match('/[^a-z0-9\-_]/', $args['business.sitename']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'163', 'msg'=>'Illegal characters in sitename.  It can only contain lowercase letters, numbers, underscores (_) or dash (-)'));
	}
	

	//
	// Turn off autocommit
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
	$rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.businesses');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	//
	// Check if name or tagline was specified
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbInsert');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
	$strsql = "";
	if( isset($args['business.name']) && $args['business.name'] != '' ) {
		$strsql .= ", name = '" . ciniki_core_dbQuote($ciniki, $args['business.name']) . "'";
		ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.businesses', 'ciniki_business_history', $args['business_id'], 
			2, 'ciniki_businesses', '', 'name', $args['business.name']);
	}
	if( isset($args['business.sitename']) ) {
		$strsql .= ", sitename = '" . ciniki_core_dbQuote($ciniki, $args['business.sitename']) . "'";
		ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.businesses', 'ciniki_business_history', $args['business_id'], 
			2, 'ciniki_businesses', '', 'sitename', $args['business.sitename']);
	}
	if( isset($args['business.category']) ) {
		$strsql .= ", category = '" . ciniki_core_dbQuote($ciniki, $args['business.category']) . "'";
		ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.businesses', 'ciniki_business_history', $args['business_id'], 
			2, 'ciniki_businesses', '', 'category', $args['business.category']);
	}
	if( isset($args['business.tagline']) ) {
		$strsql .= ", tagline = '" . ciniki_core_dbQuote($ciniki, $args['business.tagline']) . "'";
		ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.businesses', 'ciniki_business_history', $args['business_id'], 
			2, 'ciniki_businesses', '', 'tagline', $args['business.tagline']);
	}
	//
	// Always update last_updated for sync purposes
	//
	$strsql = "UPDATE ciniki_businesses SET last_updated = UTC_TIMESTAMP()" . $strsql 
		. " WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' ";
	$rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.businesses');
	if( $rc['stat'] != 'ok' ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.businesses');
		return $rc;
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
		'ciniki.manage.css',
		);
	foreach($ciniki['request']['args'] as $arg_name => $arg_value) {
		if( in_array($arg_name, $allowed_keys) ) {
			$strsql = "INSERT INTO ciniki_business_details (business_id, detail_key, detail_value, date_added, last_updated) "
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
			ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.businesses', 'ciniki_business_history', $args['business_id'], 
				2, 'ciniki_business_details', $arg_name, 'detail_value', $arg_value);
		}
	}

	$rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.businesses');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	return array('stat'=>'ok');
}
?>
