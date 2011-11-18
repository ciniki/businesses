<?php
//
// Description
// -----------
// This function will retrieve all the owners and their businesses.  This is
// only available to sys admins.
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
// <users>
//		<user id='' email='' firstname='' lastname='' display_name=''>
//			<businesses>
//				<business id='' name='' />
//			</businesses>
//		</user>
// </users>
//
function ciniki_businesses_getAllOwners($ciniki) {
	//
	// Check access to business_id as owner, or sys admin
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/businesses/private/checkAccess.php');
	$ac = ciniki_businesses_checkAccess($ciniki, 0, 'ciniki.businesses.getAllOwners');
	if( $ac['stat'] != 'ok' ) {
		return $ac;
	}
	
	//
	// Query for the business users
	// FIXME: Add other types besides Owner
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbHashIDQuery3.php');
	$strsql = "SELECT ciniki_businesses.id, ciniki_businesses.name, "
		. "ciniki_business_users.user_id, ciniki_business_users.business_id "
		. "FROM ciniki_businesses, ciniki_business_users "
		. "WHERE ciniki_businesses.id = ciniki_business_users.business_id "
		. "AND ciniki_business_users.type = 1 "
		. "ORDER BY ciniki_business_users.user_id, ciniki_businesses.name ";
	$rc = ciniki_core_dbHashIDQuery3($ciniki, $strsql, 'businesses', 'users', 'user_id', 'user', 'businesses', 'business_id', 'business');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( $rc['num_rows'] < 1 ) {
		return array('stat'=>'ok', 'users'=>array());
	}

	$user_businesses = array();
	foreach($rc['users'] as $userNUM => $u) {
		$user = $u['user'];
		$user_businesses[$user['id']] = $user['businesses'];
	}

	$strsql = "SELECT id, email, firstname, lastname, display_name FROM ciniki_users "
		. "WHERE id IN (" . ciniki_core_dbQuote($ciniki, implode(',', array_keys($user_businesses))) . ") ORDER BY lastname, firstname";
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbRspQuery.php');
	$rsp = ciniki_core_dbRspQuery($ciniki, $strsql, 'users', 'users', 'user', array('stat'=>'ok', 'users'=>array()));
	
	foreach($rsp['users'] as $userNUM => $u) {
		$rsp['users'][$userNUM]['user']['businesses'] = $user_businesses[$u['user']['id']];
	}

	return $rsp;
}
?>
