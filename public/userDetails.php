<?php
//
// Description
// -----------
// This method will return a list of the users who have permissions within a business.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id: 			The ID of the business to lock.
//
// Returns
// -------
// <rsp stat="ok" />
//
function ciniki_businesses_userDetails($ciniki) {
	//
	// Find all the required and optional arguments
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/prepareArgs.php');
	$rc = ciniki_core_prepareArgs($ciniki, 'no', array(
		'business_id'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No business specified'), 
		'user_id'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No user specified'), 
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$args = $rc['args'];

	//
	// Check access 
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/businesses/private/checkAccess.php');
	$rc = ciniki_businesses_checkAccess($ciniki, $args['business_id'], 'ciniki.businesses.userDetails');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$modules = $rc['modules'];

	//
	// Get details for a user
	//
	$strsql = "SELECT ciniki_business_users.user_id, "
		. "ciniki_users.firstname, ciniki_users.lastname, "
		. "ciniki_users.email, ciniki_users.display_name, "
		. "ciniki_business_user_details.detail_key, ciniki_business_user_details.detail_value "
		. "FROM ciniki_business_users "
		. "LEFT JOIN ciniki_users ON (ciniki_business_users.user_id = ciniki_users.id ) "
		. "LEFT OUTER JOIN ciniki_business_user_details ON (ciniki_business_users.business_id = ciniki_business_user_details.business_id "
			. "AND ciniki_business_users.user_id = ciniki_business_user_details.user_id ) "
		. "WHERE ciniki_business_users.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND ciniki_business_users.user_id = '" . ciniki_core_dbQuote($ciniki, $args['user_id']) . "' "
		. "";
	error_log($strsql);
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'businesses', array(
		array('container'=>'users', 'fname'=>'user_id', 'name'=>'user', 
			'fields'=>array('user_id', 'firstname', 'lastname', 'email', 'display_name'),
			'details'=>array('detail_key'=>'detail_value'),
			),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['users'][0]['user']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'744', 'msg'=>'Unable to find user'));
	}

	$user = $rc['users'][0]['user'];

	//
	// Check if the business is active and the module is enabled
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/businesses/private/checkModuleAccess.php');
	$rc = ciniki_businesses_checkModuleAccess($ciniki, $args['business_id'], 'ciniki', 'web');
	if( $rc['stat'] == 'ok' ) {
		$strsql = "SELECT detail_value "
			. "FROM ciniki_web_settings "
			. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "AND detail_key = 'page-contact-user-display-flags-" . ciniki_core_dbQuote($ciniki, $args['user_id']) . "' "
			. "";
		$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'web', 'user');
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( isset($rc['user']['detail_value']) && $rc['user']['detail_value'] != '' ) {
			$user['page-contact-user-display-flags-' . $args['user_id']] = $rc['user']['detail_value'];
		} else {
			$user['page-contact-user-display-flags-' . $args['user_id']] = 0;
		}
	}

	return array('stat'=>'ok', 'user'=>$user);
}
?>
