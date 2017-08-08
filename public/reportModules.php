<?php
//
// Description
// -----------
// This function will return the list of businesses and which modules they have turned on.
// This function is not part of the business reports.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:         The ID of the business to get the users for.
//
// Returns
// -------
//
function ciniki_businesses_reportModules($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];
    
    //
    // Check access to business_id as owner, or sys admin
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'checkAccess');
    $ac = ciniki_businesses_checkAccess($ciniki, 0, 'ciniki.businesses.reportModules');
    if( $ac['stat'] != 'ok' ) {
        return $ac;
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'datetimeFormat');
    $datetime_format = ciniki_users_datetimeFormat($ciniki);

    //
    // Query for the businesses and they modules enabled
    //
    $strsql = "SELECT "
        . "CONCAT_WS('.', ciniki_business_modules.package, ciniki_business_modules.module) AS modname, "
        . "ciniki_business_modules.package, "
        . "ciniki_business_modules.module, "
        . "ciniki_businesses.name AS business_name, "
        . "DATE_FORMAT(ciniki_business_modules.last_change, '" . ciniki_core_dbQuote($ciniki, $datetime_format) . "') as last_change "
        . "FROM ciniki_business_modules "
        . "LEFT JOIN ciniki_businesses ON (ciniki_business_modules.business_id = ciniki_businesses.id "
            . "AND ciniki_business_modules.status > 0) "
        . "ORDER BY ciniki_business_modules.package, ciniki_business_modules.module, ciniki_businesses.name, ciniki_business_modules.last_change DESC "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.businesses', array(
        array('container'=>'modules', 'fname'=>'modname', 'name'=>'module',
            'fields'=>array('name'=>'modname', 'package', 'module')),
        array('container'=>'businesses', 'fname'=>'business_name', 'name'=>'business',
            'fields'=>array('name'=>'business_name', 'last_change')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    
    return $rc;
}
?>
