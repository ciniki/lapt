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
function ciniki_lapt_documentGet($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'document_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Document'),
        'tags'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Include Tags'),
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
    $rc = ciniki_lapt_checkAccess($ciniki, $args['tnid'], 'ciniki.lapt.documentGet');
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
    // Return default for new Document
    //
    if( $args['document_id'] == 0 ) {
        $document = array('id'=>0,
            'title'=>'',
            'permalink'=>'',
            'status'=>'20',
            'flags'=>'0',
            'image_id'=>'',
            'synopsis'=>'',
            'content'=>'',
        );
    }

    //
    // Get the details for an existing Document
    //
    else {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'lapt', 'private', 'documentLoad');
        $rc = ciniki_lapt_documentLoad($ciniki, $args['tnid'], $args['document_id']);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.lapt.40', 'msg'=>'Unable to load document', 'err'=>$rc['err']));
        }
        $document = $rc['document'];

        if( isset($document['images']) ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'images', 'private', 'loadCacheThumbnail');
            foreach($document['images'] as $img_id => $img) {
                if( isset($img['image_id']) && $img['image_id'] > 0 ) {
                    $rc = ciniki_images_loadCacheThumbnail($ciniki, $args['tnid'], $img['image_id'], 75);
                    if( $rc['stat'] != 'ok' ) {
                        return $rc;
                    }
                    $document['images'][$img_id]['image_data'] = 'data:image/jpg;base64,' . base64_encode($rc['image']);
                }
            }
        } else {
            $document['images'] = array();
        }
    }

    $rsp = array('stat'=>'ok', 'document'=>$document);

    //
    // Check if all tags should be returned
    //
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.lapt', 0x07) > 0
        && isset($args['tags']) && $args['tags'] == 'yes' 
        ) {
        $strsql = "SELECT DISTINCT tag_type, tag_name AS names "
            . "FROM ciniki_lapt_tags "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "ORDER BY tag_type, tag_name "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.links', array(
            array('container'=>'types', 'fname'=>'tag_type', 'fields'=>array('type'=>'tag_type', 'names'), 
                'dlists'=>array('names'=>'::')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['types']) ) {
            foreach($rc['types'] as $tid => $type) {
                if( $type['type'] == 20 ) {
                    $rsp['types'] = explode('::', $type['names']);
                } elseif( $type['type'] == 40 ) {
                    $rsp['categories'] = explode('::', $type['names']);
                } elseif( $type['type'] == 60 ) {
                    $rsp['tags'] = explode('::', $type['names']);
                }
            }
        }
    }

    return $rsp;
}
?>
