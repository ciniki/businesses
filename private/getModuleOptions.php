<?php
//
// Description
// -----------
// This function will retrieve any options for a module from
// the business_details table, where the detail_key starts with
// module_name.options.
//
// Info
// ----
// Status: beta
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id: 		The database id of the business to retrieve data for.
//
function ciniki_businesses_getModuleOptions($ciniki, $module) {

	return ciniki_core_dbDetailsQueryHash($ciniki, 'ciniki_business_details', 'business_id', $business_id, $module . '_options', $module);
}
?>
