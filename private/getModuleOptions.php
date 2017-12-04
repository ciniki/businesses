<?php
//
// Description
// -----------
// This function will retrieve any options for a module from
// the tenant_details table, where the detail_key starts with
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
// tnid:         The database id of the tenant to retrieve data for.
//
function ciniki_tenants_getModuleOptions($ciniki, $module) {

    return ciniki_core_dbDetailsQueryHash($ciniki, 'ciniki_tenant_details', 'tnid', $tnid, $module . '_options', $module);
}
?>
