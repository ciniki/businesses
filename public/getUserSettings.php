<?php
//
// Description
// -----------
// This method will return all the information about a business required when the user logs into the UI. 
//
// Arguments
// ---------
// api_key:
// auth_token:
//
// Returns
// -------
// <business name="">
//  <css>
//  </css>
//  <modules>
//      <modules name='questions' />
//  </modules>
// </business>
//
function ciniki_businesses_getUserSettings($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];
    
    //
    // Check access to business_id as owner, or sys admin
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'checkAccess');
    $rc = ciniki_businesses_checkAccess($ciniki, $args['business_id'], 'ciniki.businesses.getUserSettings');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $modules = $rc['modules'];
    $groups = $rc['groups'];

    //
    // Setup the default return array
    //
    $rsp = array('stat'=>'ok', 'modules'=>array(), 'menu_items'=>array());

    //
    // Get the business name, and CSS
    // FIXME: convert ciniki.manage.css to ciniki-manage-css
    //
    $strsql = "SELECT name, d1.detail_value AS css "
        . "FROM ciniki_businesses "
        . "LEFT JOIN ciniki_business_details AS d1 ON (ciniki_businesses.id = d1.business_id AND d1.detail_key = 'ciniki.manage.css') "
        . "WHERE ciniki_businesses.id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.businesses', 'business');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['business']) ) {
        $rsp['name'] = $rc['business']['name'];
        if( isset($rc['business']['css']) ) {
            $rsp['css'] = $rc['business']['css'];
        }
    }

    //
    // Get list of employees for the business
    //
    $strsql = "SELECT DISTINCT ciniki_business_users.user_id AS id, ciniki_users.display_name "
        . "FROM ciniki_business_users, ciniki_users "
        . "WHERE ciniki_business_users.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' " 
        . "AND ciniki_business_users.user_id = ciniki_users.id "
        . "ORDER BY display_name "
        . "";
    $rc = ciniki_core_dbRspQuery($ciniki, $strsql, 'ciniki.businesses', 'users', 'user', array('stat'=>'ok', 'users'=>array()));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $rsp['users'] = $rc['users'];

    //
    // Get the permission_groups for the user requesting the business information
    //
    $strsql = "SELECT permission_group AS name, 'yes' AS status "
        . "FROM ciniki_business_users "
        . "WHERE ciniki_business_users.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' " 
        . "AND ciniki_business_users.user_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "' "
        . "AND ciniki_business_users.status = 10 "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQueryList2');
    $rc = ciniki_core_dbQueryList2($ciniki, $strsql, 'ciniki.businesses', 'permissions');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $rsp['permissions'] = $rc['permissions'];

    //
    // FIXME: Add check to see which groups the user is part of, and only hand back the module list
    //        for what they have access to.
    //
    $strsql = "SELECT CONCAT_WS('.', package, module) AS name, package, module, flags, flags>>32 as flags2 "
        . "FROM ciniki_business_modules "
        . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "AND (status = 1 OR status = 2) " // Added or mandatory
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $mrc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.businesses', array(
        array('container'=>'modules', 'fname'=>'name',
            'fields'=>array('name', 'package', 'module', 'flags', 'flags2')),
        ));
    if( $mrc['stat'] != 'ok' ) {
        return $mrc;
    }
//  $mrc = ciniki_core_dbRspQuery($ciniki, $strsql, 'ciniki.businesses', 'modules', 'module', array('stat'=>'ok', 'modules'=>array()));

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
                $rc = $fn($ciniki, $args['business_id'], array('modules'=>$mrc['modules'], 'permissions'=>$rsp['permissions']));
                if( $rc['stat'] != 'ok' ) {
                    return $rc;
                }
                if( isset($rc['settings']) ) {
                    $rsp['settings'][$module['name']] = $rc['settings'];
                }
                if( isset($rc['menu_items']) ) {
                    $rsp['menu_items'] = array_merge($rsp['menu_items'], $rc['menu_items']);
                }
            }

            //
            // FIXME: Move these into settings files for each module
            //
            if( isset($ciniki['config']['ciniki.web']['google.maps.api.key']) ) {
                $rsp['settings']['googlemapsapikey'] = $ciniki['config']['ciniki.web']['google.maps.api.key'];
            }
            
            //
            // Check for any information required to display business menu
            // This section taken from getUserModules, and done together to reduce requests
            //

            // NOTE: Also make sure to update in getUserModules

            //
            // FIXME: Move into hooks/uiSettings for each module
            //

            //
            // Get the current exhibition to display the menu at the top
            // of the main menu for quick access
            //
/*            if( $module['name'] == 'ciniki.exhibitions' ) {
                //
                // Load the two most current exhibitions
                //
                $strsql = "SELECT ciniki_exhibitions.id, ciniki_exhibitions.name, "
                    . "ciniki_exhibition_details.detail_key, "
                    . "ciniki_exhibition_details.detail_value "
                    . "FROM ciniki_exhibitions "
                    . "LEFT JOIN ciniki_exhibition_details ON (ciniki_exhibitions.id = ciniki_exhibition_details.exhibition_id "
                        . "AND ciniki_exhibition_details.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
                        . ") "
                    . "WHERE ciniki_exhibitions.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
                    . "ORDER BY ciniki_exhibitions.start_date DESC "
                    . "";
                ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
                $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.exhibitions', array(
                    array('container'=>'exhibitions', 'fname'=>'id', 'name'=>'exhibition',
                        'fields'=>array('id', 'name'),
                        'details'=>array('detail_key'=>'detail_value')),
//                  array('container'=>'settings', 'fname'=>'detail_key', 'name'=>'detail',
//                      'fields'=>array('key'=>'detail_key', 'value'=>'detail_value')),
                    ));
                if( $rc['stat'] != 'ok' ) {
                    return $rc;
                }
                if( isset($rc['exhibitions']) ) {   
                    $rsp['exhibitions'] = array();
                    // Add the first 2 to the list
                    for($j=0;$j<2;$j++) {
                        if( isset($rc['exhibitions'][$j]) ) {
                            $rsp['exhibitions'][] = $rc['exhibitions'][$j];
                        }
                    }
                }
            }
            
            //
            // Get the numbers for the main menu
            //
            if( $module['name'] == 'ciniki.atdo' ) {
                $strsql = "SELECT ciniki_atdos.type, COUNT(ciniki_atdos.id) AS num_items "
//              $strsql = "SELECT 'numtasks', COUNT(ciniki_atdos.id) "
                    . "FROM ciniki_atdos, ciniki_atdo_users "
                    . "WHERE ciniki_atdos.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
                    . "AND (ciniki_atdos.type = 2 OR ciniki_atdos.type = 7) "   // Tasks or Projects
                    . "AND ciniki_atdos.id = ciniki_atdo_users.atdo_id "
                    . "AND ciniki_atdo_users.user_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "' "
                    . "AND ciniki_atdos.status = 1 "
                    . "AND (ciniki_atdo_users.perms&0x04) = 0x04 "
                    . "GROUP BY ciniki_atdos.type "
                    . "";
                ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashIDQuery');
                $rc = ciniki_core_dbHashIDQuery($ciniki, $strsql, 'ciniki.atdo', 'atdo', 'type');
                if( $rc['stat'] != 'ok' ) {
                    return $rc;
                }
                if( isset($rc['atdo']['2']['num_items']) ) {
                    $rsp['modules'][$count]['module']['task_count'] = 0 + $rc['atdo']['2']['num_items'];
                } else {
                    $rsp['modules'][$count]['module']['task_count'] = 0;
                }
                if( isset($rc['atdo']['7']['num_items']) ) {
                    $rsp['modules'][$count]['module']['project_count'] = 0 + $rc['atdo']['7']['num_items'];
                } else {
                    $rsp['modules'][$count]['module']['project_count'] = 0;
                }
                //
                // Messages and Notes are different, as it shows how many new or unread items
                //
                $strsql = "SELECT type, COUNT(ciniki_atdos.id) AS num_items "
                    . "FROM ciniki_atdos, ciniki_atdo_users "
                    . "WHERE ciniki_atdos.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
                    . "AND ((ciniki_atdos.type = 5 AND ciniki_atdos.parent_id = 0) OR ciniki_atdos.type = 6 )"  // Notes or Messages
                    . "AND ciniki_atdos.id = ciniki_atdo_users.atdo_id "
                    . "AND ciniki_atdo_users.user_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "' "
                    . "AND ciniki_atdos.status = 1 "
                    . "AND (ciniki_atdo_users.perms&0x04) = 0x04 "
                    . "AND (ciniki_atdo_users.perms&0x08) = 0 "
                    . "GROUP BY ciniki_atdos.type "
                    . "";
                $rc = ciniki_core_dbHashIDQuery($ciniki, $strsql, 'ciniki.atdo', 'atdo', 'type');
                if( $rc['stat'] != 'ok' ) {
                    return $rc;
                }
                if( isset($rc['atdo']['6']['num_items']) ) {
                    $rsp['modules'][$count]['module']['message_count'] = 0 + $rc['atdo']['6']['num_items'];
                } else {
                    $rsp['modules'][$count]['module']['message_count'] = 0;
                }
                if( isset($rc['atdo']['5']['num_items']) ) {
                    $rsp['modules'][$count]['module']['notes_count'] = 0 + $rc['atdo']['5']['num_items'];
                } else {
                    $rsp['modules'][$count]['module']['notes_count'] = 0;
                }
            }

            if( $module['name'] == 'ciniki.newsaggregator' ) {
                $strsql = "SELECT 'unread_count' AS type, COUNT(*) AS num_items "
                    . "FROM ciniki_newsaggregator_subscriptions "
                    . "LEFT JOIN ciniki_newsaggregator_articles ON (ciniki_newsaggregator_subscriptions.feed_id = ciniki_newsaggregator_articles.feed_id "
                        . "AND ciniki_newsaggregator_subscriptions.date_read_all < ciniki_newsaggregator_articles.published_date ) "
                    . "WHERE ciniki_newsaggregator_subscriptions.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
                    . "AND ciniki_newsaggregator_subscriptions.user_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "' "
                    . "AND NOT EXISTS (SELECT article_id FROM ciniki_newsaggregator_article_users "
                        . "WHERE ciniki_newsaggregator_articles.id = ciniki_newsaggregator_article_users.article_id "
                        . "AND ciniki_newsaggregator_article_users.user_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "' "
                        . "AND (ciniki_newsaggregator_article_users.flags&0x01) = 1 "
                        . ") "
                    . "GROUP BY type "
                    . "";
                ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashIDQuery');
                $rc = ciniki_core_dbHashIDQuery($ciniki, $strsql, 'ciniki.newsaggregator', 'newsaggregator', 'type');
                if( $rc['stat'] != 'ok' ) {
                    return $rc;
                }
                if( isset($rc['newsaggregator']['unread_count']['num_items']) ) {
                    $rsp['modules'][$count]['module']['unread_count'] = 0 + $rc['newsaggregator']['unread_count']['num_items'];
                } else {
                    $rsp['modules'][$count]['module']['unread_count'] = 0;
                }
            }
*/
            $count++;
        }

        //
        // Load the business settings
        //
        $rc = ciniki_core_dbDetailsQueryDash($ciniki, 'ciniki_business_details', 'business_id', 
            $args['business_id'], 'ciniki.businesses', 'settings', 'intl');
        if( $rc['stat'] == 'ok' ) {
            $rsp['intl'] = $rc['settings'];
        }
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

    return $rsp;
}
?>
