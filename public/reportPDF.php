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
function ciniki_businesses_reportPDF(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'),
        'report_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Reports'),
        'email'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Email Report'),
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

    //
    // Check if the report is to be emailed
    //
    if( isset($args['email']) && $args['email'] == 'test' ) {
        
        //
        // Get the users email
        //
        $strsql = "SELECT id, CONCAT_WS(' ', firstname, lastname) AS name, email "
            . "FROM ciniki_users "
            . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.users', 'user');
        if( $rc['stat'] != 'ok' || !isset($rc['user']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.businesses.118', 'msg'=>'Unable to find email information', 'err'=>$rc['err']));
        }
        $name = $rc['user']['name'];
        $email = $rc['user']['email'];
        ciniki_core_loadMethod($ciniki, 'ciniki', 'mail', 'hooks', 'addMessage');
        $rc = ciniki_mail_hooks_addMessage($ciniki, $args['business_id'], array(
            'customer_email'=>$email,
            'customer_name'=>$name,
            'subject'=>$report['title'],
            'html_content'=>$report['html'],
            'text_content'=>$report['text'],
            'attachments'=>array(array('content'=>$report['pdf']->Output($filename . '.pdf', 'S'), 'filename'=>$filename)),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $ciniki['emailqueue'][] = array('mail_id'=>$rc['id'], 'business_id'=>$args['business_id']);
        return array('stat'=>'ok');
    } else {
        $report['pdf']->Output($filename . '.pdf', 'D');
        return array('stat'=>'exit');
    }

    return array('stat'=>'ok');
}
?>
