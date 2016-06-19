<?php
//
// Description
// -----------
// This method will return the intl settings for the business.  These are 
// used to set the locale, currency and timezone of the business.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:         The ID of the business to get the intl settings for.
//
// Returns
// -------
// <settings intl-default-locale="en_US"
//
function ciniki_businesses_settingsIntlGet($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];
    
    //
    // Check access to business_id as owner, or sys admin
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'checkAccess');
    $ac = ciniki_businesses_checkAccess($ciniki, $args['business_id'], 'ciniki.businesses.settingsIntlGet');
    if( $ac['stat'] != 'ok' ) {
        return $ac;
    }

    $rsp = array('stat'=>'ok', 
        'settings'=>array(
            'intl-default-locale'=>'en_CA',
            'intl-default-currency'=>'CAD', 
            'intl-default-timezone'=>'America/Toronto',
            'intl-default-distance-units'=>'km',
            ),
        'locales'=>array(),
        'currencies'=>array(),
        'timezones'=>array(),
        );
    
    $strsql = "SELECT detail_key, detail_value "
        . "FROM ciniki_business_details "
        . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "AND detail_key IN ("
            . "'intl-default-locale', "
            . "'intl-default-currency', "
            . "'intl-default-timezone', "
            . "'intl-default-distance-units' "
            . ") "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.businesses', 'setting');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['rows']) ) {
        foreach($rc['rows'] as $row) {
            $rsp['settings'][$row['detail_key']] = $row['detail_value'];
        }
    }
    
    //
    // Get the complete list of locales
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'public', 'getLocales');
    $rc = ciniki_core_getLocales($ciniki);
    if( $rc['stat'] != 'ok') {
        return $rc;
    }
    $rsp['locales'] = $rc['locales'];

    //
    // Get the complete list of currencies
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'public', 'getCurrencies');
    $rc = ciniki_core_getCurrencies($ciniki);
    if( $rc['stat'] != 'ok') {
        return $rc;
    }
    $rsp['currencies'] = $rc['currencies'];

    //
    // Get the complete list of timezones
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'public', 'getTimeZones');
    $rc = ciniki_core_getTimeZones($ciniki);
    if( $rc['stat'] != 'ok') {
        return $rc;
    }
    $rsp['timezones'] = $rc['timezones'];

    //
    // Get the complete list of distance units
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'public', 'getDistanceUnits');
    $rc = ciniki_core_getDistanceUnits($ciniki);
    if( $rc['stat'] != 'ok') {
        return $rc;
    }
    $rsp['distanceunits'] = $rc['units'];

    return $rsp;
}
?>
