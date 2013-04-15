<?php
//
// Description
// -----------
// This method will return all the information about a business required when the user
// logs into the UI. 
//
// Arguments
// ---------
// api_key:
// auth_token:
//
// Returns
// -------
// <business name="">
// 	<css>
// 	</css>
// 	<modules>
//		<modules name='questions' />
// 	</modules>
// </business>
//
function ciniki_businesses_getUserSettings($ciniki) {
	//
	// Find all the required and optional arguments
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
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
	ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'checkAccess');
	$rc = ciniki_businesses_checkAccess($ciniki, $args['business_id'], 'ciniki.businesses.getUserSettings');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	//
	// Setup the default return array
	//
	$rsp = array('stat'=>'ok', 'modules'=>array());

	//
	// Get the business name, and CSS
	// FIXME: convert ciniki.manage.css to ciniki-manage-css
	//
	$strsql = "SELECT name, d1.detail_value AS css "
		. "FROM ciniki_businesses "
		. "LEFT JOIN ciniki_business_details AS d1 ON (ciniki_businesses.id = d1.business_id AND d1.detail_key = 'ciniki.manage.css') "
		. "WHERE ciniki_businesses.id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.businesses', 'business');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( isset($rc['business']) ) {
		$rsp['name'] = $rc['business']['name'];
		if( isset($rc['business']['css']) ) {
			$rsp['css'] = $rc['business']['css'];
		}
	}

	//
	// Get list of employees for the business
	//
	$strsql = "SELECT DISTINCT ciniki_business_users.user_id AS id, ciniki_users.display_name "
		. "FROM ciniki_business_users, ciniki_users "
		. "WHERE ciniki_business_users.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' " 
		. "AND ciniki_business_users.user_id = ciniki_users.id "
		. "ORDER BY display_name "
		. "";
	$rc = ciniki_core_dbRspQuery($ciniki, $strsql, 'ciniki.businesses', 'users', 'user', array('stat'=>'ok', 'users'=>array()));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$rsp['users'] = $rc['users'];

	//
	// Get the permission_groups for the user requesting the business information
	//
	$strsql = "SELECT permission_group AS name "
		. "FROM ciniki_business_users "
		. "WHERE ciniki_business_users.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' " 
		. "AND ciniki_business_users.user_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "' "
		. "";
	$rc = ciniki_core_dbRspQuery($ciniki, $strsql, 'ciniki.businesses', 'permissions', 'group', array('stat'=>'ok', 'permissions'=>array()));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$rsp['permissions'] = $rc['permissions'];

	//
	// FIXME: Add check to see which groups the user is part of, and only hand back the module list
	//        for what they have access to.
	//
	$strsql = "SELECT CONCAT_WS('.', package, module) AS name, package, module FROM ciniki_business_modules "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND status = 1 "
		. "";
	$mrc = ciniki_core_dbRspQuery($ciniki, $strsql, 'ciniki.businesses', 'modules', 'module', array('stat'=>'ok', 'modules'=>array()));

	//
	// Check for any modules which should have some settings loaded as well
	//
	if( $mrc['stat'] == 'ok' ) {
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDetailsQuery');
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDetailsQueryDash');
		foreach($mrc['modules'] as $i => $module) {
			if( $module['module']['name'] == 'ciniki.atdo' ) {
				$rc = ciniki_core_dbDetailsQuery($ciniki, 'ciniki_atdo_settings', 'business_id', $args['business_id'], 'ciniki.atdo', 'settings', '');
				if( $rc['stat'] == 'ok' ) {
					$rsp['settings']['ciniki.atdo'] = $rc['settings'];
				}
			}
			if( $module['module']['name'] == 'ciniki.bugs' ) {
				$rc = ciniki_core_dbDetailsQuery($ciniki, 'ciniki_bug_settings', 'business_id', $args['business_id'], 'ciniki.bugs', 'settings', '');
				if( $rc['stat'] == 'ok' ) {
					$rsp['settings']['ciniki.bugs'] = $rc['settings'];
				} 
			}
			if( $module['module']['name'] == 'ciniki.customers' ) {
				$rc = ciniki_core_dbDetailsQueryDash($ciniki, 'ciniki_customer_settings', 'business_id', $args['business_id'], 'ciniki.customers', 'settings', '');
				if( $rc['stat'] == 'ok' ) {
					$rsp['settings']['ciniki.customers'] = $rc['settings'];
				} 
			}
			if( $module['module']['name'] == 'ciniki.services' ) {
				$rc = ciniki_core_dbDetailsQueryDash($ciniki, 'ciniki_service_settings', 'business_id', $args['business_id'], 'ciniki.services', 'settings', '');
				if( $rc['stat'] == 'ok' ) {
					$rsp['settings']['ciniki.services'] = $rc['settings'];
				} 
			}
			if( $module['module']['name'] == 'ciniki.mail' ) {
				$rc = ciniki_core_dbDetailsQueryDash($ciniki, 'ciniki_mail_settings', 'business_id', $args['business_id'], 'ciniki.mail', 'settings', 'mail');
				if( $rc['stat'] == 'ok' ) {
					$rsp['settings']['ciniki.mail'] = $rc['settings'];
				} 
			}
		}
	}

	return $rsp;
}
?>
