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
//  	<contact>
//			<person name='' />
//			<phone number='' />
//			<fax number='' />
//			<email address='' />
//			<address street1='' street2='' city='' province='' postal='' country='' />
//			<tollfree number='' restrictions='' />
//		</contact>
// </details>
//
function ciniki_businesses_webContact($ciniki, $business_id) {
	
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbQuote.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbDetailsQuery.php');

	$rc = ciniki_core_dbDetailsQuery($ciniki, 'ciniki_business_details', 
		'business_id', $business_id, 'businesses', 'details', 'contact');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['details']) || !is_array($rc['details']) ) {
		return array('stat'=>'ok', 'details'=>array());
	}

	return array('stat'=>'ok', 'details'=>$rc['details']);
}
?>
