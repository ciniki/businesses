<?php
//
// Description
// -----------
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
//		<module name='Products' ruleset='all_customers'>
//			<rulesets>
//				<ruleset id='all_customers' label='All Customers, Group Managed' description='' />
//			</rulesets>
//		</module>
// </modules>
//
function ciniki_businesses_getModuleRulesets($ciniki) {
	//
	// Find all the required and optional arguments
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/prepareArgs.php');
	$rc = ciniki_core_prepareArgs($ciniki, 'no', array(
		'business_id'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No business name specified'), 
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$args = $rc['args'];
	
	//
	// Check access to business_id as owner, or sys admin. 
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/businesses/private/checkAccess.php');
	$ac = ciniki_businesses_checkAccess($ciniki, $args['business_id'], 'ciniki.businesses.getModuleRulesets');
	if( $ac['stat'] != 'ok' ) {
		return $ac;
	}

	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbHashQuery.php');
	$strsql = "SELECT modules FROM businesses WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "'";	
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'businesses', '');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( $rc['num_rows'] != 1 ) {
		return array('stat'=>'fail', 'err'=>array('code'=>'211', 'msg'=>'No business found'));
	}
	$business_modules = $rc['rows'][0]['modules'];

	//
	// Get the list of modules and permissions for the business
	//
	$strsql = "SELECT module, ruleset FROM business_permissions "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "'";
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbHashIDQuery.php');
	$rc = ciniki_core_dbHashIDQuery($ciniki, $strsql, 'businesses', 'modules', 'module');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$module_rulesets = $rc['modules'];

	//
	// Get the list of available modules
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/getModuleList.php');
	$mod_list = ciniki_core_getModuleList($ciniki);

	$modules = array();
	$count = 0;
	foreach($mod_list as $module) {
		//
		// Only add modules to the list that have been installed, and turned on for the business
		//
		if( $module['name'] != '' && $module['installed'] == 'Yes' && (($business_modules & $module['bits']) == $module['bits']) ) {
			$modules[$count] = array('module'=>array('name'=>$module['name'], 'label'=>$module['label']));
			//
			// Check for the current ruleset selected
			//
			if( isset($module_rulesets[$module['name']]) ) {
				$modules[$count]['module']['ruleset'] = $module_rulesets[$module['name']]['ruleset'];
			}

			//
			// Check for any rulesets for this module
			//
			if( file_exists($ciniki['config']['core']['modules_dir'] . '/' . $module['name'] . '/private/getRulesets.php') ) {
				require_once($ciniki['config']['core']['modules_dir'] . '/' . $module['name'] . '/private/getRulesets.php');
				$func = "ciniki_" . $module['name'] . "_getRulesets";
				$rulesets = $func($ciniki);
				$i = 0;
				foreach($rulesets as $name => $ruleset) {
					$modules[$count]['module']['rulesets'][$i++] = array('ruleset'=>array('id'=>$name, 
						'label'=>$rulesets[$name]['label'], 
						'description'=>$rulesets[$name]['description'] ));
				}
			} else {
				$modules[$count]['module']['rulesets'] = array();
			}
			$count++;
		}
	}



	return array('stat'=>'ok', 'modules'=>$modules);
}
?>
