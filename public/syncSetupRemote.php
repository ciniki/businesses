<?php
//
// Description
// -----------
// This method is called by syncSetupLocal from the server
// initializating this sync.  
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
function ciniki_tenants_syncSetupRemote($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tenant_uuid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant UUID'), 
        'type'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Type'), 
        'name'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Name'), 
        'url'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'URL'), 
        'uuid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'UUID'), 
        'public_key'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Public Key'), 
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbInsert');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');

    //
    // Lookup the tenant id
    //
    $strsql = "SELECT id "
        . "FROM ciniki_tenants "
        . "WHERE ciniki_tenants.uuid = '" . ciniki_core_dbQuote($ciniki, $args['tenant_uuid']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.tenants', 'tenant');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['tenant']) || !isset($rc['tenant']['id']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.tenants.89', 'msg'=>'Access denied'));
    }
    $args['tnid']  = $rc['tenant']['id'];

    //
    // Check access 
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'checkAccess');
    $rc = ciniki_tenants_checkAccess($ciniki, $args['tnid'], 'ciniki.tenants.syncSetupRemote');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Start transaction
    //
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.tenants');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    // 
    // Create private/public keys
    //
    $k = openssl_pkey_new();
    openssl_pkey_export($k, $private_str);
    $p = openssl_pkey_get_details($k);
    $public_str = $p['key'];

    //
    // Add sync to the database, with status of unknown (0)
    //
    $flags = 0;
    if( $args['type'] == 'push' ) {
        $flags = 0x01;
    } else if( $args['type'] == 'pull' ) {
        $flags = 0x02;
    } else if( $args['type'] == 'bi' ) {
        $flags = 0x03;
    }
    $strsql = "INSERT INTO ciniki_tenant_syncs (tnid, flags, status, "
        . "local_private_key, remote_name, remote_uuid, remote_url, remote_public_key, "
        . "date_added, last_updated) VALUES ("
        . "'" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . ", '" . ciniki_core_dbQuote($ciniki, $flags) . "' "
        . ", 0 "
        . ", '" . ciniki_core_dbQuote($ciniki, $private_str) . "' "
        . ", '" . ciniki_core_dbQuote($ciniki, $args['name']) . "' "
        . ", '" . ciniki_core_dbQuote($ciniki, $args['uuid']) . "' "
        . ", '" . ciniki_core_dbQuote($ciniki, $args['url']) . "' "
        . ", '" . ciniki_core_dbQuote($ciniki, $args['public_key']) . "' "
        . ", UTC_TIMESTAMP(), UTC_TIMESTAMP() "
        . ")"; 
    $rc = ciniki_core_dbInsert($ciniki, $strsql, 'ciniki.tenants');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.tenants.90', 'msg'=>'Unable to add remote sync', 'err'=>$rc['err']));
    }
    $sync_id = $rc['insert_id'];

    ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.tenants', 'ciniki_tenant_history', $args['tnid'], 
        1, 'ciniki_tenant_syncs', $sync_id, 'flags', $flags);
    ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.tenants', 'ciniki_tenant_history', $args['tnid'], 
        1, 'ciniki_tenant_syncs', $sync_id, 'status', '0');
    ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.tenants', 'ciniki_tenant_history', $args['tnid'], 
        1, 'ciniki_tenant_syncs', $sync_id, 'remote_name', $args['name']);
    ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.tenants', 'ciniki_tenant_history', $args['tnid'], 
        1, 'ciniki_tenant_syncs', $sync_id, 'remote_uuid', $args['uuid']);
    ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.tenants', 'ciniki_tenant_history', $args['tnid'], 
        1, 'ciniki_tenant_syncs', $sync_id, 'remote_url', $args['url']);

    //
    // Create transaction
    //
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.tenants');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    
    return array('stat'=>'ok', 'public_key'=>$public_str, 'sync_url'=>$ciniki['config']['core']['sync.url']);
}
?>
