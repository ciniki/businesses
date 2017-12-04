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
// <rsp stat="ok" />
//
function ciniki_tenants_syncUpdate($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'sync_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Sync'), 
        'remote_name'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Remote Name'), 
        'type'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Sync Type'), 
        'status'=>array('required'=>'no', 'blank'=>'no', 'validlist'=>array('10','20','60'), 'name'=>'Status'), 
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access 
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'checkAccess');
    $rc = ciniki_tenants_checkAccess($ciniki, $args['tnid'], 'ciniki.tenants.syncUpdate');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');

    //
    // Create transaction
    //
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.tenants');
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    if( isset($args['type']) ) {
        if( $args['type'] == 'push' ) {
            $remote_type = 'pull';
            $args['flags'] = 0x01;
        } else if( $args['type'] == 'pull' ) {
            $remote_type = 'push';
            $args['flags'] = 0x02;
        } else if( $args['type'] == 'bi' ) {
            $remote_type = 'bi';
            $args['flags'] = 0x03;
        } else {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.tenants.91', 'msg'=>'The type must be push, pull or bi.'));
        }
    }

    $strsql = "UPDATE ciniki_tenant_syncs SET last_updated = UTC_TIMESTAMP() ";
    //
    // Add all the fields to the change log
    //
    $changelog_fields = array(
        'remote_name',
        'status',
        'flags',
        );
    foreach($changelog_fields as $field) {
        if( isset($args[$field]) ) {
            $strsql .= ", $field = '" . ciniki_core_dbQuote($ciniki, $args[$field]) . "' ";
            $rc = ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.tenants', 'ciniki_tenant_history', $args['tnid'], 
                2, 'ciniki_tenant_syncs', $args['sync_id'], $field, $args[$field]);
        }
    }
    $strsql .= "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND id = '" . ciniki_core_dbQuote($ciniki, $args['sync_id']) . "' ";
    $rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.tenants');
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.tenants');
        return $rc;
    }
    if( !isset($rc['num_affected_rows']) || $rc['num_affected_rows'] != 1 ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.tenants');
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.tenants.92', 'msg'=>'Unable to update domain'));
    }

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
