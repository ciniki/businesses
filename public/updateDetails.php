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
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/prepareArgs.php');
	$rc = ciniki_core_prepareArgs($ciniki, 'no', array(
		'business_id'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No business specified'), 
		'business.name'=>array('required'=>'no', 'blank'=>'no', 'errmsg'=>'No name specified'), 
		'business.tagline'=>array('required'=>'no', 'blank'=>'yes', 'errmsg'=>'No tagline specified'), 
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$args = $rc['args'];
	
	//
	// Check access to business_id as owner
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/businesses/private/checkAccess.php');
	$ac = ciniki_businesses_checkAccess($ciniki, $args['business_id'], 'ciniki.businesses.updateDetails');
	if( $ac['stat'] != 'ok' ) {
		return $ac;
	}

	//
	// Turn off autocommit
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbTransactionStart.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbTransactionRollback.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbTransactionCommit.php');
	$rc = ciniki_core_dbTransactionStart($ciniki, 'businesses');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	//
	// Check if name or tagline was specified
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbQuote.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbUpdate.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbInsert.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbAddChangeLog.php');
	$strsql = "";
	if( isset($args['business.name']) && $args['business.name'] != '' ) {
		$strsql .= ", name = '" . ciniki_core_dbQuote($ciniki, $args['business.name']) . "'";
		ciniki_core_dbAddChangeLog($ciniki, 'businesses', $args['business_id'], 'businesses', '', 'name', $args['business.name']);
	}
	if( isset($args['business.tagline']) ) {
		$strsql .= ", tagline = '" . ciniki_core_dbQuote($ciniki, $args['business.tagline']) . "'";
		ciniki_core_dbAddChangeLog($ciniki, 'businesses', $args['business_id'], 'businesses', '', 'tagline', $args['business.tagline']);
	}
	if( $strsql != '' ) {
		$strsql = "UPDATE businesses SET last_updated = UTC_TIMESTAMP()" . $strsql 
			. " WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' ";
		$rc = ciniki_core_dbUpdate($ciniki, $strsql, 'businesses');
		if( $rc['stat'] != 'ok' ) {
			ciniki_core_dbTransactionRollback($ciniki, 'businesses');
			return $rc;
		}
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
		'manage.theme',
		);
	foreach($ciniki['request']['args'] as $arg_name => $arg_value) {
		if( in_array($arg_name, $allowed_keys) ) {
			$strsql = "INSERT INTO business_details (business_id, detail_key, detail_value, date_added, last_updated) "
				. "VALUES ('" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "'"
				. ", '" . ciniki_core_dbQuote($ciniki, $arg_name) . "'"
				. ", '" . ciniki_core_dbQuote($ciniki, $arg_value) . "'"
				. ", UTC_TIMESTAMP(), UTC_TIMESTAMP()) "
				. "ON DUPLICATE KEY UPDATE detail_value = '" . ciniki_core_dbQuote($ciniki, $arg_value) . "' "
				. ", last_updated = UTC_TIMESTAMP() "
				. "";
			$rc = ciniki_core_dbInsert($ciniki, $strsql, 'businesses');
			if( $rc['stat'] != 'ok' ) {
				ciniki_core_dbTransactionRollback($ciniki, 'businesses');
				return $rc;
			}
			ciniki_core_dbAddChangeLog($ciniki, 'businesses', $args['business_id'], 'business_details', $arg_name, 'detail_value', $arg_value);
		}
	}

	$rc = ciniki_core_dbTransactionCommit($ciniki, 'businesses');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	return array('stat'=>'ok');
}
?>
