<?php
//
// Description
// -----------
// This function will get detail values for a tenant.  These values
// are used many places in the API and Manage.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:             The ID of the user to get the details for.
// keys:                The comma delimited list of keys to lookup values for.
//
// Returns
// -------
// <details>
//      <user firstname='' lastname='' display_name=''/>
//      <settings date_format='' />
// </details>
//
function ciniki_tenants_get($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];
    
    //
    // Check access, should only be accessible by sysadmin
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'checkAccess');
    $rc = ciniki_tenants_checkAccess($ciniki, $args['id'], 'ciniki.tenants.get');
    // Ignore if tenant is suspended or deleted, should still return info to sysadmin
    if( $rc['stat'] != 'ok' && $rc['err']['code'] != 'ciniki.tenants.17' && $rc['err']['code'] != 'ciniki.tenants.18' ) {
        return $rc;
    }

    //
    // Make sure a sysadmin.  This check needs to be here because we ignore suspended/deleted 
    // tenants above, which then doesn't check perms.  Only sysadmins have access
    // to this method.
    //
    if( ($ciniki['session']['user']['perms'] & 0x01) != 0x01 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.tenants.44', 'msg'=>'Permission denied'));
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
    $strsql = "SELECT ciniki_tenants.id, ciniki_tenants.uuid, ciniki_tenants.name, ciniki_tenants.category, ciniki_tenants.status AS tenant_status, "
        . "DATE_FORMAT(ciniki_tenants.date_added, '" . ciniki_core_dbQuote($ciniki, $date_format) . "') AS date_added, "
        . "DATE_FORMAT(ciniki_tenants.last_updated, '" . ciniki_core_dbQuote($ciniki, $date_format) . "') AS last_updated, "
        . "ciniki_tenant_subscriptions.status AS subscription_status, signup_date, trial_days, "
        . "currency, monthly, paypal_subscr_id, paypal_payer_email, paypal_payer_id, paypal_amount, "
        . "IF(last_payment_date='0000-00-00', '', DATE_FORMAT(CONVERT_TZ(ciniki_tenant_subscriptions.date_added, '+00:00', '" . ciniki_core_dbQuote($ciniki, $utc_offset) . "'), '%b %e, %Y %l:%i %p')) AS last_payment_date, "
        . "trial_days - FLOOR((UNIX_TIMESTAMP(UTC_TIMESTAMP())-UNIX_TIMESTAMP(ciniki_tenant_subscriptions.signup_date))/86400) AS trial_remaining "
        . "FROM ciniki_tenants "
        . "LEFT JOIN ciniki_tenant_subscriptions ON (ciniki_tenants.id = ciniki_tenant_subscriptions.tnid) "
        . "WHERE ciniki_tenants.id = '" . ciniki_core_dbQuote($ciniki, $args['id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.tenants', 'tenant');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['tenant']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.tenants.45', 'msg'=>'Unable to find tenant'));
    }
    $tenant = $rc['tenant'];

    if( !isset($tenant['monthly']) ) {
        $tenant['monthly'] = 0;
    }
    if( !isset($tenant['trial_remaining']) ) {
        $tenant['trial_remaining'] = 0;
    }

    //
    // Map subscription status
    //
    if( !isset($tenant['subscription_status_text']) ) {
        $tenant['subscription_status_text'] = 'None';
    } elseif( $tenant['subscription_status'] == 0 ) {
        $tenant['subscription_status_text'] = 'Unknown';
    } elseif( $tenant['subscription_status'] == 1 ) {
        $tenant['subscription_status_text'] = 'Update required';
    } elseif( $tenant['subscription_status'] == 2 ) {
        $tenant['subscription_status_text'] = 'Payment information required';
    } elseif( $tenant['subscription_status'] == 10 ) {
        $tenant['subscription_status_text'] = 'Active';
    } elseif( $tenant['subscription_status'] == 50 ) {
        $tenant['subscription_status_text'] = 'Suspended';
    } elseif( $tenant['subscription_status'] == 60 ) {
        $tenant['subscription_status_text'] = 'Cancelled';
    } elseif( $tenant['subscription_status'] == 61 ) {
        $tenant['subscription_status_text'] = 'Pending Cancel';
    }

    //
    // Get all the users that are a part of the tenant
    //
    $strsql = "SELECT ciniki_users.id, ciniki_users.firstname, ciniki_users.lastname "
        . "FROM ciniki_tenant_users, ciniki_users "
        . "WHERE ciniki_tenant_users.tnid = '" . ciniki_core_dbQuote($ciniki, $args['id']) . "' "
        . "AND ciniki_tenant_users.user_id = ciniki_users.id "
        . "AND ciniki_tenant_users.status = 10 "
        . "";
    $rc = ciniki_core_dbRspQuery($ciniki, $strsql, 'ciniki.tenants', 'users', 'user', array('stat'=>'ok', 'users'=>array()));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $tenant['users'] = $rc['users'];

    return array('stat'=>'ok', 'tenant'=>$tenant);
}
?>
