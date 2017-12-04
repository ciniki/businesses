<?php
//
// Description
// -----------
// This function will return the list of modules available in the system,
// and which modules the requested tenant has access to.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant to get the module list for.
//
// Returns
// -------
// <modules>
//      <module label='Products' name='products' status='On|Off' />
// </modules>
//
function ciniki_tenants_getModules($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'plans'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Plans'), 
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];
    
    //
    // Check access to tnid as owner, or sys admin. 
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'checkAccess');
    $ac = ciniki_tenants_checkAccess($ciniki, $args['tnid'], 'ciniki.tenants.getModules');
    if( $ac['stat'] != 'ok' ) {
        return $ac;
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashIDQuery');
    $strsql = "SELECT CONCAT_WS('.', package, module) AS name, package, module, status, ruleset "
        . "FROM ciniki_tenant_modules "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "ORDER BY name "
        . "";   
    $rc = ciniki_core_dbHashIDQuery($ciniki, $strsql, 'ciniki.tenants', 'modules', 'name');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['modules']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.tenants.49', 'msg'=>'No tenant found'));
    }
    $tenant_modules = $rc['modules'];

    //
    // Get the list of available modules
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'getModuleList');
    $rc = ciniki_core_getModuleList($ciniki);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $mod_list = $rc['modules'];

    $modules = array();
    $count = 0;
    foreach($mod_list as $module) {
        if( $module['label'] != '' && $module['installed'] == 'Yes' && (!isset($module['optional']) || $module['optional'] == 'yes') ) {
            $modules[$count] = array('label'=>$module['label'], 'package'=>$module['package'], 'name'=>$module['name'], 'status'=>'0');
            if( isset($tenant_modules[$module['package'] . '.' . $module['name']]) 
                && $tenant_modules[$module['package'] . '.' . $module['name']]['status'] == 1 ) {
                $modules[$count]['status'] = '1';
            }
            $count++;
        }
    }

    $rsp = array('stat'=>'ok', 'modules'=>$modules);
    
    //
    // Get the list of available plans for the tenant
    // 
    if( isset($args['plans']) && $args['plans'] == 'yes' ) {
        if( $args['tnid'] == '0' ) {
            $args['tnid'] = $ciniki['config']['ciniki.core']['master_tnid'];
        }
        //
        // Query the database for the plan
        //
        $strsql = "SELECT id, name, monthly, trial_days "
            . "FROM ciniki_tenant_plans "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "ORDER BY sequence "
            . "";

        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
        $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.tenants', array(
            array('container'=>'plans', 'fname'=>'id', 'name'=>'plan', 'fields'=>array('id', 'name', 'monthly')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['plans']) ) {
            $rsp['plans'] = $rc['plans'];
        }
    }

    return $rsp;
}
?>
