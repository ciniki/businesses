<?php
//
// Description
// ===========
// This method will return the list of businesses, their status and subcription information
//
// Arguments
// ---------
// user_id: 		The user making the request
// 
// Returns
// -------
//
function ciniki_businesses_subscriptionStatus($ciniki) {
    //  
    // Make sure this module is activated, and
    // check permission to run this function for this business
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'checkAccess');
    $rc = ciniki_businesses_checkAccess($ciniki, 0, 'ciniki.businesses.subscriptionStatus'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

	ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'timezoneOffset');
	$utc_offset = ciniki_users_timezoneOffset($ciniki);

	//
	// Get the billing information from the subscription table
	//
	$strsql = "SELECT ciniki_businesses.id, ciniki_businesses.status AS business_status, "
		. "ciniki_businesses.name, "
		. "IFNULL(ciniki_business_subscriptions.status, 0) AS status_id, "
		. "IFNULL(ciniki_business_subscriptions.status, 0) AS status, "
		. "trial_days, payment_type, payment_frequency, "
		. "currency, "
		. "IFNULL(monthly,0) as monthly, "
//		. "IFNULL(monthly,0) as monthly_total, "
		. "IFNULL(monthly,0)*12 AS yearly, "
//		. "IFNULL(monthly,0)*12 AS yearly_total, "
		. "paypal_subscr_id, paypal_payer_email, paypal_payer_id, paypal_amount, "
		. "IF(signup_date='0000-00-00', '', DATE_FORMAT(CONVERT_TZ(ciniki_business_subscriptions.signup_date, '+00:00', '" . ciniki_core_dbQuote($ciniki, $utc_offset) . "'), '%b %e, %Y %l:%i %p')) AS signup_date, "
		. "IF(last_payment_date='0000-00-00', '', DATE_FORMAT(CONVERT_TZ(ciniki_business_subscriptions.last_payment_date, '+00:00', '" . ciniki_core_dbQuote($ciniki, $utc_offset) . "'), '%b %e, %Y %l:%i %p')) AS last_payment_date, "
		. "IF(paid_until='0000-00-00', '', DATE_FORMAT(CONVERT_TZ(ciniki_business_subscriptions.paid_until, '+00:00', '" . ciniki_core_dbQuote($ciniki, $utc_offset) . "'), '%b %e, %Y')) AS paid_until, "
		. "IF(trial_start_date='0000-00-00', '', DATE_FORMAT(CONVERT_TZ(ciniki_business_subscriptions.trial_start_date, '+00:00', '" . ciniki_core_dbQuote($ciniki, $utc_offset) . "'), '%b %e, %Y %l:%i %p')) AS trial_start_date, "
		. "trial_days - FLOOR((UNIX_TIMESTAMP(UTC_TIMESTAMP())-UNIX_TIMESTAMP(ciniki_business_subscriptions.trial_start_date))/86400) AS trial_remaining "
		. "FROM ciniki_businesses "
		. "LEFT JOIN ciniki_business_subscriptions ON (ciniki_businesses.id = ciniki_business_subscriptions.business_id) "
		. "ORDER BY ciniki_business_subscriptions.status, ciniki_businesses.name "
		. "";

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.businesses', array(
		array('container'=>'statuses', 'fname'=>'status', 'name'=>'status',
			'fields'=>array('name'=>'status_id', 'status', 'monthly', 'yearly'),
			'sums'=>array('monthly', 'yearly'),
			'maps'=>array(
				'status'=>array('0'=>'Unknown', '1'=>'Update required', '2'=>'Trial', '10'=>'Active', '11'=>'Free Subscription', '50'=>'Suspended', '60'=>'Cancelled'),
				)),
		array('container'=>'businesses', 'fname'=>'id', 'name'=>'business', 	
			'fields'=>array('id', 'name', 'business_status', 'status', 'signup_date', 
				'trial_days', 'currency', 'monthly', 'yearly',
				'payment_type', 'payment_frequency', 'paid_until', 'last_payment_date', 
				'trial_start_date', 'trial_remaining'),
			'maps'=>array(
				'business_status'=>array('0'=>'Unknown', '1'=>'Active', '50'=>'Suspended', '60'=>'Deleted'),
				'status'=>array(''=>'None', '0'=>'Unknown', '1'=>'Update required', '2'=>'Trial', '10'=>'Active', '11'=>'Free Subscription', '50'=>'Suspended', '60'=>'Cancelled'),
				'payment_frequency'=>array('10'=>'monthly', '20'=>'yearly'),
				)),
		));
	return $rc;
}
?>
