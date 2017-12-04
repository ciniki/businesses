<?php
//
// Description
// ===========
// This method will return the domain information for a tenant domain.
//
// Arguments
// ---------
// user_id:         The user making the request
// 
// Returns
// -------
//
function ciniki_tenants_domainGet($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'domain_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Domain'), 
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];
    
    //  
    // Make sure this module is activated, and
    // check permission to run this function for this tenant
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'checkAccess');
    $rc = ciniki_tenants_checkAccess($ciniki, $args['tnid'], 'ciniki.tenants.domainGet'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki);

    $strsql = "SELECT ciniki_tenant_domains.id, domain, flags, status, "
        . "DATE_FORMAT(expiry_date, '" . ciniki_core_dbQuote($ciniki, $date_format) . "') AS expiry_date, "
        . "managed_by, "
        . "date_added, last_updated "
        . "FROM ciniki_tenant_domains "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND ciniki_tenant_domains.id = '" . ciniki_core_dbQuote($ciniki, $args['domain_id']) . "' "
        . "";
    
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.tenants', 'domain');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['domain']) ) {
        return array('stat'=>'ok', 'err'=>array('code'=>'ciniki.tenants.42', 'msg'=>'Unable to find domain'));
    }

    return array('stat'=>'ok', 'domain'=>$rc['domain']);
}
?>
