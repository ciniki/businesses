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
function ciniki_tenants_domainExpiries($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'days'=>array('required'=>'no', 'blank'=>'no', 'default'=>'90', 'name'=>'Days'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'checkAccess');
    $ac = ciniki_tenants_checkAccess($ciniki, 0, 'ciniki.tenants.domainExpiries');
    if( $ac['stat'] != 'ok' ) {
        return $ac;
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki);

    //
    // Query the database for the domain
    //
    $strsql = "SELECT ciniki_tenant_domains.id, "
        . "ciniki_tenant_domains.tnid, "
        . "ciniki_tenants.name AS tenant_name, "
        . "ciniki_tenants.status AS tenant_status, "
        . "ciniki_tenant_domains.domain, "
        . "ciniki_tenant_domains.flags, "
        . "ciniki_tenant_domains.status, "
        . "ciniki_tenant_domains.status AS status_text, "
        . "IF((ciniki_tenant_domains.flags&0x01)=0x01, 'yes', 'no') AS isprimary, "
        . "IFNULL(DATE_FORMAT(ciniki_tenant_domains.expiry_date, '" . ciniki_core_dbQuote($ciniki, $date_format) . "'), 'expiry unknown') AS expiry_date, "
        . "IFNULL(DATEDIFF(ciniki_tenant_domains.expiry_date, UTC_TIMESTAMP()), -999) AS expire_in_days, "
        . "ciniki_tenant_domains.managed_by "
        . "FROM ciniki_tenant_domains "
        . "LEFT JOIN ciniki_tenants ON (ciniki_tenant_domains.tnid = ciniki_tenants.id) "
        . "WHERE DATEDIFF(ciniki_tenant_domains.expiry_date,UTC_TIMESTAMP()) < '" . $args['days'] . "' "
        . "OR ciniki_tenant_domains.expiry_date = '0000-00-00' "
        . "ORDER BY expire_in_days ASC "
        . "";

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.tenants', array(
        array('container'=>'domains', 'fname'=>'id', 'name'=>'domain',
            'fields'=>array('id', 'tnid', 'tenant_name', 'tenant_status',
                'domain', 'flags', 'status', 'status_text', 'isprimary', 'managed_by', 
                'expiry_date', 'expire_in_days'),
            'maps'=>array(
                'tenant_status'=>array('1'=>'Active', '10'=>'Suspended', '60'=>'Deleted'),
                'status_text'=>array('1'=>'Active', '20'=>'Expired', '50'=>'Suspended', '60'=>'Deleted'),
            )),
        ));

    return $rc;
}
?>
