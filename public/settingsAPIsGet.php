<?php
//
// Description
// -----------
// This method will return the intl settings for the tenant.  These are 
// used to set the locale, currency and timezone of the tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant to get the intl settings for.
//
// Returns
// -------
// <settings intl-default-locale="en_US"
//
function ciniki_tenants_settingsAPIsGet($ciniki) {
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
    // Check access to tnid as owner, or sys admin
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'checkAccess');
    $rc = ciniki_tenants_checkAccess($ciniki, $args['tnid'], 'ciniki.tenants.settingsAPIsGet');
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
    if( (isset($ciniki['tenant']['modules']['ciniki.directory']['flags']) && ($ciniki['tenant']['modules']['ciniki.directory']['flags']&0x01) > 0)
        || (isset($ciniki['tenant']['modules']['ciniki.artistprofiles']['flags']) && ($ciniki['tenant']['modules']['ciniki.artistprofiles']['flags']&0x01) > 0)
        ) {
        $csrf = base64_encode(openssl_random_pseudo_bytes(18));
        $_SESSION['dropbox-csrf'] = $csrf;
//      $_SESSION['tnid'] = $ciniki['request']['tnid'];
        $rsp['apis']['dropbox'] = array('setup'=>'no', 'name'=>'Dropbox', 
            'appkey'=>$ciniki['config']['ciniki.core']['dropbox.appkey'],
            'redirect'=>$ciniki['config']['ciniki.core']['dropbox.redirect'],
            'csrf'=>$csrf,
            );
    }

    $rc = ciniki_core_dbDetailsQueryDash($ciniki, 'ciniki_tenant_details', 'tnid', $args['tnid'], 'ciniki.tenants', 'details', 'apis');
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
