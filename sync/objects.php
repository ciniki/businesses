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
function ciniki_tenants_sync_objects($ciniki, &$sync, $tnid, $args) {
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
//      'lookup'=>'ciniki.tenants.user.lookup',
//      'get'=>'ciniki.tenants.user.get',
//      'update'=>'ciniki.tenants.user.update',
//      'list'=>'ciniki.tenants.user.list',
//      );
    $objects['user'] = array(
        'name'=>'Tenant User',
        'table'=>'ciniki_tenant_users',
        'fields'=>array(
            'user_id'=>array('ref'=>'ciniki.users.user'),
            'eid'=>array(),
            'package'=>array(),
            'permission_group'=>array(),
            'status'=>array(),
            ),
        'history_table'=>'ciniki_tenant_history',
        );
    $objects['user_detail'] = array(
        'name'=>'Tenant User Detail',
        'table'=>'ciniki_tenant_user_details',
        'fields'=>array(
            'user_id'=>array('ref'=>'ciniki.users.user'),
            'detail_key'=>array(),
            'detail_value'=>array(),
            ),
        'history_table'=>'ciniki_tenant_history',
        );
//  $objects['tenant'] = array(
//      'name'=>'Tenant', 
//      'table'=>'ciniki_tenants',
//      'history_table'=>'ciniki_tenant_history',
//      'fields'=>array(
//          'tagline'=>array(),
//          'description'=>array(),
//          'logo_id'=>array('ref'=>'ciniki.images.image'),
//          'logo_id'=>array(),
//          ),
//      );
    $objects['details'] = array(
        'type'=>'settings',
        'name'=>'Tenant Details',
        'table'=>'ciniki_tenant_details',
        'history_table'=>'ciniki_tenant_history',
        );

    return array('stat'=>'ok', 'objects'=>$objects);
}
?>
