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
function ciniki_businesses_syncModuleHistory(&$ciniki, &$sync, $business_id, $args) {

	ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'sync', 'historyList');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'sync', 'historyAdd');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'sync', 'historyGet');

	//
	// Now get the history from each side, and make sure it's complete
	//
	$rc = ciniki_core_syncRequest($ciniki, $sync, array('method'=>'ciniki.businesses.historyList', 'type'=>$args['type'], 'since_uts'=>$sync['last_sync']));
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'988', 'msg'=>'Unable to get remote history list', 'err'=>$rc['err']));
	}
	if( !isset($rc['history']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'904', 'msg'=>'Unable to get remote history list'));
	}
	$remote_history = $rc['history'];
	
	//
	// Get the local history
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'sync', 'historyList');
	$rc = ciniki_businesses_sync_historyList($ciniki, $sync, $business_id, array('type'=>$args['type'], 'since_uts'=>$sync['last_sync']));
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'989', 'msg'=>'Unable to get local history list', 'err'=>$rc['err']));
	}
	if( !isset($rc['history']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'909', 'msg'=>'Unable to get local history'));
	}
	$local_history = $rc['history'];

	//
	// Compare remote and local history
	//
	if( ($sync['flags']&0x02) == 0x02 ) {
		foreach($remote_history as $uuid => $last_updated) {
			//
			// Check if uuid does not exist, and has not been deleted
			//
			if( !isset($local_history[$uuid]) ) {
				
				//
				// Grab remote details
				//
				$rc = ciniki_core_syncRequest($ciniki, $sync, array('method'=>'ciniki.businesses.historyGet', 'history'=>$uuid));
				if( $rc['stat'] != 'ok' ) {
					return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'990', 'msg'=>'Unable to get remote history', 'err'=>$rc['err']));
				}
				if( !isset($rc['history']) ) {
					return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'910', 'msg'=>'History not found on remote server'));
				}
				$history = $rc['history'];

				//
				// Add to local server
				//
				$rc = ciniki_businesses_sync_historyAdd($ciniki, $sync, $business_id, array('history'=>$history));
				if( $rc['stat'] != 'ok' ) {
					return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'991', 'msg'=>'Unable to update local history', 'err'=>$rc['err']));
				}
			} 
		}
	}

	//
	// Compare local against remote history
	//
	if( ($sync['flags']&0x01) == 0x01 ) {
		foreach($local_history as $uuid => $last_updated) {
			//
			// Check if uuid does not exist, and has not been deleted
			//
			if( !isset($remote_history[$uuid]) ) {
				$rc = ciniki_businesses_sync_historyGet($ciniki, $sync, $business_id, array('history'=>$uuid));
				if( $rc['stat'] != 'ok' ) {
					return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'992', 'msg'=>'Unable to get local history', 'err'=>$rc['err']));
				}
				if( !isset($rc['history']) ) {
					return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'911', 'msg'=>'History not found on local server'));
				}
				$history = $rc['history'];
				
				//
				// Add to remote server
				//
				$rc = ciniki_core_syncRequest($ciniki, $sync, array('method'=>'ciniki.businesses.historyAdd', 'history'=>$history));
				if( $rc['stat'] != 'ok' ) {
					return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'993', 'msg'=>'Unable to update remote history', 'err'=>$rc['err']));
				}
			} 
		}
	}

	return array('stat'=>'ok');
}
?>
