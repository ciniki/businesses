<?php
//
// Description
// -----------
// This function will activate a business, allowing access.
//
// Arguments
// ---------
// api_key:
// auth_token:
// id: 			The ID of the business to activate.
//
// Returns
// -------
// <rsp stat="ok" />
//
function ciniki_businesses_activate($ciniki) {
	//
	// Find all the required and optional arguments
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
	$rc = ciniki_core_prepareArgs($ciniki, 'no', array(
		'id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$args = $rc['args'];

	//
	// Check access 
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'checkAccess');
	$rc = ciniki_businesses_checkAccess($ciniki, $args['id'], 'ciniki.businesses.activate');
	if( $rc['stat'] != 'ok' && $rc['err']['code'] != '691' && $rc['err']['code'] != '692' ) {
		return $rc;
	}

	//
	// Make sure a sysadmin.  This check needs to be here because we ignore suspended/deleted 
	// businesses above, which then doesn't check perms.  Only sysadmins have access
	// to this method.
	//
	if( ($ciniki['session']['user']['perms'] & 0x01) != 0x01 ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1921', 'msg'=>'Permission denied'));
	}

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuoteRequestArg');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
	$strsql = "UPDATE ciniki_businesses SET status = 1 "
		. "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['id']) . "'";
	$rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.businesses');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
	ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.businesses', 'ciniki_business_history', $args['id'], 
		2, 'ciniki_businesses', $args['id'], 'status', '1');

	return $rc;
}
?>
