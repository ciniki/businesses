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
function ciniki_tenants_checkModuleAccess(&$ciniki, $tnid, $package, $module) {
    //
    // Get the active modules for the tenant
    //
    $strsql = "SELECT ciniki_tenants.status AS tenant_status, "
        . "ciniki_tenant_modules.status AS module_status, "
        . "ciniki_tenant_modules.package, ciniki_tenant_modules.module, "
        . "CONCAT_WS('.', ciniki_tenant_modules.package, ciniki_tenant_modules.module) AS module_id, "
        . "flags, (flags&0xFFFFFFFF00000000)>>32 as flags2, ruleset "
        . "FROM ciniki_tenants, ciniki_tenant_modules "
        . "WHERE ciniki_tenants.id = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND ciniki_tenants.id = ciniki_tenant_modules.tnid "
        // Get the options and mandatory module
        . "AND (ciniki_tenant_modules.status = 1 || ciniki_tenant_modules.status = 2) "
//      . "AND ciniki_tenant_modules.package = '" . ciniki_core_dbQuote($ciniki, $package) . "' "
//      . "AND ciniki_tenant_modules.module = '" . ciniki_core_dbQuote($ciniki, $module) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashIDQuery');
    $rc = ciniki_core_dbHashIDQuery($ciniki, $strsql, 'ciniki.tenants', 'modules', 'module_id');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    if( !isset($rc['modules']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.tenants.15', 'msg'=>'No modules enabled'));
    }

    $modules = $rc['modules'];
    $ciniki['tenant']['modules'] = $modules;

    if( !isset($rc['modules'][$package . '.' . $module]) ) {
        return array('stat'=>'fail', 'modules'=>$modules, 'err'=>array('code'=>'ciniki.tenants.16', 'msg'=>"Module '$package.$module' disabled"));
    }

    //
    // Check if the tenant is not active
    //
    if( isset($rc['modules'][$package . '.' . $module]['tenant_status']) && $rc['modules'][$package . '.' . $module]['tenant_status'] != 1 ) {
        if( $rc['modules'][$package . '.' . $module]['tenant_status'] == 50 ) {
            return array('stat'=>'fail', 'modules'=>$modules, 'err'=>array('code'=>'ciniki.tenants.17', 'msg'=>'Tenant suspended'));
        } elseif( $rc['modules'][$package . '.' . $module]['tenant_status'] == 60 ) {
            return array('stat'=>'fail', 'modules'=>$modules, 'err'=>array('code'=>'ciniki.tenants.18', 'msg'=>'Tenant deleted'));
        }
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.tenants.19', 'msg'=>'Tenant inactive'));
    }

    //
    // Check if module is enabled
    //
    if( isset($rc['modules'][$package . '.' . $module]['module_status']) 
        && $rc['modules'][$package . '.' . $module]['module_status'] != 1 
        && $rc['modules'][$package . '.' . $module]['module_status'] != 2 
        ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.tenants.20', 'msg'=>'Module disabled'));
    }

    //
    // Return the ruleset
    //
    return array('stat'=>'ok', 'ruleset'=>$rc['modules'][$package . '.' . $module]['ruleset'], 'modules'=>$rc['modules']);
}
?>
