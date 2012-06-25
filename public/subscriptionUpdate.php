<?php
//
// Description
// ===========
// This function will update the plan information
//
// Arguments
// ---------
// user_id: 		The user making the request
// 
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_businesses_subscriptionUpdate($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    require_once($ciniki['config']['core']['modules_dir'] . '/core/private/prepareArgs.php');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
		'business_id'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No business specified'), 
		'currency'=>array('required'=>'no', 'blank'=>'yes', 'errmsg'=>'No monthly specified'),
		'monthly'=>array('required'=>'no', 'blank'=>'yes', 'errmsg'=>'No monthly specified'),
		'trial_days'=>array('required'=>'no', 'blank'=>'yes', 'errmsg'=>'No trial_days specified'),
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
    $rc = ciniki_businesses_checkAccess($ciniki, $args['business_id'], 'ciniki.businesses.subscriptionUpdate'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

	//  
	// Turn off autocommit
	//  
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbTransactionStart.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbTransactionRollback.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbTransactionCommit.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbQuote.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbUpdate.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbInsert.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbAddModuleHistory.php');
	$rc = ciniki_core_dbTransactionStart($ciniki, 'businesses');
	if( $rc['stat'] != 'ok' ) { 
		return $rc;
	}   

	//
	// Get the existing subscription information
	//
	$strsql = "SELECT id, status, signup_date, trial_days, currency, monthly "
		. "FROM ciniki_business_subscriptions "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "";
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'businesses', 'subscription');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['subscription']) ) {
		$strsql = "INSERT INTO ciniki_business_subscriptions (business_id, status, signup_date, "
			. "trial_days, currency, monthly, payment_type, "
			. "date_added, last_updated) VALUES ("
			. "'" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "', "
			. "'2', "
			. "UTC_TIMESTAMP(), ";
		if( isset($args['trial_days']) && $args['trial_days'] != '' ) {
			$strsql .= "'" . ciniki_core_dbQuote($ciniki, $args['trial_days']) . "', ";
		} else {
			$strsql .= "'60', ";
		}
		if( isset($args['currency']) && $args['currency'] != '' ) {
			$strsql .= "'" . ciniki_core_dbQuote($ciniki, $args['currency']) . "', ";
		} else {
			$strsql .= "'USD', ";
		}
		if( isset($args['monthly']) && $args['monthly'] != '' ) {
			$strsql .= "'" . ciniki_core_dbQuote($ciniki, $args['monthly']) . "', ";
		} else {
			$strsql .= "'10.00', ";
		}
		$strsql .= "'paypal', "
			. "UTC_TIMESTAMP(), UTC_TIMESTAMP())";
		$rc = ciniki_core_dbInsert($ciniki, $strsql, 'businesses');
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		$subscription_id = $rc['insert_id'];
		
		if( isset($args['currency']) && $args['currency'] != '' ) {
			ciniki_core_dbAddModuleHistory($ciniki, 'businesses', 'ciniki_business_history', $args['business_id'], 
				2, 'ciniki_business_subscriptions', $subscription_id, 'currency', $args['currency']);
		}
		if( isset($args['trial_days']) && $args['trial_days'] != '' ) {
			ciniki_core_dbAddModuleHistory($ciniki, 'businesses', 'ciniki_business_history', $args['business_id'], 
				2, 'ciniki_business_subscriptions', $subscription_id, 'trial_days', $args['trial_days']);
		}
		if( isset($args['monthly']) && $args['monthly'] != '' ) {
			ciniki_core_dbAddModuleHistory($ciniki, 'businesses', 'ciniki_business_history', $args['business_id'], 
				2, 'ciniki_business_subscriptions', $subscription_id, 'monthly', $args['monthly']);
		}

	} else {
		$subscription = $rc['subscription'];

		//
		// Start building the update SQL
		//
		$strsql = "UPDATE ciniki_business_subscriptions SET last_updated = UTC_TIMESTAMP()";

		//
		// Update the status
		//
		if( (isset($args['currency']) && $args['currency'] != '' && $subscription['currency'] != $args['currency'])
			|| (isset($args['trial_days']) && $args['trial_days'] != '' && $subscription['trial_days'] != $args['trial_days'])
			|| (isset($args['monthly']) && $args['monthly'] != '' && $subscription['monthly'] != $args['monthly'])
			) {
			if( isset($args['monthly']) && $args['monthly'] == '0' ) {
				$strsql .= ", status = 10";
			}
			elseif( $subscription['status'] == 60 ) {
				$strsql .= ", status = 2";
			} else {
				$strsql .= ", status = 1";
			}
		}

		//
		// Add all the fields to the change log
		//
		$changelog_fields = array(
			'currency',
			'monthly',
			'trial_days',
			);
		foreach($changelog_fields as $field) {
			if( isset($args[$field]) ) {
				$strsql .= ", $field = '" . ciniki_core_dbQuote($ciniki, $args[$field]) . "' ";
				$rc = ciniki_core_dbAddModuleHistory($ciniki, 'businesses', 'ciniki_business_history', $args['business_id'], 
					2, 'ciniki_business_subscriptions', $subscription['id'], $field, $args[$field]);
			}
		}
		$strsql .= "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "";
		$rc = ciniki_core_dbUpdate($ciniki, $strsql, 'businesses');
		if( $rc['stat'] != 'ok' ) {
			ciniki_core_dbTransactionRollback($ciniki, 'businesses');
			return $rc;
		}
		if( !isset($rc['num_affected_rows']) || $rc['num_affected_rows'] != 1 ) {
			ciniki_core_dbTransactionRollback($ciniki, 'businesses');
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'665', 'msg'=>'Unable to update subscription'));
		}
	}


	//
	// Commit the database changes
	//
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'businesses');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	return array('stat'=>'ok');
}
?>
