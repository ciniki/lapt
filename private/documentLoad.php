<?php
//
// Description
// ===========
// This method will return all the information about an document.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the document is attached to.
// document_id:          The ID of the document to get the details for.
//
// Returns
// -------
//
function ciniki_lapt_documentLoad($ciniki, $tnid, $document_id) {
    
    //
    // Load the date format strings for the user
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'php');
    
    //
    // Get the document
    //
    $strsql = "SELECT ciniki_lapt_documents.id, "
        . "ciniki_lapt_documents.title, "
        . "ciniki_lapt_documents.permalink, "
        . "ciniki_lapt_documents.status, "
        . "ciniki_lapt_documents.flags, "
        . "ciniki_lapt_documents.doc_date, "
        . "ciniki_lapt_documents.image_id, "
        . "ciniki_lapt_documents.synopsis, "
        . "ciniki_lapt_documents.content "
        . "FROM ciniki_lapt_documents "
        . "WHERE ciniki_lapt_documents.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' ";
    if( is_numeric($document_id) ) {
        $strsql .= "AND ciniki_lapt_documents.id = '" . ciniki_core_dbQuote($ciniki, $document_id) . "' ";
    } else {
        $strsql .= "AND ciniki_lapt_documents.permalink = '" . ciniki_core_dbQuote($ciniki, $document_id) . "' ";
    }
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.lapt', array(
        array('container'=>'documents', 'fname'=>'id', 
            'fields'=>array('id', 'title', 'permalink', 'status', 'flags', 'doc_date', 'image_id', 'synopsis', 'content'),
            'utctotz'=>array('doc_date'=>array('timezone'=>'UTC', 'format'=>$date_format)),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.lapt.8', 'msg'=>'Document not found', 'err'=>$rc['err']));
    }
    if( !isset($rc['documents'][0]) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.lapt.9', 'msg'=>'Unable to find Document'));
    }
    $document = $rc['documents'][0];

    //
    // Get the categories
    //
    $strsql = "SELECT tag_type, tag_name AS names "
        . "FROM ciniki_lapt_tags "
        . "WHERE document_id = '" . ciniki_core_dbQuote($ciniki, $document_id) . "' "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "ORDER BY tag_type, tag_name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.lapt', array(
        array('container'=>'tags', 'fname'=>'tag_type', 
            'fields'=>array('tag_type', 'names'), 'dlists'=>array('names'=>'::')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['tags']) ) {
        foreach($rc['tags'] as $tags) {
            if( $tags['tag_type'] == 20 ) {
                $document['types'] = $tags['names'];
            } elseif( $tags['tag_type'] == 40 ) {
                $document['categories'] = $tags['names'];
            } elseif( $tags['tag_type'] == 60 ) {
                $document['tags'] = $tags['names'];
            }
        }
    }

    //
    // Get the links for the document
    //
    $strsql = "SELECT id, name, link_type, url, description "
        . "FROM ciniki_lapt_links "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND document_id = '" . ciniki_core_dbQuote($ciniki, $document['id']) . "' "
        . "AND link_type = 1000 "
        . "";
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.lapt', array(
        array('container'=>'links', 'fname'=>'id', 'fields'=>array('id', 'name', 'url', 'description')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $document['links'] = isset($rc['links']) ? $rc['links'] : array();

    // 
    // Get the files
    //
    $strsql = "SELECT id, uuid, name, permalink, extension, description "
        . "FROM ciniki_lapt_files "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND (flags&0x01) = 0x01 "
        . "AND document_id = '" . ciniki_core_dbQuote($ciniki, $document['id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.lapt', array(
        array('container'=>'files', 'fname'=>'id', 'fields'=>array('id', 'uuid', 'name', 'permalink', 'extension', 'description')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $document['files'] = isset($rc['files']) ? $rc['files'] : array();

    // 
    // Get the images 
    //
    $strsql = "SELECT id, "
        . "name, "
        . "permalink, "
        . "flags, "
        . "image_id, "
        . "description "
        . "FROM ciniki_lapt_images "
        . "WHERE document_id = '" . ciniki_core_dbQuote($ciniki, $document['id']) . "' "
        . "AND (flags&0x01) = 0x01 "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "";
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.lapt', array(
        array('container'=>'images', 'fname'=>'id', 
            'fields'=>array('id', 'name', 'permalink', 'flags', 'image_id', 'description')),
    ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $document['images'] = isset($rc['images']) ? $rc['images'] : array();

    return array('stat'=>'ok', 'document'=>$document);
}
?>
