<?php
//
// Description
// ===========
// This method will return the plan information.
//
// Arguments
// ---------
// user_id:         The user making the request
// 
// Returns
// -------
//
function ciniki_tenants_planGet($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'plan_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Plan'), 
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];
    
    //  
    // Make sure this module is activated, and
    // check permission to run this function for this tenant
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'checkAccess');
    $rc = ciniki_tenants_checkAccess($ciniki, $args['tnid'], 'ciniki.tenants.planGet'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    $strsql = "SELECT ciniki_tenant_plans.id, uuid, name, flags, sequence, "
        . "monthly, modules, trial_days, description, "
        . "date_added, last_updated "
        . "FROM ciniki_tenant_plans "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND ciniki_tenant_plans.id = '" . ciniki_core_dbQuote($ciniki, $args['plan_id']) . "' "
        . "";
    
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.tenants', 'plan');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['plan']) ) {
        return array('stat'=>'ok', 'err'=>array('code'=>'ciniki.tenants.57', 'msg'=>'Unable to find plan'));
    }

    return array('stat'=>'ok', 'plan'=>$rc['plan']);
}
?>
