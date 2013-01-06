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
function ciniki_businesses_syncPushUser(&$ciniki, &$sync, $business_id, $args) {
	if( !isset($args['id']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'80', 'msg'=>'Missing ID argument'));
	}

	//
	// Get the local user
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'sync', 'userGet');
	$rc = ciniki_businesses_sync_userGet($ciniki, $sync, $business_id, array('id'=>$args['id']));
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'79', 'msg'=>'Unable to get user'));
	}
	if( !isset($rc['user']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'78', 'msg'=>'User not found on remote server'));
	}
	$user = $rc['user'];

	//
	// Update the remote user
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncRequest');
	$rc = ciniki_core_syncRequest($ciniki, $sync, array('method'=>'ciniki.businesses.userUpdate', 'user'=>$user));
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'77', 'msg'=>'Unable to sync user'));
	}

	return array('stat'=>'ok');
}
?>
