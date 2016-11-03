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
function ciniki_businesses_subscriptionUpdate($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'status'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Status'),
        'currency'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Currency'),
        'monthly'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Monthly'),
        'yearly'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Yearly'),
        'trial_days'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Trial'),
        'trial_start_date'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'date', 'name'=>'Trial Start'),
        'payment_type'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Payment Type'),
        'payment_frequency'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Payment Frequency'),
        'billing_email'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Billing Email'),
        'last_payment_date'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'datetime', 'name'=>'Notes'),
        'paid_until'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'date', 'name'=>'Notes'),
        'notes'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Notes'),
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
    $rc = ciniki_businesses_checkAccess($ciniki, $args['business_id'], 'ciniki.businesses.subscriptionUpdate'); 
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
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.businesses');
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'timezoneOffset');
    $utc_offset = ciniki_users_timezoneOffset($ciniki);

    //
    // Get the existing subscription information
    //
    $strsql = "SELECT id, status, signup_date, trial_days, currency, monthly, yearly "
        . "FROM ciniki_business_subscriptions "
        . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.businesses', 'subscription');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['subscription']) ) {
        $strsql = "INSERT INTO ciniki_business_subscriptions (business_id, signup_date, trial_start_date, status, "
            . "trial_days, currency, monthly, yearly, payment_type, payment_frequency, paid_until, last_payment_date, notes, "
            . "billing_email, "
            . "date_added, last_updated) VALUES ("
            . "'" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "', "
            . "UTC_TIMESTAMP(), ";
        if( isset($args['trial_start_date']) && $args['trial_start_date'] != '' ) {
            $strsql .= "'" . ciniki_core_dbQuote($ciniki, $args['trial_start_date']) . "', ";
        } else {
            $strsql .= "UTC_TIMESTAMP(), ";
        }
        if( isset($args['status']) && $args['status'] != '' ) {
            $strsql .= "'" . ciniki_core_dbQuote($ciniki, $args['status']) . "', ";
        } else {
            $strsql .= "'2', ";
        }
        if( isset($args['trial_days']) && $args['trial_days'] != '' ) {
            $strsql .= "'" . ciniki_core_dbQuote($ciniki, $args['trial_days']) . "', ";
        } else {
            $strsql .= "'60', ";
        }
        if( isset($args['currency']) && $args['currency'] != '' ) {
            $strsql .= "'" . ciniki_core_dbQuote($ciniki, $args['currency']) . "', ";
        } else {
            $strsql .= "'USD', ";
        }
        if( isset($args['monthly']) && $args['monthly'] != '' ) {
            $strsql .= "'" . ciniki_core_dbQuote($ciniki, $args['monthly']) . "', ";
        } else {
            $strsql .= "'10.00', ";
        }
        if( isset($args['yearly']) && $args['yearly'] != '' ) {
            $strsql .= "'" . ciniki_core_dbQuote($ciniki, $args['yearly']) . "', ";
        } else {
            $strsql .= "0, ";
        }
        if( isset($args['payment_type']) ) {
            $strsql .= "'" . ciniki_core_dbQuote($ciniki, $args['payment_type']) . "', ";
        } else {
            $strsql .= "'paypal', ";
        }
        if( isset($args['payment_frequency']) && $args['payment_frequency'] != '' ) {
            $strsql .= "'" . ciniki_core_dbQuote($ciniki, $args['payment_frequency']) . "', ";
        } else {
            $strsql .= "'10', ";
        }
        if( isset($args['paid_until']) && $args['paid_until'] != '' ) {
            $strsql .= "'" . ciniki_core_dbQuote($ciniki, $args['paid_until']) . "', ";
        } else {
            $strsql .= "'', ";
        }
        if( isset($args['last_payment_date']) && $args['last_payment_date'] != '' ) {
            $strsql .= "CONVERT_TZ('" . ciniki_core_dbQuote($ciniki, $args['last_payment_date']) . "', '" . ciniki_core_dbQuote($ciniki, $utc_offset) . "', '+00:00'), ";
//          $strsql .= "'" . ciniki_core_dbQuote($ciniki, $args['last_payment_date']) . "', ";
        } else {
            $strsql .= "'', ";
        }
        if( isset($args['notes']) && $args['notes'] != '' ) {
            $strsql .= "'" . ciniki_core_dbQuote($ciniki, $args['notes']) . "', ";
        } else {
            $strsql .= "'', ";
        }
        if( isset($args['billing_email']) && $args['billing_email'] != '' ) {
            $strsql .= "'" . ciniki_core_dbQuote($ciniki, $args['billing_email']) . "', ";
        } else {
            $strsql .= "'', ";
        }
        $strsql .= "UTC_TIMESTAMP(), UTC_TIMESTAMP())";
        $rc = ciniki_core_dbInsert($ciniki, $strsql, 'ciniki.businesses');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $subscription_id = $rc['insert_id'];
        
        if( isset($args['currency']) && $args['currency'] != '' ) {
            ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.businesses', 'ciniki_business_history', $args['business_id'], 
                2, 'ciniki_business_subscriptions', $subscription_id, 'currency', $args['currency']);
        }
        if( isset($args['trial_days']) && $args['trial_days'] != '' ) {
            ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.businesses', 'ciniki_business_history', $args['business_id'], 
                2, 'ciniki_business_subscriptions', $subscription_id, 'trial_days', $args['trial_days']);
        }
        if( isset($args['monthly']) && $args['monthly'] != '' ) {
            ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.businesses', 'ciniki_business_history', $args['business_id'], 
                2, 'ciniki_business_subscriptions', $subscription_id, 'monthly', $args['monthly']);
        }
        if( isset($args['yearly']) && $args['yearly'] != '' ) {
            ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.businesses', 'ciniki_business_history', $args['business_id'], 
                2, 'ciniki_business_subscriptions', $subscription_id, 'yearly', $args['yearly']);
        }

    } else {
        $subscription = $rc['subscription'];

        //
        // Start building the update SQL
        //
        $strsql = "UPDATE ciniki_business_subscriptions SET last_updated = UTC_TIMESTAMP()";

        //
        // Update the status
        //
//      if( (isset($args['currency']) && $args['currency'] != '' && $subscription['currency'] != $args['currency'])
//          || (isset($args['trial_days']) && $args['trial_days'] != '' && $subscription['trial_days'] != $args['trial_days'])
//          || (isset($args['monthly']) && $args['monthly'] != '' && $subscription['monthly'] != $args['monthly'])
//          ) {
//          if( isset($args['monthly']) && $args['monthly'] == '0' ) {
//              $strsql .= ", status = 10";
//          }
//          elseif( $subscription['status'] == 60 ) {
//              $strsql .= ", status = 2";
//          } else {
//              $strsql .= ", status = 1";
//          }
//      }

        //
        // Add all the fields to the change log
        //
        $changelog_fields = array(
            'status',
            'currency',
            'monthly',
            'yearly',
            'trial_days',
            'trial_start_date',
            'last_payment_date',
            'payment_type',
            'payment_frequency',
            'paid_until',
            'notes',
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
                $rc = ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.businesses', 'ciniki_business_history', $args['business_id'], 
                    2, 'ciniki_business_subscriptions', $subscription['id'], $field, $args[$field]);
            }
        }
        $strsql .= " WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . "";
        $rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.businesses');
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.businesses');
            return $rc;
        }
        if( !isset($rc['num_affected_rows']) || $rc['num_affected_rows'] != 1 ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.businesses');
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.businesses.78', 'msg'=>'Unable to update subscription'));
        }
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
