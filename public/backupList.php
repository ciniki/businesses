<?php
//
// Description
// -----------
// This method will return the list of backups available for a tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:             The ID of the tenant to lock.
//
// Returns
// -------
//
function ciniki_tenants_backupList($ciniki) {
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
    // Check access 
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'checkAccess');
    $rc = ciniki_tenants_checkAccess($ciniki, $args['tnid'], 'ciniki.tenants.backupList');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $args['tnid']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];

    //
    // Get the list of backups for this tenant
    //
    $strsql = "SELECT uuid "
        . "FROM ciniki_tenants "
        . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.tenants', 'tenant');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['tenant']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.tenants.38', 'msg'=>'Unable to find tenant'));
    }
    $uuid = $rc['tenant']['uuid'];

    $backup_dir = $ciniki['config']['ciniki.core']['backup_dir'] 
        . '/' . $uuid[0] . '/' . $uuid;

    $backups = array();
    if( ($dh = opendir($backup_dir)) === false ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.tenants.39', 'msg'=>'Unable to find backups'));
        
    }
    while( ($file = readdir($dh)) !== false ) {
        if( preg_match("/^backup-(([0-9][0-9][0-9][0-9])([0-9][0-9])([0-9][0-9])-([0-9][0-9])([0-9][0-9])).zip$/", $file, $matches) ) {
            // Date on the file is UTC
            $backup_time = strtotime($matches[1]);
            $backup_date = new DateTime($matches[2] . '-' . $matches[3] . '-' . $matches[4] . ' ' . $matches[5] . '.' . $matches[6] . '.00', new DateTimeZone('UTC'));
            $backup_date->setTimezone(new DateTimeZone($intl_timezone));
            $backups[] = array('backup'=>array(
                'id'=>$file,
                'ts'=>$backup_date->format('U'),
                'name'=>$backup_date->format('M j, Y g:i a'),
                ));
        }
    }
    closedir($dh);

    //
    // Sort the backup list in descending order so latest backup is at the top.
    //
    usort($backups, function($a, $b) {
        if( $a['backup']['ts'] == $b['backup']['ts'] ) {
            return 0;
        }
        return ($a['backup']['ts'] < $b['backup']['ts'])?1:-1;
        });

    return array('stat'=>'ok', 'backups'=>$backups);
}
?>
