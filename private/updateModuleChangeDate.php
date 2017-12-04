<?php
//
// Description
// -----------
// This function will update the modules last change date.  This should happen
// whenever data is updated within a module, so when a sync happens, the
// last change date for the module is compared.
//
// Arguments
// ---------
// ciniki:
// tnid:         The ID of the tenant to update the module for.
// package:             The package the module is contained within.
// module:              The module that needs to be updated.
//
// Returns
// -------
// <rsp stat='ok' />
//
function ciniki_tenants_updateModuleChangeDate($ciniki, $tnid, $package, $module) {

    //
    // If tnid is passed as zero, then don't updated the module last_change field
    //
    if( $tnid == 0 ) {
        return array('stat'=>'ok');
    }

    //
    // Update the module.  Assume the module has been added to the ciniki_tenant_modules table,
    // if not run an insert.
    //
    $strsql = "UPDATE ciniki_tenant_modules "
        . "SET last_change = UTC_TIMESTAMP() "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND package = '" . ciniki_core_dbQuote($ciniki, $package) . "' "
        . "AND module = '" . ciniki_core_dbQuote($ciniki, $module) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
    $rc = ciniki_core_dbUpdate($ciniki, $strsql, "$package.$module");
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Check if a row was updated, if not, run an insert
    //
    if( isset($rc['num_affected_rows']) && $rc['num_affected_rows'] == 0 ) {
        $strsql = "INSERT INTO ciniki_tenant_modules (tnid, package, module, "
            . "status, ruleset, date_added, last_updated, last_change) VALUES ("
            . "'" . ciniki_core_dbQuote($ciniki, $tnid) . "', "
            . "'" . ciniki_core_dbQuote($ciniki, $package) . "', "
            . "'" . ciniki_core_dbQuote($ciniki, $module) . "', "
            . "2, '', UTC_TIMESTAMP(), UTC_TIMESTAMP(), UTC_TIMESTAMP() "
            . ")";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbInsert');
        $rc = ciniki_core_dbInsert($ciniki, $strsql, "$package.$module");
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
    }

    return $rc;
}
?>
