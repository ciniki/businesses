<?php
//
// Description
// -----------
// This method is called by syncSetupLocal from the server
// initializating this sync.  
//
// Arguments
// ---------
//
// Returns
// -------
// <rsp stat="ok" />
//
function ciniki_tenants_syncActivateRemote($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tenant_uuid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'url'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'URL'), 
        'uuid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'UUID'), 
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');

    //
    // Lookup the tenant id
    //
    $strsql = "SELECT ciniki_tenants.id, ciniki_tenant_syncs.id AS sync_id "
        . "FROM ciniki_tenants, ciniki_tenant_syncs "
        . "WHERE ciniki_tenants.uuid = '" . ciniki_core_dbQuote($ciniki, $args['tenant_uuid']) . "' "
        . "AND ciniki_tenants.id = ciniki_tenant_syncs.tnid "
        . "AND remote_url = '" . ciniki_core_dbQuote($ciniki, $args['url']) . "' "
        . "AND remote_uuid = '" . ciniki_core_dbQuote($ciniki, $args['uuid']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.tenants', 'tenant');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['tenant']) || !isset($rc['tenant']['id']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.tenants.79', 'msg'=>'Access denied'));
    }
    $args['tnid']  = $rc['tenant']['id'];
    $args['sync_id']  = $rc['tenant']['sync_id'];

    //
    // Check access 
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'checkAccess');
    $rc = ciniki_tenants_checkAccess($ciniki, $args['tnid'], 'ciniki.tenants.syncActivateRemote');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    $strsql = "UPDATE ciniki_tenant_syncs SET status = 10 "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND remote_url = '" . ciniki_core_dbQuote($ciniki, $args['url']) . "' "
        . "AND remote_uuid = '" . ciniki_core_dbQuote($ciniki, $args['uuid']) . "' "
        . "";
    $rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.tenants');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.tenants.80', 'msg'=>'Unable to add remote sync', 'err'=>$rc['err']));
    }

    // Update the log
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.tenants', 'ciniki_tenant_history', $args['tnid'], 
        1, 'ciniki_tenant_syncs', $args['sync_id'], 'status', '10');

    return array('stat'=>'ok');
}
?>
