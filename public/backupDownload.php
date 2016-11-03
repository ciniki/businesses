<?php
//
// Description
// ===========
// This method will allow the business owner to download a backup of their business.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:     The ID of the business the requested backup belongs to.
// backup_id:       The ID of the backup to be downloaded.
//
// Returns
// -------
// Binary file.
//
function ciniki_businesses_backupDownload($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'backup_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Backup'), 
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
    $rc = ciniki_businesses_checkAccess($ciniki, $args['business_id'], 'ciniki.businesses.backupDownload'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    if( !preg_match("/^backup-[0-9][0-9][0-9][0-9][0-9][0-9][0-9][0-9]-[0-9][0-9][0-9][0-9].zip$/", $args['backup_id']) ) {
        error_log('-' . $args['backup_id'] . '-');
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.businesses.35', 'msg'=>'Invalid backup file'));
    }

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
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.businesses.36', 'msg'=>'Unable to find business'));
    }
    $uuid = $rc['business']['uuid'];

    $backup_file = $ciniki['config']['ciniki.core']['backup_dir'] 
        . '/' . $uuid[0] . '/' . $uuid . '/' . $args['backup_id'];

    //
    // Check the file exists
    //
    if( !is_file($backup_file) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.businesses.37', 'msg'=>'Backup does not exist'));
    }

    header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); 
    header("Last-Modified: " . gmdate("D,d M YH:i:s") . " GMT"); 
    header('Cache-Control: no-cache, must-revalidate');
    header('Pragma: no-cache');
    // Set mime header
    $finfo = finfo_open(FILEINFO_MIME);
    if( $finfo ) { header('Content-Type: ' . finfo_file($finfo, $backup_file)); }
    // Specify Filename
    header('Content-Disposition: attachment;filename="' . $args['backup_id'] . '"');
    header('Content-Length: ' . filesize($backup_file));
    header('Cache-Control: max-age=0');

    error_log('Downloading: ' . $backup_file);
    $fp = fopen($backup_file, 'rb');
    fpassthru($fp);

    return array('stat'=>'binary');
}
?>
