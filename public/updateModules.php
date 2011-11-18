<?php
//
// Description
// -----------
// This function will return the list of modules available in the system,
// and which modules the requested business has access to.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:			The ID of the business to get the module list for.
// MODULE_NAME:			The name of the module, and the value if it's Yes or No.
//
// Returns
// -------
// <modules>
//		<module name='Products' active='Yes|No' />
// </modules>
//
function ciniki_businesses_updateModules($ciniki) {
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
	$ac = ciniki_businesses_checkAccess($ciniki, $args['business_id'], 'ciniki.businesses.updateModules');
	if( $ac['stat'] != 'ok' ) {
		return $ac;
	}

	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbAddChangeLog.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbUpdate.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbHashIDQuery.php');
	$strsql = "SELECT CONCAT_WS('.', package, module) AS name, module, status "
		. "FROM ciniki_business_modules WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "'";	
	$rc = ciniki_core_dbHashIDQuery($ciniki, $strsql, 'businesses', 'modules', 'name');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
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

    //  
    // Start transaction
    //  
    require($ciniki['config']['core']['modules_dir'] . '/core/private/dbTransactionStart.php');
    require($ciniki['config']['core']['modules_dir'] . '/core/private/dbTransactionRollback.php');
    require($ciniki['config']['core']['modules_dir'] . '/core/private/dbTransactionCommit.php');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'businesses');
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

	//
	// Find all the modules which are to change status
	//
	foreach($mod_list as $module) {
		$name = $module['name'];
		if( isset($ciniki['request']['args'][$name]) ) {
			$status = 0;		// Default to off
			if( isset($business_modules[$module['package'] . '.' . $module['name']]) 
				&& isset($business_modules[$module['package'] . '.' . $module['name']]['status']) ) {
				$status = $business_modules[$module['package'] . '.' . $module['name']]['status'];
			}
			if( $ciniki['request']['args'][$name] == 'Yes' && $status != 1 ) {
				$strsql = "INSERT INTO ciniki_business_modules "
					. "(business_id, package, module, status, ruleset, date_added, last_updated) "
					. "VALUES ('" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "', "
					. "'" . ciniki_core_dbQuote($ciniki, $module['package']) . "', "
					. "'" . ciniki_core_dbQuote($ciniki, $module['name']) . "', "
					. "'1', '', UTC_TIMESTAMP(), UTC_TIMESTAMP()) "
					. "ON DUPLICATE KEY UPDATE "
						. "status = '1'"
						. "";
				$rc = ciniki_core_dbUpdate($ciniki, $strsql, 'businesses');
				if( $rc['stat'] != 'ok' ) {
					ciniki_core_dbTransactionRollback($ciniki, 'businesses');
					return $rc;
				} 
				ciniki_core_dbAddChangeLog($ciniki, 'businesses', $args['business_id'], 'ciniki_businesses', $name, 'modules', 'Yes');
			}
			elseif( $ciniki['request']['args'][$name] == 'No' && $status != 0 ) {
				$strsql = "INSERT INTO ciniki_business_modules "
					. "(business_id, package, module, status, ruleset, date_added, last_updated) "
					. "VALUES ('" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "', "
					. "'" . ciniki_core_dbQuote($ciniki, $module['package']) . "', "
					. "'" . ciniki_core_dbQuote($ciniki, $module['name']) . "', "
					. "'0', '', UTC_TIMESTAMP(), UTC_TIMESTAMP()) "
					. "ON DUPLICATE KEY UPDATE "
						. "status = '0'"
						. "";
				$rc = ciniki_core_dbUpdate($ciniki, $strsql, 'businesses');
				if( $rc['stat'] != 'ok' ) {
					ciniki_core_dbTransactionRollback($ciniki, 'businesses');
					return $rc;
				} 
				ciniki_core_dbAddChangeLog($ciniki, 'businesses', $args['business_id'], 'ciniki_businesses', $name, 'modules', 'No');
			}
		}
	}

	$rc = ciniki_core_dbTransactionCommit($ciniki, 'businesses');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	} 

	return array('stat'=>'ok');
}
?>
