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
function ciniki_businesses_processPaypalIPN($ciniki) {
	//
	// Find all the required and optional arguments
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/prepareArgs.php');
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
	// Lookup the business_id for the IPN
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbQuote.php');
	$strsql = "SELECT id, status, name "
		. "FROM ciniki_businesses "
		. "WHERE uuid = '" . ciniki_core_dbQuote($ciniki, $args['item_number']) . "' "
		. "";
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbHashQuery.php');
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'businesses', 'business');
	if( $rc['stat'] != 'ok') {
		error_log("PAYPAL-IPN: " . $args['ipn_track_id'] . ' - ' . $rc['err']['code'] . ' - ' . $rc['err']['msg']);
	}
	$business_id = 0;
	$business_status = 0;
	if( !isset($rc['business']) ) {
		error_log("PAYPAL-IPN: " . $args['ipn_track_id'] . "Unable to locate business " . $args['item_number'] . ' for ' . $args['item_name']);
	} else {
		$business_id = $rc['business']['id'];
		$business_status = $rc['business']['status'];
	}

	//
	// Enter the information into the paypal_log
	//
	$strsql = "INSERT INTO ciniki_business_paypal_log (business_id, status, txn_type, "
		. "subscr_id, first_name, last_name, "
		. "payer_id, payer_email, "
		. "item_name, item_number, "
		. "mc_currency, mc_fee, mc_gross, mc_amount3, "
		. "serialized_args, "
		. "date_added, last_updated) VALUES ("
		. "'" . ciniki_core_dbQuote($ciniki, $business_id) . "', "
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
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbInsert.php');
	$rc = ciniki_core_dbInsert($ciniki, $strsql, 'businesses');
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
	// If no business was found, then 
	//
	if( $business_id < 1 ) {
		return array('stat'=>'ok');
	}


	//
	// subscr_signup - initial setup of subscription
	//
	if( $args['txn_type'] == 'subscr_signup' ) {
		//
		// Update the subscription
		//
		$strsql = "UPDATE ciniki_business_subscriptions "
			. "SET status = 10 "
			. ", paypal_subscr_id = '" . ciniki_core_dbQuote($ciniki, $args['subscr_id']) . "' "
			. ", paypal_payer_id = '" . ciniki_core_dbQuote($ciniki, $args['payer_id']) . "' "
			. ", paypal_payer_email = '" . ciniki_core_dbQuote($ciniki, $args['payer_email']) . "' "
			. ", paypal_amount = '" . ciniki_core_dbQuote($ciniki, $args['mc_amount3']) . "' "
			. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "";
		require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbUpdate.php');
		$rc = ciniki_core_dbUpdate($ciniki, $strsql, 'businesses');
		if( $rc['stat'] != 'ok' ) {
			error_log("PAYPAL-IPN: " . $args['ipn_track_id'] . " - Unable to update business subscription");
		}

		//
		// Unsuspend the business if suspended
		//
		if( $business_status == 50 ) {
			$strsql = "UPDATE ciniki_businesses SET status = 1 "
				. "WHERE id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
				. "AND status = 50 ";
			$rc = ciniki_core_dbUpdate($ciniki, $strsql, 'businesses');
			if( $rc['stat'] != 'ok' ) {
				error_log("PAYPAL-IPN: " . $args['ipn_track_id'] . " - Unable to unsuspend business");
			}
		}
	}

	//
	// subscr_modify - modified their subscription
	//
	elseif( $args['txn_type'] == 'subscr_modify' ) {
		//
		// Update the subscription
		//
		$strsql = "UPDATE ciniki_business_subscriptions "
			. "SET status = 10, last_updated = UTC_TIMESTAMP() "
			. "";
		if( $args['subscr_id'] != '' ) {
			$strsql .= ", paypal_subscr_id = '" . ciniki_core_dbQuote($ciniki, $args['subscr_id']) . "' ";
		}
		if( $args['payer_id'] != '' ) {
			$strsql .= ", paypal_payer_id = '" . ciniki_core_dbQuote($ciniki, $args['payer_id']) . "' ";
		}
		if( $args['payer_email'] != '' ) {
			$strsql .= ", paypal_payer_email = '" . ciniki_core_dbQuote($ciniki, $args['payer_email']) . "' ";
		}
		if( $args['mc_amount3'] != '' ) {
			$strsql .= ", paypal_amount = '" . ciniki_core_dbQuote($ciniki, $args['mc_amount3']) . "' ";
		}
		$strsql .= "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "";
		require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbUpdate.php');
		$rc = ciniki_core_dbUpdate($ciniki, $strsql, 'businesses');
		if( $rc['stat'] != 'ok' ) {
			error_log("PAYPAL-IPN: " . $args['ipn_track_id'] . " - Unable to update business subscription");
		}

		//
		// Unsuspend the business if suspended
		//
		if( $business_status == 50 ) {
			$strsql = "UPDATE ciniki_businesses SET status = 1 "
				. "WHERE id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
				. "AND status = 50 ";
			$rc = ciniki_core_dbUpdate($ciniki, $strsql, 'businesses');
			if( $rc['stat'] != 'ok' ) {
				error_log("PAYPAL-IPN: " . $args['ipn_track_id'] . " - Unable to unsuspend business");
			}
		}
	}

	//
	// subscr_payment - received a recurring payment
	//
	elseif( $args['txn_type'] == 'subscr_payment' ) {
		// 
		// Update the subscription
		//
		$strsql = "UPDATE ciniki_business_subscriptions "
			. "SET last_payment_date = UTC_TIMESTAMP() "
			. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "";
		require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbUpdate.php');
		$rc = ciniki_core_dbUpdate($ciniki, $strsql, 'businesses');
		if( $rc['stat'] != 'ok' ) {
			error_log("PAYPAL-IPN: " . $args['ipn_track_id'] . " - Unable to cancel business subscription");
		}

	}

	//
	// subscr_cancel - the customer cancelled their subscription
	//
	elseif( $args['txn_type'] == 'subscr_cancel' ) {
		// 
		// Update the subscription
		//
		$strsql = "UPDATE ciniki_business_subscriptions "
			. "SET status = 60 "
			. ", paypal_subscr_id = '" . ciniki_core_dbQuote($ciniki, $args['subscr_id']) . "' "
			. ", paypal_payer_id = '" . ciniki_core_dbQuote($ciniki, $args['payer_id']) . "' "
			. ", paypal_payer_email = '" . ciniki_core_dbQuote($ciniki, $args['payer_email']) . "' "
			. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "";
		require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbUpdate.php');
		$rc = ciniki_core_dbUpdate($ciniki, $strsql, 'businesses');
		if( $rc['stat'] != 'ok' ) {
			error_log("PAYPAL-IPN: " . $args['ipn_track_id'] . " - Unable to cancel business subscription");
		}

		//
		// Suspend the business
		//
		if( $business_status == 1 ) {
			$strsql = "UPDATE ciniki_businesses SET status = 50 "
				. "WHERE id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
				. "AND status = 1 ";
			$rc = ciniki_core_dbUpdate($ciniki, $strsql, 'businesses');
			if( $rc['stat'] != 'ok' ) {
				error_log("PAYPAL-IPN: " . $args['ipn_track_id'] . " - Unable to suspend business");
			}
		}
	}

	else {
		error_log("PAYPAL-IPN: " . $args['ipn_track_id'] . " - Unable to unknown transaction type");
	}
	
	return array('stat'=>'ok');
}
