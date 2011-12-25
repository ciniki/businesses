<?php
//
// Description
// -----------
// This function will get detail values for a business.  These values
// are used many places in the API and Manage.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:				The ID of the user to get the details for.
// keys:				The comma delimited list of keys to lookup values for.
//
// Returns
// -------
// <details>
//		<user firstname='' lastname='' display_name=''/>
//  	<settings date_format='' />
// </details>
//
function ciniki_businesses_get($ciniki) {
	//
	// Find all the required and optional arguments
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/prepareArgs.php');
	$rc = ciniki_core_prepareArgs($ciniki, 'no', array(
		'id'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No business specified'), 
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$args = $rc['args'];
	
	//
	// Check access, should only be accessible by sysadmin
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/businesses/private/checkAccess.php');
	$rc = ciniki_businesses_checkAccess($ciniki, 0, 'ciniki.businesses.get', $args['id']);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbQuote.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbHashQuery.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbDetailsQuery.php');

	require_once($ciniki['config']['core']['modules_dir'] . '/users/private/datetimeFormat.php');
	$date_format = ciniki_users_datetimeFormat($ciniki);

	//
	// Get all the information form ciniki_users table
	//
	$strsql = "SELECT id, uuid, name, status, "
		. "DATE_FORMAT(date_added, '" . ciniki_core_dbQuote($ciniki, $date_format) . "') AS date_added, "
		. "DATE_FORMAT(last_updated, '" . ciniki_core_dbQuote($ciniki, $date_format) . "') AS last_updated "
		. "FROM ciniki_businesses "
		. "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['id']) . "' "
		. "";
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.businesses', 'business');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['business']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'512', 'msg'=>'Unable to find business'));
	}
	$business = $rc['business'];

	//
	// Get all the businesses the user is a part of
	//
	$strsql = "SELECT ciniki_users.id, ciniki_users.firstname, ciniki_users.lastname "
		. "FROM ciniki_business_users, ciniki_users "
		. "WHERE ciniki_business_users.business_id = '" . ciniki_core_dbQuote($ciniki, $args['id']) . "' "
		. "AND ciniki_business_users.user_id = ciniki_users.id "
		. "";
	$rc = ciniki_core_dbRspQuery($ciniki, $strsql, 'ciniki.businesses', 'users', 'user', array('stat'=>'ok', 'users'=>array()));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$business['users'] = $rc['users'];

	return array('stat'=>'ok', 'business'=>$business);
}
?>
