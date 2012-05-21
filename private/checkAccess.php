<?php
//
// Description
// -----------
// This function will check to see the requesting user has access
// to both the businesses module and requested method.
//
// *note* The method is not currently tested, just sysadmin or business owner.
//
// Info
// ----
// Status: beta
//
// Arguments
// ---------
// ciniki:
// business_id:			The business ID to check the session user against.
// method:				The method requested.
//
// Returns
// -------
// <rsp stat='ok' />
//
function ciniki_businesses_checkAccess($ciniki, $business_id, $method) {
	//
	// Sysadmins are allowed full access
	//
	if( ($ciniki['session']['user']['perms'] & 0x01) == 0x01 ) {
		return array('stat'=>'ok');
	}

	//
	// The following functions don't require any checks, any authenticated user can access them
	//
	if( $method == 'ciniki.businesses.getUserBusinesses' 
		|| $method == 'ciniki.businesses.getUserModules' 
		) {
		return array('stat'=>'ok');
	}

	//
	// Limit the functions the business owner has access to.  Any
	// other methods will be denied access.
	//
	$available_methods = array(
		'ciniki.businesses.getDetailHistory',
		'ciniki.businesses.getDetails',
		'ciniki.businesses.getModuleHistory',
		'ciniki.businesses.getModuleRulesetHistory',
		'ciniki.businesses.getModuleRulesets',
		'ciniki.businesses.getModules',
		'ciniki.businesses.getUserSettings',
		'ciniki.businesses.getOwners',
		'ciniki.businesses.employees',
		'ciniki.businesses.updateDetails',
		'ciniki.businesses.updateModuleRulesets',
		'ciniki.businesses.subscriptionInfo',
		'ciniki.businesses.subscriptionChangeCurrency',
		);
	if( !in_array($method, $available_methods) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'57', 'msg'=>'Access denied'));
	}

	//
	// Check the session user is a business owner or employee
	//
	if( $business_id > 0 ) {
		require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbQuote.php');
		//
		// Find any users which are owners of the requested business_id
		//
		$strsql = "SELECT business_id, user_id FROM ciniki_business_users "
			. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "AND user_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "' "
			. "AND package = 'ciniki' "
			. "AND (permission_group = 'owners' OR permission_group = 'employees') "
			. "";
		require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbHashQuery.php');
		$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'businesses', 'user');
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		//
		// If the user has permission, return ok
		//
		if( isset($rc['rows']) && isset($rc['rows'][0]) 
			&& $rc['rows'][0]['user_id'] > 0 && $rc['rows'][0]['user_id'] == $ciniki['session']['user']['id'] ) {
			return array('stat'=>'ok');
		}
	}

	//
	// By default fail
	//
	return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'45', 'msg'=>'Access denied'));
}
?>
