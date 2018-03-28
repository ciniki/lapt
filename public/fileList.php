<?php
//
// Description
// -----------
// This method will return the list of Files for a tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to get File for.
//
// Returns
// -------
//
function ciniki_lapt_fileList($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner, or sys admin.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'lapt', 'private', 'checkAccess');
    $rc = ciniki_lapt_checkAccess($ciniki, $args['tnid'], 'ciniki.lapt.fileList');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the list of files
    //
    $strsql = "SELECT ciniki_lapt_files.id, "
        . "ciniki_lapt_files.document_id, "
        . "ciniki_lapt_files.name, "
        . "ciniki_lapt_files.permalink, "
        . "ciniki_lapt_files.flags, "
        . "ciniki_lapt_files.org_filename, "
        . "ciniki_lapt_files.extension "
        . "FROM ciniki_lapt_files "
        . "WHERE ciniki_lapt_files.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.lapt', array(
        array('container'=>'files', 'fname'=>'id', 
            'fields'=>array('id', 'document_id', 'name', 'permalink', 'flags', 'org_filename', 'extension')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['files']) ) {
        $files = $rc['files'];
        $file_ids = array();
        foreach($files as $iid => $file) {
            $file_ids[] = $file['id'];
        }
    } else {
        $files = array();
        $file_ids = array();
    }

    return array('stat'=>'ok', 'files'=>$files, 'nplist'=>$file_ids);
}
?>
