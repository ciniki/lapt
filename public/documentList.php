<?php
//
// Description
// -----------
// This method will return the list of Documents for a tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to get Document for.
//
// Returns
// -------
//
function ciniki_lapt_documentList($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'type'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Type'),
        'category'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Category'),
        'tags'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Tags'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner, or sys admin.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'lapt', 'private', 'checkAccess');
    $rc = ciniki_lapt_checkAccess($ciniki, $args['tnid'], 'ciniki.lapt.documentList');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the list of documents
    //
    $strsql = "SELECT documents.id, "
        . "documents.title, "
        . "documents.permalink, "
        . "documents.status, "
        . "documents.flags ";
    if( isset($args['type']) && $args['type'] != '' 
        && isset($args['category']) && $args['category'] != '' 
        ) {
        $strsql .= "FROM ciniki_lapt_tags AS types "
            . "INNER JOIN ciniki_lapt_documents AS documents ON ("
                . "types.document_id = documents.id "
                . "AND documents.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
            . "INNER JOIN ciniki_lapt_tags AS categories ON ("
                . "documents.id = categories.document_id "
                . "AND categories.permalink = '" . ciniki_core_dbQuote($ciniki, $args['category']) . "' "
                . "AND categories.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
            . "WHERE types.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND types.permalink = '" . ciniki_core_dbQuote($ciniki, $args['type']) . "' "
            . "";
    } elseif( isset($args['type']) && $args['type'] != '' ) {
        $strsql .= "FROM ciniki_lapt_tags AS types "
            . "LEFT JOIN ciniki_lapt_documents AS documents ON ("
                . "types.document_id = documents.id "
                . "AND documents.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
            . "WHERE types.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND types.permalink = '" . ciniki_core_dbQuote($ciniki, $args['type']) . "' "
            . "";
    } elseif( isset($args['category']) && $args['category'] != '' ) {
        $strsql .= "FROM ciniki_lapt_tags AS categories "
            . "LEFT JOIN ciniki_lapt_documents AS documents ON ("
                . "categories.document_id = documents.id "
                . "AND documents.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
            . "WHERE categories.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND categories.permalink = '" . ciniki_core_dbQuote($ciniki, $args['category']) . "' "
            . "";
    } else {
        $strsql .= "FROM ciniki_lapt_documents AS documents "
            . "WHERE documents.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "";
    }
    $strsql .= "ORDER BY documents.title ";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.lapt', array(
        array('container'=>'documents', 'fname'=>'id', 
            'fields'=>array('id', 'title', 'permalink', 'status', 'flags')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['documents']) ) {
        $documents = $rc['documents'];
    } else {
        $documents = array();
    }

    $rsp = array('stat'=>'ok', 'documents'=>$documents);

    //
    // Get the list of types
    //
    if( isset($args['tags']) && $args['tags'] == 'yes' ) {
        $rsp['types'] = array();
        $rsp['categories'] = array();
        //
        // Get the list of types
        //
        $strsql = "SELECT tag_name, permalink, COUNT(document_id) AS num_documents "
            . "FROM ciniki_lapt_tags "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND tag_type = 20 "
            . "GROUP BY tag_name "
            . "ORDER BY tag_name "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.links', array(
            array('container'=>'names', 'fname'=>'tag_name', 'fields'=>array('name'=>'tag_name', 'permalink', 'num_documents')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['names']) ) {
            $rsp['types'] = $rc['names'];
        }
        //
        // Get the list of categories, but only categories of type if specified
        //
        if( isset($args['type']) && $args['type'] != '' ) {
            $strsql = "SELECT categories.tag_name, categories.permalink, COUNT(categories.document_id) AS num_documents "
                . "FROM ciniki_lapt_tags AS types "
                . "INNER JOIN ciniki_lapt_tags AS categories ON ("
                    . "types.document_id = categories.document_id "
                    . "AND categories.tag_type = 40 "
                    . "AND categories.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") "
                . "WHERE types.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "AND types.permalink = '" . ciniki_core_dbQuote($ciniki, $args['type']) . "' "
                . "AND types.tag_type = 20 "
                . "GROUP BY permalink "
                . "ORDER BY permalink "
                . "";
        } else {
            $strsql = "SELECT tag_name, permalink, COUNT(document_id) AS num_documents "
                . "FROM ciniki_lapt_tags "
                . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "AND tag_type = 40 "
                . "GROUP BY permalink "
                . "ORDER BY permalink "
                . "";
        }
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.links', array(
            array('container'=>'names', 'fname'=>'permalink', 'fields'=>array('name'=>'tag_name', 'permalink', 'num_documents')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['names']) ) {
            $rsp['categories'] = $rc['names'];
        }
    }

    return $rsp;
}
?>
