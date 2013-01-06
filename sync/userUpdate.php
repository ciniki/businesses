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
function ciniki_businesses_sync_userUpdate(&$ciniki, &$sync, $business_id, $args) {
	//
	// Check the args
	//
	if( !isset($args['user']) || $args['user'] == '' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'890', 'msg'=>'No type specified'));
	}
	$remote_user = $args['user'];

	//
	// Check if user already exists in local server, and if not run the add script
	//
	$strsql = "SELECT id FROM ciniki_users "
		. "WHERE ciniki_users.uuid = '" . ciniki_core_dbQuote($ciniki, $remote_user['uuid']) . "' "
		. "";
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.users', 'user');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['user']) ) {
		ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'sync', 'userAdd');
		
		//
		// Check to see if the email address is under another uuid
		//
		$strsql = "SELECT id, uuid FROM ciniki_users "
			. "WHERE ciniki_users.email = '" . ciniki_core_dbQuote($ciniki, $remote_user['email']) . "' "
			. "";
		$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.users', 'user');
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( !isset($rc['user']) ) {
			//
			// User does not exist at all
			//
			ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'sync', 'userAdd');
			$rc = ciniki_businesses_sync_userAdd($ciniki, $sync, $business_id, $args);
			return $rc;
		} else {
			//
			// Make sure the uuid map is in added
			//
			$user_id = $rc['user']['id'];
			$user_uuid = $rc['user']['uuid'];
			if( !isset($sync['uuidmaps']['ciniki_users'][$remote_user['uuid']]) ) {
				ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncUpdateUUIDMap');
				$rc = ciniki_core_syncUpdateUUIDMap($ciniki, $sync, $business_id, 'ciniki_users', $remote_user['uuid'], $user_id);
				if( $rc['stat'] != 'ok' ) {
					return $rc;
				}
			}
		}
	} else {
		$user_id = $rc['user']['id'];
	}
	$db_updated = 0;

	//
	// Get the local user
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'sync', 'userGet');
	$rc = ciniki_businesses_sync_userGet($ciniki, $sync, $business_id, array('id'=>$user_id));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['user']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'58', 'msg'=>'User not found on local server'));
	}
	$local_user = $rc['user'];

	//  
	// Turn off autocommit
	//  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncUpdateObjectSQL');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncUpdateObjectDetailSQL');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncUpdateTableElementHistory');
	$rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.businesses');
	if( $rc['stat'] != 'ok' ) { 
		return $rc;
	}   

	//
	// Compare basic elements of user
	//
	$rc = ciniki_core_syncUpdateObjectSQL($ciniki, $sync, $business_id, $remote_user, $local_user, array(
		'firstname'=>array(),
		'lastname'=>array(),
		'display_name'=>array(),
		'date_added'=>array('type'=>'uts'),
		'last_updated'=>array('type'=>'uts'),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( isset($rc['strsql']) && $rc['strsql'] != '' ) {
		$strsql = "UPDATE ciniki_users SET " . $rc['strsql'] . " "
			. "WHERE id = '" . ciniki_core_dbQuote($ciniki, $local_user['id']) . "' "
			. "";
		$rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.users');
		if( $rc['stat'] != 'ok' ) {
			ciniki_core_dbTransactionRollback($ciniki, 'ciniki.businesses');
			return $rc;
		}
		$db_updated = 1;
	}

	//
	// Update the user history
	//
	if( isset($remote_user['history']) ) {
		if( isset($local_user['history']) ) {
			$rc = ciniki_core_syncUpdateTableElementHistory($ciniki, $sync, $business_id, 'ciniki.users',
				'ciniki_user_history', $local_user['id'], 'ciniki_users', $remote_user['history'], $local_user['history'], array());
		} else {
			$rc = ciniki_core_syncUpdateTableElementHistory($ciniki, $sync, $business_id, 'ciniki.users',
				'ciniki_user_history', $local_user['id'], 'ciniki_users', $remote_user['history'], array(), array());
		}
		if( $rc['stat'] != 'ok' ) {
			ciniki_core_dbTransactionRollback($ciniki, 'ciniki.businesses');
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'887', 'msg'=>'Unable to save history', 'err'=>$rc['err']));
		}
	}

	//
	// Update the business user permissions
	//
	if( isset($remote_user['permissions']) ) {
		foreach($remote_user['permissions'] as $permission => $remote_permission) {
			if( !isset($local_user['permissions'][$permission]) ) {
				$local_permission = array(); 

				//
				// Add mising permission
				//
				$strsql = "INSERT INTO ciniki_business_users (business_id, user_id, package, permission_group, "
					. "status, date_added, last_updated) VALUES ("
					. "'" . ciniki_core_dbQuote($ciniki, $business_id) . "', "
					. "'" . ciniki_core_dbQuote($ciniki, $user_id) . "', "
					. "'" . ciniki_core_dbQuote($ciniki, $remote_permission['package']) . "', "
					. "'" . ciniki_core_dbQuote($ciniki, $remote_permission['permission_group']) . "', "
					. "'" . ciniki_core_dbQuote($ciniki, $remote_permission['status']) . "', "
					. "FROM_UNIXTIME('" . ciniki_core_dbQuote($ciniki, $remote_permission['date_added']) . "'), "
					. "FROM_UNIXTIME('" . ciniki_core_dbQuote($ciniki, $remote_permission['last_updated']) . "') "
					. ") ";
				$rc = ciniki_core_dbInsert($ciniki, $strsql, 'ciniki.businesses');
				if( $rc['stat'] != 'ok' && $rc['err']['code'] != '73' ) {
					return $rc;
				}
				$db_updated = 1;
			} else {
				$local_permission = $local_user['permissions'][$permission];
				//
				// Update existing permission
				//
				$rc = ciniki_core_syncUpdateObjectSQL($ciniki, $sync, $business_id, $remote_permission, $local_permission, array(
					'status'=>array(),
					'date_added'=>array('type'=>'uts'),
					'last_updated'=>array('type'=>'uts'),
					));
				if( $rc['stat'] != 'ok' ) {
					return $rc;
				}
				if( isset($rc['strsql']) && $rc['strsql'] != '' ) {
					$strsql = "UPDATE ciniki_business_users SET " . $rc['strsql'] . " "
						. "WHERE user_id = '" . ciniki_core_dbQuote($ciniki, $local_user['id']) . "' "
						. "AND business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
						. "AND package = '" . ciniki_core_dbQuote($ciniki, $local_permission['package']) . "' "
						. "AND permission_group = '" . ciniki_core_dbQuote($ciniki, $local_permission['permission_group']) . "' "
						. "";
					$rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.users');
					if( $rc['stat'] != 'ok' ) {
						ciniki_core_dbTransactionRollback($ciniki, 'ciniki.businesses');
						return $rc;
					}
					$db_updated = 1;
				}
			}

			// Translate the UUID to local ID
//			$history_key = preg_replace('/^[^\.]*\./', $local_user['id'] . '.', $permission_key);
			$history_key = $local_user['id'] . '.' . $permission;
			
			//
			// Update the detail history
			//
			if( isset($remote_permission['history']) ) {
				if( isset($local_permission['history']) ) {
					$rc = ciniki_core_syncUpdateTableElementHistory($ciniki, $sync, $business_id, 'ciniki.businesses',
						'ciniki_business_history', $history_key, 'ciniki_business_users', $remote_permission['history'], $local_permission['history'], array());
				} else {
					$rc = ciniki_core_syncUpdateTableElementHistory($ciniki, $sync, $business_id, 'ciniki.businesses',
						'ciniki_business_history', $history_key, 'ciniki_business_users', $remote_permission['history'], array(), array());
				}
				if( $rc['stat'] != 'ok' ) {
					ciniki_core_dbTransactionRollback($ciniki, 'ciniki.businesses');
					return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'905', 'msg'=>'Unable to save user history', 'err'=>$rc['err']));
				}
			}
		}
	}

	//
	// Update the business user details
	//
	if( isset($remote_user['business_details']) ) {
		foreach($remote_user['business_details'] as $detail_key => $remote_detail) {
			if( !isset($local_user['business_details'][$detail_key]) ) {
				$local_detail = array();
				//
				// Add mising detail
				//
				$strsql = "INSERT INTO ciniki_business_user_details (business_id, user_id, detail_key, detail_value, "
					. "date_added, last_updated) VALUES ("
					. "'" . ciniki_core_dbQuote($ciniki, $business_id) . "', "
					. "'" . ciniki_core_dbQuote($ciniki, $user_id) . "', "
					. "'" . ciniki_core_dbQuote($ciniki, $detail_key) . "', "
					. "'" . ciniki_core_dbQuote($ciniki, $remote_detail['detail_value']) . "', "
					. "FROM_UNIXTIME('" . ciniki_core_dbQuote($ciniki, $remote_detail['date_added']) . "'), "
					. "FROM_UNIXTIME('" . ciniki_core_dbQuote($ciniki, $remote_detail['last_updated']) . "') "
					. ") ";
				$rc = ciniki_core_dbInsert($ciniki, $strsql, 'ciniki.businesses');
				if( $rc['stat'] != 'ok' && $rc['err']['code'] != '73' ) {
					return $rc;
				}
				$db_updated = 1;
			} else {
				$local_detail = $local_user['business_details'][$detail_key];
				
				//
				// Update existing permission
				//
				$rc = ciniki_core_syncUpdateObjectDetailSQL($ciniki, $sync, $business_id, $detail_key, $remote_detail, $local_detail, array(
					'detail_value'=>array(),
					'date_added'=>array('type'=>'uts'),
					'last_updated'=>array('type'=>'uts'),
					));
				if( $rc['stat'] != 'ok' ) {
					return $rc;
				}
				if( isset($rc['strsql']) && $rc['strsql'] != '' ) {
					$strsql = "UPDATE ciniki_business_user_details SET " . $rc['strsql'] . " "
						. "WHERE user_id = '" . ciniki_core_dbQuote($ciniki, $local_user['id']) . "' "
						. "AND business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
						. "AND detail_key = '" . ciniki_core_dbQuote($ciniki, $detail_key) . "' "
						. "";
					$rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.users');
					if( $rc['stat'] != 'ok' ) {
						ciniki_core_dbTransactionRollback($ciniki, 'ciniki.businesses');
						return $rc;
					}
					$db_updated = 1;
				}
			}

			//
			// Update the detail history
			//
			if( isset($remote_detail['history']) ) {
				if( isset($local_detail['history']) ) {
					$rc = ciniki_core_syncUpdateTableElementHistory($ciniki, $sync, $business_id, 'ciniki.businesses',
						'ciniki_business_history', $user_id, 'ciniki_business_user_details', $remote_detail['history'], $local_detail['history'], array());
				} else {
					$rc = ciniki_core_syncUpdateTableElementHistory($ciniki, $sync, $business_id, 'ciniki.businesses',
						'ciniki_business_history', $user_id, 'ciniki_business_user_details', $remote_detail['history'], array(), array());
				}
				if( $rc['stat'] != 'ok' ) {
					ciniki_core_dbTransactionRollback($ciniki, 'ciniki.businesses');
					return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'900', 'msg'=>'Unable to save user history', 'err'=>$rc['err']));
				}
			}
		}
	}

	//
	// Compare user details
	//
	if( isset($remote_user['user_details']) ) {
		foreach($remote_user['user_details'] as $detail_key => $remote_detail) {
			//
			// Check if detail exists in local
			//
			if( !isset($local_user['user_details'][$detail_key]) ) {
				$local_detail = array();
				//
				// Add the setting if it doesn't exist locally
				//
				$strsql = "INSERT INTO ciniki_user_details (user_id, detail_key, detail_value, date_added, last_updated) "
					. "VALUES ('" . ciniki_core_dbQuote($ciniki, $local_user['id']) . "'"
					. ", '" . ciniki_core_dbQuote($ciniki, $detail_key) . "'"
					. ", '" . ciniki_core_dbQuote($ciniki, $remote_detail['detail_value']) . "'"
					. ", FROM_UNIXTIME('" . ciniki_core_dbQuote($ciniki, $remote_detail['date_added']) . "') "
					. ", FROM_UNIXTIME('" . ciniki_core_dbQuote($ciniki, $remote_detail['last_updated']) . "') "
					. ")";
				$rc = ciniki_core_dbInsert($ciniki, $strsql, 'ciniki.users');
				if( $rc['stat'] != 'ok' ) {
					ciniki_core_dbTransactionRollback($ciniki, 'ciniki.businesses');
					return $rc;
				}
				$db_updated = 1;
			} else {
				$local_detail = $local_user['user_details'][$detail_key];
				//
				// Compare basic elements of user detail 
				//
				$rc = ciniki_core_syncUpdateObjectSQL($ciniki, $sync, $business_id, $remote_detail, $local_detail, array(
					'detail_value'=>array(),
					'date_added'=>array('type'=>'uts'),
					'last_updated'=>array('type'=>'uts'),
					));
				if( $rc['stat'] != 'ok' ) {
					return $rc;
				}
				if( isset($rc['strsql']) && $rc['strsql'] != '' ) {
					$strsql = "UPDATE ciniki_user_details SET " . $rc['strsql'] . " "
						. "WHERE user_id = '" . ciniki_core_dbQuote($ciniki, $local_user['id']) . "' "
						. "AND detail_key = '" . ciniki_core_dbQuote($ciniki, $detail_key) . "' "
						. "";
					$rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.users');
					if( $rc['stat'] != 'ok' ) {
						ciniki_core_dbTransactionRollback($ciniki, 'ciniki.businesses');
						return $rc;
					}
					$db_updated = 1;
				}
			}
			//
			// Update the detail history
			//
			if( isset($remote_detail['history']) ) {
				if( isset($local_detail['history']) ) {
					$rc = ciniki_core_syncUpdateTableElementHistory($ciniki, $sync, $business_id, 'ciniki.users',
						'ciniki_user_history', $local_detail['detail_key'], 'ciniki_user_details', $remote_detail['history'], $local_detail['history'], array());
				} else {
					$rc = ciniki_core_syncUpdateTableElementHistory($ciniki, $sync, $business_id, 'ciniki.users',
						'ciniki_user_history', $remote_detail['detail_key'], 'ciniki_user_details', $remote_detail['history'], array(), array());
				}
				if( $rc['stat'] != 'ok' ) {
					ciniki_core_dbTransactionRollback($ciniki, 'ciniki.businesses');
					return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'889', 'msg'=>'Unable to save user history', 'err'=>$rc['err']));
				}
			}
		}
	}

	//
	// Commit the database changes
	//
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.businesses');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	
	//
	// Add to syncQueue to sync with other servers.  This allows for cascading syncs.
	//
	if( $db_updated > 0 ) {
		//
		// Get the list of businesses this user is part of, and replicate that user for that business
		//
		$ciniki['syncqueue'][] = array('method'=>'ciniki.businesses.syncPushUser', 'args'=>array('id'=>$user_id, 'ignore_sync_id'=>$sync['id']));
	}

	return array('stat'=>'ok', 'id'=>$user_id);
}
?>
