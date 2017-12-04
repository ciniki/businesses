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
function ciniki_tenants_subscriptionCustomerUpdate($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'currency'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Currency'),
        'payment_frequency'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Payment Frequency'),
        'billing_email'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Billing Email'),
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];
    
    //  
    // Make sure this module is activated, and
    // check permission to run this function for this tenant
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'checkAccess');
    $rc = ciniki_tenants_checkAccess($ciniki, $args['tnid'], 'ciniki.tenants.subscriptionCustomerUpdate'); 
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
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbInsert');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.tenants');
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'timezoneOffset');
    $utc_offset = ciniki_users_timezoneOffset($ciniki);

    //
    // Get the existing subscription information
    //
    $strsql = "SELECT id, status, signup_date, trial_days, currency, monthly, yearly "
        . "FROM ciniki_tenant_subscriptions "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.tenants', 'subscription');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['subscription']) ) {
        $subscription = $rc['subscription'];

        //
        // Start building the update SQL
        //
        $strsql = "UPDATE ciniki_tenant_subscriptions SET last_updated = UTC_TIMESTAMP()";

        //
        // Add all the fields to the change log
        //
        $changelog_fields = array(
            'currency',
            'payment_frequency',
            'billing_email',
            );
        foreach($changelog_fields as $field) {
            if( isset($args[$field]) ) {
                if( $field == 'last_payment_date' ) {
                    $strsql .= ", last_payment_date = CONVERT_TZ('" . ciniki_core_dbQuote($ciniki, $args['last_payment_date']) . "', '" . ciniki_core_dbQuote($ciniki, $utc_offset) . "', '+00:00') ";
                } else {
                    $strsql .= ", $field = '" . ciniki_core_dbQuote($ciniki, $args[$field]) . "' ";
                }
                // FIXME: Stored converted date/time in history
                $rc = ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.tenants', 'ciniki_tenant_history', $args['tnid'], 
                    2, 'ciniki_tenant_subscriptions', $subscription['id'], $field, $args[$field]);
            }
        }
        $strsql .= " WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "";
        $rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.tenants');
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.tenants');
            return $rc;
        }
        if( !isset($rc['num_affected_rows']) || $rc['num_affected_rows'] != 1 ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.tenants');
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.tenants.103', 'msg'=>'Unable to update subscription'));
        }
    }


    //
    // Commit the database changes
    //
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.tenants');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    return array('stat'=>'ok');
}
?>
