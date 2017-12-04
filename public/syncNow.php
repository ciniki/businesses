<?php
//
// Description
// -----------
// This method will sync the tenant information
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:             The ID of the tenant to sync.
// sync_id:                 The ID of the sync to update.
//
// Returns
// -------
// <rsp stat="ok" />
//
function ciniki_tenants_syncNow($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'sync_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Sync'), 
        'type'=>array('required'=>'yes', 'blank'=>'no', 'validlist'=>array('incremental', 'partial', 'full'), 'name'=>'Type'),
        'module'=>array('required'=>'no', 'blank'=>'no', 'default'=>'', 'name'=>'Module'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access 
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'checkAccess');
    $rc = ciniki_tenants_checkAccess($ciniki, $args['tnid'], 'ciniki.tenants.syncNow');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'datetimeFormat');
    $datetime_format = ciniki_users_datetimeFormat($ciniki);

    //
    // Load the sync info
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncLoad');
    $rc = ciniki_core_syncLoad($ciniki, $args['tnid'], $args['sync_id']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $sync = $rc['sync'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncTenant');
    $rc = ciniki_core_syncTenant($ciniki, $sync, $args['tnid'], $args['type'], $args['module']);
    return $rc;
}
?>
