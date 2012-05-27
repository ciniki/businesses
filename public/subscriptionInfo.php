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
    require_once($ciniki['config']['core']['modules_dir'] . '/core/private/prepareArgs.php');
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
    require_once($ciniki['config']['core']['modules_dir'] . '/businesses/private/checkAccess.php');
    $rc = ciniki_businesses_checkAccess($ciniki, $args['business_id'], 'ciniki.businesses.subscriptionInfo'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

	require_once($ciniki['config']['core']['modules_dir'] . '/users/private/timezoneOffset.php');
	$utc_offset = ciniki_users_timezoneOffset($ciniki);

	//
	// Get the billing information from the subscription table
	//
	$strsql = "SELECT ciniki_businesses.name, ciniki_businesses.uuid, ciniki_business_subscriptions.status, signup_date, trial_days, "
		. "currency, monthly, paypal_subscr_id, paypal_payer_email, paypal_payer_id, paypal_amount, "
		. "IF(last_payment_date='0000-00-00', '', DATE_FORMAT(CONVERT_TZ(ciniki_business_subscriptions.date_added, '+00:00', '" . ciniki_core_dbQuote($ciniki, $utc_offset) . "'), '%b %e, %Y %l:%i %p')) AS last_payment_date, "
		. "trial_days - FLOOR((UNIX_TIMESTAMP(UTC_TIMESTAMP())-UNIX_TIMESTAMP(ciniki_business_subscriptions.signup_date))/86400) AS trial_remaining "
		. "FROM ciniki_business_subscriptions, ciniki_businesses "
		. "WHERE ciniki_business_subscriptions.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND ciniki_business_subscriptions.business_id = ciniki_businesses.id "
		. "";

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'businesses', 'subscription');
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
		} elseif( $subscription['status'] == 50 ) {
			$subscription['status_text'] = 'Suspended';
		} elseif( $subscription['status'] == 60 ) {
			$subscription['status_text'] = 'Cancelled';
		} elseif( $subscription['status'] == 61 ) {
			$subscription['status_text'] = 'Pending Cancel';
		}
	}

	if( $subscription['trial_remaining'] < 0 ) {
		$subscription['trial_remaining'] = 0;
	}

	//
	// Get the history
	//

	return array('stat'=>'ok', 'subscription'=>$subscription, 'paypal'=>array(
		'url'=>$ciniki['config']['businesses']['paypal.url'],
		'business'=>$ciniki['config']['businesses']['paypal.business'],
		'prefix'=>$ciniki['config']['businesses']['paypal.item_name.prefix'],
		'ipn'=>$ciniki['config']['businesses']['paypal.ipn']),
		);
}
?>
