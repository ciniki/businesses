<?php
//
// Description
// -----------
// This script will check the domains that are about to expire in the next 30 days
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
require_once($ciniki_root . '/ciniki-mods/core/private/checkModuleFlags.php');
require_once($ciniki_root . '/ciniki-mods/core/private/dbHashQuery.php');
require_once($ciniki_root . '/ciniki-mods/core/private/dbQuote.php');
require_once($ciniki_root . '/ciniki-mods/core/private/objectUpdate.php');

$rc = ciniki_core_init($ciniki_root, 'rest');
if( $rc['stat'] != 'ok' ) {
    header("Status: 500 Processing Error", true, 500);
    exit;
}

//
// Setup the $ciniki variable to hold all things ciniki.  
//
$ciniki = $rc['ciniki'];
$ciniki['session']['user']['id'] = -3;  // Setup to Ciniki Robot

$days = 60;
if( isset($argv[1]) && $argv[1] != '' ) {
    $days = $argv[1];
}

$whois_servers = array(
    'ca' => 'whois.cira.ca',
    'com' => 'whois.crsnic.net',
    'net' => 'whois.crsnic.net',
    'org' => 'whois.publicinterestregistry.net',
    );

$dt = new DateTime('now', new DateTimezone('UTC'));
$dt->add(new DateInterval('P' . $days . 'D'));

//
// Load the list of domains set to expire in the next $days
//
$strsql = "SELECT id, business_id, domain, status, expiry_date "
    . "FROM ciniki_business_domains "
    . "WHERE expiry_date < '" . ciniki_core_dbQuote($ciniki, $dt->format('Y-m-d')) . "' "
    . "AND status = 1 "
    . "";
$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.businesses', 'item');
if( $rc['stat'] != 'ok' ) {
    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.businesses.119', 'msg'=>'Unable to load item', 'err'=>$rc['err']));
}
$domains = array();
foreach($rc['rows'] as $row) {
    $domain = $row['domain'];
    if( preg_match("/^(.*)\.([^\.]+)\.([^\.]+)$/", $domain, $m) ) {
        $domain = $m[2] . '.' . $m[3];
        $tld = strtolower($m[3]);
    } elseif( preg_match("/^([^\.]+)\.([^\.]+)$/", $domain, $m) ) {
        $tld = strtolower($m[2]);
    } else {
        print "Invalid domain: $domain\n";
    }

    if( !isset($domains[$domain]) ) {
        $domains[$domain] = array('name' => $domain, 'business_id' => $row['business_id'], 'tld' => $tld, 'expiry_date' => $row['expiry_date'], 'ids' => array());
    }
    $domains[$domain]['ids'][] = $row['id'];
}

foreach($domains as $domain) {
    
    print $domain['name'] . "...";
    $tld = $domain['tld'];

    if( !isset($whois_servers[$tld]) ) {
        print "no whois\n";
        continue;
    }

    // Getting whois information
    $fp = fsockopen($whois_servers[$tld], 43);
    if( !$fp ) {
        print "Connection error!\n";
        continue;
    }
    fputs($fp, $domain['name'] . "\r\n");

    $lines = array();
    while( !feof($fp) ) {
        $line = trim(fgets($fp, 128));
        if( preg_match("/expiry date:\s+(.*)$/i", $line, $m) ) {
            $dt = new DateTime($m[1], new DateTimezone('UTC'));
            if( $dt->format('Y-m-d') != $domain['expiry_date'] ) {
                foreach($domain['ids'] as $domain_id) {
                    $rc = ciniki_core_objectUpdate($ciniki, $domain['business_id'], 'ciniki.businesses.domain', $domain_id, array(
                        'expiry_date'=>$dt->format('Y-m-d'), 0x07));
                    if( $rc['stat'] != 'ok' ) {
                        print " ERROR: " . $rc['err']['code'] . ' ' . $rc['err']['msg'];
                    } else {
                        print "updated";
                    }
                }
            }
        }
    }

    //print_r($lines);
    print "\n";
}

exit;
?>
