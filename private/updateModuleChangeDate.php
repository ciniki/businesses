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
// business_id:         The ID of the business to update the module for.
// package:             The package the module is contained within.
// module:              The module that needs to be updated.
//
// Returns
// -------
// <rsp stat='ok' />
//
function ciniki_businesses_updateModuleChangeDate($ciniki, $business_id, $package, $module) {

    //
    // If business_id is passed as zero, then don't updated the module last_change field
    //
    if( $business_id == 0 ) {
        return array('stat'=>'ok');
    }

    //
    // Update the module.  Assume the module has been added to the ciniki_business_modules table,
    // if not run an insert.
    //
    $strsql = "UPDATE ciniki_business_modules "
        . "SET last_change = UTC_TIMESTAMP() "
        . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
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
        $strsql = "INSERT INTO ciniki_business_modules (business_id, package, module, "
            . "status, ruleset, date_added, last_updated, last_change) VALUES ("
            . "'" . ciniki_core_dbQuote($ciniki, $business_id) . "', "
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
