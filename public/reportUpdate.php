<?php
//
// Description
// ===========
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_businesses_reportUpdate(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'),
        'report_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Reports'),
        'user_ids'=>array('required'=>'no', 'blank'=>'no', 'type'=>'idlist', 'name'=>'Users'),
        'title'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Title'),
        'frequency'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Frequency'),
        'flags'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Options'),
        'next_date'=>array('required'=>'no', 'blank'=>'no', 'type'=>'date', 'name'=>'Next Date'),
        'next_time'=>array('required'=>'no', 'blank'=>'no', 'type'=>'time', 'name'=>'Next Time'),
        'addblock'=>array('required'=>'no', 'blank'=>'no', 'name'=>'New Block'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Make sure this module is activated, and
    // check permission to run this function for this business
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'checkAccess');
    $rc = ciniki_businesses_checkAccess($ciniki, $args['business_id'], 'ciniki.businesses.reportUpdate');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Load business settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'intlSettings');
    $rc = ciniki_businesses_intlSettings($ciniki, $args['business_id']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];

    //
    // Get the existing values
    //
    $strsql = "SELECT ciniki_business_reports.id, "
        . "ciniki_business_reports.title, "
        . "ciniki_business_reports.frequency, "
        . "ciniki_business_reports.flags, "
        . "ciniki_business_reports.next_date, "
        . "ciniki_business_reports.next_date AS next_time "
        . "FROM ciniki_business_reports "
        . "WHERE ciniki_business_reports.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "AND ciniki_business_reports.id = '" . ciniki_core_dbQuote($ciniki, $args['report_id']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.businesses', array(
        array('container'=>'reports', 'fname'=>'id', 
            'fields'=>array('id', 'title', 'frequency', 'flags', 'next_date', 'next_time'),
            'utctotz'=>array(
                'next_date'=>array('format'=>'Y-m-d', 'timezone'=>$intl_timezone),
                'next_time'=>array('format'=>'H:i:s', 'timezone'=>$intl_timezone),
                ),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.businesses.105', 'msg'=>'Reports not found', 'err'=>$rc['err']));
    }
    if( !isset($rc['reports'][0]) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.businesses.106', 'msg'=>'Report not found'));
    }
    $report = $rc['reports'][0];

    //
    // Check if date or time was passed
    //
    if( isset($args['next_date']) || isset($args['next_time']) ) {
        $date_str = '';
        if( isset($args['next_date']) ) {
            $date_str = $args['next_date'];
        } else {
            $date_str = $report['next_date'];
        }
        if( isset($args['next_time']) ) {
            $date_str = ' ' . $args['next_time'];
        } else {
            $date_str = ' ' . $report['next_time'];
        }
        $ts = strtotime($date_str);
        if( $ts === FALSE || $ts < 1 ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.businesses.112', 'msg'=>'Invalid date or time format'));
        }
        $dt = new DateTime("@" . $ts, new DateTimezone($intl_timezone));
        $args['next_date'] = $dt->format('Y-m-d H:i:s');
    }

    //
    // Get the list of available blocks
    //
    $available_blocks = array();
    foreach($ciniki['business']['modules'] as $module) {
        //
        // Check if the module has a hook for businessReportBlocks
        //
        $rc = ciniki_core_loadMethod($ciniki, $module['package'], $module['module'], 'hooks', 'businessReportBlocks');
        if( $rc['stat'] == 'ok' ) {
            $fn = $rc['function_call'];
            $rc = $fn($ciniki, $args['business_id'], array());
            if( $rc['stat'] == 'ok' ) {
                $available_blocks = array_merge($available_blocks, $rc['blocks']);
            }
        }
    }

    //
    // Get the list of existing blocks to compare with new later
    //
    $strsql = "SELECT id, btype, title, sequence, block_ref, options "
        . "FROM ciniki_business_report_blocks "
        . "WHERE report_id = '" . ciniki_core_dbQuote($ciniki, $args['report_id']) . "' "
        . "AND business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "ORDER BY sequence "
        . "";
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.businesses', array(
        array('container'=>'blocks', 'fname'=>'id', 'fields'=>array('id', 'btype', 'title', 'sequence', 'block_ref', 'options')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $existing_blocks = array();
    if( isset($rc['blocks']) ) {
        $existing_blocks = $rc['blocks'];
    }

    //
    // Start transaction
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.businesses');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Update the Reports in the database
    //
    $rc = ciniki_core_objectUpdate($ciniki, $args['business_id'], 'ciniki.businesses.report', $args['report_id'], $args, 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.businesses');
        return $rc;
    }

    //
    // Check if the user_ids are updated
    //
    if( isset($args['user_ids']) ) {
        //
        // Get the list of business users
        //
        $strsql = "SELECT id "
            . "FROM ciniki_business_users "
            . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . "AND status = 10 "
            . "";
        $rc = ciniki_core_dbQueryList($ciniki, $strsql, 'ciniki.businesses', 'users', 'id');
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.businesses');
            return $rc;
        }
        $users = array();
        if( isset($rc['users']) ) {
            $users = $rc['users'];
        }

        //
        // Get the current list of users
        //
        $strsql = "SELECT id, uuid, user_id "
            . "FROM ciniki_business_report_users "
            . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . "AND report_id = '" . ciniki_core_dbQuote($ciniki, $args['report_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
        $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.businesses', array(
            array('container'=>'users', 'fname'=>'user_id', 'fields'=>array('id', 'uuid', 'user_id')),
            ));
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.businesses');
            return $rc;
        }
        $report_users = array();
        if( isset($rc['users']) ) {
            $report_user_ids = array_keys($rc['users']);
            $report_users = $rc['users'];
        }

        //
        // Check for users that need to be added
        //
        foreach($args['user_ids'] as $user_id) {
            if( !in_array($user_id, $report_user_ids) ) {
                $rc = ciniki_core_objectAdd($ciniki, $args['business_id'], 'ciniki.businesses.reportuser', array(
                    'report_id'=>$args['report_id'],
                    'user_id'=>$user_id, 
                    ), 0x04);
                if( $rc['stat'] != 'ok' ) {
                    ciniki_core_dbTransactionRollback($ciniki, 'ciniki.businesses');
                    return $rc;
                }
            }
        }

        //
        // Check for users need to be removed
        //
        foreach($report_users as $user_id => $user) {
            if( !in_array($user_id, $args['user_ids']) ) {
                $rc = ciniki_core_objectDelete($ciniki, $args['business_id'], 'ciniki.businesses.reportuser', $user['id'], $user['uuid'], 0x04);
                if( $rc['stat'] != 'ok' ) {
                    ciniki_core_dbTransactionRollback($ciniki, 'ciniki.businesses');
                    return $rc;
                }
            }
        }
    }

    //
    // Check if block options need updating
    //
    foreach($existing_blocks as $bid => $block) {
        if( !isset($available_blocks[$block['block_ref']]) ) {
            continue;
        }
        $b = $available_blocks[$block['block_ref']];
        $values = unserialize($block['options']);
        foreach($b['options'] as $oid => $option) {
            //
            // Make sure at least the default value exists in the values array
            //
            if( !isset($values[$oid]) ) {
                $values[$oid] = $option['default'];
            }
            if( isset($ciniki['request']['args']['block_' . $block['id'] . '_' . $oid]) ) {
                $values[$oid] = $ciniki['request']['args']['block_' . $block['id'] . '_' . $oid];
            }
        }
        $new_options = serialize($values);
        $update_args = array();
        if( $new_options != $block['options'] ) {
            $update_args['options'] = $new_options;
        }
        //
        // Check for new title or sequence
        //
        if( isset($ciniki['request']['args']['block_' . $block['id'] . '_title']) 
            && $ciniki['request']['args']['block_' . $block['id'] . '_title'] != $block['title'] 
            ) {
            $update_args['title'] = $ciniki['request']['args']['block_' . $block['id'] . '_title'];
        }
        if( isset($ciniki['request']['args']['block_' . $block['id'] . '_sequence']) 
            && $ciniki['request']['args']['block_' . $block['id'] . '_sequence'] != $block['sequence'] 
            ) {
            $update_args['sequence'] = $ciniki['request']['args']['block_' . $block['id'] . '_sequence'];
        }
        if( count($update_args) > 0 ) {
            $rc = ciniki_core_objectUpdate($ciniki, $args['business_id'], 'ciniki.businesses.reportblock', $block['id'], $update_args, 0x04);
            if( $rc['stat'] != 'ok' ) {
                ciniki_core_dbTransactionRollback($ciniki, 'ciniki.businesses');
                return $rc;
            }
        }
    }

    //
    // Commit the transaction
    //
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.businesses');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Update the last_change date in the business modules
    // Ignore the result, as we don't want to stop user updates if this fails.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'updateModuleChangeDate');
    ciniki_businesses_updateModuleChangeDate($ciniki, $args['business_id'], 'ciniki', 'businesses');

    //
    // Update the web index if enabled
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'hookExec');
    ciniki_core_hookExec($ciniki, $args['business_id'], 'ciniki', 'web', 'indexObject', array('object'=>'ciniki.businesses.report', 'object_id'=>$args['report_id']));

    return array('stat'=>'ok');
}
?>
