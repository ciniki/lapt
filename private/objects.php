<?php
//
// Description
// -----------
// This function returns the list of objects for the module.
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_lapt_objects(&$ciniki) {
    //
    // Build the objects
    //
    $objects = array();
    $objects['document'] = array(
        'name' => 'Document',
        'sync' => 'yes',
        'o_name' => 'document',
        'o_container' => 'documents',
        'table' => 'ciniki_lapt_documents',
        'fields' => array(
            'title' => array('name'=>'Title'),
            'permalink' => array('name'=>'Permalink'),
            'status' => array('name'=>'Status', 'default'=>'20'),
            'flags' => array('name'=>'Options', 'default'=>'0'),
            'image_id' => array('name'=>'Primary Image', 'ref'=>'ciniki.images.image', 'default'=>'0'),
            'synopsis' => array('name'=>'Synopsis', 'default'=>''),
            'content' => array('name'=>'Content', 'default'=>''),
            ),
        'history_table' => 'ciniki_lapt_history',
        );
    $objects['file'] = array(
        'name' => 'File',
        'sync' => 'yes',
        'o_name' => 'file',
        'o_container' => 'files',
        'table' => 'ciniki_lapt_files',
        'fields' => array(
            'document_id' => array('name'=>'Document', 'ref'=>'ciniki.lapt.document'),
            'name' => array('name'=>'Name'),
            'permalink' => array('name'=>'Permalink'),
            'flags' => array('name'=>'Options', 'default'=>'0'),
            'description' => array('name'=>'Description', 'default'=>''),
            'org_filename' => array('name'=>'Original Filename'),
            'extension' => array('name'=>'Extension'),
            ),
        'history_table' => 'ciniki_lapt_history',
        );
    $objects['image'] = array(
        'name' => 'Image',
        'sync' => 'yes',
        'o_name' => 'image',
        'o_container' => 'images',
        'table' => 'ciniki_lapt_images',
        'fields' => array(
            'document_id' => array('name'=>'Document', 'ref'=>'ciniki.lapt.document'),
            'name' => array('name'=>'Name', 'default'=>''),
            'permalink' => array('name'=>'', 'default'=>''),
            'flags' => array('name'=>'Options', 'default'=>'0'),
            'image_id' => array('name'=>'', 'ref'=>'ciniki.images.image'),
            'description' => array('name'=>'', 'default'=>''),
            ),
        'history_table' => 'ciniki_lapt_history',
        );
    $objects['link'] = array(
        'name' => 'Link',
        'sync' => 'yes',
        'o_name' => 'link',
        'o_container' => 'links',
        'table' => 'ciniki_lapt_links',
        'fields' => array(
            'document_id' => array('name'=>'Document', 'ref'=>'ciniki.lapt.document'),
            'name' => array('name'=>'Name'),
            'link_type' => array('name'=>'', 'default'=>'1000'),
            'url' => array('name'=>'URL'),
            'description' => array('name'=>'Description', 'default'=>''),
            ),
        'history_table' => 'ciniki_lapt_history',
        );
    $objects['tag'] = array(
        'name' => 'Tag',
        'sync' => 'yes',
        'o_name' => 'tag',
        'o_container' => 'tags',
        'table' => 'ciniki_lapt_tags',
        'fields' => array(
            'document_id' => array('name'=>'Document', 'ref'=>'ciniki.lapt.document'),
            'tag_type' => array('name'=>'Type'),
            'tag_name' => array('name'=>'Name'),
            'permalink' => array('name'=>'Permalink'),
            ),
        'history_table' => 'ciniki_lapt_history',
        );
    //
    return array('stat'=>'ok', 'objects'=>$objects);
}
?>
