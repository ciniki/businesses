<?php
//
// Description
// -----------
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_businesses_sync_userAdd(&$ciniki, $sync, $business_id, $args) {
	//
	// Check the args
	//
	if( !isset($args['user']) || $args['user'] == '' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'94', 'msg'=>'No type specified'));
	}
	$user = $args['user'];

	//  
	// Turn off autocommit
	//  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbInsert');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncUpdateTableElementHistory');
	$rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.users');
	if( $rc['stat'] != 'ok' ) { 
		return $rc;
	}   

	error_log('Adding user ' . $user['email'] . " to $business_id");

	//
	// Create a random password for the user
	//
	$password = '';
	$temp_password = '';
	$chars = 'ABCDEFGHJKMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789';
	for($i=0;$i<16;$i++) {
		$password .= substr($chars, rand(0, strlen($chars)-1), 1);
	}

	//
	// Create the user record
	//
	$strsql = "INSERT INTO ciniki_users (uuid, email, username, password, firstname, lastname, display_name, "
		. "date_added, last_updated) VALUES ("
		. "'" . ciniki_core_dbQuote($ciniki, $user['uuid']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $user['email']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $user['username']) . "', "
		. "SHA1('" . ciniki_core_dbQuote($ciniki, $password) . "'), "
		. "'" . ciniki_core_dbQuote($ciniki, $user['firstname']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $user['lastname']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $user['display_name']) . "', "
		. "FROM_UNIXTIME('" . ciniki_core_dbQuote($ciniki, $user['date_added']) . "'), "
		. "FROM_UNIXTIME('" . ciniki_core_dbQuote($ciniki, $user['last_updated']) . "') "
		. ") "
		. "";
	$rc = ciniki_core_dbInsert($ciniki, $strsql, 'ciniki.users');
	if( $rc['stat'] != 'ok' ) { 
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.users');
		return $rc;
	}
	if( !isset($rc['insert_id']) || $rc['insert_id'] < 1 ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.users');
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'891', 'msg'=>'Unable to add user'));
	}
	$user_id = $rc['insert_id'];

	if( isset($user['history']) ) {
		$rc = ciniki_core_syncUpdateTableElementHistory($ciniki, $sync, $business_id, 'ciniki.users',
			'ciniki_user_history', $user_id, 'ciniki_users', $user['history'], array(), array());
		if( $rc['stat'] != 'ok' ) {
			ciniki_core_dbTransactionRollback($ciniki, 'ciniki.users');
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'892', 'msg'=>'Unable to save history', 'err'=>$rc['err']));
		}
	}

	// 
	// Create ciniki_user_details
	//
	if( isset($user['user_details']) ) {
		foreach($user['user_details'] as $detail_key => $detail) {
			//
			// Create the email record
			//
			$strsql = "INSERT INTO ciniki_user_details (user_id, detail_key, detail_value, "
				. "date_added, last_updated) VALUES ("
				. "'" . ciniki_core_dbQuote($ciniki, $user_id) . "', "
				. "'" . ciniki_core_dbQuote($ciniki, $detail_key) . "', "
				. "'" . ciniki_core_dbQuote($ciniki, $detail['detail_value']) . "', "
				. "FROM_UNIXTIME('" . ciniki_core_dbQuote($ciniki, $detail['date_added']) . "'), "
				. "FROM_UNIXTIME('" . ciniki_core_dbQuote($ciniki, $detail['last_updated']) . "') "
				. ") "
				. "";
			$rc = ciniki_core_dbInsert($ciniki, $strsql, 'ciniki.users');
			if( $rc['stat'] != 'ok' ) { 
				ciniki_core_dbTransactionRollback($ciniki, 'ciniki.users');
				return $rc;
			}
			if( !isset($rc['insert_id']) || $rc['insert_id'] < 1 ) {
				ciniki_core_dbTransactionRollback($ciniki, 'ciniki.users');
				return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'914', 'msg'=>'Unable to add user'));
			}
			$detail_id = $rc['insert_id'];
			
			if( isset($detail['history']) ) {
				$rc = ciniki_core_syncUpdateTableElementHistory($ciniki, $sync, $business_id, 'ciniki.users',
					'ciniki_user_history', $user_id, 'ciniki_user_details', $detail['history'], array(), array());
				if( $rc['stat'] != 'ok' ) {
					ciniki_core_dbTransactionRollback($ciniki, 'ciniki.users');
					return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'894', 'msg'=>'Unable to save history', 'err'=>$rc['err']));
				}
			}
		}
	}

	//
	// Update the business user permissions
	//
	if( isset($user['permissions']) ) {
		foreach($user['permissions'] as $permission => $perm_detail) {
			$strsql = "INSERT INTO ciniki_business_users (business_id, user_id, package, permission_group, "
				. "status, date_added, last_updated) VALUES ("
				. "'" . ciniki_core_dbQuote($ciniki, $business_id) . "', "
				. "'" . ciniki_core_dbQuote($ciniki, $user_id) . "', "
				. "'" . ciniki_core_dbQuote($ciniki, $perm_detail['package']) . "', "
				. "'" . ciniki_core_dbQuote($ciniki, $perm_detail['permission_group']) . "', "
				. "'" . ciniki_core_dbQuote($ciniki, $perm_detail['status']) . "', "
				. "FROM_UNIXTIME('" . ciniki_core_dbQuote($ciniki, $perm_detail['date_added']) . "'), "
				. "FROM_UNIXTIME('" . ciniki_core_dbQuote($ciniki, $perm_detail['last_updated']) . "') "
				. ")";
			$rc = ciniki_core_dbInsert($ciniki, $strsql, 'ciniki.businesses');
			if( $rc['stat'] != 'ok' && $rc['err']['code'] != '73' ) {
				return $rc;
			}
			if( !isset($rc['insert_id']) || $rc['insert_id'] < 1 ) {
				ciniki_core_dbTransactionRollback($ciniki, 'ciniki.users');
				return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'893', 'msg'=>'Unable to add user'));
			}
			$detail_id = $rc['insert_id'];
			
			if( isset($detail['history']) ) {
				$rc = ciniki_core_syncUpdateTableElementHistory($ciniki, $sync, $business_id, 'ciniki.users',
					'ciniki_user_history', $user_id . '.' . $detail['package'] . '.' . $detail['permission_group'], 'ciniki_business_users', $detail['history'], array(), array());
				if( $rc['stat'] != 'ok' ) {
					ciniki_core_dbTransactionRollback($ciniki, 'ciniki.users');
					return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'912', 'msg'=>'Unable to save history', 'err'=>$rc['err']));
				}
			}
		}
	}

	// 
	// Create ciniki_business_user_details
	//
	if( isset($user['business_details']) ) {
		foreach($user['business_details'] as $detail_key => $detail) {
			//
			// Create the email record
			//
			$strsql = "INSERT INTO ciniki_business_user_details (business_id, user_id, detail_key, detail_value, "
				. "date_added, last_updated) VALUES ("
				. "'" . ciniki_core_dbQuote($ciniki, $business_id) . "', "
				. "'" . ciniki_core_dbQuote($ciniki, $user_id) . "', "
				. "'" . ciniki_core_dbQuote($ciniki, $detail_key) . "', "
				. "'" . ciniki_core_dbQuote($ciniki, $detail['detail_value']) . "', "
				. "FROM_UNIXTIME('" . ciniki_core_dbQuote($ciniki, $detail['date_added']) . "'), "
				. "FROM_UNIXTIME('" . ciniki_core_dbQuote($ciniki, $detail['last_updated']) . "') "
				. ") "
				. "";
			$rc = ciniki_core_dbInsert($ciniki, $strsql, 'ciniki.users');
			if( $rc['stat'] != 'ok' ) { 
				ciniki_core_dbTransactionRollback($ciniki, 'ciniki.users');
				return $rc;
			}
			if( !isset($rc['insert_id']) || $rc['insert_id'] < 1 ) {
				ciniki_core_dbTransactionRollback($ciniki, 'ciniki.users');
				return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'913', 'msg'=>'Unable to add user'));
			}
			$detail_id = $rc['insert_id'];
			
			if( isset($detail['history']) ) {
				$rc = ciniki_core_syncUpdateTableElementHistory($ciniki, $sync, $business_id, 'ciniki.users',
					'ciniki_user_history', $user_id, 'ciniki_user_details', $detail['history'], array(), array());
				if( $rc['stat'] != 'ok' ) {
					ciniki_core_dbTransactionRollback($ciniki, 'ciniki.users');
					return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'915', 'msg'=>'Unable to save history', 'err'=>$rc['err']));
				}
			}
		}
	}
	//
	// Commit the database changes
	//
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.users');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	//
	// Get the list of businesses this user is part of, and replicate that user for that business
	//
	$ciniki['syncqueue'][] = array('method'=>'ciniki.businesses.syncPushUser', 'args'=>array('id'=>$user_id));

	return array('stat'=>'ok', 'user_id'=>$user_id);
}
?>