<?php
//
// Description
// -----------
// This function will return the intl settings for the business.
//
// Arguments
// ---------
// 
// Returns
// -------
//
function ciniki_businesses_intlSettings(&$ciniki, $business_id) {

	//
	// Check the business settings cache
	//
	if( isset($ciniki['business']['settings']['intl-default-locale']) 
		&& isset($ciniki['business']['settings']['intl-default-currency']) 
		&& isset($ciniki['business']['settings']['intl-default-timezone']) 
		) {
		return array('stat'=>'ok', 'settings'=>$ciniki['business']['settings']);	
	}

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDetailsQueryDash');
	$rc = ciniki_core_dbDetailsQueryDash($ciniki, 'ciniki_business_details', 'business_id', $business_id,
		'ciniki.businesses', 'settings', 'intl');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	//
	// Set the defaults if not found
	//
	if( !isset($rc['settings']) ) {
		$rc['settings'] = array();
	}
	if( !isset($rc['settings']['intl-default-locale']) ) {
		$rc['settings']['intl-default-locale'] = 'en_CA';
	}
	if( !isset($rc['settings']['intl-default-currency']) ) {
		$rc['settings']['intl-default-currency'] = 'CAD';
	}
	if( !isset($rc['settings']['intl-default-timezone']) ) {
		$rc['settings']['intl-default-timezone'] = 'America/Toronto';
	}

	//
	// Save the settings int he business cache
	//
	if( !isset($ciniki['business']) ) {
		$ciniki['business'] = array('settings'=>$rc['settings']);
	} elseif( !isset($ciniki['business']['settings']) ) {
		$ciniki['business']['settings'] = $rc['settings'];
	} else {
		if( !isset($ciniki['business']['settings']['intl-default-locale']) ) {
			$ciniki['business']['settings']['intl-default-locale'] = $rc['settings']['intl-default-locale'];
		}
		if( !isset($ciniki['business']['settings']['intl-default-currency']) ) {
			$ciniki['business']['settings']['intl-default-currency'] = $rc['settings']['intl-default-currency'];
		}
		if( !isset($ciniki['business']['settings']['intl-default-timezone']) ) {
			$ciniki['business']['settings']['intl-default-timezone'] = $rc['settings']['intl-default-timezone'];
		}
	}

	return $rc;
}
?>
