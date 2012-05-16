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
	$rsp = ciniki_core_dbRspQuery($ciniki, $strsql, 'businesses', 'modules', 'module', array('stat'=>'ok', 'modules'=>array()));

	//
	// Check for any modules which should have some stats with them
	//
	if( $rsp['stat'] == 'ok' ) {
		foreach($rsp['modules'] as $i => $module) {
			if( $module['module']['name'] == 'ciniki.atdo' ) {
				$strsql = "SELECT 'numtasks', COUNT(ciniki_atdos.id) "
					. "FROM ciniki_atdos, ciniki_atdo_users "
					. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
					. "AND ciniki_atdos.type = 2 "	// Tasks
					. "AND ciniki_atdos.id = ciniki_atdo_users.atdo_id "
					. "AND ciniki_atdo_users.user_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "' "
					. "AND ciniki_atdos.status = 1 "
					. "AND (ciniki_atdo_users.perms&0x04) = 0x04 "
					. "";
				ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbCount');
				$rc = ciniki_core_dbCount($ciniki, $strsql, 'atdo', 'atdo');
				if( $rc['stat'] != 'ok' ) {
					return $rc;
				}
				$rsp['modules'][$i]['module']['task_count'] = $rc['atdo']['numtasks'];
				$strsql = "SELECT type, COUNT(ciniki_atdos.id) AS num_items "
					. "FROM ciniki_atdos, ciniki_atdo_users "
					. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
					. "AND (ciniki_atdos.type = 6 OR ciniki_atdos.type = 5 )"	// Messages or Notes
					. "AND ciniki_atdos.id = ciniki_atdo_users.atdo_id "
					. "AND ciniki_atdo_users.user_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "' "
					. "AND ciniki_atdos.status = 1 "
					. "AND (ciniki_atdo_users.perms&0x04) = 0x04 "
					. "AND (ciniki_atdo_users.perms&0x08) = 0x08 "
					. "GROUP BY type "
					. "";
				ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashIDQuery');
				$rc = ciniki_core_dbHashIDQuery($ciniki, $strsql, 'atdo', 'atdo', 'type');
				if( $rc['stat'] != 'ok' ) {
					return $rc;
				}
				if( isset($rc['atdo']['6']['num_items']) ) {
					$rsp['modules'][$i]['module']['message_count'] = 0 + $rc['atdo']['6']['num_items'];
				} else {
					$rsp['modules'][$i]['module']['message_count'] = 0;
				}
				if( isset($rc['atdo']['5']['num_items']) ) {
					$rsp['modules'][$i]['module']['message_count'] = 0 + $rc['atdo']['5']['num_items'];
				} else {
					$rsp['modules'][$i]['module']['message_count'] = 0;
				}
			}
		}
	}

	return $rsp;
}
?>
