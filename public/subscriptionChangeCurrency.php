<?php
//
// Description
// ===========
// This method will change the currency for the subscription, and update the status
// if an updated with paypal is required.
//
// Arguments
// ---------
// user_id:         The user making the request
// 
// Returns
// -------
//
function ciniki_tenants_subscriptionChangeCurrency($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'currency'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Currency'), 
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
    $rc = ciniki_tenants_checkAccess($ciniki, $args['tnid'], 'ciniki.tenants.subscriptionChangeCurrency'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // Check which currency
    //
    if( $args['currency'] != 'USD' 
        && $args['currency'] != 'CAD'
        ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.tenants.69', 'msg'=>'Currency must be USD or CAD.'));
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    //
    // Get the billing information from the subscription table
    //
    $strsql = "SELECT id, status, currency, paypal_subscr_id, paypal_payer_email, paypal_payer_id, paypal_amount "
        . "FROM ciniki_tenant_subscriptions "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.tenants', 'subscription');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['subscription']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.tenants.70', 'msg'=>'Unable to change currency'));
    } 
    $subscription = $rc['subscription'];

    if( $rc['subscription']['currency'] == $args['currency'] ) {
        return array('stat'=>'ok');
    }

    //
    // If active subscription, then update at paypal will be required
    //
    if( $rc['subscription']['status'] == 10 ) {
        $strsql = "UPDATE ciniki_tenant_subscriptions "
            . "SET currency = '" . ciniki_core_dbQuote($ciniki, $args['currency']) . "' "
            . ", status = 1 "
            . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $subscription['id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
        $rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.tenants');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.tenants.71', 'msg'=>'Unable to change currency', 'err'=>$rc['err']));
        }
        ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.tenants', 'ciniki_tenant_history', $args['tnid'], 
            2, 'ciniki_tenant_subscriptions', $subscription['id'], 'currency', $args['currency']);
        ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.tenants', 'ciniki_tenant_history', $args['tnid'], 
            2, 'ciniki_tenant_subscriptions', $subscription['id'], 'status', '1');
        return $rc;
    }

    //
    // If already pending update required, don't change status
    //
    elseif( $rc['subscription']['status'] == 1 || $rc['subscription']['status'] == 2 || $rc['subscription']['status'] == 60 ) {
        $strsql = "UPDATE ciniki_tenant_subscriptions "
            . "SET currency = '" . ciniki_core_dbQuote($ciniki, $args['currency']) . "' "
            . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $subscription['id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
        $rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.tenants');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.tenants.72', 'msg'=>'Unable to change currency', 'err'=>$rc['err']));
        }
        ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.tenants', 'ciniki_tenant_history', $args['tnid'], 
            2, 'ciniki_tenant_subscriptions', $subscription['id'], 'currency', $args['currency']);
        return $rc;
    }

    //
    // Get the history
    //

    return array('stat'=>'ok');
}
?>
