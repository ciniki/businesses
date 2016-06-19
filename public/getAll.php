<?php
//
// Description
// -----------
// This function will retrieve all the owners and their businesses.  This is
// only available to sys admins.
//
// Info
// ----
// Status: beta
//
// Arguments
// ---------
// api_key:
// auth_token:
//
// Returns
// -------
// <businesses>
//      <business id='' name='' >
//          <users>
//              <user id='' email='' firstname='' lastname='' display_name='' />
//          </users>
//      </business>
// </businesses>
//
function ciniki_businesses_getAll($ciniki) {
    //
    // Check access to business_id as owner, or sys admin
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'checkAccess');
    $ac = ciniki_businesses_checkAccess($ciniki, 0, 'ciniki.businesses.getAll');
    if( $ac['stat'] != 'ok' ) {
        return $ac;
    }

    //
    // Query for businesses and users
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
    $strsql = "SELECT IF(ciniki_businesses.category='','Uncategorized', ciniki_businesses.category) AS category, "
        . "ciniki_businesses.id as business_id, ciniki_businesses.name, "
        . "ciniki_businesses.status AS status_text, "
        . "ciniki_business_users.user_id, "
        . "ciniki_users.display_name, ciniki_users.firstname, ciniki_users.lastname, ciniki_users.email "
        . "FROM ciniki_businesses "
        . "LEFT JOIN ciniki_business_users ON (ciniki_businesses.id = ciniki_business_users.business_id "
            . "AND ciniki_business_users.status = 10) "
        . "LEFT JOIN ciniki_users ON (ciniki_business_users.user_id = ciniki_users.id) "
        . "ORDER BY category, ciniki_businesses.name, ciniki_users.firstname, ciniki_users.lastname ";
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.businesses', array(
        array('container'=>'categories', 'fname'=>'category', 'name'=>'category',
            'fields'=>array('name'=>'category')),
        array('container'=>'businesses', 'fname'=>'business_id', 'name'=>'business',
            'fields'=>array('id'=>'business_id', 'name', 'status_text'),
            'maps'=>array('status_text'=>array('1'=>'Active', '50'=>'Suspended', '60'=>'Deleted'))),
        array('container'=>'users', 'fname'=>'user_id', 'name'=>'user',
            'fields'=>array('id'=>'user_id', 'display_name', 'firstname', 'lastname', 'email')),
        ));
    return $rc;
}
?>
