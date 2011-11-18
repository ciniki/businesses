<?php
//
// Description
// -----------
// This method will return the list of modules the user has access to and are turned on for the business.
// The UI can use this to decide what menu items to display.
//
// Arguments
// ---------
// api_key:
// auth_token:
//
// Returns
// -------
// <modules>
//		<modules name='questions' />
// </businesses>
//
function ciniki_businesses_getUserModules($ciniki) {
	//
	// Find all the required and optional arguments
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/prepareArgs.php');
	$rc = ciniki_core_prepareArgs($ciniki, 'no', array(
		'business_id'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No business specified'), 
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$args = $rc['args'];
	
	//
	// Check access to business_id as owner, or sys admin
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/businesses/private/checkAccess.php');
	$rc = ciniki_businesses_checkAccess($ciniki, 0, 'ciniki.businesses.getUserModules');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	//
	// FIXME: Add check to see which groups the user is part of, and only hand back the module list
	//        for what they have access to.
	//
	$strsql = "SELECT CONCAT_WS('.', package, module) AS name, package, module FROM ciniki_business_modules "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND status = 1 "
		. "";
	$rc = ciniki_core_dbRspQuery($ciniki, $strsql, 'businesses', 'modules', 'module', array('stat'=>'ok', 'modules'=>array()));

	return $rc;
}
?>
