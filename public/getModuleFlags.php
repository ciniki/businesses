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
//		<module label='Products' name='products' flags='On|Off' />
// </modules>
//
function ciniki_businesses_getModuleFlags($ciniki) {
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
	$ac = ciniki_businesses_checkAccess($ciniki, $args['business_id'], 'ciniki.businesses.getModuleFlags');
	if( $ac['stat'] != 'ok' ) {
		return $ac;
	}

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashIDQuery');
	$strsql = "SELECT CONCAT_WS('.', package, module) AS name, package, module, flags "
		. "FROM ciniki_business_modules "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "ORDER BY name "
		. "";	
	$rc = ciniki_core_dbHashIDQuery($ciniki, $strsql, 'ciniki.businesses', 'modules', 'name');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['modules']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1026', 'msg'=>'No business found'));
	}
	$business_modules = $rc['modules'];

	//
	// Check for ciniki.businesses
	//
	if( !isset($business_modules['ciniki.businesses']) ) {
		$business_modules['ciniki.businesses'] = array('name'=>'Businesses', 
			'package'=>'ciniki', 'module'=>'businesses', 'flags'=>'0');
	}

	//
	// Check for the name and flags available for each module
	//
	foreach($business_modules as $mid => $module) {
		//
		// Check for info file
		//
		$business_modules[$mid]['proper_name'] = $module['name'];
		$info_filename = $ciniki['config']['ciniki.core']['root_dir'] . '/' . $module['package'] . '-mods/' . $module['module'] . '/_info.ini';
		if( file_exists($info_filename) ) {
			$info = parse_ini_file($info_filename);
			if( isset($info['name']) && $info['name'] != '' ) {
				$business_modules[$mid]['proper_name'] = $info['name'];
			} 
		}
		
		//
		// Check if flags file exists
		//
		$rc = ciniki_core_loadMethod($ciniki, $module['package'], $module['module'], 'private', 'flags');
		if( $rc['stat'] == 'ok' ) {
			$fn = $module['package'] . '_' . $module['module'] . '_flags';
			$rc = $fn($ciniki, $business_modules);
			$business_modules[$mid]['available_flags'] = $rc['flags'];
		}
	}

	return array('stat'=>'ok', 'modules'=>$business_modules);
}
?>
