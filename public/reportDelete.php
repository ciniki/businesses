<?php
//
// Description
// -----------
// This method will delete an reports.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:            The ID of the business the reports is attached to.
// report_id:            The ID of the reports to be removed.
//
// Returns
// -------
//
function ciniki_businesses_reportDelete(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'),
        'report_id'=>array('required'=>'yes', 'blank'=>'yes', 'name'=>'Reports'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to business_id as owner
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'checkAccess');
    $rc = ciniki_businesses_checkAccess($ciniki, $args['business_id'], 'ciniki.businesses.reportDelete');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the current settings for the reports
    //
    $strsql = "SELECT id, uuid "
        . "FROM ciniki_business_reports "
        . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "AND id = '" . ciniki_core_dbQuote($ciniki, $args['report_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.businesses', 'report');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['report']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.businesses.109', 'msg'=>'Reports does not exist.'));
    }
    $report = $rc['report'];

    //
    // Check for any dependencies before deleting
    //

    //
    // Check if any modules are currently using this object
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectCheckUsed');
    $rc = ciniki_core_objectCheckUsed($ciniki, $args['business_id'], 'ciniki.businesses.report', $args['report_id']);
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.businesses.110', 'msg'=>'Unable to check if the reports is still being used.', 'err'=>$rc['err']));
    }
    if( $rc['used'] != 'no' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.businesses.111', 'msg'=>'The reports is still in use. ' . $rc['msg']));
    }

    //
    // Start transaction
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDelete');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.businesses');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Remove the users
    //
    $strsql = "SELECT id, uuid "
        . "FROM ciniki_business_report_users "
        . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "AND report_id = '" . ciniki_core_dbQuote($ciniki, $args['report_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.businesses', 'item');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['rows']) ) {
        $users = $rc['rows'];
        foreach($users as $user) {
            $rc = ciniki_core_objectDelete($ciniki, $args['business_id'], 'ciniki.businesses.reportuser', $user['id'], $user['uuid'], 0x04);
            if( $rc['stat'] != 'ok' ) {
                ciniki_core_dbTransactionRollback($ciniki, 'ciniki.businesses');
                return $rc;
            }
        }
    }

    //
    // Remove the blocks
    //
    $strsql = "SELECT id, uuid "
        . "FROM ciniki_business_report_blocks "
        . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "AND report_id = '" . ciniki_core_dbQuote($ciniki, $args['report_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.businesses', 'item');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['rows']) ) {
        $blocks = $rc['rows'];
        foreach($blocks as $block) {
            $rc = ciniki_core_objectDelete($ciniki, $args['business_id'], 'ciniki.businesses.reportblock', $block['id'], $block['uuid'], 0x04);
            if( $rc['stat'] != 'ok' ) {
                ciniki_core_dbTransactionRollback($ciniki, 'ciniki.businesses');
                return $rc;
            }
        }
    }

    //
    // Remove the report
    //
    $rc = ciniki_core_objectDelete($ciniki, $args['business_id'], 'ciniki.businesses.report',
        $args['report_id'], $report['uuid'], 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.businesses');
        return $rc;
    }

    //
    // Commit the transaction
    //
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.businesses');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Update the last_change date in the business modules
    // Ignore the result, as we don't want to stop user updates if this fails.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'updateModuleChangeDate');
    ciniki_businesses_updateModuleChangeDate($ciniki, $args['business_id'], 'ciniki', 'businesses');

    return array('stat'=>'ok');
}
?>
