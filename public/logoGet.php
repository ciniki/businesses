<?php
//
// Description
// -----------
// This function will return the image binary data in jpg format.
//
// Info
// ----
// Status: defined
//
// Arguments
// ---------
// image_id:			The ID if the image requested.
// version:				The version of the image (regular, thumbnail)
//
//						*note* the thumbnail is not referring to the size, but to a 
//						square cropped version, designed for use as a thumbnail.
//						This allows only a portion of the original image to be used
//						for thumbnails, as some images are too complex for thumbnails.
//
// maxlength:			The max length of the longest side should be.  This allows
//						for generation of thumbnail's, etc.
//
// Returns
// -------
// logo_id
//
function ciniki_businesses_logoGet($ciniki) {
	//
	// Find all the required and optional arguments
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
	$rc = ciniki_core_prepareArgs($ciniki, 'no', array(
		'business_id'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No business specified'), 	
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$args = $rc['args'];
	
	//
	// Check access 
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'checkAccess');
	$rc = ciniki_businesses_checkAccess($ciniki, $args['business_id'], 'ciniki.businesses.logoGet');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	//
	// Get the busines logo_id
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
	$strsql = "SELECT logo_id FROM ciniki_businesses "
		. "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "";
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'businesses', 'business');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( isset($rc['business']['logo_id']) ) {
		$logo_id = $rc['business']['logo_id'];
	} else {
		$logo_id = 0;
	}

	return array('stat'=>'ok', 'logo_id'=>$logo_id);

//	ciniki_core_loadMethod($ciniki, 'ciniki', 'images', 'private', 'getImage');
//	return ciniki_images_getImage($ciniki, $args['business_id'], $logo_id, $args['version'], $args['maxlength']);
}
?>
