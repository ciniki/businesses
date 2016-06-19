<?php
//
// Description
// -----------
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:         The ID of the business to get the module list for.
//
// Returns
// -------
// <modules>
//      <module name='Products' ruleset='all_customers'>
//          <rulesets>
//              <ruleset id='all_customers' label='All Customers, Group Managed' description='' />
//          </rulesets>
//      </module>
// </modules>
//
function ciniki_businesses_getModuleRulesets($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];
    
    //
    // Check access to business_id as owner, or sys admin. 
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'checkAccess');
    $ac = ciniki_businesses_checkAccess($ciniki, $args['business_id'], 'ciniki.businesses.getModuleRulesets');
    if( $ac['stat'] != 'ok' ) {
        return $ac;
    }

/*  ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
    $strsql = "SELECT package, module, status, ruleset "
        . "FROM ciniki_business_modules "
        . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "'";    
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.businesses', '');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( $rc['num_rows'] != 1 ) {
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'211', 'msg'=>'No business found'));
    }
    $business_modules = $rc['rows'][0]['modules'];
*/
    //
    // Get the list of modules and permissions for the business
    //
    $strsql = "SELECT CONCAT_WS('.', package, module) AS name, package, module, status, ruleset "
        . "FROM ciniki_business_modules "
        . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "'";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashIDQuery');
    $rc = ciniki_core_dbHashIDQuery($ciniki, $strsql, 'ciniki.businesses', 'modules', 'name');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $module_rulesets = $rc['modules'];

    //
    // Get the list of available modules
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'getModuleList');
    $rc = ciniki_core_getModuleList($ciniki);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $mod_list = $rc['modules'];

    $modules = array();
    $count = 0;
    foreach($mod_list as $module) {
        //
        // Only add modules to the list that have been installed, and turned on for the business
        //
        $name = $module['package'] . '.' . $module['name'];
        if( $module['name'] != '' && $module['installed'] == 'Yes' 
            && isset($module_rulesets[$name]) && $module_rulesets[$name]['status'] == 1 ) {
            $modules[$count] = array('module'=>array('name'=>$module['package'] . '.' . $module['name'], 'label'=>$module['label']));
            //
            // Check for the current ruleset selected
            //
            if( isset($module_rulesets[$name]) ) {
                $modules[$count]['module']['ruleset'] = $module_rulesets[$name]['ruleset'];
            }

            //
            // Check for any rulesets for this module
            //
            if( file_exists($ciniki['config']['ciniki.core']['root_dir'] . '/' . $module['package'] . '-mods/' . $module['name'] . '/private/getRulesets.php') ) {
                ciniki_core_loadMethod($ciniki, $module['package'], $module['name'], 'private', 'getRulesets');
                $func = "ciniki_" . $module['name'] . "_getRulesets";
                $rulesets = $func($ciniki);
                $i = 0;
                foreach($rulesets as $name => $ruleset) {
                    $modules[$count]['module']['rulesets'][$i++] = array('ruleset'=>array('id'=>$name, 
                        'label'=>$rulesets[$name]['label'], 
                        'description'=>$rulesets[$name]['description'] ));
                }
            } else {
                $modules[$count]['module']['rulesets'] = array();
            }
            $count++;
        }
    }

    return array('stat'=>'ok', 'modules'=>$modules);
}
?>
