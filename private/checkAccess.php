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
	// Check the user is authenticated
	//
	if( !isset($ciniki['session'])
		|| !isset($ciniki['session']['user'])
		|| !isset($ciniki['session']['user']['id'])
		|| $ciniki['session']['user']['id'] < 1 ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'43', 'msg'=>'User not authenticated'));
	}
	
	//
	// Check the user has permission to the business, 
	// owners have full permissions, as do MOSS_ADMIN's
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
		'ciniki.businesses.addOwner',
		'ciniki.businesses.getDetailHistory',
		'ciniki.businesses.getDetails',
		'ciniki.businesses.getModuleHistory',
		'ciniki.businesses.getModuleRulesetHistory',
		'ciniki.businesses.getModuleRulesets',
		'ciniki.businesses.getModules',
		'ciniki.businesses.getOwners',
		'ciniki.businesses.removeOwner',
		'ciniki.businesses.updateDetails',
		'ciniki.businesses.updateModuleRulesets',
		);
	if( !in_array($method, $available_methods) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'57', 'msg'=>'Access denied'));
	}

	//
	// Check the session user is a business owner
	//
	if( $business_id > 0 ) {
		require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbQuote.php');
		//
		// Find any users which are owners of the requested business_id
		//
		$strsql = "SELECT business_id, user_id FROM ciniki_business_users "
			. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "AND user_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "' "
			. "AND type = 1 "		// This is a business owner
			. "";
		require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbRspQuery.php');
		$rsp = ciniki_core_dbRspQuery($ciniki, $strsql, 'businesses', 'perms', 'perm', array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'50', 'msg'=>'Access denied')));
		if( $rsp['stat'] != 'ok' ) {
			return $rsp;
		}
		if( $rsp['num_rows'] == 1 
			&& $rsp['perms'][0]['perm']['business_id'] == $business_id
			&& $rsp['perms'][0]['perm']['user_id'] == $ciniki['session']['user']['id'] ) {
			return array('stat'=>'ok');
		}
	}

	//
	// By default fail
	//
	return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'45', 'msg'=>'Access denied'));
}
?>
