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
    require_once($ciniki['config']['core']['modules_dir'] . '/businesses/private/checkAccess.php');
    $rc = ciniki_businesses_checkAccess($ciniki, 0, 'ciniki.businesses.subscriptionStatus'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

	require_once($ciniki['config']['core']['modules_dir'] . '/users/private/timezoneOffset.php');
	$utc_offset = ciniki_users_timezoneOffset($ciniki);

	//
	// Get the billing information from the subscription table
	//
	$strsql = "SELECT ciniki_businesses.id, ciniki_businesses.status AS business_status, "
		. "ciniki_businesses.name, ciniki_business_subscriptions.status, signup_date, trial_days, "
		. "currency, monthly, paypal_subscr_id, paypal_payer_email, paypal_payer_id, paypal_amount, "
		. "IF(last_payment_date='0000-00-00', '', DATE_FORMAT(CONVERT_TZ(ciniki_business_subscriptions.date_added, '+00:00', '" . ciniki_core_dbQuote($ciniki, $utc_offset) . "'), '%b %e, %Y %l:%i %p')) AS last_payment_date, "
		. "trial_days - FLOOR((UNIX_TIMESTAMP(UTC_TIMESTAMP())-UNIX_TIMESTAMP(ciniki_business_subscriptions.signup_date))/86400) AS trial_remaining "
		. "FROM ciniki_businesses "
		. "LEFT JOIN ciniki_business_subscriptions ON (ciniki_businesses.id = ciniki_business_subscriptions.business_id) "
		. "ORDER BY ciniki_businesses.name "
		. "";

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.businesses', array(
		array('container'=>'businesses', 'fname'=>'id', 'name'=>'business', 	
			'fields'=>array('id', 'name', 'business_status', 'status', 'signup_date', 'trial_days', 'currency', 'monthly', 'last_payment_date', 'trial_remaining'),
			'maps'=>array(
				'business_status'=>array('0'=>'Unknown', '1'=>'Active', '50'=>'Suspended', '60'=>'Deleted'),
				'status'=>array(''=>'None', '0'=>'Unknown', '1'=>'Update required', '2'=>'Trial', '10'=>'Active', '50'=>'Suspended', '60'=>'Cancelled'),
				)),
		));
	return $rc;
}
?>
