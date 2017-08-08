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
function ciniki_businesses_objects($ciniki) {
    
    $objects = array();
    $objects['report'] = array(
        'name'=>'Reports',
        'sync'=>'yes',
        'table'=>'ciniki_business_reports',
        'o_name'=>'report',
        'o_container'=>'reports',
        'fields'=>array(
            'title'=>array('name'=>'Title'),
            'frequency'=>array('name'=>'Frequency'),
            'flags'=>array('name'=>'Options', 'default'=>0x03),
            'next_date'=>array('name'=>'Next Date'),
            ),
        'history_table'=>'ciniki_business_history',
        );
    $objects['reportuser'] = array(
        'name'=>'Report Users',
        'sync'=>'yes',
        'table'=>'ciniki_business_report_users',
        'o_name'=>'reportuser',
        'o_container'=>'reportusers',
        'fields'=>array(
            'report_id'=>array('name'=>'Report', 'ref'=>'ciniki.businesses.report'),
            'user_id'=>array('name'=>'User', 'ref'=>'ciniki.users.user'),
            ),
        'history_table'=>'ciniki_business_history',
        );
    $objects['reportblock'] = array(
        'name'=>'Report Blocks',
        'sync'=>'yes',
        'table'=>'ciniki_business_report_blocks',
        'o_name'=>'reportblock',
        'o_container'=>'reportblock',
        'fields'=>array(
            'report_id'=>array('name'=>'Report', 'ref'=>'ciniki.businesses.report'),
            'btype'=>array('name'=>'Block Type'),
            'title'=>array('name'=>'Title', 'default'=>''),
            'sequence'=>array('name'=>'Order'),
            'block_ref'=>array('name'=>'Block'),
            'options'=>array('name'=>'Options', 'default'=>''),
            ),
        'history_table'=>'ciniki_business_history',
        );
    
    return array('stat'=>'ok', 'objects'=>$objects);
}
?>
