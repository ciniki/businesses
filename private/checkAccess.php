<?php
//
// Description
// -----------
// This function will check to see the requesting user has access
// to both the tenants module and requested method.
//
// *note* The method is not currently tested, just sysadmin or tenant owner.
//
// Info
// ----
// Status: beta
//
// Arguments
// ---------
// ciniki:
// tnid:         The tenant ID to check the session user against.
// method:              The method requested.
//
// Returns
// -------
// <rsp stat='ok' />
//
function ciniki_tenants_checkAccess(&$ciniki, $tnid, $method) {
    //
    // Get the list of modules
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'checkModuleAccess');
    $rc = ciniki_tenants_checkModuleAccess($ciniki, $tnid, 'ciniki', 'tenants');
    // Ignore if tenants module is not in the list, it's on by default
    if( $rc['stat'] != 'ok' && $rc['err']['code'] != 'ciniki.tenants.16' ) {
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
        . "FROM ciniki_tenant_users "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND user_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "' "
        . "AND package = 'ciniki' "
        . "AND status = 10 "    // Active user
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQueryList');
    $rc = ciniki_core_dbQueryList($ciniki, $strsql, 'ciniki.tenants', 'groups', 'permission_group');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['groups']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.tenants.12', 'msg'=>'Access denied'));
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
    if( $method == 'ciniki.tenants.getUserTenants' 
        || $method == 'ciniki.tenants.getUserModules' 
        ) {
        return array('stat'=>'ok', 'modules'=>$modules, 'groups'=>$groups);
    }

    //
    // If no tenant is specified, all functions are for sysadmin only
    //
    if( $tnid == 0 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.tenants.13', 'msg'=>'Access denied'));
    }

    //
    // The following methods are only available to tenant owners, no employees
    //
    $owner_methods = array(
        'ciniki.tenants.userList',
        'ciniki.tenants.userAdd',
        'ciniki.tenants.userRemove',
        'ciniki.tenants.userDetails',
        'ciniki.tenants.userUpdateDetails',
        'ciniki.tenants.backupList',
        'ciniki.tenants.backupDownload',
        'ciniki.tenants.getDetailHistory',
        'ciniki.tenants.getDetails',
        'ciniki.tenants.getModuleHistory',
        'ciniki.tenants.getModuleRulesetHistory',
        'ciniki.tenants.getModuleRulesets',
        'ciniki.tenants.getModules',
        'ciniki.tenants.getUserSettings',
        'ciniki.tenants.getOwners',
        'ciniki.tenants.employees',
        'ciniki.tenants.updateDetails',
        'ciniki.tenants.updateModuleRulesets',
        'ciniki.tenants.subscriptionInfo',
        'ciniki.tenants.subscriptionChangeCurrency',
        'ciniki.tenants.subscriptionCustomerUpdate',
        'ciniki.tenants.subscriptionCancel',
        'ciniki.tenants.settingsIntlGet',
        'ciniki.tenants.settingsIntlUpdate',
        'ciniki.tenants.settingsAPIsGet',
        'ciniki.tenants.settingsAPIsUpdate',
        'ciniki.tenants.subscriptionStripeProcess',
        'ciniki.tenants.reportAdd',
        'ciniki.tenants.reportDelete',
        'ciniki.tenants.reportGet',
        'ciniki.tenants.reportList',
        'ciniki.tenants.reportUpdate',
        );
    if( in_array($method, $owner_methods) && in_array('owners', $groups) ) {
        return array('stat'=>'ok', 'modules'=>$modules, 'groups'=>$groups);
    }

    //
    // The following methods are available to resellers
    //
    $reseller_methods = array(
        'ciniki.tenants.getModules',
        'ciniki.tenants.updateModules',
        'ciniki.tenants.getModuleFlags',
        'ciniki.tenants.updateModuleFlags',
        'ciniki.tenants.domainAdd',
        'ciniki.tenants.domainGet',
        'ciniki.tenants.domainList',
        'ciniki.tenants.domainUpdate',
        );
    if( (in_array($method, $owner_methods) || in_array($method, $reseller_methods))
        && in_array('resellers', $groups) 
        ) {
        return array('stat'=>'ok', 'modules'=>$modules, 'groups'=>$groups);
    }

    //
    // Limit the functions the tenant owner has access to.  Any
    // other methods will be denied access.
    //
    $employee_methods = array(
        'ciniki.tenants.getDetailHistory',
        'ciniki.tenants.getDetails',
        'ciniki.tenants.getModuleHistory',
        'ciniki.tenants.getModuleRulesetHistory',
        'ciniki.tenants.getModuleRulesets',
        'ciniki.tenants.getModules',
        'ciniki.tenants.getUserSettings',
        'ciniki.tenants.getOwners',
        'ciniki.tenants.employees',
        'ciniki.tenants.updateDetails',
        'ciniki.tenants.updateModuleRulesets',
        'ciniki.tenants.subscriptionInfo',
        'ciniki.tenants.subscriptionChangeCurrency',
        'ciniki.tenants.subscriptionCancel',
        'ciniki.tenants.settingsIntlGet',
        'ciniki.tenants.settingsIntlUpdate',
        );
    if( in_array($method, $employee_methods) && in_array('employees', $groups) ) {
        return array('stat'=>'ok', 'modules'=>$modules, 'groups'=>$groups);
    }

    $salesreps_methods = array(
        'ciniki.tenants.getUserSettings',
        );
    if( in_array($method, $salesreps_methods) && in_array('salesreps', $groups) ) {
        return array('stat'=>'ok', 'modules'=>$modules, 'groups'=>$groups);
    }

    //
    // By default fail
    //
    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.tenants.14', 'msg'=>'Access denied'));
}
?>
