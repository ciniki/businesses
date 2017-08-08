<?php
//
// Description
// ===========
// This method will return all the information about an reports.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:         The ID of the business the reports is attached to.
// report_id:          The ID of the reports to get the details for.
//
// Returns
// -------
//
function ciniki_businesses_reportPDF($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'),
        'report_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Reports'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Make sure this module is activated, and
    // check permission to run this function for this business
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'checkAccess');
    $rc = ciniki_businesses_checkAccess($ciniki, $args['business_id'], 'ciniki.businesses.reportPDF');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Execute the report
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'reportExec'); 
    $rc = ciniki_businesses_reportExec($ciniki, $args['business_id'], $args['report_id']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $report = $rc['report'];

    $filename = preg_replace("/[^0-9a-zA-Z ]/", "", $report['title']);
    $filename = preg_replace("/ /", '-', $filename);
    $report['pdf']->Output($filename . '.pdf', 'D');

    return array('stat'=>'exit');
}
?>
