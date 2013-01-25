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
function ciniki_businesses_sync_objects($ciniki, &$sync, $business_id, $args) {
	//
	// Note: Pass the standard set of arguments in, they may be required in the future
	//
	
	$objects = array();
	$objects['user'] = array(
		'name'=>'User', 
		'table'=>'ciniki_business_users',	// Need table name so history gets pulled
		'fields'=>array(
			'user_id'=>array('ciniki.businesses.user.user'),
			),
		'history_table'=>'ciniki_business_history',
		'lookup'=>'ciniki.businesses.user.lookup',
		'get'=>'ciniki.businesses.user.get',
		'update'=>'ciniki.businesses.user.update',
		'list'=>'ciniki.businesses.user.list',
		);
	$objects['business'] = array(
		'name'=>'Business', 
		'table'=>'ciniki_businesses',
		'history_table'=>'ciniki_business_history',
		'fields'=>array(
			'tagline'=>array(),
			'description'=>array(),
//			'logo_id'=>array('ref'=>'ciniki.images.image'),
			'logo_id'=>array(),
			),
		);
	$settings = array(
		'table'=>'ciniki_business_details',
		);

	return array('stat'=>'ok', 'objects'=>$objects, 'settings'=>$settings);

	return array('stat'=>'ok', 'objects'=>array(
		'user'=>array(),
	));
}
?>
