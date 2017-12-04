<?php
//
// Description
// ===========
//
// Arguments
// ---------
// ciniki:
// tnid:         The ID of the tenant the reports is attached to.
// report_id:           The ID of the reports to get the details for.
//
// Returns
// -------
//
function ciniki_tenants_reportBlock($ciniki, $tnid, &$report, $block) {

    //
    // Make sure chunks are defined
    //
    if( isset($block['chunks']) ) {
        //
        // Add the block title
        //
        if( isset($block['title']) && $block['title'] != '' ) {
            // Text
            $report['text'] .= $block['title'] . "\n";
            $report['text'] .= str_repeat("=", strlen($block['title'])) . "\n\n";
            // Html
            $report['html'] .= "<h1>" . $block['title'] . "</h1>";
            // PDF
            $report['pdf']->addTitle(1, $block['title'], 'yes');
            // Excel
            // FIXME: Add Title to Excel
        }

        //
        // Add the content based on type
        //
        foreach($block['chunks'] as $chunk) {
            $fn = '';
            switch($chunk['type']) {
                case 'message': $fn = 'ciniki_tenants_reportChunkMessage'; break;
                case 'table': $fn = 'ciniki_tenants_reportChunkTable'; break;
                case 'text': $fn = 'ciniki_tenants_reportChunkText'; break;
            }
            $rc = $fn($ciniki, $tnid, $report, $chunk);
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
        }
        $report['text'] .= "\n\n";
        $report['html'] .= "<br/>";
    }

    return array('stat'=>'ok', 'report'=>$report);
}
?>
