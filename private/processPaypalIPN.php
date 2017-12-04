<?php
//
// Description
// -----------
// This method will log an IPN entry from paypal.  Each time a subscription is creating, updated or cancelled,
// an IPN will be sent to the server.
//
// Returns
// -------
// <rsp stat='ok' />
//
function ciniki_tenants_processPaypalIPN($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'txn_type'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'errmsg'=>'No txn_type specified'),
        'subscr_id'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'errmsg'=>'No subscr_id specified'),
        'first_name'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'errmsg'=>'No first_name specified'),
        'last_name'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'errmsg'=>'No last_name specified'),
        'payer_id'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'errmsg'=>'No payer_id specified'),
        'payer_email'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'errmsg'=>'No payer_email specified'),
        'item_name'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'errmsg'=>'No item_name specified'),
        'item_number'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'errmsg'=>'No item_number specified'),
        'mc_currency'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'errmsg'=>'No mc_currency specified'),
        'mc_fee'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'errmsg'=>'No mc_fee specified'),
        'mc_gross'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'errmsg'=>'No mc_gross specified'),
        'mc_amount3'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'errmsg'=>'No mc_amount3 specified'),
        ));
    if( $rc['stat'] != 'ok' ) {
        error_log("PAYPAL-IPN: " . $ciniki['request']['args']['ipn_track_id'] . ' - ' . $rc['err']['code'] . ' - ' . $rc['err']['msg']);
    }
    if( !isset($rc['args']) ) {
        $args = array('txn_type'=>'', 
            'subscr_id'=>'',
            'first_name'=>'',
            'last_name'=>'',
            'payer_id'=>'',
            'payer_email'=>'',
            'item_name'=>'',
            'item_number'=>'',
            'mc_currency'=>'',
            'mc_fee'=>'',
            'mc_gross'=>'',
            'mc_amount3'=>'',
            );
    } else {
        $args = $rc['args'];
    }

    //
    // Lookup the tnid for the IPN
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    $strsql = "SELECT id, status, name "
        . "FROM ciniki_tenants "
        . "WHERE uuid = '" . ciniki_core_dbQuote($ciniki, $args['item_number']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.tenants', 'tenant');
    if( $rc['stat'] != 'ok') {
        error_log("PAYPAL-IPN: " . $args['ipn_track_id'] . ' - ' . $rc['err']['code'] . ' - ' . $rc['err']['msg']);
    }
    $tnid = 0;
    $tenant_status = 0;
    if( !isset($rc['tenant']) ) {
        error_log("PAYPAL-IPN: " . $args['ipn_track_id'] . "Unable to locate tenant " . $args['item_number'] . ' for ' . $args['item_name']);
    } else {
        $tnid = $rc['tenant']['id'];
        $tenant_status = $rc['tenant']['status'];
    }


    //
    // Enter the information into the paypal_log
    //
    $strsql = "INSERT INTO ciniki_tenant_paypal_log (tnid, status, txn_type, "
        . "subscr_id, first_name, last_name, "
        . "payer_id, payer_email, "
        . "item_name, item_number, "
        . "mc_currency, mc_fee, mc_gross, mc_amount3, "
        . "serialized_args, "
        . "date_added, last_updated) VALUES ("
        . "'" . ciniki_core_dbQuote($ciniki, $tnid) . "', "
        . "0, "
        . "'" . ciniki_core_dbQuote($ciniki, $args['txn_type']) . "', "
        . "'" . ciniki_core_dbQuote($ciniki, $args['subscr_id']) . "', "
        . "'" . ciniki_core_dbQuote($ciniki, $args['first_name']) . "', "
        . "'" . ciniki_core_dbQuote($ciniki, $args['last_name']) . "', "
        . "'" . ciniki_core_dbQuote($ciniki, $args['payer_id']) . "', "
        . "'" . ciniki_core_dbQuote($ciniki, $args['payer_email']) . "', "
        . "'" . ciniki_core_dbQuote($ciniki, $args['item_name']) . "', "
        . "'" . ciniki_core_dbQuote($ciniki, $args['item_number']) . "', "
        . "'" . ciniki_core_dbQuote($ciniki, $args['mc_currency']) . "', "
        . "'" . ciniki_core_dbQuote($ciniki, $args['mc_fee']) . "', "
        . "'" . ciniki_core_dbQuote($ciniki, $args['mc_gross']) . "', "
        . "'" . ciniki_core_dbQuote($ciniki, $args['mc_amount3']) . "', "
        . "'" . ciniki_core_dbQuote($ciniki, serialize($ciniki['request']['args'])) . "', "
        . "UTC_TIMESTAMP(), UTC_TIMESTAMP()"
        . ")";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbInsert');
    $rc = ciniki_core_dbInsert($ciniki, $strsql, 'ciniki.tenants');
    if( $rc['stat'] != 'ok' ) {
        error_log("PAYPAL-IPN: " . $args['ipn_track_id'] . " - Unable to log " . $args['item_number'] . ' for ' . $args['item_name']);
    }
    $log_id = 0;
    if( isset($rc['insert_id']) && $rc['insert_id'] > 0 ) {
        $log_id = $rc['insert_id'];
    } else {
        error_log("PAYPAL-IPN: " . $args['ipn_track_id'] . " - Unable to log " . $args['item_number'] . ' for ' . $args['item_name']);
    }

    //
    // If no tenant was found, then 
    //
    if( $tnid < 1 ) {
        return array('stat'=>'ok');
    }

    //
    // Lookup the subscription id
    //
    $strsql = "SELECT id "
        . "FROM ciniki_tenant_subscriptions "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.tenants', 'subscription');
    if( $rc['stat'] != 'ok') {
        error_log("PAYPAL-IPN: " . $args['ipn_track_id'] . ' - ' . $rc['err']['code'] . ' - ' . $rc['err']['msg']);
    }
    if( !isset($rc['subscription']) ) {
        error_log("PAYPAL-IPN: " . $args['ipn_track_id'] . ' - No tenant subscription found');
        return array('stat'=>'ok');
    }
    $subscription_id = $rc['subscription']['id'];

    //
    // subscr_signup - initial setup of subscription
    //
    if( $args['txn_type'] == 'subscr_signup' ) {
        //
        // Update the subscription
        //
        $strsql = "UPDATE ciniki_tenant_subscriptions "
            . "SET status = 10 "
            . ", paypal_subscr_id = '" . ciniki_core_dbQuote($ciniki, $args['subscr_id']) . "' "
            . ", paypal_payer_id = '" . ciniki_core_dbQuote($ciniki, $args['payer_id']) . "' "
            . ", paypal_payer_email = '" . ciniki_core_dbQuote($ciniki, $args['payer_email']) . "' "
            . ", paypal_amount = '" . ciniki_core_dbQuote($ciniki, $args['mc_amount3']) . "' "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
        $rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.tenants');
        if( $rc['stat'] != 'ok' ) {
            error_log("PAYPAL-IPN: " . $args['ipn_track_id'] . " - Unable to update tenant subscription");
        }
        ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.tenants', 'ciniki_tenant_history', $tnid, 
            2, 'ciniki_tenant_subscriptions', $subscription_id, 'status', '10');
        ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.tenants', 'ciniki_tenant_history', $tnid, 
            2, 'ciniki_tenant_subscriptions', $subscription_id, 'paypal_subscr_id', $args['subscr_id']);
        ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.tenants', 'ciniki_tenant_history', $tnid, 
            2, 'ciniki_tenant_subscriptions', $subscription_id, 'paypal_payer_id', $args['payer_id']);
        ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.tenants', 'ciniki_tenant_history', $tnid, 
            2, 'ciniki_tenant_subscriptions', $subscription_id, 'paypal_payer_email', $args['payer_email']);
        ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.tenants', 'ciniki_tenant_history', $tnid, 
            2, 'ciniki_tenant_subscriptions', $subscription_id, 'paypal_amount', $args['mc_amount3']);

        //
        // Unsuspend the tenant if suspended
        //
        if( $tenant_status == 50 ) {
            $strsql = "UPDATE ciniki_tenants SET status = 1 "
                . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . "AND status = 50 ";
            $rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.tenants');
            if( $rc['stat'] != 'ok' ) {
                error_log("PAYPAL-IPN: " . $args['ipn_track_id'] . " - Unable to unsuspend tenant");
            }
            ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.tenants', 'ciniki_tenant_history', $tnid, 
                2, 'ciniki_tenants', $tnid, 'status', '50');
        }
    }

    //
    // subscr_modify - modified their subscription
    //
    elseif( $args['txn_type'] == 'subscr_modify' ) {
        //
        // Update the subscription
        //
        $strsql = "UPDATE ciniki_tenant_subscriptions "
            . "SET status = 10, last_updated = UTC_TIMESTAMP() "
            . "";
        if( $args['subscr_id'] != '' ) {
            $strsql .= ", paypal_subscr_id = '" . ciniki_core_dbQuote($ciniki, $args['subscr_id']) . "' ";
            ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.tenants', 'ciniki_tenant_history', $tnid, 
                2, 'ciniki_tenant_subscriptions', $subscription_id, 'paypal_subscr_id', $args['subscr_id']);
        }
        if( $args['payer_id'] != '' ) {
            $strsql .= ", paypal_payer_id = '" . ciniki_core_dbQuote($ciniki, $args['payer_id']) . "' ";
            ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.tenants', 'ciniki_tenant_history', $tnid, 
                2, 'ciniki_tenant_subscriptions', $subscription_id, 'paypal_payer_id', $args['payer_id']);
        }
        if( $args['payer_email'] != '' ) {
            $strsql .= ", paypal_payer_email = '" . ciniki_core_dbQuote($ciniki, $args['payer_email']) . "' ";
            ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.tenants', 'ciniki_tenant_history', $tnid, 
                2, 'ciniki_tenant_subscriptions', $subscription_id, 'paypal_payer_email', $args['payer_email']);
        }
        if( $args['mc_amount3'] != '' ) {
            $strsql .= ", paypal_amount = '" . ciniki_core_dbQuote($ciniki, $args['mc_amount3']) . "' ";
            ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.tenants', 'ciniki_tenant_history', $tnid, 
                2, 'ciniki_tenant_subscriptions', $subscription_id, 'paypal_amount', $args['mc_amount3']);
        }
        $strsql .= "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
        $rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.tenants');
        if( $rc['stat'] != 'ok' ) {
            error_log("PAYPAL-IPN: " . $args['ipn_track_id'] . " - Unable to update tenant subscription");
        }
        ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.tenants', 'ciniki_tenant_history', $tnid, 
            2, 'ciniki_tenant_subscriptions', $subscription_id, 'status', '10');

        //
        // Unsuspend the tenant if suspended
        //
        if( $tenant_status == 50 ) {
            $strsql = "UPDATE ciniki_tenants SET status = 1 "
                . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . "AND status = 50 ";
            $rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.tenants');
            if( $rc['stat'] != 'ok' ) {
                error_log("PAYPAL-IPN: " . $args['ipn_track_id'] . " - Unable to unsuspend tenant");
            }
            ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.tenants', 'ciniki_tenant_history', $tnid, 
                2, 'ciniki_tenants', $tnid, 'status', '1');
        }
    }

    //
    // subscr_payment - received a recurring payment
    //
    elseif( $args['txn_type'] == 'subscr_payment' ) {
        // 
        // Update the subscription
        //
        $strsql = "UPDATE ciniki_tenant_subscriptions "
            . "SET last_payment_date = UTC_TIMESTAMP() "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
        $rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.tenants');
        if( $rc['stat'] != 'ok' ) {
            error_log("PAYPAL-IPN: " . $args['ipn_track_id'] . " - Unable to cancel tenant subscription");
        }
    }

    //
    // subscr_cancel - the customer cancelled their subscription
    //
    elseif( $args['txn_type'] == 'subscr_cancel' ) {
        // 
        // Update the subscription
        //
        $strsql = "UPDATE ciniki_tenant_subscriptions "
            . "SET status = 60 "
            . ", paypal_subscr_id = '" . ciniki_core_dbQuote($ciniki, $args['subscr_id']) . "' "
            . ", paypal_payer_id = '" . ciniki_core_dbQuote($ciniki, $args['payer_id']) . "' "
            . ", paypal_payer_email = '" . ciniki_core_dbQuote($ciniki, $args['payer_email']) . "' "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
        $rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.tenants');
        if( $rc['stat'] != 'ok' ) {
            error_log("PAYPAL-IPN: " . $args['ipn_track_id'] . " - Unable to cancel tenant subscription");
        }
        ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.tenants', 'ciniki_tenant_history', $tnid, 
            2, 'ciniki_tenant_subscriptions', $subscription_id, 'status', '60');

        //
        // Suspend the tenant
        //
        if( $tenant_status == 1 ) {
            $strsql = "UPDATE ciniki_tenants SET status = 50 "
                . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . "AND status = 1 ";
            $rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.tenants');
            if( $rc['stat'] != 'ok' ) {
                error_log("PAYPAL-IPN: " . $args['ipn_track_id'] . " - Unable to suspend tenant");
            }
            ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.tenants', 'ciniki_tenant_history', $tnid, 
                2, 'ciniki_tenants', $tnid, 'status', '50');
        }
    }

    else {
        error_log("PAYPAL-IPN: " . $args['ipn_track_id'] . " - Unable to unknown transaction type");
    }
    
    return array('stat'=>'ok');
}
