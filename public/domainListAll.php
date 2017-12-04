<?php
//
// Description
// -----------
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_tenants_domainListAll($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'limit'=>array('required'=>'no', 'blank'=>'no', 'default'=>'0', 'name'=>'Limit'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'checkAccess');
    $ac = ciniki_tenants_checkAccess($ciniki, 0, 'ciniki.tenants.domainListAll');
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
        . "ciniki_tenant_domains.managed_by, "
        . "IF((ciniki_tenant_domains.flags&0x01)=0x01, 'yes', 'no') AS isprimary, "
        . "IFNULL(DATE_FORMAT(ciniki_tenant_domains.expiry_date, '" . ciniki_core_dbQuote($ciniki, $date_format) . "'), 'expiry unknown') AS expiry_date, "
        . "DATEDIFF(ciniki_tenant_domains.expiry_date, UTC_TIMESTAMP()) AS expire_in_days "
        . "FROM ciniki_tenant_domains "
        . "LEFT JOIN ciniki_tenants ON (ciniki_tenant_domains.tnid = ciniki_tenants.id) "
        . "ORDER BY ciniki_tenants.name "
        . "";
    if( isset($args['limit']) && $args['limit'] > 0 ) {
        $strsql .= "LIMIT '" . ciniki_core_dbQuote($ciniki, $args['limit']) . "' ";
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.tenants', array(
        array('container'=>'domains', 'fname'=>'id', 'name'=>'domain',
            'fields'=>array('id', 'tnid', 'tenant_name', 'tenant_status',
                'domain', 'flags', 'status', 'isprimary', 
                'expiry_date', 'expire_in_days', 'managed_by'),
            'maps'=>array('tenant_status'=>array('1'=>'Active', '10'=>'Suspended', '60'=>'Deleted')),
            ),
        ));

    return $rc;
}
?>
