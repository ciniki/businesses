<?php
//
// Description
// -----------
//
// DEPRECATED - This functionality should now be done via getUserSettings.
//
// This method will return the list of modules the user has access to and are turned on for the business.
// The UI can use this to decide what menu items to display.
//
// **NOTE** Any changed in this method should be duplicated to getUserSettings.
//
// Arguments
// ---------
// api_key:
// auth_token:
//
// Returns
// -------
// <modules>
//      <modules name='questions' />
// </businesses>
//
function ciniki_businesses_getUserModules($ciniki) {
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
    $rc = ciniki_businesses_checkAccess($ciniki, 0, 'ciniki.businesses.getUserModules');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // FIXME: Add check to see which groups the user is part of, and only hand back the module list
    //        for what they have access to.
    //
    $strsql = "SELECT CONCAT_WS('.', package, module) AS name, package, module, flags "
        . "FROM ciniki_business_modules "
        . "WHERE ciniki_business_modules.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "AND (ciniki_business_modules.status = 1 "
            . "OR ciniki_business_modules.status = 2 "
            . ") "
        . "";
    $rsp = ciniki_core_dbRspQuery($ciniki, $strsql, 'ciniki.businesses', 'modules', 'module', array('stat'=>'ok', 'modules'=>array()));

    //
    // Check for any modules which should have some stats with them
    //
    if( $rsp['stat'] == 'ok' ) {
        foreach($rsp['modules'] as $i => $module) {
            //
            // Get the current exhibition to display the menu at the top
            // of the main menu for quick access
            //
            if( $module['module']['name'] == 'ciniki.exhibitions' ) {
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
                    for($i=0;$i<2;$i++) {
                        if( isset($rc['exhibitions'][$i]) ) {
                            $rsp['exhibitions'][] = $rc['exhibitions'][$i];
                        }
                    }
                }
            }
            //
            // Get the numbers for the main menu
            //
            if( $module['module']['name'] == 'ciniki.atdo' ) {
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
                    $rsp['modules'][$i]['module']['task_count'] = 0 + $rc['atdo']['2']['num_items'];
                } else {
                    $rsp['modules'][$i]['module']['task_count'] = 0;
                }
                if( isset($rc['atdo']['7']['num_items']) ) {
                    $rsp['modules'][$i]['module']['project_count'] = 0 + $rc['atdo']['7']['num_items'];
                } else {
                    $rsp['modules'][$i]['module']['project_count'] = 0;
                }
//              ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbCount');
//              $rc = ciniki_core_dbCount($ciniki, $strsql, 'ciniki.atdo', 'atdo');
//              if( $rc['stat'] != 'ok' ) {
//                  return $rc;
//              }
//              $rsp['modules'][$i]['module']['task_count'] = $rc['atdo']['numtasks'];
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
                    $rsp['modules'][$i]['module']['message_count'] = 0 + $rc['atdo']['6']['num_items'];
                } else {
                    $rsp['modules'][$i]['module']['message_count'] = 0;
                }
                if( isset($rc['atdo']['5']['num_items']) ) {
                    $rsp['modules'][$i]['module']['notes_count'] = 0 + $rc['atdo']['5']['num_items'];
                } else {
                    $rsp['modules'][$i]['module']['notes_count'] = 0;
                }
            }
            if( $module['module']['name'] == 'ciniki.newsaggregator' ) {
                $strsql = "SELECT 'unread_count' AS type, COUNT(*) AS num_items "
                    . "FROM ciniki_newsaggregator_subscriptions "
                    . "LEFT JOIN ciniki_newsaggregator_articles ON (ciniki_newsaggregator_subscriptions.feed_id = ciniki_newsaggregator_articles.feed_id "
                        . "AND ciniki_newsaggregator_subscriptions.date_read_all < ciniki_newsaggregator_articles.published_date ) "
//                  . "LEFT JOIN ciniki_newsaggregator_article_users ON (ciniki_newsaggregator_articles.id = ciniki_newsaggregator_article_users.article_id "
//                      . "AND ciniki_newsaggregator_article_users.user_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "') "
//                      . "AND (ciniki_newsaggregator_article_users.flags&0x01) = 0 )
                    . "WHERE ciniki_newsaggregator_subscriptions.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
                    . "AND ciniki_newsaggregator_subscriptions.user_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "' "
//                  . "AND (ciniki_newsaggregator_article_users.flags = NULL OR (ciniki_newsaggregator_article_users.flags&0x01) = 0) "
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
                    $rsp['modules'][$i]['module']['unread_count'] = 0 + $rc['newsaggregator']['unread_count']['num_items'];
                } else {
                    $rsp['modules'][$i]['module']['unread_count'] = 0;
                }
            }
        }
    }

    return $rsp;
}
?>
