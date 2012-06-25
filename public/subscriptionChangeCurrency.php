<?php
//
// Description
// ===========
// This method will change the currency for the subscription, and update the status
// if an updated with paypal is required.
//
// Arguments
// ---------
// user_id: 		The user making the request
// 
// Returns
// -------
//
function ciniki_businesses_subscriptionChangeCurrency($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    require_once($ciniki['config']['core']['modules_dir'] . '/core/private/prepareArgs.php');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No business specified'), 
        'currency'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No currency specified'), 
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
    $rc = ciniki_businesses_checkAccess($ciniki, $args['business_id'], 'ciniki.businesses.subscriptionChangeCurrency'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

	//
	// Check which currency
	//
	if( $args['currency'] != 'USD' 
		&& $args['currency'] != 'CAD'
		) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'676', 'msg'=>'Currency must be USD or CAD.'));
	}

    require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbAddModuleHistory.php');
	//
	// Get the billing information from the subscription table
	//
	$strsql = "SELECT id, status, currency, paypal_subscr_id, paypal_payer_email, paypal_payer_id, paypal_amount "
		. "FROM ciniki_business_subscriptions "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "";

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'businesses', 'subscription');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['subscription']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'677', 'msg'=>'Unable to change currency'));
	} 
	$subscription = $rc['subscription'];

	if( $rc['subscription']['currency'] == $args['currency'] ) {
		return array('stat'=>'ok');
	}

	//
	// If active subscription, then update at paypal will be required
	//
	if( $rc['subscription']['status'] == 10 ) {
		$strsql = "UPDATE ciniki_business_subscriptions "
			. "SET currency = '" . ciniki_core_dbQuote($ciniki, $args['currency']) . "' "
			. ", status = 1 "
			. "WHERE id = '" . ciniki_core_dbQuote($ciniki, $subscription['id']) . "' "
			. "";
		require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbUpdate.php');
		$rc = ciniki_core_dbUpdate($ciniki, $strsql, 'businesses');
		if( $rc['stat'] != 'ok' ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'678', 'msg'=>'Unable to change currency', 'err'=>$rc['err']));
		}
		ciniki_core_dbAddModuleHistory($ciniki, 'businesses', 'ciniki_business_history', $args['business_id'], 
			2, 'ciniki_business_subscriptions', $subscription['id'], 'currency', $args['currency']);
		ciniki_core_dbAddModuleHistory($ciniki, 'businesses', 'ciniki_business_history', $args['business_id'], 
			2, 'ciniki_business_subscriptions', $subscription['id'], 'status', '1');
		return $rc;
	}

	//
	// If already pending update required, don't change status
	//
	elseif( $rc['subscription']['status'] == 1 || $rc['subscription']['status'] == 2 || $rc['subscription']['status'] == 60 ) {
		$strsql = "UPDATE ciniki_business_subscriptions "
			. "SET currency = '" . ciniki_core_dbQuote($ciniki, $args['currency']) . "' "
			. "WHERE id = '" . ciniki_core_dbQuote($ciniki, $subscription['id']) . "' "
			. "";
		require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbUpdate.php');
		$rc = ciniki_core_dbUpdate($ciniki, $strsql, 'businesses');
		if( $rc['stat'] != 'ok' ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'679', 'msg'=>'Unable to change currency', 'err'=>$rc['err']));
		}
		ciniki_core_dbAddModuleHistory($ciniki, 'businesses', 'ciniki_business_history', $args['business_id'], 
			2, 'ciniki_business_subscriptions', $subscription['id'], 'currency', $args['currency']);
		return $rc;
	}

	//
	// Get the history
	//

	return array('stat'=>'ok');
}
?>
