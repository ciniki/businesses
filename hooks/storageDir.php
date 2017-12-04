<?php
//
// Description
// -----------
// This function will return the storage directory for the tenant.  This is used
// by the ciniki.images module to store storaged images.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant to get the details for.
// keys:                The comma delimited list of keys to lookup values for.
//
// Returns
// -------
// <details>
//      <tenant name='' tagline='' />
// </details>
//
function ciniki_tenants_hooks_storageDir(&$ciniki, $tnid, $args) {
    $rsp = array('stat'=>'ok', 'details'=>array());

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');

    //
    // Determine the tnid
    //
    if( $tnid == 0 ) {
        $storage_dir = $ciniki['config']['ciniki.core']['storage_dir'] 
            . '/0/0' ;
    }
    elseif( isset($ciniki['tenant']['settings']['storage_dir']) ) {
        return array('stat'=>'ok', 'storage_dir'=>$ciniki['tenant']['settings']['storage_dir']);
    }
    elseif( $tnid > 0 ) {
        $strsql = "SELECT uuid "
            . "FROM ciniki_tenants "
            . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' ";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.tenants', 'tenant');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( !isset($rc['tenant']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.tenants.7', 'msg'=>'Unable to get tenant details'));
        }

        $tenant_uuid = $rc['tenant']['uuid'];

        $storage_dir = $ciniki['config']['ciniki.core']['storage_dir'] . '/' 
            . $tenant_uuid[0] . '/' . $tenant_uuid;

        //
        // Save settings in $ciniki storage for faster access
        //
        if( !isset($ciniki['tenant']) ) {
            $ciniki['tenant'] = array('settings'=>array('storage_dir'=>$storage_dir));
        } 
        elseif( !isset($ciniki['tenant']['settings']) ) {
            $ciniki['tenant']['settings'] = array('storage_dir'=>$storage_dir);
        } 
        else {
            $ciniki['tenant']['settings']['storage_dir'] = $storage_dir;
        }
    } else {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.tenants.8', 'msg'=>'Unable to get tenant storage directory'));
    }

    return array('stat'=>'ok', 'storage_dir'=>$storage_dir);
}
?>
