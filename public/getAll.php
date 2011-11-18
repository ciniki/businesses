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
// <businesses>
//		<business id='' name='' >
//			<users>
//				<user id='' email='' firstname='' lastname='' display_name='' />
//			</users>
//		</business>
// </businesses>
//
function ciniki_businesses_getAll($ciniki) {
	//
	// Check access to business_id as owner, or sys admin
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/businesses/private/checkAccess.php');
	$ac = ciniki_businesses_checkAccess($ciniki, 0, 'ciniki.businesses.getAll');
	if( $ac['stat'] != 'ok' ) {
		return $ac;
	}
	
	//
	// Query for the business users
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbHashIDQuery4.php');
	$strsql = "SELECT ciniki_businesses.id as business_id, ciniki_businesses.name, ciniki_business_users.user_id "
		. "FROM ciniki_businesses "
		. "LEFT JOIN ciniki_business_users ON (ciniki_businesses.id = ciniki_business_users.business_id AND ciniki_business_users.type = 1) "
		. "ORDER BY ciniki_businesses.name ";
	$rsp = ciniki_core_dbHashIDQuery4($ciniki, $strsql, 'businesses',
		array('container'=>'businesses', 'fname'=>'business_id', 'name'=>'business', 'fields'=>array('name')), 
		array('container'=>'users', 'fname'=>'user_id', 'name'=>'user', 'fields'=>array())
		);
	if( $rsp['stat'] != 'ok' ) {
		return $rsp;
	}
	if( $rsp['num_rows'] < 1 ) {
		return array('stat'=>'ok', 'businesses'=>array());
	}

	$users = array();
	foreach($rsp['businesses'] as $bNUM => $b) {
		foreach($b['business']['users'] as $uNUM => $u) {
			$users[$u['user']['id']] = 1;
		}
	}

	$strsql = "SELECT id, email, firstname, lastname, display_name FROM ciniki_users "
		. "WHERE id IN (" . ciniki_core_dbQuote($ciniki, implode(',', array_keys($users))) . ") ORDER BY lastname, firstname";
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbHashIDQuery.php');
	$urc = ciniki_core_dbHashIDQuery($ciniki, $strsql, 'users', 'users', 'id');

	$users = array();
	foreach($rsp['businesses'] as $bNUM => $b) {
		foreach($b['business']['users'] as $uNUM => $u) {
			$rsp['businesses'][$bNUM]['business']['users'][$uNUM]['user'] = $urc['users'][$u['user']['id']];
		}
	}

	return $rsp;
}
?>
