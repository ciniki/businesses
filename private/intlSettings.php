<?php
//
// Description
// -----------
// This function will return the intl settings for the tenant.
//
// Arguments
// ---------
// 
// Returns
// -------
//
function ciniki_tenants_intlSettings(&$ciniki, $tnid) {

    //
    // Check the tenant settings cache
    //
    if( isset($ciniki['tenant']['settings']['intl-default-locale']) 
        && isset($ciniki['tenant']['settings']['intl-default-currency']) 
        && isset($ciniki['tenant']['settings']['intl-default-timezone']) 
        ) {
        return array('stat'=>'ok', 'settings'=>$ciniki['tenant']['settings']);    
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDetailsQueryDash');
    $rc = ciniki_core_dbDetailsQueryDash($ciniki, 'ciniki_tenant_details', 'tnid', $tnid,
        'ciniki.tenants', 'settings', 'intl');
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
    if( !isset($rc['settings']['intl-default-distance-units']) ) {
        $rc['settings']['intl-default-distance-units'] = 'km';
    }

    //
    // Save the settings in the tenant cache
    //
    if( !isset($ciniki['tenant']) ) {
        $ciniki['tenant'] = array('settings'=>$rc['settings']);
    } elseif( !isset($ciniki['tenant']['settings']) ) {
        $ciniki['tenant']['settings'] = $rc['settings'];
    } else {
        if( !isset($ciniki['tenant']['settings']['intl-default-locale']) ) {
            $ciniki['tenant']['settings']['intl-default-locale'] = $rc['settings']['intl-default-locale'];
            $ciniki['tenant']['settings']['intl-default-currency-fmt'] = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
        }
        if( !isset($ciniki['tenant']['settings']['intl-default-currency']) ) {
            $ciniki['tenant']['settings']['intl-default-currency'] = $rc['settings']['intl-default-currency'];
        }
        if( !isset($ciniki['tenant']['settings']['intl-default-timezone']) ) {
            $ciniki['tenant']['settings']['intl-default-timezone'] = $rc['settings']['intl-default-timezone'];
        }
        if( !isset($ciniki['tenant']['settings']['intl-default-distance-units']) ) {
            $ciniki['tenant']['settings']['intl-default-distance-units'] = $rc['settings']['intl-default-distance-units'];
        }
    }

    return $rc;
}
?>
