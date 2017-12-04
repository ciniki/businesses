<?php
//
// Description
// -----------
// This method will return all the information about a tenant required when the user logs into the UI. 
//
// Arguments
// ---------
// api_key:
// auth_token:
//
// Returns
// -------
// <tenant name="">
//  <css>
//  </css>
//  <modules>
//      <modules name='questions' />
//  </modules>
// </tenant>
//
function ciniki_tenants_getUserSettings($ciniki) {
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
    $rc = ciniki_tenants_checkAccess($ciniki, $args['tnid'], 'ciniki.tenants.getUserSettings');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $modules = $rc['modules'];
    $groups = $rc['groups'];

    //
    // Setup the default return array
    //
    $rsp = array('stat'=>'ok', 'modules'=>array(), 'menu_items'=>array(), 'settings_menu_items'=>array(), 'settings'=>array('uiAppOverrides'=>array()));

    //
    // Get the tenant name, and CSS
    // FIXME: convert ciniki.manage.css to ciniki-manage-css
    //
    $strsql = "SELECT name, d1.detail_value AS css "
        . "FROM ciniki_tenants "
        . "LEFT JOIN ciniki_tenant_details AS d1 ON (ciniki_tenants.id = d1.tnid AND d1.detail_key = 'ciniki.manage.css') "
        . "WHERE ciniki_tenants.id = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.tenants', 'tenant');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['tenant']) ) {
        $rsp['name'] = $rc['tenant']['name'];
        if( isset($rc['tenant']['css']) ) {
            $rsp['css'] = $rc['tenant']['css'];
        }
    }

    //
    // Get list of employees for the tenant
    //
    $strsql = "SELECT DISTINCT ciniki_tenant_users.user_id AS id, ciniki_users.display_name "
        . "FROM ciniki_tenant_users, ciniki_users "
        . "WHERE ciniki_tenant_users.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' " 
        . "AND ciniki_tenant_users.user_id = ciniki_users.id "
        . "ORDER BY display_name "
        . "";
    $rc = ciniki_core_dbRspQuery($ciniki, $strsql, 'ciniki.tenants', 'users', 'user', array('stat'=>'ok', 'users'=>array()));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $rsp['users'] = $rc['users'];

    //
    // Get the permission_groups for the user requesting the tenant information
    //
    $strsql = "SELECT permission_group AS name, 'yes' AS status "
        . "FROM ciniki_tenant_users "
        . "WHERE ciniki_tenant_users.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' " 
        . "AND ciniki_tenant_users.user_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "' "
        . "AND ciniki_tenant_users.status = 10 "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQueryList2');
    $rc = ciniki_core_dbQueryList2($ciniki, $strsql, 'ciniki.tenants', 'permissions');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $rsp['permissions'] = $rc['permissions'];

    //
    // FIXME: Add check to see which groups the user is part of, and only hand back the module list
    //        for what they have access to.
    //
    $strsql = "SELECT CONCAT_WS('.', package, module) AS name, package, module, flags, flags>>32 as flags2 "
        . "FROM ciniki_tenant_modules "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND (status = 1 OR status = 2) " // Added or mandatory
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $mrc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.tenants', array(
        array('container'=>'modules', 'fname'=>'name',
            'fields'=>array('name', 'package', 'module', 'flags', 'flags2')),
        ));
    if( $mrc['stat'] != 'ok' ) {
        return $mrc;
    }
//  $mrc = ciniki_core_dbRspQuery($ciniki, $strsql, 'ciniki.tenants', 'modules', 'module', array('stat'=>'ok', 'modules'=>array()));

    //
    // Check for any modules which should have some settings loaded as well
    //
    $count = 0;
    if( $mrc['stat'] == 'ok' && isset($mrc['modules']) ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDetailsQuery');
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDetailsQueryDash');
        foreach($mrc['modules'] as $i => $module) {
            // 
            // Add the module to the list of modules to hand back
            //
            $rsp['modules'][$count] = array('module'=>$module);

            //
            // Check for uiSettings in other modules
            //
            $rc = ciniki_core_loadMethod($ciniki, $module['package'], $module['module'], 'hooks', 'uiSettings');
            if( $rc['stat'] == 'ok' ) {
                $fn = $rc['function_call'];
                $rc = $fn($ciniki, $args['tnid'], array('modules'=>$mrc['modules'], 'permissions'=>$rsp['permissions']));
                if( $rc['stat'] != 'ok' ) {
                    return $rc;
                }
                if( isset($rc['settings']) ) {
                    $rsp['settings'][$module['name']] = $rc['settings'];
                }
                if( isset($rc['menu_items']) ) {
                    $rsp['menu_items'] = array_merge($rsp['menu_items'], $rc['menu_items']);
                }
                if( isset($rc['settings_menu_items']) ) {
                    $rsp['settings_menu_items'] = array_merge($rsp['settings_menu_items'], $rc['settings_menu_items']);
                }
                if( isset($rc['uiAppOverrides']) ) {
                    $rsp['settings']['uiAppOverrides'] = array_merge($rsp['settings']['uiAppOverrides'], $rc['uiAppOverrides']);
                }
            }

            //
            // FIXME: Move these into settings files for each module
            //
            if( isset($ciniki['config']['ciniki.web']['google.maps.api.key']) ) {
                $rsp['settings']['googlemapsapikey'] = $ciniki['config']['ciniki.web']['google.maps.api.key'];
            }
            
            $count++;
        }

        //
        // Load the tenant settings
        //
        $rc = ciniki_core_dbDetailsQueryDash($ciniki, 'ciniki_tenant_details', 'tnid', 
            $args['tnid'], 'ciniki.tenants', 'settings', 'intl');
        if( $rc['stat'] == 'ok' ) {
            $rsp['intl'] = $rc['settings'];
        }
    }

    //
    // Check for tenant reports flag, and a tenant owners
    //
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.tenants', 0x040000) 
        && (in_array('owners', $groups) || ($ciniki['session']['user']['perms']&0x01) == 0x01) 
        ) {
        $rsp['settings_menu_items'][] = array(
            'priority'=>1050, 
            'label'=>'Reports', 
            'edit'=>array('app'=>'ciniki.tenants.reports'),
            );
    } 

    //
    // Sort the menu items based on priority
    //
    usort($rsp['menu_items'], function($a, $b) {
        if( $a['priority'] == $b['priority'] ) {
            return 0;
        }
        return $a['priority'] > $b['priority'] ? -1 : 1;
    });

    //
    // Sort the setttings menu items based on priority
    //
    usort($rsp['settings_menu_items'], function($a, $b) {
        if( $a['priority'] == $b['priority'] ) {
            return 0;
        }
        return $a['priority'] > $b['priority'] ? -1 : 1;
    });

    //
    // Check the menu_items duplicates
    //
    $prev_label = '';
    foreach($rsp['menu_items'] as $iid => $item) {
        if( $item['label'] == $prev_label ) {
            unset($rsp['menu_items'][$iid]);
        }
        $prev_label = $item['label'];
    }

    //
    // Check for menu items with no edit specified
    //
    foreach($rsp['menu_items'] as $iid => $item) {
        if( !isset($item['edit']) && !isset($item['subitems']) ) {
            unset($rsp['menu_items'][$iid]);
        }
    }


    return $rsp;
}
?>
