<?php
//
// Description
// -----------
// This function will get detail values for a business.  These values
// are used many places in the API and Manage.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:             The ID of the user to get the details for.
// keys:                The comma delimited list of keys to lookup values for.
//
// Returns
// -------
// <details>
//      <user firstname='' lastname='' display_name=''/>
//      <settings date_format='' />
// </details>
//
function ciniki_businesses_get($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];
    
    //
    // Check access, should only be accessible by sysadmin
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'checkAccess');
    $rc = ciniki_businesses_checkAccess($ciniki, $args['id'], 'ciniki.businesses.get');
    // Ignore if business is suspended or deleted, should still return info to sysadmin
    if( $rc['stat'] != 'ok' && $rc['err']['code'] != '691' && $rc['err']['code'] != '692' ) {
        return $rc;
    }

    //
    // Make sure a sysadmin.  This check needs to be here because we ignore suspended/deleted 
    // businesses above, which then doesn't check perms.  Only sysadmins have access
    // to this method.
    //
    if( ($ciniki['session']['user']['perms'] & 0x01) != 0x01 ) {
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1922', 'msg'=>'Permission denied'));
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDetailsQuery');

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'datetimeFormat');
    $date_format = ciniki_users_datetimeFormat($ciniki);

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'timezoneOffset');
    $utc_offset = ciniki_users_timezoneOffset($ciniki);

    //
    // Get all the information form ciniki_users table
    //
    $strsql = "SELECT ciniki_businesses.id, ciniki_businesses.uuid, ciniki_businesses.name, ciniki_businesses.category, ciniki_businesses.status AS business_status, "
        . "DATE_FORMAT(ciniki_businesses.date_added, '" . ciniki_core_dbQuote($ciniki, $date_format) . "') AS date_added, "
        . "DATE_FORMAT(ciniki_businesses.last_updated, '" . ciniki_core_dbQuote($ciniki, $date_format) . "') AS last_updated, "
        . "ciniki_business_subscriptions.status AS subscription_status, signup_date, trial_days, "
        . "currency, monthly, paypal_subscr_id, paypal_payer_email, paypal_payer_id, paypal_amount, "
        . "IF(last_payment_date='0000-00-00', '', DATE_FORMAT(CONVERT_TZ(ciniki_business_subscriptions.date_added, '+00:00', '" . ciniki_core_dbQuote($ciniki, $utc_offset) . "'), '%b %e, %Y %l:%i %p')) AS last_payment_date, "
        . "trial_days - FLOOR((UNIX_TIMESTAMP(UTC_TIMESTAMP())-UNIX_TIMESTAMP(ciniki_business_subscriptions.signup_date))/86400) AS trial_remaining "
        . "FROM ciniki_businesses "
        . "LEFT JOIN ciniki_business_subscriptions ON (ciniki_businesses.id = ciniki_business_subscriptions.business_id) "
        . "WHERE ciniki_businesses.id = '" . ciniki_core_dbQuote($ciniki, $args['id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.businesses', 'business');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['business']) ) {
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'513', 'msg'=>'Unable to find business'));
    }
    $business = $rc['business'];

    if( !isset($business['monthly']) ) {
        $business['monthly'] = 0;
    }
    if( !isset($business['trial_remaining']) ) {
        $business['trial_remaining'] = 0;
    }

    //
    // Map subscription status
    //
    if( !isset($business['subscription_status_text']) ) {
        $business['subscription_status_text'] = 'None';
    } elseif( $business['subscription_status'] == 0 ) {
        $business['subscription_status_text'] = 'Unknown';
    } elseif( $business['subscription_status'] == 1 ) {
        $business['subscription_status_text'] = 'Update required';
    } elseif( $business['subscription_status'] == 2 ) {
        $business['subscription_status_text'] = 'Payment information required';
    } elseif( $business['subscription_status'] == 10 ) {
        $business['subscription_status_text'] = 'Active';
    } elseif( $business['subscription_status'] == 50 ) {
        $business['subscription_status_text'] = 'Suspended';
    } elseif( $business['subscription_status'] == 60 ) {
        $business['subscription_status_text'] = 'Cancelled';
    } elseif( $business['subscription_status'] == 61 ) {
        $business['subscription_status_text'] = 'Pending Cancel';
    }

    //
    // Get all the users that are a part of the business
    //
    $strsql = "SELECT ciniki_users.id, ciniki_users.firstname, ciniki_users.lastname "
        . "FROM ciniki_business_users, ciniki_users "
        . "WHERE ciniki_business_users.business_id = '" . ciniki_core_dbQuote($ciniki, $args['id']) . "' "
        . "AND ciniki_business_users.user_id = ciniki_users.id "
        . "AND ciniki_business_users.status = 10 "
        . "";
    $rc = ciniki_core_dbRspQuery($ciniki, $strsql, 'ciniki.businesses', 'users', 'user', array('stat'=>'ok', 'users'=>array()));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $business['users'] = $rc['users'];

    return array('stat'=>'ok', 'business'=>$business);
}
?>
