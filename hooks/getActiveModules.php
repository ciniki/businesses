<?php
//
// Description
// -----------
// This function will verify the tenant is active, and the module is active.
//
// Arguments
// ---------
// ciniki:
// tnid:         The tenant ID to check the session user against.
// method:              The requested method.
//
// Returns
// -------
// <rsp stat='ok' />
//
function ciniki_tenants_hooks_getActiveModules($ciniki, $tnid, $args) {
    //
    // Check if the module is enabled for this tenant, don't really care about the ruleset
    //
    $strsql = "SELECT ciniki_tenants.status AS tenant_status, "
        . "ciniki_tenant_modules.status AS module_status, "
        . "CONCAT_WS('.', ciniki_tenant_modules.package, ciniki_tenant_modules.module) AS module_id, "
        . "flags, flags>>32 as flags2, ruleset "
        . "FROM ciniki_tenants, ciniki_tenant_modules "
        . "WHERE ciniki_tenants.id = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND ciniki_tenants.id = ciniki_tenant_modules.tnid "
        . "AND ciniki_tenant_modules.status = 1 "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashIDQuery');
    $rc = ciniki_core_dbHashIDQuery($ciniki, $strsql, 'ciniki.tenants', 'modules', 'module_id');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['modules']) ) {
        return array('stat'=>'ok', 'modules'=>array());
    }
    return $rc;
}
?>
