<?php
//
// Description
// -----------
// This function will get detail values for a business.
//
// Info
// ----
// Status: beta
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:			The ID of the business to get the details for.
// keys:				The comma delimited list of keys to lookup values for.
//
// Returns
// -------
// <details>
//		<business name='' tagline='' />
// </details>
//
function ciniki_businesses_web_details($ciniki, $business_id) {
	$rsp = array('stat'=>'ok', 'details'=>array());

	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbQuote.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbHashQuery.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbDetailsQuery.php');

	//
	// Get the business name and tagline
	//
	$strsql = "SELECT name, tagline FROM ciniki_businesses "
		. "WHERE id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' ";
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'details', 'business');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$rsp['details']['name'] = $rc['business']['name'];
	$rsp['details']['tagline'] = $rc['business']['tagline'];
	
	return $rsp;
}
?>
