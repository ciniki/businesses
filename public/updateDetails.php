<?php
//
// Description
// -----------
// This function will take a list of details to be updated within the database.  The
// fields will be used for the contact information and tenant information
// on the Contact Page for the tenant.
//
// Info
// ----
// Status: beta
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:                 The ID of the tenant to get the details for.
// tenant.name:               (optional) The name to set for the tenant.
// tenant.tagline:            (optional) The tagline for the website.  Used on website.
// contact.address.street1:     (optional) The address for the tenant.
// contact.address.street2:     (optional) The second address line for the tenant.
// contact.address.city:        (optional) The city for the tenant.
// contact.address.province:    (optional) The province for the tenant.
// contact.address.postal:      (optional) The postal code for the tenant.
// contact.address.country:     (optional) The county of the tenant.
// contact.person.name:         (optional) The contact person for the tenant.
// contact.phone.number:        (optional) The contact phone number for the tenant.  
// contact.cell.number:         (optional) The contact cell phone number for the tenant.  
// contact.tollfree.number:     (optional) The toll free number for the tenant.
// contact.fax.number:          (optional) The fax number for the tenant.
// contact.email.address:       (optional) The contact email address for the tenant.
//
// Returns
// -------
// <rsp stat='ok' />
//
function ciniki_tenants_updateDetails(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'tenant.name'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Tenant Name'), 
        'tenant.category'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Category'), 
        'tenant.sitename'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Sitename'), 
        'tenant.tagline'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Tagline'), 
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];
    
    //
    // Check access to tnid as owner
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'checkAccess');
    $ac = ciniki_tenants_checkAccess($ciniki, $args['tnid'], 'ciniki.tenants.updateDetails');
    if( $ac['stat'] != 'ok' ) {
        return $ac;
    }

    //
    // Check the sitename is proper format
    //
    if( isset($args['tenant.sitename']) && preg_match('/[^a-z0-9\-_]/', $args['tenant.sitename']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.tenants.93', 'msg'=>'Illegal characters in sitename.  It can only contain lowercase letters, numbers, underscores (_) or dash (-)'));
    }
    

    //
    // Turn off autocommit
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.tenants');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Check if name or tagline was specified
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbInsert');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    $strsql = "";
    if( isset($args['tenant.name']) && $args['tenant.name'] != '' ) {
        $strsql .= ", name = '" . ciniki_core_dbQuote($ciniki, $args['tenant.name']) . "'";
        ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.tenants', 'ciniki_tenant_history', $args['tnid'], 
            2, 'ciniki_tenants', '', 'name', $args['tenant.name']);
    }
    if( isset($args['tenant.sitename']) ) {
        $strsql .= ", sitename = '" . ciniki_core_dbQuote($ciniki, $args['tenant.sitename']) . "'";
        ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.tenants', 'ciniki_tenant_history', $args['tnid'], 
            2, 'ciniki_tenants', '', 'sitename', $args['tenant.sitename']);
    }
    if( isset($args['tenant.category']) ) {
        $strsql .= ", category = '" . ciniki_core_dbQuote($ciniki, $args['tenant.category']) . "'";
        ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.tenants', 'ciniki_tenant_history', $args['tnid'], 
            2, 'ciniki_tenants', '', 'category', $args['tenant.category']);
    }
    if( isset($args['tenant.tagline']) ) {
        $strsql .= ", tagline = '" . ciniki_core_dbQuote($ciniki, $args['tenant.tagline']) . "'";
        ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.tenants', 'ciniki_tenant_history', $args['tnid'], 
            2, 'ciniki_tenants', '', 'tagline', $args['tenant.tagline']);
    }
    //
    // Always update last_updated for sync purposes
    //
    $strsql = "UPDATE ciniki_tenants SET last_updated = UTC_TIMESTAMP()" . $strsql 
        . " WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' ";
    $rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.tenants');
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.tenants');
        return $rc;
    }

    //
    // Allowed tenant detail keys 
    //
    $allowed_keys = array(
        'contact.address.street1',
        'contact.address.street2',
        'contact.address.city',
        'contact.address.province',
        'contact.address.postal',
        'contact.address.country',
        'contact.person.name',
        'contact.phone.number',
        'contact.cell.number',
        'contact.tollfree.number',
        'contact.fax.number',
        'contact.email.address',
        'ciniki.manage.css',
        'social-facebook-url',
        'social-twitter-tenant-name',
        'social-twitter-username',
        'social-flickr-url',
        'social-etsy-url',
        'social-pinterest-username',
        'social-tumblr-username',
        'social-youtube-url',
        'social-vimeo-url',
        'social-instagram-username',
        'social-linkedin-url',
        );
    foreach($ciniki['request']['args'] as $arg_name => $arg_value) {
        if( in_array($arg_name, $allowed_keys) ) {
            $strsql = "INSERT INTO ciniki_tenant_details (tnid, detail_key, detail_value, date_added, last_updated) "
                . "VALUES ('" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "'"
                . ", '" . ciniki_core_dbQuote($ciniki, $arg_name) . "'"
                . ", '" . ciniki_core_dbQuote($ciniki, $arg_value) . "'"
                . ", UTC_TIMESTAMP(), UTC_TIMESTAMP()) "
                . "ON DUPLICATE KEY UPDATE detail_value = '" . ciniki_core_dbQuote($ciniki, $arg_value) . "' "
                . ", last_updated = UTC_TIMESTAMP() "
                . "";
            $rc = ciniki_core_dbInsert($ciniki, $strsql, 'ciniki.tenants');
            if( $rc['stat'] != 'ok' ) {
                ciniki_core_dbTransactionRollback($ciniki, 'ciniki.tenants');
                return $rc;
            }
            ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.tenants', 'ciniki_tenant_history', $args['tnid'], 
                2, 'ciniki_tenant_details', $arg_name, 'detail_value', $arg_value);
            $ciniki['syncqueue'][] = array('push'=>'ciniki.tenants.details', 
                'args'=>array('id'=>$arg_name));
        }
    }

    $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.tenants');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    return array('stat'=>'ok');
}
?>
