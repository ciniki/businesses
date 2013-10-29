<?php
//
// Description
// -----------
// This function will return the list of modules available in the system,
// and which modules the requested business has access to.
//
// Info
// ----
// Status: beta
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:			The ID of the business to get the module list for.
//
// Returns
// -------
// <modules>
//		<module label='Products' name='products' status='On|Off' />
// </modules>
//
function ciniki_businesses_getModules($ciniki) {
	//
	// Find all the required and optional arguments
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
	$rc = ciniki_core_prepareArgs($ciniki, 'no', array(
		'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$args = $rc['args'];
	
	//
	// Check access to business_id as owner, or sys admin. 
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'checkAccess');
	$ac = ciniki_businesses_checkAccess($ciniki, $args['business_id'], 'ciniki.businesses.getModules');
	if( $ac['stat'] != 'ok' ) {
		return $ac;
	}

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashIDQuery');
	$strsql = "SELECT CONCAT_WS('.', package, module) AS name, package, module, status, ruleset "
		. "FROM ciniki_business_modules "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "ORDER BY name "
		. "";	
	$rc = ciniki_core_dbHashIDQuery($ciniki, $strsql, 'ciniki.businesses', 'modules', 'name');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['modules']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'195', 'msg'=>'No business found'));
	}
	$business_modules = $rc['modules'];

	//
	// Get the list of available modules
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'getModuleList');
	$rc = ciniki_core_getModuleList($ciniki);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$mod_list = $rc['modules'];

	$modules = array();
	$count = 0;
	foreach($mod_list as $module) {
		if( $module['label'] != '' && $module['installed'] == 'Yes' && (!isset($module['optional']) || $module['optional'] == 'yes') ) {
			$modules[$count] = array('module'=>array('label'=>$module['label'], 'package'=>$module['package'], 
				'name'=>$module['name'], 'status'=>'0'));
			if( isset($business_modules[$module['package'] . '.' . $module['name']]) 
				&& $business_modules[$module['package'] . '.' . $module['name']]['status'] == 1 ) {
				$modules[$count]['module']['status'] = '1';
			}
			$count++;
		}
	}
	


	return array('stat'=>'ok', 'modules'=>$modules);
}
?>
