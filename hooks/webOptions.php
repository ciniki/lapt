<?php
//
// Description
// -----------
// This function will return the list of options for the module that can be set for the website.
//
// Arguments
// ---------
// ciniki:
// settings:        The web settings structure.
// tnid:     The ID of the tenant to get lapt for.
//
// args:            The possible arguments for posts
//
//
// Returns
// -------
//
function ciniki_lapt_hooks_webOptions(&$ciniki, $tnid, $args) {

    //
    // Check to make sure the module is enabled
    //
    if( !isset($ciniki['tenant']['modules']['ciniki.lapt']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.lapt.38', 'msg'=>"I'm sorry, the page you requested does not exist."));
    }

    //
    // Get the settings from the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDetailsQueryDash');
    $rc = ciniki_core_dbDetailsQueryDash($ciniki, 'ciniki_web_settings', 'tnid', $tnid, 'ciniki.web', 'settings', 'page-lapt');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['settings']) ) {
        $settings = array();
    } else {
        $settings = $rc['settings'];
    }

    //
    // Get the list of Types
    //

    $options = array();
/*    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.lapt', 0x01) ) {
        $strsql = "SELECT types.tag_name AS label, "
            . "types.permalink AS value "
            . "FROM ciniki_lapt_tags AS types "
            . "INNER JOIN ciniki_lapt_documents AS documents ON ("
                . "types.document_id = documents.id "
                . "AND documents.status = 50 "
                . "AND documents.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "WHERE types.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND types.tag_type = 20 "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.lapt', 'item');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.lapt.58', 'msg'=>'Unable to load types', 'err'=>$rc['err']));
        }
        if( isset($rc['rows']) && count($rc['rows']) > 0 ) {
            array_unshift($rc['rows'], array('label'=>'All', 'value'=>''));
            $options[] = array(
                'label'=>'Display Type',
                'setting'=>'page-lapt-display-type', 
                'type'=>'select',
                'value'=>(isset($settings['page-lapt-display-type'])?$settings['page-lapt-display-type']:''),
                'options'=>$rc['rows'],
                ); 
        }
    }  */
/*    $options[] = array(
        'label'=>'Display Format',
        'setting'=>'page-lapt-display-format', 
        'type'=>'toggle',
        'value'=>(isset($settings['page-lapt-display-format'])?$settings['page-lapt-display-format']:'cilist'),
        'toggles'=>array(
            array('value'=>'cilist', 'label'=>'Date List'),
            array('value'=>'imagelist', 'label'=>'Image List'),
            ),
        ); */

    $pages['ciniki.lapt'] = array('name'=>'Library', 'options'=>$options);

    //
    // For specific pages, no as many options are required
    //
    $options = array();
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.lapt', 0x01) ) {
        $strsql = "SELECT types.tag_name, "
            . "types.permalink "
            . "FROM ciniki_lapt_tags AS types "
            . "INNER JOIN ciniki_lapt_documents AS documents ON ("
                . "types.document_id = documents.id "
                . "AND documents.status = 50 "
                . "AND documents.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "WHERE types.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND types.tag_type = 20 "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.lapt', 'item');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.lapt.39', 'msg'=>'Unable to load types', 'err'=>$rc['err']));
        }
        foreach($rc['rows'] as $row) {
            $pages['ciniki.lapt.' . $row['permalink']] = array('name'=>'Library - ' . $row['tag_name'], 'options'=>$options);
        } 
    }
    
    return array('stat'=>'ok', 'pages'=>$pages);
}
?>
