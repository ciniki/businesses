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
function ciniki_businesses_flags($ciniki, $modules) {
	$flags = array(
		array('flag'=>array('bit'=>'1', 'name'=>'Employees', 'group'=>'ciniki.employees')),
		array('flag'=>array('bit'=>'2', 'name'=>'Sales Reps', 'group'=>'ciniki.salesreps')),
		array('flag'=>array('bit'=>'3', 'name'=>'Warehouse', 'group'=>'ciniki.warehouse')),
		array('flag'=>array('bit'=>'4', 'name'=>'Marketing', 'group'=>'ciniki.marketing')),
//		array('flag'=>array('bit'=>'5', 'name'=>'')),
//		array('flag'=>array('bit'=>'6', 'name'=>'')),
//		array('flag'=>array('bit'=>'7', 'name'=>'')),
//		array('flag'=>array('bit'=>'8', 'name'=>'')),
//		array('flag'=>array('bit'=>'9', 'name'=>'')),
//		array('flag'=>array('bit'=>'10', 'name'=>'')),
//		array('flag'=>array('bit'=>'11', 'name'=>'')),
//		array('flag'=>array('bit'=>'12', 'name'=>'')),
//		array('flag'=>array('bit'=>'13', 'name'=>'')),
//		array('flag'=>array('bit'=>'14', 'name'=>'')),
//		array('flag'=>array('bit'=>'15', 'name'=>'')),
//		array('flag'=>array('bit'=>'16', 'name'=>'')),
		// 0x010000
		array('flag'=>array('bit'=>'17', 'name'=>'External ID')),
//		array('flag'=>array('bit'=>'18', 'name'=>'')),
//		array('flag'=>array('bit'=>'19', 'name'=>'')),
//		array('flag'=>array('bit'=>'20', 'name'=>'')),
		);

	return array('stat'=>'ok', 'flags'=>$flags);
}
?>
