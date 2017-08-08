<?php
//
// Description
// -----------
// The module maps
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_businesses_maps($ciniki) {
    $maps = array();
    $maps['report'] = array(
        'frequency'=>array(
            '10'=>'Weekly',
            '30'=>'Monthly',
        ),
    );

    return array('stat'=>'ok', 'maps'=>$maps);
}
?>
