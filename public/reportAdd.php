<?php
//
// Description
// -----------
// This method will add a new reports for the business.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:        The ID of the business to add the Reports to.
//
// Returns
// -------
//
function ciniki_businesses_reportAdd(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'),
        'user_ids'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'idlist', 'name'=>'Users'),
        'title'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Title'),
        'frequency'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Frequency'),
        'flags'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Options'),
        'next_date'=>array('required'=>'yes', 'blank'=>'no', 'type'=>'date', 'name'=>'Next Date'),
        'next_time'=>array('required'=>'yes', 'blank'=>'no', 'type'=>'time', 'name'=>'Next Time'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];


    //
    // Check access to business_id as owner
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'checkAccess');
    $rc = ciniki_businesses_checkAccess($ciniki, $args['business_id'], 'ciniki.businesses.reportAdd');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Load business settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'intlSettings');
    $rc = ciniki_businesses_intlSettings($ciniki, $args['business_id']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];

    $ts = strtotime($args['next_date'] . ' ' . $args['next_time']);
    if( $ts === FALSE || $ts < 1 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.businesses.104', 'msg'=>'Invalid date or time format'));
    }
    $dt = new DateTime("@" . $ts, new DateTimezone($intl_timezone));
    $args['next_date'] = $dt->format('Y-m-d H:i:s');

    //
    // Get the list of business users
    //
    $strsql = "SELECT user_id "
        . "FROM ciniki_business_users "
        . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "AND status = 10 "
        . "";
    $rc = ciniki_core_dbQueryList($ciniki, $strsql, 'ciniki.businesses', 'users', 'user_id');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $users = array();
    if( isset($rc['users']) ) {
        $users = $rc['users'];
    }

    //
    // Start transaction
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.businesses');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Add the reports to the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    $rc = ciniki_core_objectAdd($ciniki, $args['business_id'], 'ciniki.businesses.report', $args, 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.businesses');
        return $rc;
    }
    $report_id = $rc['id'];

    //
    // Add the users
    //
    if( isset($args['user_ids']) && is_array($args['user_ids']) ) {
        foreach($args['user_ids'] as $id) {
            if( in_array($id, $users) ) {
                $rc = ciniki_core_objectAdd($ciniki, $args['business_id'], 'ciniki.businesses.reportuser', array(
                    'report_id'=>$report_id,
                    'user_id'=>$id,
                    ), 0x04);
            }
        }
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

    //
    // Update the web index if enabled
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'hookExec');
    ciniki_core_hookExec($ciniki, $args['business_id'], 'ciniki', 'web', 'indexObject', array('object'=>'ciniki.businesses.report', 'object_id'=>$report_id));

    return array('stat'=>'ok', 'id'=>$report_id);
}
?>
