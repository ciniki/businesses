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
//  $objects['user'] = array(
//      'name'=>'User', 
//      'table'=>'ciniki_users',
//      'fields'=>array(
//          'email'=>array(),
//          'firstname'=>array(),
//          'lastname'=>array(),
//          'display_name'=>array(),
//          'timeout'=>array(),
//          'avatar_id'=>array('ref'=>'ciniki.images.image'),
//          ),
//      'history_table'=>'ciniki_user_history',
//      'lookup'=>'ciniki.businesses.user.lookup',
//      'get'=>'ciniki.businesses.user.get',
//      'update'=>'ciniki.businesses.user.update',
//      'list'=>'ciniki.businesses.user.list',
//      );
    $objects['user'] = array(
        'name'=>'Business User',
        'table'=>'ciniki_business_users',
        'fields'=>array(
            'user_id'=>array('ref'=>'ciniki.users.user'),
            'eid'=>array(),
            'package'=>array(),
            'permission_group'=>array(),
            'status'=>array(),
            ),
        'history_table'=>'ciniki_business_history',
        );
    $objects['user_detail'] = array(
        'name'=>'Business User Detail',
        'table'=>'ciniki_business_user_details',
        'fields'=>array(
            'user_id'=>array('ref'=>'ciniki.users.user'),
            'detail_key'=>array(),
            'detail_value'=>array(),
            ),
        'history_table'=>'ciniki_business_history',
        );
//  $objects['business'] = array(
//      'name'=>'Business', 
//      'table'=>'ciniki_businesses',
//      'history_table'=>'ciniki_business_history',
//      'fields'=>array(
//          'tagline'=>array(),
//          'description'=>array(),
//          'logo_id'=>array('ref'=>'ciniki.images.image'),
//          'logo_id'=>array(),
//          ),
//      );
    $objects['details'] = array(
        'type'=>'settings',
        'name'=>'Business Details',
        'table'=>'ciniki_business_details',
        'history_table'=>'ciniki_business_history',
        );

    return array('stat'=>'ok', 'objects'=>$objects);
}
?>
