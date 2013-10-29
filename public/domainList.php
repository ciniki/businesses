<?php
//
// Description
// -----------
// This function will lookup the client domain in the database, and return the business id.
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_businesses_domainList($ciniki) {
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
	// Check access to business_id as owner
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'checkAccess');
	$ac = ciniki_businesses_checkAccess($ciniki, $args['business_id'], 'ciniki.businesses.domainList');
	if( $ac['stat'] != 'ok' ) {
		return $ac;
	}

	ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
	$date_format = ciniki_users_dateFormat($ciniki);

	//
	// Query the database for the domain
	//
	$strsql = "SELECT id, domain, flags, status, "
		. "IF((flags&0x01)=0x01, 'yes', 'no') AS isprimary, "
		. "IFNULL(DATE_FORMAT(expiry_date, '" . ciniki_core_dbQuote($ciniki, $date_format) . "'), 'expiry unknown') AS expiry_date, "
		. "status, managed_by "
		. "FROM ciniki_business_domains "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "";

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbRspQuery');
	return ciniki_core_dbRspQuery($ciniki, $strsql, 'ciniki.businesses', 'domains', 'domain', array('stat'=>'ok', 'domains'=>array()));
}
?>
