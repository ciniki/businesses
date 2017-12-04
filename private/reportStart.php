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
function ciniki_businesses_reportStart($ciniki, $business_id, &$report) {

    $report['text'] = '';
    $report['html'] = '';
    $report['pdf'] = null;
    $report['excel'] = null;

    //
    // Load business details
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'businessDetails');
    $rc = ciniki_businesses_businessDetails($ciniki, $business_id);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['details']) && is_array($rc['details']) ) {   
        $business_details = $rc['details'];
    } else {
        $business_details = array();
    }

    //
    // Start PDF
    //
    if( !class_exists('MYPDF') ) {
        //
        // Load TCPDF Library
        //
        require_once($ciniki['config']['ciniki.core']['lib_dir'] . '/tcpdf/tcpdf.php');

        class MYPDF extends TCPDF {
            public $business_name = '';
            public $title = '';
            public $pagenumbers = 'yes';
            public $coverpage = 'no';
            public $toc = 'no';
            public $header_height = 10;
            public $footer_height = 12;
            public $bottom_margin = 25;
            public $footer_text = '';
            public $usable_width = 186;
            public $section_numbering = 'no';
            public $s = 0;
            public $ss = 0;
            public $sss = 0;


            //
            // Page Header
            //
            public function Header() {
                $this->SetFont('helvetica', 'B', 18);
                $this->SetLineWidth(0.25);
                $this->SetDrawColor(125);
                $this->setCellPaddings(5,1,5,2);
                if( $this->title != '' ) {
                    $this->Cell(0, 22, $this->title, '', false, 'C', 0, '', 0, false, 'M', 'B');
                }
                $this->setCellPaddings(0,0,0,0);
            }

            //
            // Page footer
            //
            public function Footer() {
                // Position at 15 mm from bottom
                // Set font
                if( $this->pagenumbers == 'yes' ) {
                    $this->SetY(-15);
                    $this->SetFont('helvetica', '', 10);
                    $this->Cell(150, 8, $this->footer_text, 'T', false, 'L', 0, '', 0, false, 'T', 'M');
                    $this->Cell($this->usable_width-150, 8, $this->pageNo(), 'T', false, 'R', 0, '', 0, false, 'T', 'M');
                }
            }

            public function addTitle($depth, $title, $toc='no') {
                if( $depth == 1 ) {
//                    $this->section_title = $title;
//                    $this->ssection_title = '';
//                    $this->sssection_title = '';
                    $this->s++;
                    $this->ss = 0;
                    $this->sss = 0;
                    if( $this->getY() > ($this->getPageHeight() - $this->top_margin - $this->bottom_margin - 40) ) {
                        $this->AddPage();
                    } else {
                        if( $this->s > 1 ) {
                            $this->Ln(8);
                        }
                    }
                    $this->SetFont('helvetica', 'B', '16');
                    if( $this->section_numbering == 'yes' ) {
                        $prefix = $this->s . '. ';
                    } else {
                        $prefix = '';
                    }
                    $this->MultiCell($this->usable_width, 12, $prefix . $title, 0, 'L', false, 1, '', '', true, 0, false, true, 0, 'T');
                    if( $toc == 'yes' && $this->toc == 'yes' ) { 
                        $this->Bookmark($prefix . $title, 0, 0, '', '');
                    }
                } elseif( $depth == 2 ) {
//                    $this->ssection_title = $title;
//                    $this->sssection_title = '';
                    $this->ss++;
                    $this->sss = 0;
                    if( $this->getY() > ($this->getPageHeight() - $this->top_margin - $this->bottom_margin - 40) ) {
                        $this->AddPage();
                    }
                    $this->SetFont('helvetica', 'B', '14');
                    if( $this->section_numbering == 'yes' ) {
                        $prefix = $this->s . '. ';
                        $prefix = $this->s . '.' . $this->ss . '. ';
                    } else {
                        $prefix = '';
                    }
                    $this->MultiCell($this->usable_width, 10, $prefix . $title, 0, 'L', false, 1, '', '', true, 0, false, true, 0, 'T');
                    if( $toc == 'yes' && $this->toc == 'yes' ) { 
                        $this->Bookmark($prefix . $title, 1, 0, '', '');
                    }
                } elseif( $depth == 3 ) {
//                    $this->sssection_title = $title;
                    if( $this->getY() > ($this->getPageHeight() - $this->top_margin - $this->bottom_margin - 40) ) {
                        $this->AddPage();
                    }
                    $this->sss++;
                    $this->SetFont('helvetica', 'B', '12');
                    if( $this->section_numbering == 'yes' ) {
                        $prefix = $this->s . '.' . $this->ss . '.' . $this->sss . '. ';
                    } else {
                        $prefix = '';
                    }
                    $this->MultiCell($this->usable_width, 10, $prefix . $title, 0, 'L', false, 1, '', '', true, 0, false, true, 0, 'T');
                    if( $toc == 'yes' && $this->toc == 'yes' ) { 
                        $this->Bookmark($prefix . $title, 2, 0, '', '');
                    }
                } else {
                    if( $this->getY() > ($this->getPageHeight() - $this->top_margin - $this->bottom_margin - 40) ) {
                        $this->AddPage();
                    }
                    $this->SetFont('helvetica', 'B', '12');
                    $this->MultiCell($this->usable_width, 8, $title, 0, 'L', false, 1, '', '', true, 0, false, true, 0, 'T');
                }
            }

            public function addHtml($depth, $content) { 
                $this->SetFont('helvetica', '', '10');
                $this->writeHTMLCell($this->usable_width, 10, '', '', '<style>p, ul, dt {color: #808080;}</style>' . $content, 0, 1, false, true, 'L');
    //            $this->writeHTMLCell($this->usable_width, 10, '', '', preg_replace('/<p>/', '<p style="color: #808080;">', $content), 0, 1, false, true, 'L');
            }
        }
    }

    $report['pdf'] = new MYPDF('P', PDF_UNIT, 'LETTER', true, 'UTF-8', false);

    //
    // Set margins
    //
    $report['pdf']->top_margin = 25;
    $report['pdf']->left_margin = 15;
    $report['pdf']->right_margin = 15;
    $report['pdf']->bottom_margin = 20;
    $report['pdf']->SetMargins($report['pdf']->left_margin, $report['pdf']->top_margin, $report['pdf']->right_margin);
    $report['pdf']->SetHeaderMargin($report['pdf']->header_height);
    $report['pdf']->setPageOrientation('P', false);
    $report['pdf']->SetFooterMargin($report['pdf']->bottom_margin);
    $report['pdf']->title = $report['title'];

    $report['pdf']->SetAutoPageBreak(TRUE, $report['pdf']->bottom_margin);

    //
    // Set PDF basics
    //
    $report['pdf']->SetCreator('Ciniki');
    $report['pdf']->SetAuthor($business_details['name']);
    $report['pdf']->footer_text = $business_details['name'];
    $report['pdf']->SetTitle($report['title']);
    $report['pdf']->SetSubject('');
    $report['pdf']->SetKeywords('');

    //
    // Start the pdf
    //
    $report['pdf']->AddPage();

    return array('stat'=>'ok');
}
?>
