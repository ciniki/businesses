<?php
//
// Description
// -----------
// This method will check the number of records in each sync object table 
// for a tenant.  If no sync_id is provided, it will return the table
// row counts for all syncs.
//
// Returns
// -------
// <syncs>
//      <sync id="1" uuid="" name="">
// </syncs>
// <modules>
//      <module name="customers">
//          <tables>
//              <table name="ciniki_customers" local="3" uuid="3" ... >
//          </tables>
//      </module>
// </modules>
//
function ciniki_tenants_syncCheckRowCounts($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'sync_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Sync'), 
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access 
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'checkAccess');
    $rc = ciniki_tenants_checkAccess($ciniki, $args['tnid'], 'ciniki.tenants.syncCheckRowCounts');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbGetRowCounts');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncLoad');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncRequest');

    //
    // Get the local object table counts
    //
    $rc = ciniki_core_dbGetRowCounts($ciniki, $args['tnid']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $modules = $rc['modules'];

    //
    // Get the list of syncs, or just one if specified
    //
    $strsql = "SELECT ciniki_tenant_syncs.id, "
        . "ciniki_tenant_syncs.remote_name, ciniki_tenant_syncs.remote_uuid "
        . "FROM ciniki_tenant_syncs "
        . "WHERE ciniki_tenant_syncs.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND status = 10 ";
    if( isset($args['sync_id']) && $args['sync_id'] != '' ) {
        $strsql .= "AND id = '" . ciniki_core_dbQuote($ciniki, $args['sync_id']) . "' ";
    }
    $strsql .= "ORDER BY remote_name ";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.tenants', array(
        array('container'=>'syncs', 'fname'=>'id', 'name'=>'sync',
            'fields'=>array('id', 'name'=>'remote_name', 'remote_uuid')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['syncs']) ) {
        $syncs = array();
    } else {
        $syncs = $rc['syncs'];
    }

    //
    // loop through all remote syncs and get the table counts
    //
    foreach($syncs as $sid => $s) {
        $rc = ciniki_core_syncLoad($ciniki, $args['tnid'], $s['sync']['id']);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $sync = $rc['sync'];

        $rc = ciniki_core_syncRequest($ciniki, $sync, array('method'=>'ciniki.core.rowCounts'));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $remote_modules = $rc['modules'];
        
        //
        // Add remote information to modules
        //
        foreach($modules as $mid => $mod) {
            if( !isset($mod['tables']) ) {
                continue;
            }
            foreach($mod['tables'] as $tid => $table) {
                if( isset($remote_modules[$mid]['tables'][$tid]['rows']) ) {
                    $modules[$mid]['tables'][$tid]['sync-' . $s['sync']['id']] = $remote_modules[$mid]['tables'][$tid]['rows'];
                    if( $table['rows'] != $remote_modules[$mid]['tables'][$tid]['rows'] ) {
                        $modules[$mid]['tables'][$tid]['flagged'] = 'yes'; 
                    }
                } else {
                    $modules[$mid]['tables'][$tid]['flagged'] = 'yes';
                }
            }
        }
    }

    //
    // Expand modules to be proper return array, adding 'module' and 'table'
    //
    $mods = array();
    foreach($modules as $mid => $mod) {
        $tables = array();
        if( isset($mod['tables']) ) {
            foreach($mod['tables'] as $tid => $table) {
                $tables[] = array('table'=>$table);
            }
        }
        $mods[] = array('module'=>array('name'=>$mid, 'tables'=>$tables));
    }

    // hard coded return value, so the sync information does not also get passed back.
    return array('stat'=>'ok', 'syncs'=>$syncs, 'modules'=>$mods);
}
?>
