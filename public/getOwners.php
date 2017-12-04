<?php
//
// Description
// -----------
// This function will retrieve the users who are owners or employee's 
// of the specified tenant.  No customers will be returned in this query,
// unless they are also an owner or employee of this tenant.
//
// *note* Only tenant owners are implemented, employee's will be in the future.
//
// Info
// ----
// Status: beta
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant to get the users for.
//
// Returns
// -------
// <users>
//      <user id='1' email='' firstname='' lastname='' display_name='' />
// </users>
//
function ciniki_tenants_getOwners($ciniki) {
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
    $ac = ciniki_tenants_checkAccess($ciniki, $args['tnid'], 'ciniki.tenants.getOwners');
    if( $ac['stat'] != 'ok' ) {
        return $ac;
    }

    //
    // Query for the tenant users
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
    $strsql = "SELECT user_id FROM ciniki_tenant_users "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND type = 1 ";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.tenants', 'tenant');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( $rc['num_rows'] < 1 ) {
        return array('stat'=>'ok', 'users'=>array());
    }

    $user_id_list = array();
    $strsql = "SELECT id, email, firstname, lastname, display_name FROM ciniki_users "
        . "WHERE id IN (";
    $comma = '';
    foreach($rc['rows'] as $i) {
        $strsql .= $comma . ciniki_core_dbQuote($ciniki, $i['user_id']);
        $comma = ', ';
    }
    $strsql .= ") ORDER BY lastname, firstname";
    
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbRspQuery');
    return ciniki_core_dbRspQuery($ciniki, $strsql, 'ciniki.users', 'users', 'user', array('stat'=>'ok', 'users'=>array()));
}
?>
