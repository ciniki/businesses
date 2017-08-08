<?php
//
// Description
// ===========
// This function will add a chunk of text to the report.
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
function ciniki_businesses_reportChunkMessage($ciniki, $business_id, &$report, $chunk) {

    $report['text'] .= $chunk['content'] . "\n\n";

    $rc = ciniki_web_processContent($ciniki, array(), $chunk['content'], 'message');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $report['html'] .= $rc['content'] . "\n";

    $report['pdf']->addHtml(1, $rc['content']);

    return array('stat'=>'ok');
}
?>
