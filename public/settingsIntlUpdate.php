<?php
//
// Description
// -----------
// This method will update the intl settings for a tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant to get the intl settings for.
//
// Returns
// -------
// <settings intl-default-locale="en_US"
//
function ciniki_tenants_settingsIntlUpdate($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];
    
    //
    // Check access to tnid as owner, or sys admin
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'checkAccess');
    $ac = ciniki_tenants_checkAccess($ciniki, $args['tnid'], 'ciniki.tenants.settingsIntlUpdate');
    if( $ac['stat'] != 'ok' ) {
        return $ac;
    }

    //
    // Turn off autocommit
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbInsert');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.tenants');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // FIXME: Add check that the values submitted are valid
    //

    //
    // Allowed tenant detail keys 
    //
    $allowed_keys = array(
        'intl-default-locale',
        'intl-default-currency',
        'intl-default-timezone',
        'intl-default-distance-units',
        );
    foreach($ciniki['request']['args'] as $arg_name => $arg_value) {
        if( in_array($arg_name, $allowed_keys) ) {
            $strsql = "INSERT INTO ciniki_tenant_details (tnid, "
                . "detail_key, detail_value, date_added, last_updated) "
                . "VALUES ('" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "'"
                . ", '" . ciniki_core_dbQuote($ciniki, $arg_name) . "'"
                . ", '" . ciniki_core_dbQuote($ciniki, $arg_value) . "'"
                . ", UTC_TIMESTAMP(), UTC_TIMESTAMP()) "
                . "ON DUPLICATE KEY UPDATE detail_value = '" . ciniki_core_dbQuote($ciniki, $arg_value) . "' "
                . ", last_updated = UTC_TIMESTAMP() "
                . "";
            $rc = ciniki_core_dbInsert($ciniki, $strsql, 'ciniki.tenants');
            if( $rc['stat'] != 'ok' ) {
                ciniki_core_dbTransactionRollback($ciniki, 'ciniki.tenants');
                return $rc;
            }
            ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.tenants', 'ciniki_tenant_history', 
                $args['tnid'], 2, 'ciniki_tenant_details', $arg_name, 'detail_value', $arg_value);
            $ciniki['syncqueue'][] = array('push'=>'ciniki.tenants.details', 
                'args'=>array('id'=>$arg_name));
        }
    }

    //
    // Commit the transaction
    //
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.tenants');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    return array('stat'=>'ok');
}
?>
