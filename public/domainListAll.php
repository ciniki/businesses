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
function ciniki_businesses_domainListAll($ciniki) {
	//
	// Find all the required and optional arguments
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
	$rc = ciniki_core_prepareArgs($ciniki, 'no', array(
		'limit'=>array('required'=>'no', 'blank'=>'no', 'default'=>'0', 'name'=>'Limit'),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$args = $rc['args'];

	//
	// Check access to business_id as owner
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'checkAccess');
	$ac = ciniki_businesses_checkAccess($ciniki, 0, 'ciniki.businesses.domainListAll');
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
		. "ciniki_businesses.status AS business_status, "
		. "ciniki_business_domains.domain, "
		. "ciniki_business_domains.flags, "
		. "ciniki_business_domains.status, "
		. "IF((ciniki_business_domains.flags&0x01)=0x01, 'yes', 'no') AS isprimary, "
		. "IFNULL(DATE_FORMAT(ciniki_business_domains.expiry_date, '" . ciniki_core_dbQuote($ciniki, $date_format) . "'), 'expiry unknown') AS expiry_date, "
		. "DATEDIFF(ciniki_business_domains.expiry_date, UTC_TIMESTAMP()) AS expire_in_days "
		. "FROM ciniki_business_domains "
		. "LEFT JOIN ciniki_businesses ON (ciniki_business_domains.business_id = ciniki_businesses.id) "
		. "ORDER BY ciniki_businesses.name "
		. "";
	if( isset($args['limit']) && $args['limit'] > 0 ) {
		$strsql .= "LIMIT '" . ciniki_core_dbQuote($ciniki, $args['limit']) . "' ";
	}

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.businesses', array(
		array('container'=>'domains', 'fname'=>'id', 'name'=>'domain',
			'fields'=>array('id', 'business_id', 'business_name', 'business_status',
				'domain', 'flags', 'status', 'isprimary', 
				'expiry_date', 'expire_in_days'),
			'maps'=>array('business_status'=>array('1'=>'Active', '10'=>'Suspended', '60'=>'Deleted')),
			),
		));

	return $rc;
}
?>
