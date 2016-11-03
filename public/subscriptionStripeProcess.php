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
function ciniki_businesses_subscriptionStripeProcess($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'currency'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Currency'),
        'payment_frequency'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Payment Frequency'),
        'billing_email'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Billing Email'),
        'action'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Action'),
        'token'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Stripe Token'),
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
    $rc = ciniki_businesses_checkAccess($ciniki, $args['business_id'], 'ciniki.businesses.subscriptionStripeProcess'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'php');

    //
    // Load the subscription
    //
    $strsql = "SELECT ciniki_business_subscriptions.id, "
        . "ciniki_business_subscriptions.status, "
        . "ciniki_businesses.name, "
        . "signup_date, "
        . "trial_days, "
        . "currency, "
        . "monthly, "
        . "yearly, "
        . "billing_email, "
        . "DATE_FORMAT(trial_start_date, '%b %e, %Y') AS trial_start_date, "
        . "payment_type, "
        . "payment_frequency, "
        . "notes "
        . "FROM ciniki_business_subscriptions, ciniki_businesses "
        . "WHERE ciniki_business_subscriptions.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "AND ciniki_business_subscriptions.business_id = ciniki_businesses.id "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.businesses', 'subscription');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['subscription']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.businesses.73', 'msg'=>'The subscription does not exist'));
    }
    $subscription = $rc['subscription'];

    //
    // Check if they changed anything before submitting
    //
    $update_args = array();
    if( isset($args['currency']) && $args['currency'] != $subscription['currency'] ) {
        $update_args['currency'] = $args['currency'];
        $subscription['currency'] = $args['currency'];
    }
    if( isset($args['payment_frequency']) && $args['payment_frequency'] != $subscription['payment_frequency'] ) {
        $update_args['payment_frequency'] = $args['payment_frequency'];
        $subscription['payment_frequency'] = $args['payment_frequency'];
    }
    if( isset($args['billing_email']) && $args['billing_email'] != $subscription['billing_email'] ) {
        $update_args['billing_email'] = $args['billing_email'];
        $subscription['billing_email'] = $args['billing_email'];
    }

    //
    // Setup stripe plan and trial_end_date
    //
    $now = new DateTime('now', new DateTimeZone('UTC'));
    $trial_end_date = new DateTime($subscription['trial_start_date'], new DateTimeZone('UTC'));
    if( $subscription['trial_days'] > 0 ) {
        $trial_end_date->add(new DateInterval('P' . $subscription['trial_days'] . 'D'));
    }
    $subscription['trial_remaining'] = $now->diff($trial_end_date)->format('%r%a');
    if( $subscription['trial_remaining'] > 0 ) {
        $subscription['trial_end'] = $trial_end_date->format($date_format);
        $subscription['trial_end_ts'] = $trial_end_date->format('U');
    }

    if( $subscription['currency'] == 'CAD' ) {
        $subscription['stripe_plan'] = 'cad_';
    } elseif( $subscription['currency'] == 'USD' ) {
        $subscription['stripe_plan'] = 'usd_';
    } else {
        $subscription['stripe_plan'] = '';
    }
    if( $subscription['stripe_plan'] != '' ) {
        if( $subscription['payment_frequency'] == 10 ) {
            
            $subscription['stripe_plan'] .= 'monthly';
            $subscription['stripe_quantity'] = floor($subscription['monthly']);
        } elseif( $subscription['payment_frequency'] == 20 ) {
            $subscription['stripe_plan'] .= 'yearly';
            $subscription['stripe_quantity'] = floor($subscription['yearly']);
        }
    }

    if( $subscription['stripe_plan'] == '' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.businesses.74', 'msg'=>'No plan for the subscription.'));
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

    //
    // Decide what should be done.
    //
    if( $args['action'] == 'subscribe' ) {

        require_once($ciniki['config']['ciniki.core']['lib_dir'] . '/Stripe/init.php');
        \Stripe\Stripe::setApiKey($ciniki['config']['ciniki.businesses']['stripe.secret']);

        //
        // Issue the stripe customer create
        //
        $stripe_customer = array(
            'description'=>$subscription['name'],
            'source'=>$args['token'],
            'email'=>$subscription['billing_email'],
            );
        try {
            $customer = \Stripe\Customer::create($stripe_customer);
            $subscription['stripe_customer_id'] = $customer['id'];
            $update_args['stripe_customer_id'] = $customer['id'];
        } catch( Exception $e) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.businesses.75', 'msg'=>$e->getMessage()));
        }

        //
        // Set the customer to the plan
        //
        $stripe_subscription = array(
            'customer'=>$subscription['stripe_customer_id'],
            'plan'=>$subscription['stripe_plan'],
            'quantity'=>$subscription['stripe_quantity'],
            );
        if( isset($subscription['trial_end_ts']) ) {
            $stripe_subscription['trial_end'] = $subscription['trial_end_ts'];
        }

        try {
            $sub = \Stripe\Subscription::create($stripe_subscription);
            $update_args['stripe_subscription_id'] = $sub['id'];
        } catch( Exception $e) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.businesses.76', 'msg'=>$e->getMessage()));
        }

        //
        // Subscription succeeded
        //
        $update_args['status'] = 10;
        if( $subscription['payment_type'] != 'stripe' ) {
            $update_args['payment_type'] = 'stripe';
        }

        $strsql = '';
        foreach($update_args as $fname => $fvalue) {
            $strsql .= ($strsql != '' ? ', ' : '') . "$fname = '" . ciniki_core_dbQuote($ciniki, $fvalue) . "' ";
        }
        $strsql = "UPDATE ciniki_business_subscriptions SET " 
            . $strsql 
            . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $subscription['id']) . "' "
            . "AND business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . "";
        $rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.businesses');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.businesses.77', 'msg'=>'Unable to update subscription', 'err'=>$rc['err']));
        }
        foreach($update_args as $fname => $fvalue) {
            ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.businesses', 'ciniki_business_history', $args['business_id'], 
                2, 'ciniki_business_subscriptions', $subscription['id'], $fname, $fvalue);
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
