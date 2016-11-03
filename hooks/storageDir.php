<?php
//
// Description
// -----------
// This function will return the storage directory for the business.  This is used
// by the ciniki.images module to store storaged images.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:         The ID of the business to get the details for.
// keys:                The comma delimited list of keys to lookup values for.
//
// Returns
// -------
// <details>
//      <business name='' tagline='' />
// </details>
//
function ciniki_businesses_hooks_storageDir(&$ciniki, $business_id, $args) {
    $rsp = array('stat'=>'ok', 'details'=>array());

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');

    //
    // Determine the business_id
    //
    if( $business_id == 0 ) {
        $storage_dir = $ciniki['config']['ciniki.core']['storage_dir'] 
            . '/0/0' ;
    }
    elseif( isset($ciniki['business']['settings']['storage_dir']) ) {
        return array('stat'=>'ok', 'storage_dir'=>$ciniki['business']['settings']['storage_dir']);
    }
    elseif( $business_id > 0 ) {
        $strsql = "SELECT uuid "
            . "FROM ciniki_businesses "
            . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' ";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.businesses', 'business');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( !isset($rc['business']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.businesses.7', 'msg'=>'Unable to get business details'));
        }

        $business_uuid = $rc['business']['uuid'];

        $storage_dir = $ciniki['config']['ciniki.core']['storage_dir'] . '/' 
            . $business_uuid[0] . '/' . $business_uuid;

        //
        // Save settings in $ciniki storage for faster access
        //
        if( !isset($ciniki['business']) ) {
            $ciniki['business'] = array('settings'=>array('storage_dir'=>$storage_dir));
        } 
        elseif( !isset($ciniki['business']['settings']) ) {
            $ciniki['business']['settings'] = array('storage_dir'=>$storage_dir);
        } 
        else {
            $ciniki['business']['settings']['storage_dir'] = $storage_dir;
        }
    } else {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.businesses.8', 'msg'=>'Unable to get business storage directory'));
    }

    return array('stat'=>'ok', 'storage_dir'=>$storage_dir);
}
?>
