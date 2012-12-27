<?php
//
// Description
// -----------
// This function will return a list of businesses which are to be listed
// on the main page of the master business website.
//
// Returns
// -------
//
function ciniki_businesses_web_featured($ciniki, $settings) {
	
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');

	//
	// Get the list of businesses, and their sitename or domain name
	// Exclude the master business
	//
	$strsql = "SELECT ciniki_businesses.id, ciniki_businesses.name, ciniki_businesses.sitename, ciniki_business_domains.domain "
		. "FROM ciniki_web_settings, ciniki_business_modules, ciniki_businesses "
		. "LEFT JOIN ciniki_business_domains ON (ciniki_businesses.id = ciniki_business_domains.business_id AND (ciniki_business_domains.flags&0x01) = 0x01 ) "
		. "WHERE ciniki_web_settings.detail_key = 'site-featured' AND ciniki_web_settings.detail_value = 'yes' "
		. "AND ciniki_web_settings.business_id = ciniki_businesses.id "
		. "AND ciniki_businesses.id = ciniki_business_modules.business_id "
		. "AND ciniki_business_modules.package = 'ciniki' "
		. "AND ciniki_business_modules.module = 'web' "
		. "AND ciniki_business_modules.status = 1 "
		. "ORDER BY ciniki_businesses.name "
		. "";

	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.businesses', array(
		array('container'=>'businesses', 'fname'=>'id', 'name'=>'business',
			'fields'=>array('id', 'name', 'sitename', 'domain')),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['businesses']) || !is_array($rc['businesses']) ) {
		return array('stat'=>'ok', 'businesses'=>array());
	}

	return array('stat'=>'ok', 'businesses'=>$rc['businesses']);
}
?>
