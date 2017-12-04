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
//
function ciniki_tenants_dbIntegrityCheck($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'fix'=>array('required'=>'no', 'default'=>'no', 'name'=>'Fix Problems'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];
    
    //
    // Check access to tnid as owner, or sys admin
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'checkAccess');
    $rc = ciniki_tenants_checkAccess($ciniki, $args['tnid'], 'ciniki.tenants.dbIntegrityCheck', 0);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDelete');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbFixTableHistory');

    if( $args['fix'] == 'yes' ) {
        //
        // Remove old incorrect formatted entries
        //
        $strsql = "DELETE FROM ciniki_tenant_history "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND table_name = 'ciniki_tenant_users' "
            . "AND table_field LIKE '%.%.%' "
            . "";
        $rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.tenants');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }

        $strsql = "DELETE FROM ciniki_tenant_history "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND table_name = 'ciniki_tenant_users' "
            . "AND table_key LIKE '%.%.%' "
            . "";
        $rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.tenants');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }

        $strsql = "DELETE FROM ciniki_tenant_history "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND table_name = 'ciniki_tenant_user_details' "
            . "AND table_field LIKE '%.%' "
            . "";
        $rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.tenants');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }

        // Remote entries with blank table_field
        $strsql = "DELETE FROM ciniki_tenant_history "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND table_name = 'ciniki_tenants' "
            . "AND table_field = '' "
            . "";
        $rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.tenants');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        

        //
        // Add the proper history for ciniki_tenant_users
        //
        $rc = ciniki_core_dbFixTableHistory($ciniki, 'ciniki.tenants', $args['tnid'],
            'ciniki_tenant_users', 'ciniki_tenant_history',
            array('uuid', 'user_id', 'package', 'permission_group', 'status'));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }

        $rc = ciniki_core_dbFixTableHistory($ciniki, 'ciniki.tenants', $args['tnid'],
            'ciniki_tenant_user_details', 'ciniki_tenant_history',
            array('uuid', 'user_id', 'detail_key', 'detail_value'));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }

        //
        // Check for items missing a UUID
        //
        $strsql = "UPDATE ciniki_tenant_history SET uuid = UUID() WHERE uuid = ''";
        $rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.tenants');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
    }

    return array('stat'=>'ok');
}
?>
