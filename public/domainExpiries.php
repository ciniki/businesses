<?php
//
// Description
// -----------
// This function will lookup the client domain in the database, and return the business id.
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_businesses_domainExpiries($ciniki) {
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
    // Check access to business_id as owner
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'checkAccess');
    $ac = ciniki_businesses_checkAccess($ciniki, 0, 'ciniki.businesses.domainExpiries');
    if( $ac['stat'] != 'ok' ) {
        return $ac;
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki);

    //
    // Query the database for the domain
    //
    $strsql = "SELECT ciniki_business_domains.id, "
        . "ciniki_business_domains.business_id, "
        . "ciniki_businesses.name AS business_name, "
        . "ciniki_businesses.status AS business_status, "
        . "ciniki_business_domains.domain, "
        . "ciniki_business_domains.flags, "
        . "ciniki_business_domains.status, "
        . "ciniki_business_domains.status AS status_text, "
        . "IF((ciniki_business_domains.flags&0x01)=0x01, 'yes', 'no') AS isprimary, "
        . "IFNULL(DATE_FORMAT(ciniki_business_domains.expiry_date, '" . ciniki_core_dbQuote($ciniki, $date_format) . "'), 'expiry unknown') AS expiry_date, "
        . "IFNULL(DATEDIFF(ciniki_business_domains.expiry_date, UTC_TIMESTAMP()), -999) AS expire_in_days, "
        . "ciniki_business_domains.managed_by "
        . "FROM ciniki_business_domains "
        . "LEFT JOIN ciniki_businesses ON (ciniki_business_domains.business_id = ciniki_businesses.id) "
        . "WHERE DATEDIFF(ciniki_business_domains.expiry_date,UTC_TIMESTAMP()) < '" . $args['days'] . "' "
        . "OR ciniki_business_domains.expiry_date = '0000-00-00' "
        . "ORDER BY expire_in_days ASC "
        . "";

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.businesses', array(
        array('container'=>'domains', 'fname'=>'id', 'name'=>'domain',
            'fields'=>array('id', 'business_id', 'business_name', 'business_status',
                'domain', 'flags', 'status', 'status_text', 'isprimary', 'managed_by', 
                'expiry_date', 'expire_in_days'),
            'maps'=>array(
                'business_status'=>array('1'=>'Active', '10'=>'Suspended', '60'=>'Deleted'),
                'status_text'=>array('1'=>'Active', '20'=>'Expired', '50'=>'Suspended', '60'=>'Deleted'),
            )),
        ));

    return $rc;
}
?>
