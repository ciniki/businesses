<?php
//
// Description
// -----------
// This method will ping the remote server, using full encryption to make sure
// the two systems can talk to each other.
//
// Returns
// -------
// <rsp stat="ok" />
//
function ciniki_tenants_syncPing($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'sync_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Sync'), 
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access 
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'checkAccess');
    $rc = ciniki_tenants_checkAccess($ciniki, $args['tnid'], 'ciniki.tenants.syncPing');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncLoad');
    $rc = ciniki_core_syncLoad($ciniki, $args['tnid'], $args['sync_id']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $sync = $rc['sync'];

    //
    // Make the request
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncRequest');
    $rc = ciniki_core_syncRequest($ciniki, $sync, array('method'=>'ciniki.core.ping'));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    return array('stat'=>'ok'); 
}
?>
