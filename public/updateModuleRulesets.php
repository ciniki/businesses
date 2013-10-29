<?php
//
// Description
// -----------
// This function will update the Rulesets for a module.
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
// MODULE_NAME:			The name of the module, and the value if it's Yes or No.
//
// Returns
// -------
//
function ciniki_businesses_updateModuleRulesets($ciniki) {
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
	$ac = ciniki_businesses_checkAccess($ciniki, $args['business_id'], 'ciniki.businesses.updateModuleRulesets');
	if( $ac['stat'] != 'ok' ) {
		return $ac;
	}

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbInsert');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
	$strsql = "SELECT CONCAT_WS('.', package, module) AS name, package, module, status, ruleset FROM ciniki_business_modules "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "'";	
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashIDQuery');
	$rc = ciniki_core_dbHashIDQuery($ciniki, $strsql, 'ciniki.businesses', 'modules', 'name');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( $rc['num_rows'] > 0 ) { 
		$db_modules = $rc['modules'];
	} else {
		$db_modules = array();
	}

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
	// Turn off autocommit
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
	$rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.businesses');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	foreach($mod_list as $module) {
		$name = $module['package'] . '.' . $module['name'];
		$package = $module['package'];
		$status = 1;		// default to on
		if( isset($ciniki['request']['args'][$name]) ) {
			$new_ruleset = $ciniki['request']['args'][$name];
			if( isset($db_modules[$name]) && $db_modules[$name] != $new_ruleset ) {
				$strsql = "UPDATE ciniki_business_modules "
					. "SET ruleset = '" . ciniki_core_dbQuote($ciniki, $new_ruleset) . "', "
					. "last_updated=UTC_TIMESTAMP() "
					. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
					. "AND package = '" . ciniki_core_dbQuote($ciniki, $module['package']) . "' "
					. "AND module = '" . ciniki_core_dbQuote($ciniki, $module['name']) . "'";
				$rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.businesses');
				if( $rc['stat'] != 'ok' ) {
					ciniki_core_dbTransactionRollback($ciniki, 'ciniki.businesses');
					return $rc;
				} 
				if( $rc['num_affected_rows'] > 0 ) {
					ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.businesses', 'ciniki_business_history', $args['business_id'], 
						2, 'ciniki_business_modules', "$name", 'ruleset', $new_ruleset);
				} else {
					ciniki_core_dbTransactionRollback($ciniki, 'ciniki.businesses');
					return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'212', 'msg'=>'Error occured during update'));
				}
			}
			if( !isset($db_modules[$name]) && $new_ruleset != '') {
				$strsql = "INSERT INTO ciniki_business_modules (business_id, package, module, status, ruleset, date_added, last_updated) VALUES ("
					. "'" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "', "
					. "'" . ciniki_core_dbQuote($ciniki, $module['package']) . "', "
					. "'" . ciniki_core_dbQuote($ciniki, $module['name']) . "', "
					. "'" . ciniki_core_dbQuote($ciniki, $status) . "', "
					. "'" . ciniki_core_dbQuote($ciniki, $new_ruleset) . "', "
					. "UTC_TIMESTAMP(), UTC_TIMESTAMP())";
				$rc = ciniki_core_dbInsert($ciniki, $strsql, 'ciniki.businesses');
				if( $rc['stat'] != 'ok' ) {
					ciniki_core_dbTransactionRollback($ciniki, 'ciniki.businesses');
					return $rc;
				} 
				if( $rc['num_affected_rows'] > 0 ) {
					ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.businesses', 'ciniki_business_history', $args['business_id'], 
						2, 'ciniki_business_modules', "$name", 'status', $status);
					ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.businesses', 'ciniki_business_history', $args['business_id'], 
						2, 'ciniki_business_modules', "$name", 'ruleset', $new_ruleset);
				} else {
					ciniki_core_dbTransactionRollback($ciniki, 'ciniki.businesses');
					return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'213', 'msg'=>'Error occured during update'));
				}
			}
		}
	}

	//
	// Commit the changes
	//
	$rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.businesses');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	return array('stat'=>'ok');
}
?>
