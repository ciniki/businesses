<?php
//
// Description
// -----------
// This method will return the list of Reportss for a business.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:        The ID of the business to get Reports for.
//
// Returns
// -------
//
function ciniki_businesses_reportList($ciniki) {
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
    // Check access to business_id as owner, or sys admin.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'checkAccess');
    $rc = ciniki_businesses_checkAccess($ciniki, $args['business_id'], 'ciniki.businesses.reportList');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'datetimeFormat');
    $datetime_format = ciniki_users_datetimeFormat($ciniki, 'php');

    //
    // Load timezone settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'intlSettings');
    $rc = ciniki_businesses_intlSettings($ciniki, $ciniki['config']['ciniki.core']['master_business_id']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];

    //
    // Load business maps
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'maps');
    $rc = ciniki_businesses_maps($ciniki);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $maps = $rc['maps'];

    //
    // Get the list of reports
    //
    $strsql = "SELECT r.id, "
        . "r.title, "
        . "r.frequency, "
        . "r.frequency AS frequency_text, "
        . "r.flags, "
        . "r.next_date "
        . "FROM ciniki_business_reports AS r "
        . "WHERE r.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.businesses', array(
        array('container'=>'reports', 'fname'=>'id', 
            'fields'=>array('id', 'title', 'frequency', 'frequency_text', 'flags', 'next_date'),
            'maps'=>array('frequency_text'=>$maps['report']['frequency']),
            'utctotz'=>array('next_date'=>array('format'=>$datetime_format, 'timezone'=>$intl_timezone)),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['reports']) ) {
        $reports = $rc['reports'];
        $report_ids = array();
        foreach($reports as $iid => $report) {
            $report_ids[] = $report['id'];
        }
    } else {
        $reports = array();
        $report_ids = array();
    }

    return array('stat'=>'ok', 'reports'=>$reports, 'nplist'=>$report_ids);
}
?>
