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
function ciniki_businesses_domainExpiries($ciniki) {
	//
	// Find all the required and optional arguments
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
	$rc = ciniki_core_prepareArgs($ciniki, 'no', array(
		'days'=>array('required'=>'no', 'blank'=>'no', 'default'=>'90', 'name'=>'Days'),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$args = $rc['args'];

	//
	// Check access to business_id as owner
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'checkAccess');
	$ac = ciniki_businesses_checkAccess($ciniki, 0, 'ciniki.businesses.domainExpiries');
	if( $ac['stat'] != 'ok' ) {
		return $ac;
	}

	ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
	$date_format = ciniki_users_dateFormat($ciniki);

	//
	// Query the database for the domain
	//
	$strsql = "SELECT ciniki_business_domains.id, "
		. "ciniki_business_domains.business_id, "
		. "ciniki_businesses.name AS business_name, "
		. "ciniki_business_domains.domain, "
		. "ciniki_business_domains.flags, "
		. "ciniki_business_domains.status, "
		. "IF((ciniki_business_domains.flags&0x01)=0x01, 'yes', 'no') AS isprimary, "
		. "IFNULL(DATE_FORMAT(ciniki_business_domains.expiry_date, '" . ciniki_core_dbQuote($ciniki, $date_format) . "'), 'expiry unknown') AS expiry_date, "
		. "ciniki_business_domains.status, "
		. "DATEDIFF(UTC_TIMESTAMP(),ciniki_business_domains.expiry_date) AS age "
		. "FROM ciniki_business_domains "
		. "LEFT JOIN ciniki_businesses ON (ciniki_business_domains.business_id = ciniki_businesses.id) "
		. "WHERE DATEDIFF(ciniki_business_domains.expiry_date,UTC_TIMESTAMP()) < '" . $args['days'] . "' "
		. "ORDER BY age "
		. "";

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbRspQuery');
	return ciniki_core_dbRspQuery($ciniki, $strsql, 'ciniki.businesses', 'domains', 'domain', array('stat'=>'ok', 'domains'=>array()));
}
?>
