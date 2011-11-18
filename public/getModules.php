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
//		<module label='Products' name='products' active='Yes|No' />
// </modules>
//
function ciniki_businesses_getModules($ciniki) {
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
	// Check access to business_id as owner, or sys admin. 
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/businesses/private/checkAccess.php');
	$ac = ciniki_businesses_checkAccess($ciniki, $args['business_id'], 'ciniki.businesses.getModules');
	if( $ac['stat'] != 'ok' ) {
		return $ac;
	}

	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbHashIDQuery.php');
	$strsql = "SELECT CONCAT_WS('.', package, module) AS name, package, module, status, ruleset "
		. "FROM ciniki_business_modules "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "'";	
	$rc = ciniki_core_dbHashIDQuery($ciniki, $strsql, 'businesses', 'modules', 'name');
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
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/getModuleList.php');
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
				'name'=>$module['name'], 'active'=>'No'));
			if( isset($business_modules[$module['package'] . '.' . $module['name']]) 
				&& $business_modules[$module['package'] . '.' . $module['name']]['status'] == 1 ) {
				$modules[$count]['module']['active'] = 'Yes';
			}
			$count++;
		}
	}

	return array('stat'=>'ok', 'modules'=>$modules);
}
?>
