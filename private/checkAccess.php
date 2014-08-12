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
	// Get the list of modules
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'checkModuleAccess');
	$rc = ciniki_businesses_checkModuleAccess($ciniki, $business_id, 'ciniki', 'businesses');
	// Ignore if businesses module is not in the list, it's on by default
	if( $rc['stat'] != 'ok' && $rc['err']['code'] != '696' && $rc['err']['code'] != '692' ) {
		return $rc;
	}
	// Normally there is a check here to see if permissions denied, but not used in this case
	// just want to get the modules.
	$modules = $rc['modules'];

	//
	// Sysadmins are allowed full access
	//
	if( ($ciniki['session']['user']['perms'] & 0x01) == 0x01 ) {
		return array('stat'=>'ok', 'modules'=>$modules);
	}

	//
	// The following functions don't require any checks, any authenticated user can access them
	//
	if( $method == 'ciniki.businesses.getUserBusinesses' 
		|| $method == 'ciniki.businesses.getUserModules' 
		) {
		return array('stat'=>'ok', 'modules'=>$modules);
	}

	//
	// The following methods are only available to business owners, no employees
	//
	$owner_methods = array(
		'ciniki.businesses.userList',
		'ciniki.businesses.userAdd',
		'ciniki.businesses.userRemove',
		'ciniki.businesses.userDetails',
		'ciniki.businesses.userUpdateDetails',
		'ciniki.businesses.backupList',
		'ciniki.businesses.backupDownload',
//		'ciniki.businesses.logoGet',
//		'ciniki.businesses.logoSave',
//		'ciniki.businesses.logoDelete',
		);
	//
	// Check the session user is a business owner or employee
	//
	if( $business_id > 0 ) {
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
		//
		// Find any users which are owners of the requested business_id
		//
		$strsql = "SELECT business_id, user_id FROM ciniki_business_users "
			. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "AND user_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "' "
			. "AND package = 'ciniki' "
			. "AND status = 10 "	// Active owner
			. "AND (permission_group = 'owners') "
			. "";
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
		$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.businesses', 'user');
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
		'ciniki.businesses.subscriptionCancel',
		'ciniki.businesses.settingsIntlGet',
		'ciniki.businesses.settingsIntlUpdate',
		);
	if( !in_array($method, $available_methods) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'57', 'msg'=>'Access denied'));
	}

	//
	// Check the session user is a business owner or employee
	//
	if( $business_id > 0 ) {
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
		//
		// Find any users which are owners of the requested business_id
		//
		$strsql = "SELECT business_id, user_id FROM ciniki_business_users "
			. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "AND user_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "' "
			. "AND package = 'ciniki' "
			. "AND status = 10 "	// Active owner or employee
			. "AND (permission_group = 'owners' OR permission_group = 'employees') "
			. "";
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
		$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.businesses', 'user');
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		//
		// If the user has permission, return ok
		//
		if( isset($rc['rows']) && isset($rc['rows'][0]) 
			&& $rc['rows'][0]['user_id'] > 0 && $rc['rows'][0]['user_id'] == $ciniki['session']['user']['id'] ) {
			return array('stat'=>'ok', 'modules'=>$modules);
		}
	}

	//
	// By default fail
	//
	return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'45', 'msg'=>'Access denied'));
}
?>
