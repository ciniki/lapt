<?php
//
// Description
// ===========
// This method will return the file in it's binary form.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:     The ID of the tenant the requested file belongs to.
// file_id:         The ID of the file to be downloaded.
//
// Returns
// -------
// Binary file.
//
function ciniki_lapt_fileDownload($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'file_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'File'), 
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];
    
    //  
    // Make sure this module is activated, and
    // check permission to run this function for this tenant
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'lapt', 'private', 'checkAccess');
    $rc = ciniki_lapt_checkAccess($ciniki, $args['tnid'], 'ciniki.lapt.fileDownload', 0); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // Get the tenant storage directory
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'hooks', 'storageDir');
    $rc = ciniki_tenants_hooks_storageDir($ciniki, $args['tnid'], array());
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $tenant_storage_dir = $rc['storage_dir'];

    //
    // Get the uuid for the file
    //
    $strsql = "SELECT ciniki_lapt_files.id, "
        . "ciniki_lapt_files.uuid, "
        . "ciniki_lapt_files.name, "
        . "ciniki_lapt_files.extension "
        . "FROM ciniki_lapt_files "
        . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['file_id']) . "' "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.lapt', 'file');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['file']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.lapt.36', 'msg'=>'Unable to find file'));
    }
    $filename = $rc['file']['name'] . '.' . $rc['file']['extension'];
    $uuid = $rc['file']['uuid'];

    //
    // Build the storage filename
    //
    $storage_filename = $tenant_storage_dir . '/ciniki.lapt/files/' . $uuid[0] . '/' . $uuid;
    if( file_exists($storage_filename) ) {
        $binary_content = file_get_contents($storage_filename);
    } else {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.lapt.37', 'msg'=>'Unable to find file'));
    }

    header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); 
    header("Last-Modified: " . gmdate("D,d M YH:i:s") . " GMT"); 
    header('Cache-Control: no-cache, must-revalidate');
    header('Pragma: no-cache');

    if( $rc['file']['extension'] == 'pdf' ) {
        header('Content-Type: application/pdf');
    } elseif( $rc['file']['extension'] == 'mp3' ) {
        header('Content-Type: audio/mpeg');
    }

    // Specify Filename
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    header('Content-Length: ' . strlen($binary_content));
    header('Cache-Control: max-age=0');

    print $binary_content;
    exit();
    
    return array('stat'=>'binary');
}
?>
