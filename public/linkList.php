<?php
//
// Description
// -----------
// This method will return the list of Links for a tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to get Link for.
//
// Returns
// -------
//
function ciniki_lapt_linkList($ciniki) {
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
    $rc = ciniki_lapt_checkAccess($ciniki, $args['tnid'], 'ciniki.lapt.linkList');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the list of links
    //
    $strsql = "SELECT ciniki_lapt_links.id, "
        . "ciniki_lapt_links.document_id, "
        . "ciniki_lapt_links.name, "
        . "ciniki_lapt_links.link_type, "
        . "ciniki_lapt_links.url "
        . "FROM ciniki_lapt_links "
        . "WHERE ciniki_lapt_links.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.lapt', array(
        array('container'=>'links', 'fname'=>'id', 
            'fields'=>array('id', 'document_id', 'name', 'link_type', 'url')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['links']) ) {
        $links = $rc['links'];
        $link_ids = array();
        foreach($links as $iid => $link) {
            $link_ids[] = $link['id'];
        }
    } else {
        $links = array();
        $link_ids = array();
    }

    return array('stat'=>'ok', 'links'=>$links, 'nplist'=>$link_ids);
}
?>
