<?php
//
// Description
// -----------
// This method will add a new document for the tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to add the Document to.
//
// Returns
// -------
//
function ciniki_lapt_documentAdd(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'title'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Title'),
        'permalink'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Permalink'),
        'status'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Status'),
        'flags'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Options'),
        'doc_date'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Document Date'),
        'image_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Primary Image'),
        'synopsis'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Synopsis'),
        'content'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Content'),
        'types'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'list', 'delimiter'=>'::', 'name'=>'Types'),
        'categories'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'list', 'delimiter'=>'::', 'name'=>'Categories'),
        'tags'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'list', 'delimiter'=>'::', 'name'=>'Tags'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'lapt', 'private', 'checkAccess');
    $rc = ciniki_lapt_checkAccess($ciniki, $args['tnid'], 'ciniki.lapt.documentAdd');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Setup permalink
    //
    if( !isset($args['permalink']) || $args['permalink'] == '' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'makePermalink');
        $args['permalink'] = ciniki_core_makePermalink($ciniki, $args['title']);
    }

    //
    // Make sure the permalink is unique
    //
    $strsql = "SELECT id, title, permalink "
        . "FROM ciniki_lapt_documents "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND permalink = '" . ciniki_core_dbQuote($ciniki, $args['permalink']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.lapt', 'item');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( $rc['num_rows'] > 0 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.lapt.4', 'msg'=>'You already have a document with that title, please choose another.'));
    }

    //
    // Start transaction
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.lapt');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Add the document to the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    $rc = ciniki_core_objectAdd($ciniki, $args['tnid'], 'ciniki.lapt.document', $args, 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.lapt');
        return $rc;
    }
    $document_id = $rc['id'];

    //
    // Update the types
    //
    if( isset($args['types']) ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'tagsUpdate');
        $rc = ciniki_core_tagsUpdate($ciniki, 'ciniki.lapt', 'tag', $args['tnid'],
            'ciniki_lapt_tags', 'ciniki_lapt_history',
            'document_id', $document_id, 20, $args['types']);
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.lapt');
            return $rc;
        }
    }

    //
    // Update the categories
    //
    if( isset($args['categories']) ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'tagsUpdate');
        $rc = ciniki_core_tagsUpdate($ciniki, 'ciniki.lapt', 'tag', $args['tnid'],
            'ciniki_lapt_tags', 'ciniki_lapt_history',
            'document_id', $document_id, 40, $args['categories']);
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.lapt');
            return $rc;
        }
    }

    //
    // Update the tags
    //
    if( isset($args['tags']) ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'tagsUpdate');
        $rc = ciniki_core_tagsUpdate($ciniki, 'ciniki.lapt', 'tag', $args['tnid'],
            'ciniki_lapt_tags', 'ciniki_lapt_history',
            'document_id', $document_id, 60, $args['tags']);
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.lapt');
            return $rc;
        }
    }

    //
    // Commit the transaction
    //
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.lapt');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Update the last_change date in the tenant modules
    // Ignore the result, as we don't want to stop user updates if this fails.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'updateModuleChangeDate');
    ciniki_tenants_updateModuleChangeDate($ciniki, $args['tnid'], 'ciniki', 'lapt');

    //
    // Update the web index if enabled
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'hookExec');
    ciniki_core_hookExec($ciniki, $args['tnid'], 'ciniki', 'web', 'indexObject', array('object'=>'ciniki.lapt.document', 'object_id'=>$document_id));

    return array('stat'=>'ok', 'id'=>$document_id);
}
?>
