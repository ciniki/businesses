<?php
//
// Description
// -----------
// This function will get detail values for a tenant.
//
// Info
// ----
// Status: beta
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant to get the details for.
// keys:                The comma delimited list of keys to lookup values for.
//
// Returns
// -------
// <details>
//      <contact>
//          <person name='' />
//          <phone number='' />
//          <fax number='' />
//          <email address='' />
//          <address street1='' street2='' city='' province='' postal='' country='' />
//          <tollfree number='' restrictions='' />
//      </contact>
// </details>
//
function ciniki_tenants_web_contact($ciniki, $settings, $tnid) {
    
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDetailsQuery');

    $rsp = array('stat'=>'ok', 'details'=>array(), 'users'=>array());

    $rc = ciniki_core_dbDetailsQuery($ciniki, 'ciniki_tenant_details', 'tnid', $tnid, 'ciniki.tenants', 'details', 'contact');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['details']) && is_array($rc['details']) ) {
        $rsp['details'] = $rc['details'];
    }

    //
    // Get the tenant name
    //
    if( isset($settings['page-contact-tenant-name-display']) && $settings['page-contact-tenant-name-display'] == 'yes' ) {
        $strsql = "SELECT ciniki_tenants.name "
            . "FROM ciniki_tenants "
            . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.tenants', 'tenant');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['tenant']['name']) ) {
            $rsp['details']['contact.tenant.name'] = $rc['tenant']['name'];
        }
    }

    //
    // Check if there are owner/employee's required, and get the list of tenant users
    //
    if( isset($settings['page-contact-user-display']) && $settings['page-contact-user-display'] == 'yes' ) {
        $strsql = "SELECT ciniki_tenant_users.user_id, "
            . "ciniki_users.firstname, ciniki_users.lastname, "
            . "ciniki_users.email, ciniki_users.display_name, "
            . "ciniki_tenant_user_details.detail_key, ciniki_tenant_user_details.detail_value "
            . "FROM ciniki_tenant_users "
            . "LEFT JOIN ciniki_users ON (ciniki_tenant_users.user_id = ciniki_users.id ) "
            . "LEFT OUTER JOIN ciniki_tenant_user_details ON (ciniki_tenant_users.tnid = ciniki_tenant_user_details.tnid "
                . "AND ciniki_tenant_users.user_id = ciniki_tenant_user_details.user_id ) "
            . "WHERE ciniki_tenant_users.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND ciniki_tenant_users.status = 10 "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
        $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.tenants', array(
            array('container'=>'users', 'fname'=>'user_id', 'name'=>'user', 
                'fields'=>array('id'=>'user_id', 'firstname', 'lastname', 'email', 'display_name'),
                'details'=>array('detail_key'=>'detail_value'),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['users']) ) {
            $rsp['users'] = $rc['users'];
        }
    }

    return $rsp;
}
?>
