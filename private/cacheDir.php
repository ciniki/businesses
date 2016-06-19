<?php
//
// Description
// -----------
// This function will return the cache directory for the business.  This is used
// by the ciniki.images module to store cached images.
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
function ciniki_businesses_cacheDir(&$ciniki, $business_id) {
    $rsp = array('stat'=>'ok', 'details'=>array());

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');

    //
    // Determine the business_id
    //
    if( $business_id == 0 ) {
        $cache_dir = $ciniki['config']['ciniki.core']['cache_dir'] 
            . '/0/00000000-0000-0000-0000-000000000000' ;
    }
    elseif( isset($ciniki['business']['settings']['cache_dir']) ) {
        return array('stat'=>'ok', 'cache_dir'=>$ciniki['business']['settings']['cache_dir']);
    }
    elseif( $business_id > 0 ) {
        $strsql = "SELECT uuid FROM ciniki_businesses "
            . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' ";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.businesses', 'business');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( !isset($rc['business']) ) {
            return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1426', 'msg'=>'Unable to get business details'));
        }

        $business_uuid = $rc['business']['uuid'];

        $cache_dir = $ciniki['config']['ciniki.core']['cache_dir'] . '/' 
            . $business_uuid[0] . '/' . $business_uuid;

        //
        // Save settings in $ciniki cache for faster access
        //
        if( !isset($ciniki['business']) ) {
            $ciniki['business'] = array('settings'=>array('cache_dir'=>$cache_dir));
        } 
        elseif( !isset($ciniki['business']['settings']) ) {
            $ciniki['business']['settings'] = array('cache_dir'=>$cache_dir);
        } 
        else {
            $ciniki['business']['settings']['cache_dir'] = $cache_dir;
        }
    } else {
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1427', 'msg'=>'Unable to get business cache directory'));
    }

    return array('stat'=>'ok', 'cache_dir'=>$cache_dir);
}
?>
