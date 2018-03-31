<?php
//
// Description
// ===========
// This method will return all the information about an tag.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the tag is attached to.
// permalink:          The ID of the tag to get the details for.
//
// Returns
// -------
//
function ciniki_lapt_tagGet($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'tag_type'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tag Type'),
        'permalink'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tag Name'),
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
    $rc = ciniki_lapt_checkAccess($ciniki, $args['tnid'], 'ciniki.lapt.tagGet');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Grab the settings for the tenant from the database
    //
    $strsql = "SELECT detail_key, detail_value "
        . "FROM ciniki_lapt_settings "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND detail_key LIKE 'tag-" . ciniki_core_dbQuote($ciniki, $args['tag_type']) . "-%-" . ciniki_core_dbQuote($ciniki, $args['permalink']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQueryList2');
    $rc = ciniki_core_dbQueryList2($ciniki, $strsql, 'ciniki.lapt', 'settings');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $settings = isset($rc['settings']) ? $rc['settings'] : array();

    $tag = array(
        'title'=>(isset($settings["tag-{$args['tag_type']}-title-{$args['permalink']}"]) ? $settings["tag-{$args['tag_type']}-title-{$args['permalink']}"] : ''),
        'sequence'=>(isset($settings["tag-{$args['tag_type']}-sequence-{$args['permalink']}"]) ? $settings["tag-{$args['tag_type']}-sequence-{$args['permalink']}"] : ''),
        'image'=>(isset($settings["tag-{$args['tag_type']}-image-{$args['permalink']}"]) ? $settings["tag-{$args['tag_type']}-image-{$args['permalink']}"] : ''),
        'content'=>(isset($settings["tag-{$args['tag_type']}-content-{$args['permalink']}"]) ? $settings["tag-{$args['tag_type']}-content-{$args['permalink']}"] : ''),
        );

    return array('stat'=>'ok', 'tag'=>$tag);
}
?>
