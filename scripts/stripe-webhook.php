<?php
//
// Description
// -----------
// The rest.php file is the entry point for the API through the REST protocol.
//

//
// Initialize Ciniki by including the ciniki_api.php
//
global $ciniki_root;
$ciniki_root = dirname(__FILE__);
if( !file_exists($ciniki_root . '/ciniki-api.ini') ) {
    $ciniki_root = dirname(dirname(dirname(dirname(__FILE__))));
}
// loadMethod is required by all function to ensure the functions are dynamically loaded
require_once($ciniki_root . '/ciniki-mods/core/private/loadMethod.php');
require_once($ciniki_root . '/ciniki-mods/core/private/init.php');
require_once($ciniki_root . '/ciniki-mods/core/private/checkSecureConnection.php');
require_once($ciniki_root . '/ciniki-mods/core/private/callPublicMethod.php');
require_once($ciniki_root . '/ciniki-mods/core/private/printHashToXML.php');
require_once($ciniki_root . '/ciniki-mods/core/private/printResponse.php');

$rc = ciniki_core_init($ciniki_root, 'rest');
if( $rc['stat'] != 'ok' ) {
    header("Status: 500 Processing Error", true, 500);
    exit;
}

//
// Setup the $ciniki variable to hold all things ciniki.  
//
$ciniki = $rc['ciniki'];

//
// Ensure the connection is over SSL
//
$rc = ciniki_core_checkSecureConnection($ciniki);
if( $rc['stat'] != 'ok' ) {
    header("Status: 500 Not secure", true, 500);
    exit;
}

$input = @file_get_contents("php://input");
$event_json = json_decode($input, true);

file_put_contents("/tmp/last_stripe_webhook", print_r($event_json, true));
error_log(print_r($event_json, true));


//
// FIXME: Add processing to determine where to send webhook
//

exit;

?>
