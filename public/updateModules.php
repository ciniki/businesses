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
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbHashQuery.php');
	$strsql = "SELECT modules FROM businesses WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "'";	
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'businesses', '');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( $rc['num_rows'] != 1 ) {
		return array('stat'=>'fail', 'err'=>array('code'=>'199', 'msg'=>'No business found'));
	}
	$business_modules = $rc['rows'][0]['modules'];

    //  
    // Get the list of available modules
    //  
    require_once($ciniki['config']['core']['modules_dir'] . '/core/private/getModuleList.php');
    $mod_list = ciniki_core_getModuleList($ciniki);

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

	foreach($mod_list as $module) {
		$name = $module['name'];
		$bits = $module['bits'];
		if( isset($ciniki['request']['args'][$name]) ) {
			if( $ciniki['request']['args'][$name] == 'Yes' && ($business_modules & $bits) != $bits ) {
				$strsql = sprintf("UPDATE businesses SET modules = modules | 0x%x "
					. "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "'", $bits);
				$rc = ciniki_core_dbUpdate($ciniki, $strsql, 'businesses');
				if( $rc['stat'] != 'ok' ) {
					ciniki_core_dbTransactionRollback($ciniki, 'businesses');
					return $rc;
				} 
				ciniki_core_dbAddChangeLog($ciniki, 'businesses', $args['business_id'], 'businesses', $name, 'modules', 'Yes');
			}
			elseif( $ciniki['request']['args'][$name] == 'No' && ($business_modules & $bits) == $bits ) {
				$strsql = sprintf("UPDATE businesses SET modules = modules ^ 0x%x "
					. "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "'", $bits);
				$rc = ciniki_core_dbUpdate($ciniki, $strsql, 'businesses');
				if( $rc['stat'] != 'ok' ) {
					ciniki_core_dbTransactionRollback($ciniki, 'businesses');
					return $rc;
				} 
				ciniki_core_dbAddChangeLog($ciniki, 'businesses', $args['business_id'], 'businesses', $name, 'modules', 'No');
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
