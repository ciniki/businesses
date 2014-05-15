<?php
//
// Description
// -----------
// This method will return the list of backups available for a business.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id: 			The ID of the business to lock.
//
// Returns
// -------
//
function ciniki_businesses_backupList($ciniki) {
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
	// Check access 
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'checkAccess');
	$rc = ciniki_businesses_checkAccess($ciniki, $args['business_id'], 'ciniki.businesses.backupList');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'intlSettings');
	$rc = ciniki_businesses_intlSettings($ciniki, $args['business_id']);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$intl_timezone = $rc['settings']['intl-default-timezone'];

	//
	// Get the list of backups for this business
	//
	$strsql = "SELECT uuid "
		. "FROM ciniki_businesses "
		. "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "";
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.businesses', 'business');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['business']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1728', 'msg'=>'Unable to find business'));
	}
	$uuid = $rc['business']['uuid'];

	$backup_dir = $ciniki['config']['ciniki.core']['backup_dir'] 
		. '/' . $uuid[0] . '/' . $uuid;

	$backups = array();
	if( ($dh = opendir($backup_dir)) === false ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1735', 'msg'=>'Unable to find backups'));
		
	}
	while( ($file = readdir($dh)) !== false ) {
		if( preg_match("/^backup-(([0-9][0-9][0-9][0-9])([0-9][0-9])([0-9][0-9])-([0-9][0-9])([0-9][0-9])).zip$/", $file, $matches) ) {
			// Date on the file is UTC
			$backup_time = strtotime($matches[1]);
			$backup_date = new DateTime($matches[2] . '-' . $matches[3] . '-' . $matches[4] . ' ' . $matches[5] . '.' . $matches[6] . '.00', new DateTimeZone('UTC'));
			$backup_date->setTimezone(new DateTimeZone($intl_timezone));
			$backups[] = array('backup'=>array(
				'id'=>$file,
				'name'=>$backup_date->format('M j, Y g:i a'),
				));
		}
	}
	closedir($dh);

	return array('stat'=>'ok', 'backups'=>$backups);
}
?>
