<?php
//
// Description
// ===========
// This method will return the plan information.
//
// Arguments
// ---------
// user_id: 		The user making the request
// 
// Returns
// -------
//
function ciniki_businesses_planGet($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    require_once($ciniki['config']['core']['modules_dir'] . '/core/private/prepareArgs.php');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No business specified'), 
        'plan_id'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No plan specified'), 
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
    $rc = ciniki_businesses_checkAccess($ciniki, $args['business_id'], 'ciniki.businesses.planGet'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

	$strsql = "SELECT ciniki_business_plans.id, uuid, name, flags, monthly, modules, trial_days, description, "
		. "date_added, last_updated "
		. "FROM ciniki_business_plans "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND ciniki_business_plans.id = '" . ciniki_core_dbQuote($ciniki, $args['plan_id']) . "' "
		. "";
	
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.businesses', 'plan');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['plan']) ) {
		return array('stat'=>'ok', 'err'=>array('pkg'=>'ciniki', 'code'=>'667', 'msg'=>'Unable to find plan'));
	}

	return array('stat'=>'ok', 'plan'=>$rc['plan']);
}
?>
