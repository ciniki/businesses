<?php
//
// Description
// -----------
// This function will get the history of a field from the ciniki_core_change_logs table.
// This allows the user to view what has happened to a data element, and if they
// choose, revert to a previous version.
//
// Info
// ----
// Status: beta
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant to get the details for.
// key:                 The detail key to get the history for.
//
// Returns
// -------
//  <history>
//      <action date="2011/02/03 00:03:00" value="Value field set to" user_id="1" />
//      ...
//  </history>
//  <users>
//      <user id="1" name="users.display_name" />
//      ...
//  </users>
//
function ciniki_tenants_getDetailHistory($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'field'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Field'), 
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];
    
    //
    // Check access to tnid as owner, or sys admin
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'checkAccess');
    $ac = ciniki_tenants_checkAccess($ciniki, $args['tnid'], 'ciniki.tenants.getDetailHistory');
    if( $ac['stat'] != 'ok' ) {
        return $ac;
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbGetModuleHistory');
    if( $args['field'] == 'tenant.name' ) {
        return ciniki_core_dbGetModuleHistory($ciniki, 'ciniki.tenants', 'ciniki_tenant_history', $args['tnid'], 
            'ciniki_tenants', '', 'name');
    } elseif( $args['field'] == 'tenant.category' ) {
        return ciniki_core_dbGetModuleHistory($ciniki, 'ciniki.tenants', 'ciniki_tenant_history', $args['tnid'], 
            'ciniki_tenants', '', 'category');
    } elseif( $args['field'] == 'tenant.sitename' ) {
        return ciniki_core_dbGetModuleHistory($ciniki, 'ciniki.tenants', 'ciniki_tenant_history', $args['tnid'], 
            'ciniki_tenants', '', 'sitename');
    } elseif( $args['field'] == 'tenant.tagline' ) {
        return ciniki_core_dbGetModuleHistory($ciniki, 'ciniki.tenants', 'ciniki_tenant_history', $args['tnid'], 
            'ciniki_tenants', '', 'tagline');
    }

    return ciniki_core_dbGetModuleHistory($ciniki, 'ciniki.tenants', 'ciniki_tenant_history', $args['tnid'], 
        'ciniki_tenant_details', $args['field'], 'detail_value');
}
?>
