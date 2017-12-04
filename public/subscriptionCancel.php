<?php
//
// Description
// ===========
// Cancel a subscription if active
//
// Arguments
// ---------
// user_id:         The user making the request
// 
// Returns
// -------
//
function ciniki_tenants_subscriptionCancel($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
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
    $rc = ciniki_tenants_checkAccess($ciniki, $args['tnid'], 'ciniki.tenants.subscriptionCancel'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    //
    // Get the billing information from the subscription table
    //
    $strsql = "SELECT id, status, currency, paypal_subscr_id, paypal_payer_email, paypal_payer_id, paypal_amount, "
        . "stripe_customer_id, stripe_subscription_id "
        . "FROM ciniki_tenant_subscriptions "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.tenants', 'subscription');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['subscription']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.tenants.64', 'msg'=>'No active subscriptions'));
    } 
    $subscription = $rc['subscription'];

    //
    // Cancel a stripe subscription
    //
    if( $subscription['stripe_customer_id'] != '' && $subscription['stripe_subscription_id'] != '' ) {
        require_once($ciniki['config']['ciniki.core']['lib_dir'] . '/Stripe/init.php');
        \Stripe\Stripe::setApiKey($ciniki['config']['ciniki.tenants']['stripe.secret']);

        //
        // Issue the stripe customer create
        //
        try {
            $sub = \Stripe\Subscription::retrieve($subscription['stripe_subscription_id']);
            $sub->cancel();
        } catch( Exception $e) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.tenants.65', 'msg'=>'Unable to cancel subscription. Please contact us for help.'));
        }

        //
        // If active subscription, then update at paypal will be required
        //
        if( $subscription['status'] < 60 ) {
            $strsql = "UPDATE ciniki_tenant_subscriptions "
                . "SET status = 60 "
                . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $subscription['id']) . "' "
                . "";
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
            $rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.tenants');
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.tenants.66', 'msg'=>'Unable to cancel subscription', 'err'=>$rc['err']));
            }
            ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.tenants', 'ciniki_tenant_history', $args['tnid'], 
                2, 'ciniki_tenant_subscriptions', $subscription['id'], 'status', '61');
            return $rc;
        }
    }

    //
    // Cancel a paypal subscription
    //
    elseif( $subscription['paypal_subscr_id'] != '' ) {
        // 
        // Send cancel to paypal
        //
        $paypal_args = 'PROFILEID=' . $subscription['paypal_subscr_id'] . '&ACTION=Cancel&Note=' . urlencode('Cancel requested by ' . $ciniki['session']['user']['email']);
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'paypalPost');
        $rc = ciniki_core_paypalPost($ciniki, 'ManageRecurringPaymentsProfileStatus', $paypal_args);
        if( $rc['stat'] !='ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.tenants.67', 'msg'=>'Unable to process cancellation, please try again or contact support', 'err'=>$rc['err']));
        }

        //
        // If active subscription, then update at paypal will be required
        //
        if( $subscription['status'] < 60 ) {
            $strsql = "UPDATE ciniki_tenant_subscriptions "
                . "SET status = 61 "
                . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $subscription['id']) . "' "
                . "";
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
            $rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.tenants');
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.tenants.68', 'msg'=>'Unable to cancel subscription', 'err'=>$rc['err']));
            }
            ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.tenants', 'ciniki_tenant_history', $args['tnid'], 
                2, 'ciniki_tenant_subscriptions', $subscription['id'], 'status', '61');
            return $rc;
        }
    } 


    return array('stat'=>'ok');
}
?>
