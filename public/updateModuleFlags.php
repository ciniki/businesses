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
// MODULE_NAME:			The name of the module, and the value if it's On or Off.
//
// Returns
// -------
// <modules>
//		<module name='Products' active='On|Off' />
// </modules>
//
function ciniki_businesses_updateModuleFlags($ciniki) {
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
	$ac = ciniki_businesses_checkAccess($ciniki, $args['business_id'], 'ciniki.businesses.updateModuleFlags');
	if( $ac['stat'] != 'ok' ) {
		return $ac;
	}

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbInsert');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashIDQuery');
	$strsql = "SELECT CONCAT_WS('.', package, module) AS name, module, status "
		. "FROM ciniki_business_modules WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "'";	
	$rc = ciniki_core_dbHashIDQuery($ciniki, $strsql, 'ciniki.businesses', 'modules', 'name');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
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

    //  
    // Start transaction
    //  
    require($ciniki['config']['core']['modules_dir'] . '/core/private/dbTransactionStart.php');
    require($ciniki['config']['core']['modules_dir'] . '/core/private/dbTransactionRollback.php');
    require($ciniki['config']['core']['modules_dir'] . '/core/private/dbTransactionCommit.php');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.businesses');
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

	//
	// Find all the modules which are to change status
	//
	foreach($mod_list as $module) {
		$name = $module['package'] . '.' . $module['name'];
		
		if( isset($ciniki['request']['args'][$name]) ) {
			//
			// Add the module if it doesn't exist
			//
			if( !isset($business_modules[$name]) ) {
				$strsql = "INSERT INTO ciniki_business_modules (business_id, package, module, "
					. "status, flags, ruleset, date_added, last_updated) VALUES ("
					. "'" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
					. ", '" . ciniki_core_dbQuote($ciniki, $module['package']) . "' "
					. ", '" . ciniki_core_dbQuote($ciniki, $module['name']) . "' "
					. ", '2'"
					. ", '" . ciniki_core_dbQuote($ciniki, $ciniki['request']['args'][$name]) . "' "
					. ", ''"
					. ", UTC_TIMESTAMP(), UTC_TIMESTAMP() "
					. ")";
				$rc = ciniki_core_dbInsert($ciniki, $strsql, 'ciniki.businesses');
				if( $rc['stat'] != 'ok' ) {
					ciniki_core_dbTransactionRollback($ciniki, 'ciniki.businesses');
					return $rc;
				} 
				ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.businesses', 'ciniki_business_history', 
					$args['business_id'], 1, 'ciniki_business_modules', $name, 'status', '2');
				ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.businesses', 'ciniki_business_history', 
					$args['business_id'], 1, 'ciniki_business_modules', $name, 'flags', 
					$ciniki['request']['args'][$name]);
			} 
			//
			// Update the existing module
			//
			else {
				$strsql = "UPDATE ciniki_business_modules SET "
					. "flags = '" . ciniki_core_dbQuote($ciniki, $ciniki['request']['args'][$name]) . "', "
					. "last_updated = UTC_TIMESTAMP() "
					. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
					. "AND package = '" . ciniki_core_dbQuote($ciniki, $module['package']) . "' "
					. "AND module = '" . ciniki_core_dbQuote($ciniki, $module['name']) . "' "
					. "";
				$rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.businesses');
				if( $rc['stat'] != 'ok' ) {
					ciniki_core_dbTransactionRollback($ciniki, 'ciniki.businesses');
					return $rc;
				} 
				ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.businesses', 'ciniki_business_history', 
					$args['business_id'], 2, 'ciniki_business_modules', $name, 'flags', 
					$ciniki['request']['args'][$name]);
			}
		}
	}

	$rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.businesses');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	} 

	return array('stat'=>'ok');
}
?>
