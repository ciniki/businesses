<?php
//
// Description
// -----------
// Return the list of business owners.
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_businesses_hooks_businessOwners(&$ciniki, $business_id, $args) {

    //
    // Select the owners only
    //
    $strsql = "SELECT ciniki_business_users.user_id, "
        . "ciniki_business_users.status, "
        . "ciniki_users.email, "
        . "ciniki_users.username, "
        . "ciniki_users.firstname, "
        . "ciniki_users.lastname, "
        . "ciniki_users.display_name "
        . "FROM ciniki_business_users, ciniki_users "
        . "WHERE ciniki_business_users.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
        . "AND ciniki_business_users.package = 'ciniki' "
        . "AND (ciniki_business_users.permission_group = 'owners') "
        . "AND ciniki_business_users.status = 10 "      // Active owners only
        . "AND ciniki_business_users.user_id = ciniki_users.id "
        . "AND ciniki_users.status < 11 "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.businesses', array(
        array('container'=>'users', 'fname'=>'user_id',
            'fields'=>array('user_id', 'firstname', 'lastname', 'display_name', 'email')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2607', 'msg'=>'Unable to get list of owners', 'err'=>$rc['err']));
    }
    if( !isset($rc['users']) ) {
        return array('stat'=>'ok', 'users'=>array());
    }

    return array('stat'=>'ok', 'users'=>$rc['users']);
}
?>
