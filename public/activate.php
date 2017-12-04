<?php
//
// Description
// -----------
// This function will activate a tenant, allowing access.
//
// Arguments
// ---------
// api_key:
// auth_token:
// id:          The ID of the tenant to activate.
//
// Returns
// -------
// <rsp stat="ok" />
//
function ciniki_tenants_activate($ciniki) {
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
    $rc = ciniki_tenants_checkAccess($ciniki, $args['id'], 'ciniki.tenants.activate');
    if( $rc['stat'] != 'ok' && $rc['err']['code'] != 'ciniki.tenants.17' && $rc['err']['code'] != 'ciniki.tenants.18' ) {
        return $rc;
    }

    //
    // Make sure a sysadmin.  This check needs to be here because we ignore suspended/deleted 
    // tenants above, which then doesn't check perms.  Only sysadmins have access
    // to this method.
    //
    if( ($ciniki['session']['user']['perms'] & 0x01) != 0x01 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.tenants.22', 'msg'=>'Permission denied'));
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuoteRequestArg');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
    $strsql = "UPDATE ciniki_tenants "
        . "SET status = 1 "
        . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['id']) . "'";
    $rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.tenants');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.tenants', 'ciniki_tenant_history', $args['id'], 
        2, 'ciniki_tenants', $args['id'], 'status', '1');

    return $rc;
}
?>
