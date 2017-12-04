<?php
//
// Description
// ===========
// This function will add a table to the report.
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
function ciniki_businesses_reportChunkTable($ciniki, $business_id, &$report, $chunk) {

    if( isset($chunk['textlist']) && $chunk['textlist'] != '' ) {
        $report['text'] .= $chunk['textlist'];
    }

    $html = '<table cellpadding="5">';
    $html .= "<thead><tr>";
    $pdfhtml = '<table border="0" cellpadding="5" cellspacing="0" style="border: 0.1px solid #aaa;">';
    $pdfhtml .= "<thead><tr>";
    foreach($chunk['columns'] as $col) {
        $html .= "<th>" . $col['label'] . "</th>";
        $pdfhtml .= '<th bgcolor="#dddddd" style="border: 0.1px solid #aaa;' 
            . (isset($col['pdfwidth']) ? 'width:' . $col['pdfwidth'] : '') . '">' . $col['label'] . "</th>";
    }
    $html .= "</tr></thead>";
    $html .= "<tbody>";
    $pdfhtml .= "</tr></thead>";
    $pdfhtml .= "<tbody>";

    foreach($chunk['data'] as $row) {
        $html .= "<tr>";
        $pdfhtml .= '<tr nobr="true">';
        foreach($chunk['columns'] as $col) {
            $html .= '<td style="border: 1px solid #aaa; padding: 5px;">';
            $pdfhtml .= '<td style="border: 0.1px solid #aaa;' . (isset($col['pdfwidth']) ? 'width:' . $col['pdfwidth'] : '') . '">' ;
            if( isset($row[$col['field']]) ) {
                $html .= preg_replace("/\n/", "<br/>", $row[$col['field']]);
                $pdfhtml .= preg_replace("/\n/", "<br/>", $row[$col['field']]);
            }
            $html .= "</td>";
            $pdfhtml .= "</td>";
        }
        $html .= "</tr>";
        $pdfhtml .= "</tr>";
    }
    $html .= "</tbody>";
    $html .= "</table>";
    $pdfhtml .= "</tbody>";
    $pdfhtml .= "</table>";

    $report['html'] .= $html;


    $report['pdf']->addHtml(1, $pdfhtml);

    return array('stat'=>'ok');
}
?>
