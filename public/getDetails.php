<?php
//
// Description
// -----------
// This function will get detail values for a tenant.  These values
// are used many places in the API and MOSSi.
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
//      <tenant name='' tagline='' />
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
function ciniki_tenants_getDetails($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'keys'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Keys'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];
    
    //
    // Check access to tnid as owner, or sys admin
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'checkAccess');
    $ac = ciniki_tenants_checkAccess($ciniki, $args['tnid'], 'ciniki.tenants.getDetails');
    if( $ac['stat'] != 'ok' ) {
        return $ac;
    }

    // Split the keys, if specified
    if( isset($args['keys']) && $args['keys'] != '' ) {
//  if( isset($ciniki['request']['args']['keys']) && $ciniki['request']['args']['keys'] != '' ) {
        $detail_keys = preg_split('/,/', $args['keys']);
    } else {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.tenants.46', 'msg'=>'No keys specified'));
    }

    $rsp = array('stat'=>'ok', 'details'=>array());

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDetailsQuery');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDetailsQueryDash');
    foreach($detail_keys as $detail_key) {
        if( $detail_key == 'tenant' ) {
            $strsql = "SELECT name, category, sitename, tagline FROM ciniki_tenants "
                . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' ";
            $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.tenants', 'tenant');
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            $rsp['details']['tenant.name'] = $rc['tenant']['name'];
            $rsp['details']['tenant.category'] = $rc['tenant']['category'];
            $rsp['details']['tenant.sitename'] = $rc['tenant']['sitename'];
            $rsp['details']['tenant.tagline'] = $rc['tenant']['tagline'];
        } elseif( in_array($detail_key, array('contact', 'ciniki')) ) {
            $rc = ciniki_core_dbDetailsQuery($ciniki, 'ciniki_tenant_details', 'tnid', $args['tnid'], 'ciniki.tenants', 'details', $detail_key);
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            if( $rc['details'] != null ) {
                $rsp['details'] += $rc['details'];
            }
        } elseif( in_array($detail_key, array('social')) ) {
            $rc = ciniki_core_dbDetailsQueryDash($ciniki, 'ciniki_tenant_details', 'tnid', $args['tnid'], 'ciniki.tenants', 'details', $detail_key);
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            if( $rc['details'] != null ) {
                $rsp['details'] += $rc['details'];
            }
        }
    }

    return $rsp;
}
?>
