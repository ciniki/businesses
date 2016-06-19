<?php
//
// Description
// ===========
// This function will update the plan information
//
// Arguments
// ---------
// user_id:         The user making the request
// 
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_businesses_planUpdate($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'plan_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Plan'), 
        'name'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Name'), 
        'flags'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Flags'), 
        'sequence'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Sequence'), 
        'monthly'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Monthly Price'),
        'modules'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Modules'),
        'trial_days'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Number of Trial Days'),
        'description'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Description'),
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
    $rc = ciniki_businesses_checkAccess($ciniki, $args['business_id'], 'ciniki.businesses.planUpdate'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //  
    // Turn off autocommit
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.businesses');
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // Start building the update SQL
    //
    $strsql = "UPDATE ciniki_business_plans SET last_updated = UTC_TIMESTAMP()";

    //
    // Add all the fields to the change log
    //
    $changelog_fields = array(
        'name',
        'flags',
        'sequence',
        'monthly',
        'modules',
        'trial_days',
        'description',
        );
    foreach($changelog_fields as $field) {
        if( isset($args[$field]) ) {
            $strsql .= ", $field = '" . ciniki_core_dbQuote($ciniki, $args[$field]) . "' ";
            $rc = ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.businesses', 'ciniki_business_history', $args['business_id'], 
                2, 'ciniki_business_plans', $args['plan_id'], $field, $args[$field]);
        }
    }
    $strsql .= "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "AND id = '" . ciniki_core_dbQuote($ciniki, $args['plan_id']) . "' ";
    $rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.businesses');
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.businesses');
        return $rc;
    }
    if( !isset($rc['num_affected_rows']) || $rc['num_affected_rows'] != 1 ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.businesses');
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'670', 'msg'=>'Unable to update plan'));
    }

    //
    // Commit the database changes
    //
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.businesses');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    return array('stat'=>'ok');
}
?>
