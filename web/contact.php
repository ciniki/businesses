<?php
//
// Description
// -----------
// This function will get detail values for a business.
//
// Info
// ----
// Status: beta
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:			The ID of the business to get the details for.
// keys:				The comma delimited list of keys to lookup values for.
//
// Returns
// -------
// <details>
//  	<contact>
//			<person name='' />
//			<phone number='' />
//			<fax number='' />
//			<email address='' />
//			<address street1='' street2='' city='' province='' postal='' country='' />
//			<tollfree number='' restrictions='' />
//		</contact>
// </details>
//
function ciniki_businesses_web_contact($ciniki, $settings, $business_id) {
	
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbQuote.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbDetailsQuery.php');

	$rsp = array('stat'=>'ok', 'details'=>array(), 'users'=>array());

	$rc = ciniki_core_dbDetailsQuery($ciniki, 'ciniki_business_details', 'business_id', $business_id, 'ciniki.businesses', 'details', 'contact');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( isset($rc['details']) && is_array($rc['details']) ) {
		$rsp['details'] = $rc['details'];
	}

	//
	// Get the business name
	//
	if( isset($settings['page-contact-business-name-display']) && $settings['page-contact-business-name-display'] == 'yes' ) {
		$strsql = "SELECT ciniki_businesses.name "
			. "FROM ciniki_businesses "
			. "WHERE id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "";
		$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.businesses', 'business');
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( isset($rc['business']['name']) ) {
			$rsp['details']['contact.business.name'] = $rc['business']['name'];
		}
	}

	//
	// Check if there are owner/employee's required, and get the list of business users
	//
	if( isset($settings['page-contact-user-display']) && $settings['page-contact-user-display'] == 'yes' ) {
		$strsql = "SELECT ciniki_business_users.user_id, "
			. "ciniki_users.firstname, ciniki_users.lastname, "
			. "ciniki_users.email, ciniki_users.display_name, "
			. "ciniki_business_user_details.detail_key, ciniki_business_user_details.detail_value "
			. "FROM ciniki_business_users "
			. "LEFT JOIN ciniki_users ON (ciniki_business_users.user_id = ciniki_users.id ) "
			. "LEFT OUTER JOIN ciniki_business_user_details ON (ciniki_business_users.business_id = ciniki_business_user_details.business_id "
				. "AND ciniki_business_users.user_id = ciniki_business_user_details.user_id ) "
			. "WHERE ciniki_business_users.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "AND ciniki_business_users.status = 1 "
			. "";
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
		$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.businesses', array(
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
