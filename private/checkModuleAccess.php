<?php
//
// Description
// -----------
// This function will verify the business is active, and the module is active.
//
// Info
// ----
// Status: beta
//
// Arguments
// ---------
// ciniki:
// business_id:			The business ID to check the session user against.
// method:				The requested method.
//
// Returns
// -------
// <rsp stat='ok' />
//
function ciniki_businesses_checkModuleAccess($ciniki, $business_id, $package, $module) {
	//
	// Check if the module is enabled for this business, don't really care about the ruleset
	//
	$strsql = "SELECT ciniki_businesses.status AS business_status, "
		. "ciniki_business_modules.status AS module_status, "
		. "CONCAT_WS('.', ciniki_business_modules.package, ciniki_business_modules.module) AS module_id, "
		. "ruleset "
		. "FROM ciniki_businesses, ciniki_business_modules "
		. "WHERE ciniki_businesses.id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "AND ciniki_businesses.id = ciniki_business_modules.business_id "
		. "AND ciniki_business_modules.package = '" . ciniki_core_dbQuote($ciniki, $package) . "' "
		. "AND ciniki_business_modules.module = '" . ciniki_core_dbQuote($ciniki, $module) . "' "
		. "";
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbHashIDQuery.php');
	$rc = ciniki_core_dbHashIDQuery($ciniki, $strsql, 'businesses', 'modules', 'module_id');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	if( !isset($rc['modules']) || !isset($rc['modules'][$package . '.' . $module]) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'696', 'msg'=>'Module disabled'));
	}
	$modules = $rc['modules'];

	//
	// Check if the business is not active
	//
	if( isset($rc['modules'][$package . '.' . $module]['business_status']) && $rc['modules'][$package . '.' . $module]['business_status'] != 1 ) {
		if( $rc['modules'][$package . '.' . $module]['business_status'] == 50 ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'691', 'msg'=>'Business suspended'));
		} elseif( $rc['modules'][$package . '.' . $module]['business_status'] == 60 ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'692', 'msg'=>'Business deleted'));
		}
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'693', 'msg'=>'Business inactive'));
	}

	//
	// Check if module is enabled
	//
	if( isset($rc['modules'][$package . '.' . $module]['module_status']) && $rc['modules'][$package . '.' . $module]['module_status'] != 1 ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'694', 'msg'=>'Module disabled'));
	}

	//
	// Return the ruleset
	//
	return array('stat'=>'ok', 'ruleset'=>$rc['modules'][$package . '.' . $module]['ruleset'], 'modules'=>$rc['modules']);
}
?>