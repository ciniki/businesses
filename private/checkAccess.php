<?php
//
// Description
// -----------
// This function will check to see the requesting user has access
// to both the businesses module and requested method.
//
// *note* The method is not currently tested, just sysadmin or business owner.
//
// Info
// ----
// Status: beta
//
// Arguments
// ---------
// ciniki:
// business_id:         The business ID to check the session user against.
// method:              The method requested.
//
// Returns
// -------
// <rsp stat='ok' />
//
function ciniki_businesses_checkAccess(&$ciniki, $business_id, $method) {
    //
    // Get the list of modules
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'checkModuleAccess');
    $rc = ciniki_businesses_checkModuleAccess($ciniki, $business_id, 'ciniki', 'businesses');
    // Ignore if businesses module is not in the list, it's on by default
    if( $rc['stat'] != 'ok' && $rc['err']['code'] != 'ciniki.businesses.16' ) {
        return $rc;
    }
    // Normally there is a check here to see if permissions denied, but not used in this case
    // just want to get the modules.
    $modules = $rc['modules'];

    //
    // Get the list of permission_groups the user is a part of
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    $strsql = "SELECT permission_group "
        . "FROM ciniki_business_users "
        . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
        . "AND user_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "' "
        . "AND package = 'ciniki' "
        . "AND status = 10 "    // Active user
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQueryList');
    $rc = ciniki_core_dbQueryList($ciniki, $strsql, 'ciniki.businesses', 'groups', 'permission_group');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['groups']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.businesses.12', 'msg'=>'Access denied'));
    }
    $groups = $rc['groups'];

    //
    // Sysadmins are allowed full access
    //
    if( ($ciniki['session']['user']['perms'] & 0x01) == 0x01 ) {
        return array('stat'=>'ok', 'modules'=>$modules, 'groups'=>$groups);
    }

    //
    // The following functions don't require any checks, any authenticated user can access them
    //
    if( $method == 'ciniki.businesses.getUserBusinesses' 
        || $method == 'ciniki.businesses.getUserModules' 
        ) {
        return array('stat'=>'ok', 'modules'=>$modules, 'groups'=>$groups);
    }

    //
    // If no business is specified, all functions are for sysadmin only
    //
    if( $business_id == 0 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.businesses.13', 'msg'=>'Access denied'));
    }

    //
    // The following methods are only available to business owners, no employees
    //
    $owner_methods = array(
        'ciniki.businesses.userList',
        'ciniki.businesses.userAdd',
        'ciniki.businesses.userRemove',
        'ciniki.businesses.userDetails',
        'ciniki.businesses.userUpdateDetails',
        'ciniki.businesses.backupList',
        'ciniki.businesses.backupDownload',
        'ciniki.businesses.getDetailHistory',
        'ciniki.businesses.getDetails',
        'ciniki.businesses.getModuleHistory',
        'ciniki.businesses.getModuleRulesetHistory',
        'ciniki.businesses.getModuleRulesets',
        'ciniki.businesses.getModules',
        'ciniki.businesses.getUserSettings',
        'ciniki.businesses.getOwners',
        'ciniki.businesses.employees',
        'ciniki.businesses.updateDetails',
        'ciniki.businesses.updateModuleRulesets',
        'ciniki.businesses.subscriptionInfo',
        'ciniki.businesses.subscriptionChangeCurrency',
        'ciniki.businesses.subscriptionCustomerUpdate',
        'ciniki.businesses.subscriptionCancel',
        'ciniki.businesses.settingsIntlGet',
        'ciniki.businesses.settingsIntlUpdate',
        'ciniki.businesses.settingsAPIsGet',
        'ciniki.businesses.settingsAPIsUpdate',
        'ciniki.businesses.subscriptionStripeProcess',
        );
    if( in_array($method, $owner_methods) && in_array('owners', $groups) ) {
        return array('stat'=>'ok', 'modules'=>$modules, 'groups'=>$groups);
    }

    //
    // The following methods are available to resellers
    //
    $reseller_methods = array(
        'ciniki.businesses.getModules',
        'ciniki.businesses.updateModules',
        'ciniki.businesses.getModuleFlags',
        'ciniki.businesses.updateModuleFlags',
        'ciniki.businesses.domainAdd',
        'ciniki.businesses.domainGet',
        'ciniki.businesses.domainList',
        'ciniki.businesses.domainUpdate',
        );
    if( (in_array($method, $owner_methods) || in_array($method, $reseller_methods))
        && in_array('resellers', $groups) 
        ) {
        return array('stat'=>'ok', 'modules'=>$modules, 'groups'=>$groups);
    }

    //
    // Limit the functions the business owner has access to.  Any
    // other methods will be denied access.
    //
    $employee_methods = array(
        'ciniki.businesses.getDetailHistory',
        'ciniki.businesses.getDetails',
        'ciniki.businesses.getModuleHistory',
        'ciniki.businesses.getModuleRulesetHistory',
        'ciniki.businesses.getModuleRulesets',
        'ciniki.businesses.getModules',
        'ciniki.businesses.getUserSettings',
        'ciniki.businesses.getOwners',
        'ciniki.businesses.employees',
        'ciniki.businesses.updateDetails',
        'ciniki.businesses.updateModuleRulesets',
        'ciniki.businesses.subscriptionInfo',
        'ciniki.businesses.subscriptionChangeCurrency',
        'ciniki.businesses.subscriptionCancel',
        'ciniki.businesses.settingsIntlGet',
        'ciniki.businesses.settingsIntlUpdate',
        );
    if( in_array($method, $employee_methods) && in_array('employees', $groups) ) {
        return array('stat'=>'ok', 'modules'=>$modules, 'groups'=>$groups);
    }

    $salesreps_methods = array(
        'ciniki.businesses.getUserSettings',
        );
    if( in_array($method, $salesreps_methods) && in_array('salesreps', $groups) ) {
        return array('stat'=>'ok', 'modules'=>$modules, 'groups'=>$groups);
    }

    //
    // By default fail
    //
    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.businesses.14', 'msg'=>'Access denied'));
}
?>
