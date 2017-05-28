<?php
//
// Description
// -----------
// This function will return the list of businesses which the user has access to.
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
//      <business id='4592' name='Temporary Test Business' />
//      <business id='20719' name='Old Test Business' />
// </businesses>
//
function ciniki_businesses_getUserBusinesses($ciniki) {
    //
    // Any authenticated user has access to this function, so no need to check permissions
    //

    //
    // Check access to business_id as owner, or sys admin
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'checkAccess');
    $ac = ciniki_businesses_checkAccess($ciniki, 0, 'ciniki.businesses.getUserBusinesses');
    if( $ac['stat'] != 'ok' ) {
        return $ac;
    }

    // 
    // Check the database for user and which businesses they have access to.  If they
    // are a ciniki-manage, they have access to all businesses.
    // Link to the business_users table to grab the groups the user belongs to for that business.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    if( ($ciniki['session']['user']['perms'] & 0x01) == 0x01 ) {
        //
        // Check if there is a debug file of action to do on login
        //
        if( file_exists($ciniki['config']['ciniki.core']['root_dir'] . '/loginactions.js') ) {
            $login_actions = file_get_contents($ciniki['config']['ciniki.core']['root_dir'] . '/loginactions.js'); 
        }

        $strsql = "SELECT ciniki_businesses.category, "
            . "ciniki_businesses.id, "
            . "ciniki_businesses.name "
            . "FROM ciniki_businesses "
            . "ORDER BY category, ciniki_businesses.status, ciniki_businesses.name "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
        $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.businesses', array(
            array('container'=>'categories', 'fname'=>'category', 'name'=>'category',
                'fields'=>array('name'=>'category')),
            array('container'=>'businesses', 'fname'=>'id', 'name'=>'business',
                'fields'=>array('id', 'name')),
            ));

        if( isset($login_actions) && $login_actions != '' ) {
            $rc['loginActions'] = $login_actions;
        }

        return $rc;
    } else {
        $strsql = "SELECT DISTINCT ciniki_businesses.id, name "
            . "FROM ciniki_business_users, ciniki_businesses "
            . "WHERE ciniki_business_users.user_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "' "
            . "AND ciniki_business_users.status = 10 "
            . "AND ciniki_business_users.business_id = ciniki_businesses.id "
            . "AND ciniki_businesses.status < 60 "  // Allow suspended businesses to be listed, so user can login and update billing/unsuspend
            . "ORDER BY ciniki_business_users.permission_group, ciniki_businesses.name ";
//      $strsql = "SELECT DISTINCT id, name, ciniki_business_users.permission_group, "
//          . "d1.detail_value AS css "
//          . "FROM ciniki_business_users, ciniki_businesses "
//          . "LEFT JOIN ciniki_business_details AS d1 ON (ciniki_businesses.id = d1.business_id AND d1.detail_key = 'ciniki.manage.css') "
//          . "WHERE ciniki_business_users.user_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "' "
//          . "AND ciniki_business_users.status = 1 "
//          . "AND ciniki_business_users.business_id = ciniki_businesses.id "
//          . "AND ciniki_businesses.status < 60 "  // Allow suspended businesses to be listed, so user can login and update billing/unsuspend
//          . "ORDER BY ciniki_businesses.name ";
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbRspQuery');
    $rc = ciniki_core_dbRspQuery($ciniki, $strsql, 'ciniki.businesses', 'businesses', 'business', array('stat'=>'fail', 'err'=>array('code'=>'ciniki.businesses.50', 'msg'=>'No businesses found')));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    return $rc;
}
?>
