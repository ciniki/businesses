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
	ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'checkAccess');
	$ac = ciniki_businesses_checkAccess($ciniki, 0, 'ciniki.businesses.getAllOwners');
	if( $ac['stat'] != 'ok' ) {
		return $ac;
	}

	$strsql = "SELECT ciniki_users.id AS user_id, email, firstname, lastname, display_name, "
		. "ciniki_businesses.id AS business_id, ciniki_businesses.name "
		. "FROM ciniki_users "
		. "LEFT JOIN ciniki_business_users ON (ciniki_users.id = ciniki_business_users.user_id "
			. "AND ciniki_business_users.status = 10) "
		. "LEFT JOIN ciniki_businesses ON (ciniki_business_users.business_id = ciniki_businesses.id) "
		. "ORDER BY ciniki_users.lastname, ciniki_users.firstname, ciniki_businesses.name "
		. "";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.businesses', array(
		array('container'=>'users', 'fname'=>'user_id', 'name'=>'user',
			'fields'=>array('id'=>'user_id', 'display_name', 'firstname', 'lastname', 'email')),
		array('container'=>'businesses', 'fname'=>'business_id', 'name'=>'business',
			'fields'=>array('id'=>'business_id', 'name')),
		));
	return $rc;
}
?>
