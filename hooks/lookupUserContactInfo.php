<?php
//
// Description
// -----------
// This function will return a list of user display names, which can be returned
// via the API to an end user.  No email addresses or permissions will be returned.
//
// Arguments
// ---------
// ciniki:
// container_name:      The name for the array container for the users.
// ids:                 The array of user IDs to lookup in the database.
//
// Returns
// -------
// <users>
//      <user id='1' display_name='' />
// </users>
//
function ciniki_tenants_hooks_lookupUserContactInfo(&$ciniki, $tnid, $args) {

    if( !isset($args['user_id']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.tenants.5', 'msg'=>'No user specified.'));
    }

    //
    // Get the default information for the user
    //
    $strsql = "SELECT ciniki_tenant_users.id, "
        . "ciniki_users.id, ciniki_users.display_name, ciniki_users.firstname, ciniki_users.lastname, ciniki_users.email "
        . "FROM ciniki_tenant_users, ciniki_users "
        . "WHERE ciniki_tenant_users.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND ciniki_tenant_users.user_id = '" . ciniki_core_dbQuote($ciniki, $args['user_id']) . "' "
        . "AND ciniki_tenant_users.user_id = ciniki_users.id "
        . "LIMIT 1"
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.users', 'user');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['user']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.tenants.6', 'msg'=>'Unable to find user'));
    }
    $user = $rc['user'];

    //
    // Lookup contact details
    //
    $strsql = "SELECT detail_key, detail_value "
        . "FROM ciniki_tenant_user_details "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND user_id = '" . ciniki_core_dbQuote($ciniki, $args['user_id']) . "' "
        . "AND detail_key like 'contact%' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQueryList2');
    $rc = ciniki_core_dbQueryList2($ciniki, $strsql, 'ciniki.tenants', 'details');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['details']['contact.cell.number']) && $rc['details']['contact.cell.number'] != '' ) {
        $user['sms'] = $rc['details']['contact.cell.number'];
    }
    if( isset($rc['details']['contact.email.address']) && $rc['details']['contact.email.address'] != '' ) {
        $user['email'] = $rc['details']['contact.email.address'];
    }

    return array('stat'=>'ok', 'user'=>$user);;
}
?>
