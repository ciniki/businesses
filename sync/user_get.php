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
// $user_uuid => Array(
//		[uuid] => d704ebb4-5554-11e2-9d3d-e17298a56f3f
//		[id] => 3
//		[email] => test@doun.org
//		[username] => test1
//		[firstname] => Test1
//		[lastname] => last
//		[status] => 1
//		[timeout] => 0
//		[display_name] => Test1
//		[date_added] => 1357201364
//		[last_updated] => 1357277499
//		[permissions] => Array(
//			[ciniki.owners] => Array(
//				[status] => 1
//				[date_added] => 1234123443
//				[last_updated] => 1234123443
//				[history] => Array(
//					[c6535860-5760-11e2-9d3d-e17298a56f3f] => Array(
//						[user] => 65be7d9c-508d-11e2-9d3d-e17298a56f3f
//						[session] => 130105.125305.186b02
//						[action] => 1
//						[table_field] => status
//						[new_value] => 1
//						[log_date] => 1357426393
//					)
//				)
//			)
//			[ciniki.employees] => Array(
//				[status] => 1
//				[date_added] => 1234123443
//				[last_updated] => 1234123443
//				[history] => Array(
//					...
//				)
//			)
//		)
//		[business_details] => Array(
//			[employee.title] => Array(
//				[detail_value] => 'Owner'
//				[date_added] => 1351283023
//				[last_updated] => 1351283023
//				[history] => Array(
//					...
//				)
//			)
//		)
//		[user_details] => Array(
//			[settings.datetime_format] => Array(
//				[detail_value] => %b %3, %Y %l:%i %p
//				[date_added] => 1231344322
//				[last_updated] => 1231344322
//				[history] => Array(
//					...
//				)
//			)
//			[settings.date_format] => Array(
//				[detail_value] => %b %3, %Y
//				[date_added] => 1231344322
//				[last_updated] => 1231344322
//				[history] => Array(
//				)
//			)
//		)
//		[history] => Array(
//					...
//		)
// )
//
function ciniki_businesses_user_get($ciniki, $sync, $business_id, $args) {
	//
	// Check the args
	//
	if( (!isset($args['uuid']) || $args['uuid'] == '') 
		&& (!isset($args['id']) || $args['id'] == '') ) {
//		&& (!isset($args['permission_key']) || $args['permission_key'] == '') ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'92', 'msg'=>'No user specified'));
	}

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');

	//
	// Get the customer information
	//
	$strsql = "SELECT u1.uuid, "
		. "u1.id, u1.email, u1.username, u1.firstname, u1.lastname, u1.timeout, u1.display_name, u1.status AS user_status, "
		. "UNIX_TIMESTAMP(u1.date_added) AS date_added, "
		. "UNIX_TIMESTAMP(u1.last_updated) AS last_updated, "
		. "ciniki_business_users.business_id, "
		. "ciniki_businesses.uuid AS business_uuid, "
		. "ciniki_business_users.package, "
		. "ciniki_business_users.permission_group, "
		. "CONCAT_WS('.', ciniki_business_users.package, ciniki_business_users.permission_group) AS permission, "
		. "ciniki_business_users.status AS permission_status, "
		. "UNIX_TIMESTAMP(ciniki_business_users.date_added) AS permission_date_added, "
		. "UNIX_TIMESTAMP(ciniki_business_users.last_updated) AS permission_last_updated, "
//		. "CONCAT_WS('.', ciniki_business_users.package, ciniki_business_users.permission_group) AS permission_key, "
		. "ciniki_business_history.id AS history_id, "
		. "ciniki_business_history.uuid AS history_uuid, "
		. "u2.uuid AS user_uuid, "
		. "ciniki_business_history.session, "
		. "ciniki_business_history.action, "
		. "ciniki_business_history.table_field, "
		. "ciniki_business_history.new_value, "
		. "UNIX_TIMESTAMP(ciniki_business_history.log_date) AS log_date "
		. "FROM ciniki_users AS u1 "
		. "LEFT JOIN ciniki_business_users ON (u1.id = ciniki_business_users.user_id "
			. "AND ciniki_business_users.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "') "
		. "LEFT JOIN ciniki_user_details ON (u1.id = ciniki_user_details.user_id) "
		. "LEFT JOIN ciniki_businesses ON (ciniki_business_users.business_id = ciniki_businesses.id) "
		. "LEFT JOIN ciniki_business_history ON ("
			. "CONCAT_WS('.', ciniki_business_users.user_id, ciniki_business_users.package, ciniki_business_users.permission_group) = ciniki_business_history.table_key "
			. "AND ciniki_business_history.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "AND ciniki_business_history.table_name = 'ciniki_business_users' "
			. ") "
		. "LEFT JOIN ciniki_users AS u2 ON (ciniki_business_history.user_id = u2.id) "
		. "";
	if( isset($args['uuid']) && $args['uuid'] != '' ) {
		$strsql .= "WHERE u1.uuid = '" . ciniki_core_dbQuote($ciniki, $args['uuid']) . "' ";
	} elseif( isset($args['id']) && $args['id'] != '' ) {
		$strsql .= "WHERE u1.id = '" . ciniki_core_dbQuote($ciniki, $args['id']) . "' ";
//	} elseif( isset($args['permission_key']) && $args['permission_key'] != '' ) {
//		$k = preg_split('/\./', $args['permission_key']);
//		$strsql .= "WHERE u1.id = '" . ciniki_core_dbQuote($ciniki, $k[0]) . "' "
//			. "AND ciniki_business_users.package = '" . ciniki_core_dbQuote($ciniki, $k[1]) . "' "
//			. "AND ciniki_business_users.permission_group = '" . ciniki_core_dbQuote($ciniki, $k[2]) . "' "
//			. "";
	} else {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'91', 'msg'=>'No user specified'));
	}
	$rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.customers', array(
		array('container'=>'users', 'fname'=>'uuid', 
			'fields'=>array('uuid', 'id', 'email', 'username', 'firstname', 'lastname', 'timeout', 'display_name',
				'status'=>'user_status', 'date_added', 'last_updated')),
		array('container'=>'permissions', 'fname'=>'permission', 
			'fields'=>array('business_id', 'business_uuid', 'package', 'permission_group', 'status'=>'permission_status',
				'date_added'=>'permission_date_added', 'last_updated'=>'permission_last_updated')),
		array('container'=>'history', 'fname'=>'history_uuid',
			'fields'=>array('user'=>'user_uuid', 'session', 
				'action', 'table_field', 'new_value', 'log_date')),
		));
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'85', 'msg'=>'Error retrieving the user information', 'err'=>$rc['err']));
	}

	//
	// Check that one and only one row was returned
	//
	if( !isset($rc['users']) || count($rc['users']) != 1 ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'82', 'msg'=>'User does not exist'));
	}
	$user = array_pop($rc['users']);
	if( !isset($user['permissions']) ) {
		$user['permissions'] = array();
	}

//	//
//	// Go throught and lookup the user permission_history
//	//
//	$user_uuids = array();
//	foreach($user['permissions'] as $permission => $perm_details) {
//		if( !isset($perm_details['history']) ) {
//			$user['permissions'][$permission]['history'] = array();
//		} else {
//			foreach($perm_details['history'] as $history_uuid => $history) {
//				if( $history['table_field'] == 'user_id' ) {
//					//
//					// Lookup user ID
//					//
//					if( isset($user_uuids[$history['new_value']]) ) {
//						$history['new_value'] = $user_uuids[$history['new_value']];
//					} else {
//						$strsql = "SELECT uuid FROM ciniki_users "
//							. "WHERE id = '" . ciniki_core_dbQuote($ciniki, $history['new_value']) . "' "
//							. "";
//						$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.users', 'user');
//						if( $rc['stat'] != 'ok' ) {
//							return $rc;
//						}
//						if( !isset($rc['user']) ) {
//							$user['permissions'][$permission]['history'][$history_uuid]['new_value'] = '';
//						} else {
//							$user_uuids[$history['new_value']] = $rc['user']['uuid'];
//							$user['permissions'][$permission]['history'][$history_uuid]['new_value'] = $rc['user']['uuid'];
//						}
//					}
//				}
//			}
//		}
//	}

	//
	// Get business details for user
	//
	$strsql = "SELECT ciniki_business_user_details.detail_key, "
		. "ciniki_business_user_details.detail_value, "
		. "UNIX_TIMESTAMP(ciniki_business_user_details.date_added) AS detail_date_added, "
		. "UNIX_TIMESTAMP(ciniki_business_user_details.last_updated) AS detail_last_updated, "
		. "ciniki_business_history.id AS history_id, "
		. "ciniki_business_history.uuid AS history_uuid, "
		. "ciniki_users.uuid AS user_uuid, "
		. "ciniki_business_history.session, "
		. "ciniki_business_history.action, "
		. "ciniki_business_history.table_field, "
		. "ciniki_business_history.new_value, "
		. "UNIX_TIMESTAMP(ciniki_business_history.log_date) AS log_date "
		. "FROM ciniki_business_user_details "
		. "LEFT JOIN ciniki_business_history ON (ciniki_business_user_details.detail_key = ciniki_business_history.table_field "
			. "AND ciniki_business_history.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "AND ciniki_business_history.table_name = 'ciniki_business_user_details' "
			. "AND ciniki_business_history.table_key = '" . ciniki_core_dbQuote($ciniki, $user['id']) . "' "
			. ") "
		. "LEFT JOIN ciniki_users ON (ciniki_business_history.user_id = ciniki_users.id) "
		. "WHERE ciniki_business_user_details.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "AND ciniki_business_user_details.user_id = '" . ciniki_core_dbQuote($ciniki, $user['id']) . "' "
		. "";
	$rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.users', array(
		array('container'=>'details', 'fname'=>'detail_key', 
			'fields'=>array('detail_value', 'date_added'=>'detail_date_added', 'last_updated'=>'detail_last_updated')),
		array('container'=>'history', 'fname'=>'history_uuid',
			'fields'=>array('user'=>'user_uuid', 'session', 
				'action', 'table_field', 'new_value', 'log_date')),
		));
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'898', 'msg'=>'Error retrieving the user information', 'err'=>$rc['err']));
	}
	if( !isset($rc['details']) ) {
		$user['business_details'] = array();
	} else {
		$user['business_details'] = $rc['details'];
	}

//	foreach($user['business_details'] as $detail_key => $detail) {
//		if( !isset($user['business_details'][$detail_key]['history']) ) {
//			$user['business_details'][$detail_key]['history'] = array();
//		}
//	}

	//
	// Get details for user
	//
	$strsql = "SELECT ciniki_user_details.detail_key, "
		. "ciniki_user_details.detail_value, "
		. "UNIX_TIMESTAMP(ciniki_user_details.date_added) AS detail_date_added, "
		. "UNIX_TIMESTAMP(ciniki_user_details.last_updated) AS detail_last_updated, "
		. "ciniki_user_history.id AS history_id, "
		. "ciniki_user_history.uuid AS history_uuid, "
		. "ciniki_users.uuid AS user_uuid, "
		. "ciniki_user_history.session, "
		. "ciniki_user_history.action, "
		. "ciniki_user_history.table_field, "
		. "ciniki_user_history.new_value, "
		. "UNIX_TIMESTAMP(ciniki_user_history.log_date) AS log_date "
		. "FROM ciniki_user_details "
		. "LEFT JOIN ciniki_user_history ON (ciniki_user_details.detail_key = ciniki_user_history.table_key "
			. "AND ciniki_user_history.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "AND ciniki_user_history.table_name = 'ciniki_user_details' "
			. ") "
		. "LEFT JOIN ciniki_users ON (ciniki_user_history.user_id = ciniki_users.id) "
		. "WHERE ciniki_user_details.user_id = '" . ciniki_core_dbQuote($ciniki, $user['id']) . "' "
		. "";
	$rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.users', array(
		array('container'=>'details', 'fname'=>'detail_key', 
			'fields'=>array('detail_value', 'date_added'=>'detail_date_added', 'last_updated'=>'detail_last_updated')),
		array('container'=>'history', 'fname'=>'history_uuid',
			'fields'=>array('user'=>'user_uuid', 'session', 
				'action', 'table_field', 'new_value', 'log_date')),
		));
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'81', 'msg'=>'Error retrieving the user information', 'err'=>$rc['err']));
	}
	if( !isset($rc['details']) ) {
		$user['user_details'] = array();
	} else {
		$user['user_details'] = $rc['details'];
	}

	//
	// Get the history for the user
	//
	$strsql = "SELECT "
		. "ciniki_user_history.id AS history_id, "
		. "ciniki_user_history.uuid AS history_uuid, "
		. "ciniki_users.uuid AS user_uuid, "
		. "ciniki_user_history.session, "
		. "ciniki_user_history.action, "
		. "ciniki_user_history.table_field, "
		. "ciniki_user_history.new_value, "
		. "UNIX_TIMESTAMP(ciniki_user_history.log_date) AS log_date "
		. "FROM ciniki_user_history "
		. "LEFT JOIN ciniki_users ON (ciniki_user_history.user_id = ciniki_users.id) "
		. "WHERE ciniki_user_history.table_key = '" . ciniki_core_dbQuote($ciniki, $user['id']) . "' "
		. "AND ciniki_user_history.table_name = 'ciniki_users' "
		. "AND ciniki_user_history.business_id = 0 "
		. "AND table_field IN ('firstname', 'lastname', 'display_name') "
		. "";
	$rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.users', array(
		array('container'=>'history', 'fname'=>'history_uuid',
			'fields'=>array('user'=>'user_uuid', 'session', 
				'action', 'table_field', 'new_value', 'log_date')),
		));
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'886', 'msg'=>'Error retrieving the user information', 'err'=>$rc['err']));
	}
	if( !isset($rc['history']) ) {
		$user['history'] = array();
	} else {
		$user['history'] = $rc['history'];
	}

	return array('stat'=>'ok', 'user'=>$user);
}
?>
