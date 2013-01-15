<?php
//
// Description
// -----------
// This function will add a new business.  You must be a sys admin to 
// be authorized to add a business.
//
// Arguments
// ---------
// api_key:
// auth_token:
// 
//
// Returns
// -------
// <rsp stat='ok' id='new business id' />
//
function ciniki_businesses_add($ciniki) {
	//
	// Find all the required and optional arguments
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
	$rc = ciniki_core_prepareArgs($ciniki, 'no', array(
		'business.name'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business Name'), 
		'business.sitename'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Sitename'), 
		'business.tagline'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Tagline'), 
		'business.category'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Category'), 
		'owner.name.first'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Owner First Name'), 
		'owner.name.last'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Owner Last Name'), 
		'owner.name.display'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Owner Display Name'), 
		'owner.email.address'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Owner Email'), 
		'owner.username'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Owner Username'), 
		'owner.password'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Owner Password'), 
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$args = $rc['args'];

	//
	// Check access to business_id as owner, or sys admin
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'checkAccess');
	$ac = ciniki_businesses_checkAccess($ciniki, 0, 'ciniki.businesses.add');
	if( $ac['stat'] != 'ok' ) {
		return $ac;
	}

	//
	// If the sitename is not specified, then create
	//
	if( $args['business.sitename'] == '' ) {
		$args['business.sitename'] = preg_replace('/[^a-z0-9\-_]/', '', strtolower($args['business.name']));
	}

	//
	// Check the sitename is proper format
	//
	if( preg_match('/[^a-z0-9\-_]/', $args['business.sitename']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'162', 'msg'=>'Illegal characters in sitename.  It can only contain lowercase letters, numbers, underscores (_) or dash (-)'));
	}
	
	//
	// Load required functions
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbInsert');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');

	//
	// Turn off autocommit
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
	$rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.businesses');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	//
	// Add the business to the database
	//
	$strsql = "INSERT INTO ciniki_businesses (uuid, name, category, sitename, tagline, status, date_added, last_updated) VALUES ("
		. "UUID(), "
		. "'" . ciniki_core_dbQuote($ciniki, $args['business.name']) . "' "
		. ", '" . ciniki_core_dbQuote($ciniki, $args['business.category']) . "' "
		. ", '" . ciniki_core_dbQuote($ciniki, $args['business.sitename']) . "' "
		. ", '" . ciniki_core_dbQuote($ciniki, $args['business.tagline']) . "' "
		. ", 1, UTC_TIMESTAMP(), UTC_TIMESTAMP())";
	$rc = ciniki_core_dbInsert($ciniki, $strsql, 'ciniki.businesses');
	if( $rc['stat'] != 'ok' ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.businesses');
		return $rc;
	}
	if( !isset($rc['insert_id']) || $rc['insert_id'] < 1 ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.businesses');
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'159', 'msg'=>'Unable to add business'));
	}
	$business_id = $rc['insert_id'];
	ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.businesses', 'ciniki_business_history', $business_id, 
		1, 'ciniki_businesses', $business_id, 'name', $args['business.name']);
	ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.businesses', 'ciniki_business_history', $business_id, 
		1, 'ciniki_businesses', $business_id, 'tagline', $args['business.tagline']);
	ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.businesses', 'ciniki_business_history', $business_id, 
		1, 'ciniki_businesses', $business_id, 'sitename', $args['business.sitename']);
	ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.businesses', 'ciniki_business_history', $business_id, 
		1, 'ciniki_businesses', $business_id, 'status', '1');

	if( $business_id < 1 ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.businesses');
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'161', 'msg'=>'Unable to add business'));
	}

	//
	// Allowed business detail keys 
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
		'contact.tollfree.number',
		'contact.fax.number',
		'contact.email.address',
		);
	foreach($ciniki['request']['args'] as $arg_name => $arg_value) {
		if( in_array($arg_name, $allowed_keys) ) {
			$strsql = "INSERT INTO ciniki_business_details (business_id, detail_key, detail_value, date_added, last_updated) "
				. "VALUES ('" . ciniki_core_dbQuote($ciniki, $business_id) . "', "
				. "'" . ciniki_core_dbQuote($ciniki, $arg_name) . "', "
				. "'" . ciniki_core_dbQuote($ciniki, $arg_value) . "', "
				. "UTC_TIMESTAMP(), UTC_TIMESTAMP()) ";
			$rc = ciniki_core_dbInsert($ciniki, $strsql, 'ciniki.businesses');
			if( $rc['stat'] != 'ok' ) {
				ciniki_core_dbTransactionRollback($ciniki, 'ciniki.businesses');
				return $rc;
			}
			ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.businesses', 'ciniki_business_history', $business_id, 
				1, 'ciniki_business_details', $arg_name, 'detail_value', $arg_value);
		}
	}

	//
	// Check if user needs to be added
	//
	$user_id = 0;
	if( (isset($args['owner.name.username']) && $args['owner.name.username'] != '')
		|| (isset($args['owner.email.address']) && $args['owner.email.address'] != '') ) {

		//
		// Check if user already exists
		//
		$strsql = "SELECT id, email, username "
			. "FROM ciniki_users "
			. "WHERE username = '" . ciniki_core_dbQuote($ciniki, $args['owner.name.username']) . "' "
			. "OR email = '" . ciniki_core_dbQuote($ciniki, $args['owner.email.address']) . "' "
			. "";
		$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.businesses', 'user');
		if( $rc['stat'] != 'ok' ) {
			ciniki_core_dbTransactionRollback($ciniki, 'ciniki.businesses');
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'158', 'msg'=>'Unable to lookup user'));
		}
		$user_id = 0;
		if( isset($rc['user']) ) {
			// User exists, check if email different
			if( $rc['user']['email'] != $args['owner.email.address'] ) {
				// Username matches, but email doesn't, they are trying to create a new account
				ciniki_core_dbTransactionRollback($ciniki, 'ciniki.businesses');
				return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'75', 'msg'=>'Username already taken'));
			}
			else {
				$user_id = $rc['user']['id'];
			}
		} else {
			//
			// User doesn't exist, so can be created
			//
			if( !isset($args['owner.name.first']) || $args['owner.name.first'] == '' 
				|| !isset($args['owner.name.last']) || $args['owner.name.last'] == '' 
				|| !isset($args['owner.name.display']) || $args['owner.name.display'] == '' ) {
				return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'154', 'msg'=>'You must specify a first, last and display name'));
			}
			$strsql = "INSERT INTO ciniki_users (uuid, date_added, email, username, firstname, lastname, display_name, "
				. "perms, status, timeout, password, temp_password, temp_password_date, last_updated) VALUES ("
				. "UUID(), "
				. "UTC_TIMESTAMP()" 
				. ", '" . ciniki_core_dbQuote($ciniki, $args['owner.email.address']) . "'" 
				. ", '" . ciniki_core_dbQuote($ciniki, $args['owner.username']) . "'" 
				. ", '" . ciniki_core_dbQuote($ciniki, $args['owner.name.first']) . "'" 
				. ", '" . ciniki_core_dbQuote($ciniki, $args['owner.name.last']) . "'" 
				. ", '" . ciniki_core_dbQuote($ciniki, $args['owner.name.display']) . "'" 
				. ", 0, 1, 0, "
				. "SHA1('" . ciniki_core_dbQuote($ciniki, $args['owner.password']) . "'), "
				. "SHA1('" . ciniki_core_dbQuote($ciniki, '') . "'), "
				. "UTC_TIMESTAMP(), "
				. "UTC_TIMESTAMP())";
			$rc = ciniki_core_dbInsert($ciniki, $strsql, 'ciniki.users');
			if( $rc['stat'] != 'ok' ) { 
				ciniki_core_dbTransactionRollback($ciniki, 'ciniki.businesses');
				return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'76', 'msg'=>'Unable to add owner'));
			} else {
				$user_id = $rc['insert_id'];
			}
		}
	}

	//
	// Add the business owner
	//
	if( $user_id > 0 ) {
		$strsql = "INSERT INTO ciniki_business_users (business_id, user_id, "
			. "package, permission_group, status, date_added, last_updated) VALUES ("
			. "'" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. ", '" . ciniki_core_dbQuote($ciniki, $user_id) . "' "
			. ", 'ciniki', 'owners', 10, UTC_TIMESTAMP(), UTC_TIMESTAMP())";
		$rc = ciniki_core_dbInsert($ciniki, $strsql, 'ciniki.businesses');
		if( $rc['stat'] != 'ok' ) {
			ciniki_core_dbTransactionRollback($ciniki, 'ciniki.businesses');
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'157', 'msg'=>'Unable to add ciniki owner'));
		} 
	}

	$rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.businesses');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	return array('stat'=>'ok', 'id'=>$business_id);
}
?>
