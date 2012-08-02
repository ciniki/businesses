<?php
//
// Description
// ===========
// This function will update the domain information
//
// Arguments
// ---------
// user_id: 		The user making the request
// 
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_businesses_domainUpdate($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    require_once($ciniki['config']['core']['modules_dir'] . '/core/private/prepareArgs.php');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
		'business_id'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No business specified'), 
		'domain_id'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No domain specified'), 
		'domain'=>array('required'=>'no', 'blank'=>'no', 'errmsg'=>'No domain specified'), 
		'flags'=>array('required'=>'no', 'blank'=>'yes', 'errmsg'=>'No flags specified'), 
		'status'=>array('required'=>'no', 'blank'=>'yes', 'errmsg'=>'No status specified'),
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];
    
    //  
    // Make sure this module is activated, and
    // check permission to run this function for this business
    //  
    require_once($ciniki['config']['core']['modules_dir'] . '/businesses/private/checkAccess.php');
    $rc = ciniki_businesses_checkAccess($ciniki, $args['business_id'], 'ciniki.businesses.domainUpdate'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

	//  
	// Turn off autocommit
	//  
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbTransactionStart.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbTransactionRollback.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbTransactionCommit.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbQuote.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbUpdate.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbAddModuleHistory.php');
	$rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.businesses');
	if( $rc['stat'] != 'ok' ) { 
		return $rc;
	}   

	//
	// Start building the update SQL
	//
	$strsql = "UPDATE ciniki_business_domains SET last_updated = UTC_TIMESTAMP()";

	//
	// Add all the fields to the change log
	//
	$changelog_fields = array(
		'domain',
		'flags',
		'status',
		);
	foreach($changelog_fields as $field) {
		if( isset($args[$field]) ) {
			$strsql .= ", $field = '" . ciniki_core_dbQuote($ciniki, $args[$field]) . "' ";
			$rc = ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.businesses', 'ciniki_business_history', $args['business_id'], 
				2, 'ciniki_business_domains', $args['domain_id'], $field, $args[$field]);
		}
	}
	$strsql .= "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND id = '" . ciniki_core_dbQuote($ciniki, $args['domain_id']) . "' ";
	$rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.businesses');
	if( $rc['stat'] != 'ok' ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.businesses');
		return $rc;
	}
	if( !isset($rc['num_affected_rows']) || $rc['num_affected_rows'] != 1 ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.businesses');
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'620', 'msg'=>'Unable to update domain'));
	}

	//
	// Commit the database changes
	//
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.businesses');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	return array('stat'=>'ok');
}
?>
