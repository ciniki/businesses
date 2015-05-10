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
function ciniki_businesses_add(&$ciniki) {
	//
	// Find all the required and optional arguments
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
	$rc = ciniki_core_prepareArgs($ciniki, 'no', array(
		'plan_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Plan'), 
		'payment_type'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Payment'), 
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
		'contact.person.name'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Contact'), 
		'contact.email.address'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Email'), 
		'contact.phone.number'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Phone'), 
		'contact.cell.number'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Cell'), 
		'contact.tollfree.number'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Tollfree'), 
		'contact.fax.number'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Fax'), 
		'contact.address.street1'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Address Line 1'), 
		'contact.address.street2'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Address Line 2'), 
		'contact.address.city'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'City'), 
		'contact.address.province'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Province'), 
		'contact.address.postal'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Postal'), 
		'contact.address.country'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Country'), 
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
	// Load timezone settings
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'intlSettings');
	$rc = ciniki_businesses_intlSettings($ciniki, $ciniki['config']['ciniki.core']['master_business_id']);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$intl_timezone = $rc['settings']['intl-default-timezone'];

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
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
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
	$strsql = "INSERT INTO ciniki_businesses (uuid, name, category, sitename, tagline, status, reseller_id, date_added, last_updated) VALUES ("
		. "UUID(), "
		. "'" . ciniki_core_dbQuote($ciniki, $args['business.name']) . "' "
		. ", '" . ciniki_core_dbQuote($ciniki, $args['business.category']) . "' "
		. ", '" . ciniki_core_dbQuote($ciniki, $args['business.sitename']) . "' "
		. ", '" . ciniki_core_dbQuote($ciniki, $args['business.tagline']) . "' "
		. ", 1 "
		. ", '" . ciniki_core_dbQuote($ciniki, $ciniki['config']['ciniki.core']['master_business_id']) . "' "
		. ", UTC_TIMESTAMP(), UTC_TIMESTAMP())";
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
	$customer_address_args = array();
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
	if( (isset($args['owner.username']) && $args['owner.username'] != '')
		|| (isset($args['owner.email.address']) && $args['owner.email.address'] != '') ) {

		//
		// Check if user already exists
		//
		$strsql = "SELECT id, email, username "
			. "FROM ciniki_users "
			. "WHERE username = '" . ciniki_core_dbQuote($ciniki, $args['owner.username']) . "' "
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

	//
	// Add the customer to the master business
	//
	if( (isset($args['owner.name.first']) && $args['owner.name.first'] != '')
		|| (isset($args['owner.name.last']) && $args['owner.name.last'] != '') 
		) {
		//
		// Add the customer
		//
		$customer_args = array(
			'type'=>'1',
			'first'=>(isset($args['owner.name.first'])&&$args['owner.name.first']!='')?$args['owner.name.first']:'',
			'last'=>(isset($args['owner.name.last'])&&$args['owner.name.last']!='')?$args['owner.name.last']:'',
			'company'=>(isset($args['business.name'])&&$args['business.name']!='')?$args['business.name']:'',
			'email_address'=>(isset($args['owner.email.address'])&&$args['owner.email.address']!='')?$args['owner.email.address']:'',
			'flags'=>0x01,
			'address1'=>(isset($args['contact.address.street1'])&&$args['contact.address.street1']!='')?$args['contact.address.street1']:'',
			'address2'=>(isset($args['contact.address.street2'])&&$args['contact.address.street2']!='')?$args['contact.address.street2']:'',
			'city'=>(isset($args['contact.address.city'])&&$args['contact.address.city']!='')?$args['contact.address.city']:'',
			'province'=>(isset($args['contact.address.province'])&&$args['contact.address.province']!='')?$args['contact.address.province']:'',
			'postal'=>(isset($args['contact.address.postal'])&&$args['contact.address.postal']!='')?$args['contact.address.postal']:'',
			'country'=>(isset($args['contact.address.country'])&&$args['contact.address.country']!='')?$args['contact.address.country']:'',
			);
		if( isset($args['contact.phone.number']) && $args['contact.phone.number'] != '' ) {
			$customer_args['phone_label_1'] = 'Work';
			$customer_args['phone_number_1'] = $args['contact.phone.number'];
		}
		if( isset($args['contact.cell.number']) && $args['contact.cell.number'] != '' ) {
			$customer_args['phone_label_2'] = 'Cell';
			$customer_args['phone_number_2'] = $args['contact.cell.number'];
		}
		if( isset($args['contact.tollfree.number']) && $args['contact.tollfree.number'] != '' ) {
			$customer_args['phone_label_3'] = 'Tollfree';
			$customer_args['phone_number_3'] = $args['contact.tollfree.number'];
		}
		if( isset($args['contact.fax.number']) && $args['contact.fax.number'] != '' ) {
			$customer_args['phone_label_4'] = 'Fax';
			$customer_args['phone_number_4'] = $args['contact.fax.number'];
		}
		ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'hooks', 'customerAdd');
		$rc = ciniki_customers_hooks_customerAdd($ciniki, $ciniki['config']['ciniki.core']['master_business_id'], $customer_args);
		if( $rc['stat'] != 'ok' ) {
			ciniki_core_dbTransactionRollback($ciniki, 'ciniki.businesses');
			return $rc;
		}
		$customer_id = $rc['id'];
	}

	//
	// Check if a plan was specified and then setup for that plan
	//
	if( isset($args['plan_id']) && $args['plan_id'] > 0 ) {
		$strsql = "SELECT business_id, modules, monthly, trial_days "
			. "FROM ciniki_business_plans "
			. "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['plan_id']) . "' "
			. "AND business_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['config']['ciniki.core']['master_business_id']) . "' "
			. "";
		$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.businesses', 'plan');
		if( $rc['stat'] != 'ok' ) {
			ciniki_core_dbTransactionRollback($ciniki, 'ciniki.businesses');
			return $rc;
		}
		if( !isset($rc['plan']) ) {
			ciniki_core_dbTransactionRollback($ciniki, 'ciniki.businesses');
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2384', 'msg'=>'Unable to find plan'));
		}
		$plan = $rc['plan'];

		$modules = preg_split('/,/', $plan['modules']);
		foreach($modules as $module) {
			list($pmod,$flags) = explode(':', $module);
			$mod = explode('.', $pmod);
			$strsql = "INSERT INTO ciniki_business_modules (business_id, "
				. "package, module, status, flags, ruleset, date_added, last_updated, last_change) VALUES ("
				. "'" . ciniki_core_dbQuote($ciniki, $business_id) . "', "
				. "'" . ciniki_core_dbQuote($ciniki, $mod[0]) . "', "
				. "'" . ciniki_core_dbQuote($ciniki, $mod[1]) . "', "
				. "1, "
				. "'" . ciniki_core_dbQuote($ciniki, $flags) . "', "
				. "'', UTC_TIMESTAMP(), UTC_TIMESTAMP(), UTC_TIMESTAMP())";
			$rc = ciniki_core_dbInsert($ciniki, $strsql, 'ciniki.businesses');
			if( $rc['stat'] != 'ok' ) {
				ciniki_core_dbTransactionRollback($ciniki, 'ciniki.businesses');
				return $rc;
			}
			//
			// Check if there is an initialization script for the module when the business is enabled
			//
			$rc = ciniki_core_loadMethod($ciniki, $mod[0], $mod[1], 'private', 'moduleInitialize');
			if( $rc['stat'] == 'ok' ) {
				$fn = $mod[0] . '_' . $mod[1] . '_moduleInitialize';
				$rc = $fn($ciniki, $business_id);
				if( $rc['stat'] != 'ok' ) {
					ciniki_core_dbTransactionRollback($ciniki, 'ciniki.businesses');
					return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2385', 'msg'=>'Unable to initialize module ' . $mod[0] . '.' . $mod[1], 'err'=>$rc['err']));
				}
			}
		}

		//
		// FIXME: Link together the subscription and the invoice 
		//

		//
		// Add the subscription plan
		//
		if( isset($args['payment_type']) && $args['payment_type'] == 'monthlypaypal' ) {
			$strsql = "INSERT INTO ciniki_business_subscriptions (business_id, status, "
				. "signup_date, trial_start_date, trial_days, currency, "
				. "monthly, discount_percent, discount_amount, payment_type, payment_frequency, "
				. "date_added, last_updated) VALUES ("
				. "'" . ciniki_core_dbQuote($ciniki, $business_id) . "', "
				. "2, UTC_TIMESTAMP(), UTC_TIMESTAMP(), "
				. "' " . ciniki_core_dbQuote($ciniki, $plan['trial_days']) . "' "
				. ", 'CAD', "
				. "'" . ciniki_core_dbQuote($ciniki, $plan['monthly']) . "', "
				. "0, 0, 'paypal', 10, "
				. "UTC_TIMESTAMP(), UTC_TIMESTAMP())";
			$rc = ciniki_core_dbInsert($ciniki, $strsql, 'ciniki.businesses');
			if( $rc['stat'] != 'ok' ) {
				ciniki_core_dbTransactionRollback($ciniki, 'ciniki.businesses');
				return $rc;
			} 
		}

		//
		// Add the yearly invoice to the master business
		//
		elseif( $args['payment_type'] == 'yearlycheque' && isset($customer_id) ) {
			$tz = new DateTimeZone($intl_timezone);
			$dt = new DateTime('now', $tz);
			$dt->add(new DateInterval('P' . $plan['trial_days'] . 'D'));
			$invoice_args = array(
				'source_id'=>'0',
				'status'=>'10',
				'customer_id'=>$customer_id,
				'invoice_number'=>'',
				'invoice_type'=>'12',
				'invoice_date'=>$dt->format('Y-m-d 12:00:00'),
				'items'=>array(array('description'=>'Web Hosting',
					'quantity'=>'12',
					'status'=>'0',
					'flags'=>0,
					'object'=>'ciniki.businesses.business',
					'object_id'=>$business_id,
					'price_id'=>'0',
					'code'=>'',
					'shipped_quantity'=>'0',
					'unit_amount'=>$plan['monthly'],
					'unit_discount_amount'=>'0',
					'unit_discount_percentage'=>'0',
					'taxtype_id'=>'0',
					'notes'=>'{{thismonth[\'M Y\']}} - {{lastmonth[\'M\']}} {{nextyear[\'Y\']}}',
					)),
				);
			ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'hooks', 'invoiceAdd');
			$rc = ciniki_sapos_hooks_invoiceAdd($ciniki, $ciniki['config']['ciniki.core']['master_business_id'], $invoice_args);
			if( $rc['stat'] != 'ok' ) {
				ciniki_core_dbTransactionRollback($ciniki, 'ciniki.businesses');
				return $rc;
			} 
			if( !isset($rc['id']) ) {
				return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2387', 'msg'=>'Unable to create invoice'));
			}
		}
	}

	//
	// FIXME: Send welcome email with login information
	//



	//
	// Commit the changes
	//
	$rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.businesses');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	return array('stat'=>'ok', 'id'=>$business_id);
}
?>
