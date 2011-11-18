<?php
//
// Description
// -----------
// This function will get detail values for a business.  These values
// are used many places in the API and MOSSi.
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
function ciniki_businesses_getDetails($ciniki) {
	//
	// Find all the required and optional arguments
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/prepareArgs.php');
	$rc = ciniki_core_prepareArgs($ciniki, 'no', array(
		'business_id'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No business specified'), 
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$args = $rc['args'];
	
	//
	// Check access to business_id as owner, or sys admin
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/businesses/private/checkAccess.php');
	$ac = ciniki_businesses_checkAccess($ciniki, $args['business_id'], 'ciniki.businesses.getDetails');
	if( $ac['stat'] != 'ok' ) {
		return $ac;
	}

	// Split the keys, if specified
	if( isset($ciniki['request']['args']['keys']) && $ciniki['request']['args']['keys'] != '' ) {
		$detail_keys = preg_split('/,/', $ciniki['request']['args']['keys']);
	} else {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'118', 'msg'=>'No keys specified'));
	}

	$rsp = array('stat'=>'ok', 'details'=>array());

	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbQuote.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbHashQuery.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbDetailsQuery.php');
	foreach($detail_keys as $detail_key) {
		if( $detail_key == 'business' ) {
			$strsql = "SELECT name, tagline FROM ciniki_businesses "
				. "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' ";
			$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'details', 'business');
			if( $rc['stat'] != 'ok' ) {
				return $rc;
			}
			$rsp['details']['business.name'] = $rc['business']['name'];
			$rsp['details']['business.tagline'] = $rc['business']['tagline'];
		} elseif( in_array($detail_key, array('contact', 'ciniki')) ) {
			$rc = ciniki_core_dbDetailsQuery($ciniki, 'ciniki_business_details', 
				'business_id', $args['business_id'], 'businesses', 'details', $detail_key);
			if( $rc['stat'] != 'ok' ) {
				return $rc;
			}
			if( $rc['details'] != null ) {
				$rsp['details'] += $rc['details'];
			}
		}
	}

	return $rsp;

}
?>
