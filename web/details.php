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

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDetailsQuery');

	//
	// Get the business name and tagline
	//
	$strsql = "SELECT name, sitename, tagline, logo_id FROM ciniki_businesses "
		. "WHERE id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' ";
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.businesses', 'business');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['business']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1051', 'msg'=>'Unable to get business details'));
	}
	$rsp['details']['name'] = $rc['business']['name'];
	$rsp['details']['sitename'] = $rc['business']['sitename'];
	$rsp['details']['tagline'] = $rc['business']['tagline'];
	$rsp['details']['logo_id'] = $rc['business']['logo_id'];
	
	return $rsp;
}
?>
