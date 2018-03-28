<?php
//
// Description
// ===========
// This method will return all the information about an file.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the file is attached to.
// file_id:          The ID of the file to get the details for.
//
// Returns
// -------
//
function ciniki_lapt_fileGet($ciniki) {
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
    $rc = ciniki_lapt_checkAccess($ciniki, $args['tnid'], 'ciniki.lapt.fileGet');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Load tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $args['tnid']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    $intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
    $intl_currency = $rc['settings']['intl-default-currency'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'php');

    //
    // Return default for new File
    //
    if( $args['file_id'] == 0 ) {
        $file = array('id'=>0,
            'document_id'=>'',
            'name'=>'',
            'permalink'=>'',
            'flags'=>0x01,
            'description'=>'',
            'org_filename'=>'',
            'extension'=>'',
        );
    }

    //
    // Get the details for an existing File
    //
    else {
        $strsql = "SELECT ciniki_lapt_files.id, "
            . "ciniki_lapt_files.document_id, "
            . "ciniki_lapt_files.name, "
            . "ciniki_lapt_files.permalink, "
            . "ciniki_lapt_files.flags, "
            . "ciniki_lapt_files.description, "
            . "ciniki_lapt_files.org_filename, "
            . "ciniki_lapt_files.extension "
            . "FROM ciniki_lapt_files "
            . "WHERE ciniki_lapt_files.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_lapt_files.id = '" . ciniki_core_dbQuote($ciniki, $args['file_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.lapt', array(
            array('container'=>'files', 'fname'=>'id', 
                'fields'=>array('document_id', 'name', 'permalink', 'flags', 'description', 'org_filename', 'extension'),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.lapt.24', 'msg'=>'File not found', 'err'=>$rc['err']));
        }
        if( !isset($rc['files'][0]) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.lapt.25', 'msg'=>'Unable to find File'));
        }
        $file = $rc['files'][0];
    }

    return array('stat'=>'ok', 'file'=>$file);
}
?>
