<?php
//
// Description
// -----------
// This method will add a new plan to a tenant.  
//
// Arguments
// ---------
//
// Returns
// -------
// <rsp stat="ok">
//
function ciniki_tenants_planAdd($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'name'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Name'), 
        'flags'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'0', 'name'=>'Flags'), 
        'sequence'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'1', 'name'=>'Sequence'), 
        'monthly'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'1', 'name'=>'Monthly Price'),
        'modules'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'1', 'name'=>'Modules'),
        'trial_days'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'1', 'name'=>'Number of Trial Days'),
        'description'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'1', 'name'=>'Description'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];
    
    //
    // Check access to tnid as owner
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'checkAccess');
    $ac = ciniki_tenants_checkAccess($ciniki, $args['tnid'], 'ciniki.tenants.planAdd');
    if( $ac['stat'] != 'ok' ) {
        return $ac;
    }

    //
    // Start transaction
    //
    require($ciniki['config']['core']['modules_dir'] . '/core/private/dbTransactionStart.php');
    require($ciniki['config']['core']['modules_dir'] . '/core/private/dbTransactionRollback.php');
    require($ciniki['config']['core']['modules_dir'] . '/core/private/dbTransactionCommit.php');
    require($ciniki['config']['core']['modules_dir'] . '/core/private/dbInsert.php');
    require($ciniki['config']['core']['modules_dir'] . '/core/private/dbAddModuleHistory.php');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.tenants');
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // FIXME: Add ability to set modules when site is added, right now default to most apps on
    //
    $strsql = "INSERT INTO ciniki_tenant_plans (uuid, tnid, "
        . "name, flags, sequence, monthly, modules, trial_days, description, "
        . "date_added, last_updated ) VALUES ( "
        . "UUID(), "
        . "'" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "', "
        . "'" . ciniki_core_dbQuote($ciniki, $args['name']) . "', "
        . "'" . ciniki_core_dbQuote($ciniki, $args['flags']) . "', "
        . "'" . ciniki_core_dbQuote($ciniki, $args['sequence']) . "', "
        . "'" . ciniki_core_dbQuote($ciniki, $args['monthly']) . "', "
        . "'" . ciniki_core_dbQuote($ciniki, $args['modules']) . "', "
        . "'" . ciniki_core_dbQuote($ciniki, $args['trial_days']) . "', "
        . "'" . ciniki_core_dbQuote($ciniki, $args['description']) . "', "
        . "UTC_TIMESTAMP(), UTC_TIMESTAMP())";
    $rc = ciniki_core_dbInsert($ciniki, $strsql, 'ciniki.tenants');
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.tenants');
        return $rc;
    }
    if( !isset($rc['insert_id']) || $rc['insert_id'] < 1 ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.tenants');
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.tenants.55', 'msg'=>'Unable to add plan'));
    }
    $plan_id = $rc['insert_id'];

    //
    // Add all the fields to the change log
    //
    $changelog_fields = array(
        'name',
        'flags',
        'sequence',
        'monthly',
        'modules',
        'trial_days',
        'description',
        );
    foreach($changelog_fields as $field) {
        if( isset($args[$field]) && $args[$field] != '' ) {
            $rc = ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.tenants', 'ciniki_tenant_history', $args['tnid'], 
                1, 'ciniki_tenant_plans', $plan_id, $field, $args[$field]);
        }
    }

    //
    // Commit the transaction
    //
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.tenants');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    return array('stat'=>'ok', 'id'=>$plan_id);
}
?>
