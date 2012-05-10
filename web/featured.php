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
	
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbQuote.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbHashQueryTree.php');

	//
	// Get the list of businesses, and their sitename or domain name
	// Exclude the master business
	//
	$strsql = "SELECT ciniki_businesses.id, ciniki_businesses.name, ciniki_businesses.sitename, ciniki_business_domains.domain "
		. "FROM ciniki_businesses "
		. "LEFT JOIN ciniki_business_domains ON (ciniki_businesses.id = ciniki_business_domains.business_id AND (ciniki_business_domains.flags&0x01) = 0x01 ) "
		. "WHERE ciniki_businesses.id <> '" . ciniki_core_dbQuote($ciniki, $ciniki['config']['core']['master_business_id']) . "' "
		. "ORDER BY ciniki_businesses.name "
		. "";

	error_log($strsql);
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'businesses', array(
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
