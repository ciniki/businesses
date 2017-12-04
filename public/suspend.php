<?php
//
// Description
// -----------
// This function will suspend a tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// id:          The ID of the tenant to archive.
//
// Returns
// -------
// <rsp stat="ok" />
//
function ciniki_tenants_suspend($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access 
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'checkAccess');
    $rc = ciniki_tenants_checkAccess($ciniki, $args['id'], 'ciniki.tenants.suspend');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuoteRequestArg');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
    $strsql = "UPDATE ciniki_tenants SET status = 50 "
        . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['id']) . "'";
    $rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.tenants');
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }
    // Update the log
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.tenants', 'ciniki_tenant_history', $args['id'], 
        2, 'ciniki_tenants', $args['id'], 'status', '50');

    return $rc;
}
?>
