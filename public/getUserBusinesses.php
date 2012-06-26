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
//		<business id='4592' name='Temporary Test Business' />
//		<business id='20719' name='Old Test Business' />
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
	// are a ciniki-manage, they have access to all businesses.
	// Link to the business_users table to grab the groups the user belongs to for that business.
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbQuote.php');
	if( ($ciniki['session']['user']['perms'] & 0x01) == 0x01 ) {
		$strsql = "SELECT ciniki_businesses.id, name, "
			. "IF(id='" . ciniki_core_dbQuote($ciniki, $ciniki['config']['core']['master_business_id']) . "', 'yes', 'no') AS ismaster "
			. "FROM ciniki_businesses "
//			. "LEFT JOIN ciniki_business_users ON (ciniki_businesses.id = ciniki_business_users.business_id "
//				. "AND ciniki_business_users.user_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "' ) "
			. "WHERE ciniki_businesses.status < 60 "
//			. "LEFT JOIN ciniki_business_details AS d1 ON (ciniki_businesses.id = d1.business_id AND d1.detail_key = 'ciniki.manage.css') "
			. "ORDER BY ismaster DESC, ciniki_businesses.status, ciniki_businesses.name ";
	} else {
		$strsql = "SELECT DISTINCT ciniki_businesses.id, name "
			. "FROM ciniki_business_users, ciniki_businesses "
			. "WHERE ciniki_business_users.user_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "' "
			. "AND ciniki_business_users.status = 1 "
			. "AND ciniki_business_users.business_id = ciniki_businesses.id "
			. "AND ciniki_businesses.status < 60 "	// Allow suspended businesses to be listed, so user can login and update billing/unsuspend
			. "ORDER BY ciniki_businesses.name ";
//		$strsql = "SELECT DISTINCT id, name, ciniki_business_users.permission_group, "
//			. "d1.detail_value AS css "
//			. "FROM ciniki_business_users, ciniki_businesses "
//			. "LEFT JOIN ciniki_business_details AS d1 ON (ciniki_businesses.id = d1.business_id AND d1.detail_key = 'ciniki.manage.css') "
//			. "WHERE ciniki_business_users.user_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "' "
//			. "AND ciniki_business_users.status = 1 "
//			. "AND ciniki_business_users.business_id = ciniki_businesses.id "
//			. "AND ciniki_businesses.status < 60 "	// Allow suspended businesses to be listed, so user can login and update billing/unsuspend
//			. "ORDER BY ciniki_businesses.name ";
	}	
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbRspQuery.php');

	return ciniki_core_dbRspQuery($ciniki, $strsql, 'businesses', 'businesses', 'business', array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'66', 'msg'=>'No businesses found')));

}
?>
