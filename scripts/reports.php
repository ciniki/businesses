<?php
//
// Description
// -----------
// This script will run the reports for businesses. This is separate from cron jobs
// so a break in the report code will not stop cron jobs.
//

//
// Initialize Ciniki by including the ciniki_api.php
//
global $ciniki_root;
$ciniki_root = dirname(__FILE__);
if( !file_exists($ciniki_root . '/ciniki-api.ini') ) {
    $ciniki_root = dirname(dirname(dirname(dirname(__FILE__))));
}
// loadMethod is required by all function to ensure the functions are dynamically loaded
require_once($ciniki_root . '/ciniki-mods/core/private/loadMethod.php');
require_once($ciniki_root . '/ciniki-mods/core/private/init.php');
require_once($ciniki_root . '/ciniki-mods/core/private/checkModuleFlags.php');
require_once($ciniki_root . '/ciniki-mods/core/private/dbHashQuery.php');
require_once($ciniki_root . '/ciniki-mods/core/private/dbQuote.php');
require_once($ciniki_root . '/ciniki-mods/businesses/private/checkModuleAccess.php');
require_once($ciniki_root . '/ciniki-mods/businesses/private/reportRun.php');

$rc = ciniki_core_init($ciniki_root, 'rest');
if( $rc['stat'] != 'ok' ) {
    header("Status: 500 Processing Error", true, 500);
    exit;
}

//
// Setup the $ciniki variable to hold all things ciniki.  
//
$ciniki = $rc['ciniki'];

//
// Load the list of reports to run
//
$strsql = "SELECT id, business_id, frequency, next_date "
    . "FROM ciniki_business_reports "
    . "WHERE next_date <= UTC_TIMESTAMP() "
    . "";
$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.businesses', 'report');
if( $rc['stat'] != 'ok' ) {
    error_log('Unable to get list of reports');
    exit;
}
if( !isset($rc['rows']) || count($rc['rows']) < 1 ) {
    // Nothing to do
    exit;
}

$reports = $rc['rows'];
foreach($reports as $report) {
    //
    // Setup the defaults for business in ciniki array
    //
    $ciniki['business'] = array('settings'=>array(), 'modules'=>array(), 'user'=>array('perms'=>0));

    //
    // Load the business modules
    //
    $rc = ciniki_businesses_checkModuleAccess($ciniki, $report['business_id'], 'ciniki', 'businesses');
    if( $rc['stat'] != 'ok' ) {
        error_log('RPTERR: Module not enabled');
        continue;
    }

    //
    // Run the report
    //
    $rc = ciniki_businesses_reportRun($ciniki, $report['business_id'], $report['id']);
    if( $rc['stat'] != 'ok' ) { 
        error_log('RPTERR: ' . print_r($rc, true));
        continue;
    }
}

exit;
?>
