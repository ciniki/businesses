<?php
//
// Description
// -----------
// This method will sync the business information
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id: 			The ID of the business to sync.
// sync_id:					The ID of the sync to update.
//
// Returns
// -------
// <rsp stat="ok" />
//
function ciniki_businesses_syncNow($ciniki) {
	//
	// Find all the required and optional arguments
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
	$rc = ciniki_core_prepareArgs($ciniki, 'no', array(
		'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
		'sync_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Sync'), 
		'type'=>array('required'=>'yes', 'blank'=>'no', 'validlist'=>array('incremental', 'partial', 'full'), 'name'=>'Type'),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$args = $rc['args'];

	//
	// Check access 
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'checkAccess');
	$rc = ciniki_businesses_checkAccess($ciniki, $args['business_id'], 'ciniki.businesses.syncNow');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'datetimeFormat');
	$datetime_format = ciniki_users_datetimeFormat($ciniki);

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncBusiness');
	$rc = ciniki_core_syncBusiness($ciniki, $args['business_id'], $args['sync_id'], $args['type']);
	return $rc;
}
?>
