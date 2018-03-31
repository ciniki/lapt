<?php
//
// Description
// ===========
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_lapt_tagUpdate(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'tag_type'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tag'),
        'permalink'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tag'),
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
    $rc = ciniki_lapt_checkAccess($ciniki, $args['tnid'], 'ciniki.lapt.tagUpdate');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Load the settings
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
    
    //
    // Start transaction
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbInsert');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.lapt');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // The list of allowed fields for updating
    //
    $changelog_fields = array(
        'title',
        'sequence',
        'image',
        'content',
        );

    //
    // Check each valid setting and see if a new value was passed in the arguments for it.
    // Insert or update the entry in the ciniki_lapt_settings table
    //
    foreach($changelog_fields as $field) {  
        if( isset($ciniki['request']['args'][$field]) ) {
            if( isset($settings["tag-{$args['tag_type']}-{$field}-{$args['permalink']}"]) ) {
                // Update the settings
                if( $settings["tag-{$args['tag_type']}-{$field}-{$args['permalink']}"] != $ciniki['request']['args'][$field] ) {
                    $strsql = "UPDATE ciniki_lapt_settings "
                        . "SET detail_value = '" . ciniki_core_dbQuote($ciniki, $ciniki['request']['args'][$field]) . "' "
                        . ", last_updated = UTC_TIMESTAMP() "
                        . "WHERE detail_key = 'tag-" . ciniki_core_dbQuote($ciniki, "{$args['tag_type']}-{$field}-{$args['permalink']}") . "' "
                        . "";
                    $rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.lapt');
                    if( $rc['stat'] != 'ok' ) {
                        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.lapt');
                        return $rc;
                    }
                    ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.lapt', 'ciniki_lapt_history', $args['tnid'], 
                        2, 'ciniki_lapt_settings', "tag-{$args['tag_type']}-{$field}-{$args['permalink']}", 'detail_value', $ciniki['request']['args'][$field]);
                    $ciniki['syncqueue'][] = array('push'=>'ciniki.lapt.setting', 
                        'args'=>array('id'=>"tag-{$args['tag_type']}-{$field}-{$args['permalink']}"));
                }
            } else {
                // Add the setting
                error_log('add');
                $strsql = "INSERT INTO ciniki_lapt_settings (tnid, detail_key, detail_value, date_added, last_updated) "
                    . "VALUES ('" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "'"
                    . ", 'tag-" . ciniki_core_dbQuote($ciniki, "{$args['tag_type']}-{$field}-{$args['permalink']}") . "' "
                    . ", '" . ciniki_core_dbQuote($ciniki, $ciniki['request']['args'][$field]) . "'"
                    . ", UTC_TIMESTAMP(), UTC_TIMESTAMP()) ";
                $rc = ciniki_core_dbInsert($ciniki, $strsql, 'ciniki.lapt');
                if( $rc['stat'] != 'ok' ) {
                    ciniki_core_dbTransactionRollback($ciniki, 'ciniki.lapt');
                    return $rc;
                }
                ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.lapt', 'ciniki_lapt_history', $args['tnid'], 
                    1, 'ciniki_lapt_settings', "tag-{$args['tag_type']}-{$field}-{$args['permalink']}", 'detail_value', $ciniki['request']['args'][$field]);
                $ciniki['syncqueue'][] = array('push'=>'ciniki.lapt.setting', 
                    'args'=>array('id'=>"tag-{$args['tag_type']}-{$field}-{$args['permalink']}"));
            }
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
    ciniki_core_hookExec($ciniki, $args['tnid'], 'ciniki', 'web', 'indexObject', array('object'=>'ciniki.lapt.tag', 'object_id'=>$args['permalink']));

    return array('stat'=>'ok');
}
?>
