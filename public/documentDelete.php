<?php
//
// Description
// -----------
// This method will delete an document.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:            The ID of the tenant the document is attached to.
// document_id:            The ID of the document to be removed.
//
// Returns
// -------
//
function ciniki_lapt_documentDelete(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'document_id'=>array('required'=>'yes', 'blank'=>'yes', 'name'=>'Document'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'lapt', 'private', 'checkAccess');
    $rc = ciniki_lapt_checkAccess($ciniki, $args['tnid'], 'ciniki.lapt.documentDelete');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the current settings for the document
    //
    $strsql = "SELECT id, uuid "
        . "FROM ciniki_lapt_documents "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND id = '" . ciniki_core_dbQuote($ciniki, $args['document_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.lapt', 'document');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['document']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.lapt.5', 'msg'=>'Document does not exist.'));
    }
    $document = $rc['document'];

    //
    // Check for any dependencies before deleting
    //

    //
    // Check if any modules are currently using this object
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectCheckUsed');
    $rc = ciniki_core_objectCheckUsed($ciniki, $args['tnid'], 'ciniki.lapt.document', $args['document_id']);
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.lapt.6', 'msg'=>'Unable to check if the document is still being used.', 'err'=>$rc['err']));
    }
    if( $rc['used'] != 'no' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.lapt.7', 'msg'=>'The document is still in use. ' . $rc['msg']));
    }

    //
    // Start transaction
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDelete');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.lapt');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Remove the document tags
    //
    $strsql = "SELECT id, uuid "
        . "FROM ciniki_lapt_tags "
        . "WHERE document_id = '" . ciniki_core_dbQuote($ciniki, $args['document_id']) . "' "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.lapt', 'item');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.lapt.11', 'msg'=>'Unable to load item', 'err'=>$rc['err']));
    }
    if( isset($rc['rows']) ) {
        $tags = $rc['rows'];
        foreach($tags as $tag) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');
            $rc = ciniki_core_objectDelete($ciniki, $args['tnid'], 'ciniki.lapt.tag', $tag['id'], $tag['uuid'], 0x04);
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.lapt.12', 'msg'=>'Unable to remove tag', 'err'=>$rc['err']));
            }
        }
    }

    //
    // Remove the files
    //

    //
    // Remove the images
    //

    //
    // Remove the links
    //

    //
    // Remove the document
    //
    $rc = ciniki_core_objectDelete($ciniki, $args['tnid'], 'ciniki.lapt.document',
        $args['document_id'], $document['uuid'], 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.lapt');
        return $rc;
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

    return array('stat'=>'ok');
}
?>
