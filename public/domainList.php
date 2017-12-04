<?php
//
// Description
// -----------
// This function will lookup the client domain in the database, and return the tenant id.
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_tenants_domainList($ciniki) {
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
    // Check access to tnid as owner
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'checkAccess');
    $ac = ciniki_tenants_checkAccess($ciniki, $args['tnid'], 'ciniki.tenants.domainList');
    if( $ac['stat'] != 'ok' ) {
        return $ac;
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki);

    //
    // Query the database for the domain
    //
    $strsql = "SELECT id, domain, flags, status, "
        . "IF((flags&0x01)=0x01, 'yes', 'no') AS isprimary, "
        . "IFNULL(DATE_FORMAT(expiry_date, '" . ciniki_core_dbQuote($ciniki, $date_format) . "'), 'expiry unknown') AS expiry_date, "
        . "status, managed_by "
        . "FROM ciniki_tenant_domains "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbRspQuery');
    return ciniki_core_dbRspQuery($ciniki, $strsql, 'ciniki.tenants', 'domains', 'domain', array('stat'=>'ok', 'domains'=>array()));
}
?>
