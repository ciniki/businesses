<?php
//
// Description
// ===========
// This method will return subscription information for a business.
//
// Arguments
// ---------
// user_id: 		The user making the request
// 
// Returns
// -------
//
function ciniki_businesses_subscriptionInfo($ciniki) {
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
    $rc = ciniki_businesses_checkAccess($ciniki, $args['business_id'], 'ciniki.businesses.subscriptionInfo'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

	ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'timezoneOffset');
	$utc_offset = ciniki_users_timezoneOffset($ciniki);

	//
	// Get the billing information from the subscription table
	//
	$strsql = "SELECT ciniki_businesses.name, ciniki_businesses.uuid, ciniki_business_subscriptions.status, signup_date, trial_days, "
		. "currency, monthly, paypal_subscr_id, paypal_payer_email, paypal_payer_id, paypal_amount, "
		. "IF(last_payment_date='0000-00-00', '', DATE_FORMAT(CONVERT_TZ(ciniki_business_subscriptions.date_added, '+00:00', '" . ciniki_core_dbQuote($ciniki, $utc_offset) . "'), '%b %e, %Y %l:%i %p')) AS last_payment_date, "
		. "IF(paid_until='0000-00-00', '', DATE_FORMAT(CONVERT_TZ(ciniki_business_subscriptions.paid_until, '+00:00', '" . ciniki_core_dbQuote($ciniki, $utc_offset) . "'), '%b %e, %Y')) AS paid_until, "
		. "trial_days - FLOOR((UNIX_TIMESTAMP(UTC_TIMESTAMP())-UNIX_TIMESTAMP(ciniki_business_subscriptions.signup_date))/86400) AS trial_remaining, "
		. "payment_type, payment_frequency, notes "
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
		$subscription = array('status'=>0, 'status_text'=>'No subscription', 'monthly'=>0);
	} else {
		$subscription = $rc['subscription'];
		if( $subscription['status'] == 0 ) {
			$subscription['status_text'] = 'Unknown';
		} elseif( $subscription['status'] == 1 ) {
			$subscription['status_text'] = 'Update required';
		} elseif( $subscription['status'] == 2 ) {
			$subscription['status_text'] = 'Payment information required';
		} elseif( $subscription['status'] == 10 ) {
			$subscription['status_text'] = 'Active';
		} elseif( $subscription['status'] == 11 ) {
			$subscription['status_text'] = 'Free Subscription';
		} elseif( $subscription['status'] == 50 ) {
			$subscription['status_text'] = 'Suspended';
		} elseif( $subscription['status'] == 60 ) {
			$subscription['status_text'] = 'Cancelled';
		} elseif( $subscription['status'] == 61 ) {
			$subscription['status_text'] = 'Pending Cancel';
		}
	}

	if( isset($subscription['trail_remaining']) && $subscription['trial_remaining'] < 0 ) {
		$subscription['trial_remaining'] = 0;
	} 

	//
	// Get the history
	//

	return array('stat'=>'ok', 'subscription'=>$subscription, 'paypal'=>array(
		'url'=>$ciniki['config']['ciniki.businesses']['paypal.url'],
		'business'=>$ciniki['config']['ciniki.businesses']['paypal.business'],
		'prefix'=>$ciniki['config']['ciniki.businesses']['paypal.item_name.prefix'],
		'ipn'=>$ciniki['config']['ciniki.businesses']['paypal.ipn']),
		);
}
?>
