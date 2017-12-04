<?php
//
// Description
// -----------
// This function will return a list of tenants which are to be listed
// on the main page of the master tenant website.
//
// Returns
// -------
//
function ciniki_tenants_web_featured($ciniki, $settings) {
    
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');

    //
    // Get the list of tenants, and their sitename or domain name
    // Exclude the master tenant
    //
    $strsql = "SELECT ciniki_tenants.id, ciniki_tenants.name, ciniki_tenants.sitename, ciniki_tenant_domains.domain "
        . "FROM ciniki_web_settings, ciniki_tenant_modules, ciniki_tenants "
        . "LEFT JOIN ciniki_tenant_domains ON (ciniki_tenants.id = ciniki_tenant_domains.tnid AND (ciniki_tenant_domains.flags&0x01) = 0x01 ) "
        . "WHERE ciniki_web_settings.detail_key = 'site-featured' AND ciniki_web_settings.detail_value = 'yes' "
        . "AND ciniki_web_settings.tnid = ciniki_tenants.id "
        . "AND ciniki_tenants.id = ciniki_tenant_modules.tnid "
        . "AND ciniki_tenant_modules.package = 'ciniki' "
        . "AND ciniki_tenant_modules.module = 'web' "
        . "AND ciniki_tenant_modules.status = 1 "
        . "ORDER BY ciniki_tenants.name "
        . "";

    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.tenants', array(
        array('container'=>'tenants', 'fname'=>'id', 'name'=>'tenant',
            'fields'=>array('id', 'name', 'sitename', 'domain')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['tenants']) || !is_array($rc['tenants']) ) {
        return array('stat'=>'ok', 'tenants'=>array());
    }

    return array('stat'=>'ok', 'tenants'=>$rc['tenants']);
}
?>
