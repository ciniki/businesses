<?php
//
// Description
// -----------
// This method will return the intl settings for the business.  These are 
// used to set the locale, currency and timezone of the business.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:         The ID of the business to get the intl settings for.
//
// Returns
// -------
// <settings intl-default-locale="en_US"
//
function ciniki_businesses_settingsAPIsGet($ciniki) {
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
    // Check access to business_id as owner, or sys admin
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'checkAccess');
    $rc = ciniki_businesses_checkAccess($ciniki, $args['business_id'], 'ciniki.businesses.settingsAPIsGet');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Setup default structure
    //
    $rsp = array('stat'=>'ok', 'apis'=>array());

    //
    // Check if dropbox should be allowed
    //
    if( (isset($ciniki['business']['modules']['ciniki.directory']['flags']) && ($ciniki['business']['modules']['ciniki.directory']['flags']&0x01) > 0)
        || (isset($ciniki['business']['modules']['ciniki.artistprofiles']['flags']) && ($ciniki['business']['modules']['ciniki.artistprofiles']['flags']&0x01) > 0)
        ) {
        $csrf = base64_encode(openssl_random_pseudo_bytes(18));
        $_SESSION['dropbox-csrf'] = $csrf;
//      $_SESSION['business_id'] = $ciniki['request']['business_id'];
        $rsp['apis']['dropbox'] = array('setup'=>'no', 'name'=>'Dropbox', 
            'appkey'=>$ciniki['config']['ciniki.core']['dropbox.appkey'],
            'redirect'=>$ciniki['config']['ciniki.core']['dropbox.redirect'],
            'csrf'=>$csrf,
            );
    }

    $rc = ciniki_core_dbDetailsQueryDash($ciniki, 'ciniki_business_details', 'business_id', $args['business_id'], 'ciniki.businesses', 'details', 'apis');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['details']['apis-dropbox-access-token']) 
        && $rc['details']['apis-dropbox-access-token'] != ''
        ) {
        $rsp['apis']['dropbox']['setup'] = 'yes';
    }

    return $rsp;
}
?>
