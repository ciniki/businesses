<?php
//
// Description
// -----------
// This method will return the information about the local server, and the list of replications
// connected to this server.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:             The ID of the tenant to lock.
//
// Returns
// -------
// <rsp stat="ok" />
//
function ciniki_tenants_syncInfo($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access 
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'checkAccess');
    $rc = ciniki_tenants_checkAccess($ciniki, $args['tnid'], 'ciniki.tenants.syncInfo');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'datetimeFormat');
    $datetime_format = ciniki_users_datetimeFormat($ciniki);

    //
    // Get the local tenant information
    //
    $strsql = "SELECT uuid "
        . "FROM ciniki_tenants "
        . "WHERE ciniki_tenants.id = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' " 
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.tenants', 'tenant');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['tenant']) ) { 
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.tenants.83', 'msg'=>'Unable to get tenant information'));
    }
    $uuid = $rc['tenant']['uuid'];

    //
    // Get the list of syncs setup for this tenant
    //
    $strsql = "SELECT id, tnid, flags, flags AS type, status, status AS status_text, "
        . "remote_name, remote_url, remote_uuid, "
        . "IFNULL(DATE_FORMAT(last_sync, '" . ciniki_core_dbQuote($ciniki, $datetime_format) . "'), '') as last_sync, "
        . "IFNULL(DATE_FORMAT(last_partial, '" . ciniki_core_dbQuote($ciniki, $datetime_format) . "'), '') as last_partial, "
        . "IFNULL(DATE_FORMAT(last_full, '" . ciniki_core_dbQuote($ciniki, $datetime_format) . "'), '') as last_full "
        . "FROM ciniki_tenant_syncs "
        . "WHERE ciniki_tenant_syncs.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' " 
        . "ORDER BY remote_name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.tenants', array(
        array('container'=>'syncs', 'fname'=>'id', 'name'=>'sync',
            'fields'=>array('id', 'tnid', 'flags', 'type', 'status', 'status_text', 'remote_name', 'remote_url', 'remote_uuid',
                'last_sync', 'last_partial', 'last_full'),
            'maps'=>array('status_text'=>array('10'=>'Active', '60'=>'Suspended'),
                'type'=>array('1'=>'Push', '2'=>'Pull', '3'=>'Bi'))),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['syncs']) ) {
        $syncs = array();
    } else {
        $syncs = $rc['syncs'];
    }
    
    return array('stat'=>'ok', 'name'=>$ciniki['config']['core']['sync.name'], 'uuid'=>$uuid, 'local_url'=>$ciniki['config']['core']['sync.url'], 'syncs'=>$syncs);
}
?>
