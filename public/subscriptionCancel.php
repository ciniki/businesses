<?php
//
// Description
// ===========
// Cancel a subscription if active
//
// Arguments
// ---------
// user_id: 		The user making the request
// 
// Returns
// -------
//
function ciniki_businesses_subscriptionCancel($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No business specified'), 
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
    $rc = ciniki_businesses_checkAccess($ciniki, $args['business_id'], 'ciniki.businesses.subscriptionCancel'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
	//
	// Get the billing information from the subscription table
	//
	$strsql = "SELECT id, status, currency, paypal_subscr_id, paypal_payer_email, paypal_payer_id, paypal_amount "
		. "FROM ciniki_business_subscriptions "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "";

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.businesses', 'subscription');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['subscription']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'671', 'msg'=>'No active subscriptions'));
	} 
	$subscription = $rc['subscription'];

	if( $subscription['paypal_subscr_id'] != '' ) {
		// 
		// Send cancel to paypal
		//
		$paypal_args = 'PROFILEID=' . $subscription['paypal_subscr_id'] . '&ACTION=Cancel&Note=' . urlencode('Cancel requested by ' . $ciniki['session']['user']['email']);
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'paypalPost');
		$rc = ciniki_core_paypalPost($ciniki, 'ManageRecurringPaymentsProfileStatus', $paypal_args);
		if( $rc['stat'] !='ok' ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'674', 'msg'=>'Unable to process cancellation, please try again or contact support', 'err'=>$rc['err']));
		}
	} 

	//
	// If active subscription, then update at paypal will be required
	//
	if( $subscription['status'] < 60 ) {
		$strsql = "UPDATE ciniki_business_subscriptions "
			. "SET status = 61 "
			. "WHERE id = '" . ciniki_core_dbQuote($ciniki, $subscription['id']) . "' "
			. "";
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
		$rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.businesses');
		if( $rc['stat'] != 'ok' ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'673', 'msg'=>'Unable to cancel subscription', 'err'=>$rc['err']));
		}
		ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.businesses', 'ciniki_business_history', $args['business_id'], 
			2, 'ciniki_business_subscriptions', $subscription['id'], 'status', '61');
		return $rc;
	}

	return array('stat'=>'ok');
}
?>
