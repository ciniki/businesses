<?php
//
// Description
// ===========
// This function runs the report and builds the text and pdf versions.
//
// Arguments
// ---------
// ciniki:
// business_id:         The ID of the business the reports is attached to.
// report_id:           The ID of the reports to get the details for.
//
// Returns
// -------
//
function ciniki_businesses_reportRun($ciniki, $business_id, $report_id) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'reportExec');
    $rc = ciniki_businesses_reportExec($ciniki, $business_id, $report_id);
    if( $rc['stat'] != 'ok' ) {
        //
        // Email the error code and information, that way they know the report ran but there was a problem.
        //
        $report = array(
            'text'=>"There was an error processing the report.\n\n" . print_r($rc, true),
            'html'=>"<p>There was an error processing the report.</p><br/><br/>" . print_r($rc, true),
            );
        //
        // FIXME: Email sysadmins as well
        //
        
    } else {
        $report = $rc['report'];
    }

    //
    // Create the email 
    //
    if( isset($report['text']) ) {
        
        print_r($report['text']);
        print_r($report['html']);
    }


    return array('stat'=>'ok');
}
?>
