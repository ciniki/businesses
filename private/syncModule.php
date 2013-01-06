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
function ciniki_businesses_syncModule(&$ciniki, &$sync, $business_id, $args) {

	//
	// Get the remote list of users
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncRequest');
	$rc = ciniki_core_syncRequest($ciniki, $sync, array('method'=>'ciniki.businesses.userList', 'type'=>$args['type'], 'since_uts'=>$sync['last_sync']));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	if( !isset($rc['users']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'76', 'msg'=>'Unable to get remote users'));
	}
	$remote_list = $rc['users'];
//	$remote_deleted = $rc['deleted'];

	//
	// Load required sync methods
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'sync', 'userList');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'sync', 'userAdd');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'sync', 'userGet');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'sync', 'userUpdate');

//
//	Users are never deleted
//

//	//
//	// Deal with deleted users first
//	//
//	foreach($remote_deleted as $permission_key => $history) {
//		//
//		// Check if active in local server
//		//
//		$rc = ciniki_businesses_sync_userGet($ciniki, $sync, $business_id, array('permission_key'=>$permission_key));
//		if( $rc['stat'] != 'ok' && $rc['err']['code'] != '82' ) {
//			return $rc;
//		}
//		if( isset($rc['user']) ) {
//			$local_user = $rc['user'];
//			//
//			// Check if remote delete is newer that latest update locally
//			//
//			if( $history['log_date'] > $local_user['last_updated'] ) {
//				//
//				// Delete the user
//				//
//				error_log("Delete local: " . $permission_key);
//				ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'sync', 'userDelete');
//				$rc = ciniki_businesses_sync_userDelete($ciniki, $sync, $business_id, array('permission_key'=>$permission_key, 'history'=>$history));
//				if( $rc['stat'] != 'ok' ) {
//					return $rc;
//				}
//			}
//		}
//		// If the user does not exist, then it's already deleted and we don't care
//	}

	//
	// Get the local list of users
	//
	$rc = ciniki_businesses_sync_userList($ciniki, $sync, $business_id, array('type'=>$args['type'], 'since_uts'=>$sync['last_sync']));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['users']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'75', 'msg'=>'Unable to get local users'));
	}
	$local_list = $rc['users'];
//	$local_deleted = $rc['deleted'];

//	foreach($local_deleted as $permission_key => $history) {
//		// 
//		// Check if active in remote server
//		//
//		$rc = ciniki_core_syncRequest($ciniki, $sync, array('method'=>'ciniki.businesses.userGet', 'permission_key'=>$permission_key));
//		if( $rc['stat'] != 'ok' && $rc['err']['code'] != '82' ) {
//			return $rc;
//		}
//		if( isset($rc['user']) ) {
//			$remote_user = $rc['user'];
//			//
//			// Check if remote delete is newer that latest update locally
//			//
//			if( $history['log_date'] > $remote_user['last_updated'] ) {
//				//
//				// Delete the user
//				//
//				error_log("Delete remote: " . $permission_key);
//				$rc = ciniki_core_syncRequest($ciniki, $sync, array('method'=>'ciniki.businesses.userDelete', 'permission_key'=>$permission_key, 'history'=>$history));
//				if( $rc['stat'] != 'ok' ) {
//					return $rc;
//				}
//			}
//		}
//	}

	//
	// For the pull side
	//
	if( ($sync['flags']&0x02) == 0x02 ) {
		foreach($remote_list as $uuid => $last_updated) {
			//
			// A full sync will compare every user, 
			// a partial or incremental will only check records where the last_updated differs
			// Check if uuid does not exist, and has not been deleted
			//
			if( $args['type'] == 'full' || !isset($local_list[$uuid]) || $local_list[$uuid] != $last_updated ) {
				//
				// Get the remote user
				//
				$rc = ciniki_core_syncRequest($ciniki, $sync, array('method'=>'ciniki.businesses.userGet', 'uuid'=>$uuid));
				if( $rc['stat'] != 'ok' ) {
					return $rc;
				}
				if( !isset($rc['user']) ) {
					return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'895', 'msg'=>'User not found on remote server'));
				}
				$user = $rc['user'];

				//
				// Add to the local database
				//
				$rc = ciniki_businesses_sync_userUpdate($ciniki, $sync, $business_id, array('user'=>$user));
				if( $rc['stat'] != 'ok' ) {
					return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'896', 'msg'=>'Unable to add user', 'err'=>$rc['err']));;
				}
			} 
		}
	}

	//
	// For the push side
	//
	if( ($sync['flags']&0x01) == 0x01 ) {
		foreach($local_list as $uuid => $last_updated) {
			//
			// Check if uuid does not exist, and has not been deleted
			//
			if( $args['type'] == 'full' || !isset($remote_list[$uuid]) || $remote_list[$uuid] != $last_updated ) {
				//
				// Get the local user
				//
				$rc = ciniki_businesses_sync_userGet($ciniki, $sync, $business_id, array('uuid'=>$uuid));
				if( $rc['stat'] != 'ok' ) {
					return $rc;
				}
				if( !isset($rc['user']) ) {
					return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'897', 'msg'=>'User not found on remote server'));
				}
				$user = $rc['user'];

				//
				// Update the user
				//
				$rc = ciniki_core_syncRequest($ciniki, $sync, array('method'=>'ciniki.businesses.userUpdate', 'user'=>$user));
				if( $rc['stat'] != 'ok' ) {
					return $rc;
				}
			}
		}
	}

	//
	// FIXME: Add in history sync
	//

	return array('stat'=>'ok');
}
?>
