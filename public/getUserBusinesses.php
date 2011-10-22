<?php
//
// Description
// -----------
// This function will return the list of businesses which the user has access to.
//
// Info
// ----
// Status: beta
//
// Arguments
// ---------
// api_key:
// auth_token:
//
// Returns
// -------
// <businesses>
//		<business id='4592' name='Temporary Test Business' modules='bitmask' />
//		<business id='20719' name='Old Test Business' modules='bitmask' />
// </businesses>
//
function ciniki_businesses_getUserBusinesses($ciniki) {
	//
	// Any authenticated user has access to this function, so no need to check permissions
	//

	//
	// Check access to business_id as owner, or sys admin
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/businesses/private/checkAccess.php');
	$ac = ciniki_businesses_checkAccess($ciniki, 0, 'ciniki.businesses.getUserBusinesses');
	if( $ac['stat'] != 'ok' ) {
		return $ac;
	}

	// 
	// Check the database for user and which businesses they have access to.  If they
	// are a MOSS Admin, they have access to all businesses.
	// Link to the business_users table to grab the groups the user belongs to for that business.
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbQuote.php');
	if( ($ciniki['session']['user']['perms'] & 0x01) == 0x01 ) {
		$strsql = "SELECT id, modules, name, business_users.groups, "
			. "d1.detail_value AS manage_theme "
			. "FROM businesses "
			. "LEFT JOIN business_users ON (businesses.id = business_users.business_id "
				. "AND business_users.user_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "' ) "
			. "LEFT JOIN business_details AS d1 ON (businesses.id = d1.business_id AND d1.detail_key = 'manage.theme') "
			. "ORDER BY businesses.status, businesses.name ";
	} else {
		$strsql = "SELECT id, modules, name, business_users.groups, "
			. "d1.detail_value AS manage_theme "
			. "FROM business_users, businesses "
			. "LEFT JOIN business_details AS d1 ON (businesses.id = d1.business_id AND d1.detail_key = 'manage.theme') "
			. "WHERE business_users.user_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "' "
			. "AND business_users.status = 1 "
			. "AND business_users.business_id = businesses.id "
			. "AND businesses.status = 1 "
			. "ORDER BY businesses.name ";
	}	
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbRspQuery.php');

	return ciniki_core_dbRspQuery($ciniki, $strsql, 'businesses', 'businesses', 'business', array('stat'=>'fail', 'err'=>array('code'=>'66', 'msg'=>'No businesses found')));

}
?>
