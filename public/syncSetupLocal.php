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
function ciniki_tenants_syncSetupLocal($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'remote_name'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Remote Name'), 
        'remote_uuid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Remote UUID'), 
        'type'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Type'), 
        'json_api'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Remote JSON API URL'), 
        'remote_key'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Remote API Key'), 
        'username'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Remote Username'), 
        'password'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Remote Password'), 
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access 
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'checkAccess');
    $rc = ciniki_tenants_checkAccess($ciniki, $args['tnid'], 'ciniki.tenants.syncSetupLocal');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbInsert');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'cinikiAPIAuth');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'cinikiAPIGet');

    //
    // Get the tenant uuid
    //
    $strsql = "SELECT uuid "
        . "FROM ciniki_tenants "
        . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.tenants', 'tenant');
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    if( !isset($rc['tenant']) || !isset($rc['tenant']['uuid']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.tenants.84', 'msg'=>'Internal error'));
    }
    $tenant_uuid  = $rc['tenant']['uuid'];

    //
    // Create transaction
    //
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.tenants');
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // auth against remote machine
    //
    $rc = ciniki_core_cinikiAPIAuth($ciniki, $args['json_api'], $args['remote_key'], $args['username'], $args['password']);
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }
    $api = $rc['api'];

    //
    // create local private/public keys
    //
    $k = openssl_pkey_new();
    openssl_pkey_export($k, $private_str);
    $p = openssl_pkey_get_details($k);
    $public_str = $p['key'];

    //
    // call syncSetupRemote
    //
    if( $args['type'] == 'push' ) {
        $remote_type = 'pull';
        $flags = 0x01;
    } else if( $args['type'] == 'pull' ) {
        $remote_type = 'push';
        $flags = 0x02;
    } else if( $args['type'] == 'bi' ) {
        $remote_type = 'bi';
        $flags = 0x03;
    } else {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.tenants.85', 'msg'=>'The type must be push, pull or bi.'));
    }
    $remote_args = array(
        'tenant_uuid'=>$args['remote_uuid'],
        'type'=>$remote_type,
        'name'=>$ciniki['config']['core']['sync.name'],
        'url'=>$ciniki['config']['core']['sync.url'],
        'uuid'=>$tenant_uuid,
        'public_key'=>$public_str,
    );
    $rc = ciniki_core_cinikiAPIPost($ciniki, $api, 'ciniki.tenants.syncSetupRemote', null, $remote_args);
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.tenants.86', 'msg'=>'Unable to add remote sync', 'err'=>$rc['err']));
    }
    $sync_url = $rc['sync_url'];
    $public_key = $rc['public_key'];

    //
    // Add sync to local
    //
    $strsql = "INSERT INTO ciniki_tenant_syncs (tnid, flags, status, "
        . "local_private_key, remote_name, remote_uuid, remote_url, remote_public_key, "
        . "date_added, last_updated) VALUES ("
        . "'" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . ", '" . ciniki_core_dbQuote($ciniki, $flags) . "' "
        . ", 10 " 
        . ", '" . ciniki_core_dbQuote($ciniki, $private_str) . "' "
        . ", '" . ciniki_core_dbQuote($ciniki, $args['remote_name']) . "' "
        . ", '" . ciniki_core_dbQuote($ciniki, $args['remote_uuid']) . "' "
        . ", '" . ciniki_core_dbQuote($ciniki, $sync_url) . "' "
        . ", '" . ciniki_core_dbQuote($ciniki, $public_key) . "' "
        . ", UTC_TIMESTAMP(), UTC_TIMESTAMP() "
        . ")"; 
    $rc = ciniki_core_dbInsert($ciniki, $strsql, 'ciniki.tenants');
    if( $rc['stat'] != 'ok' ) { 
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.tenants.87', 'msg'=>'Unable to add remote sync', 'err'=>$rc['err']));
    }
    $sync_id = $rc['insert_id'];
    ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.tenants', 'ciniki_tenant_history', $args['tnid'], 
        1, 'ciniki_tenant_syncs', $sync_id, 'flags', $flags);
    ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.tenants', 'ciniki_tenant_history', $args['tnid'], 
        1, 'ciniki_tenant_syncs', $sync_id, 'status', '10');
    ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.tenants', 'ciniki_tenant_history', $args['tnid'], 
        1, 'ciniki_tenant_syncs', $sync_id, 'remote_name', $args['remote_name']);
    ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.tenants', 'ciniki_tenant_history', $args['tnid'], 
        1, 'ciniki_tenant_syncs', $sync_id, 'remote_uuid', $args['remote_uuid']);
    ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.tenants', 'ciniki_tenant_history', $args['tnid'], 
        1, 'ciniki_tenant_syncs', $sync_id, 'remote_url', $sync_url);

    //
    // call syncActivateRemote
    //
    $remote_args = array(
        'tenant_uuid'=>$args['remote_uuid'],
        'url'=>$ciniki['config']['core']['sync.url'],
        'uuid'=>$tenant_uuid,
    );
    $rc = ciniki_core_cinikiAPIPost($ciniki, $api, 'ciniki.tenants.syncActivateRemote', null, $remote_args);
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.tenants.88', 'msg'=>'Unable to add remote sync', 'err'=>$rc['err']));
    }

    $rc = ciniki_core_cinikiAPIPost($ciniki, $api, 'ciniki.users.logout', null, null);
    // Ignore response

    //
    // Commit transaction
    //
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.tenants');
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    return array('stat'=>'ok');
}
?>
