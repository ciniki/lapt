<?php
//
// Description
// -----------
// This function will process a web request for the blog module.
//
// Arguments
// ---------
// ciniki:
// settings:        The web settings structure.
// tnid:     The ID of the tenant to get post for.
//
// args:            The possible arguments for posts
//
//
// Returns
// -------
//
function ciniki_lapt_web_processRequest(&$ciniki, $settings, $tnid, $args) {

    if( !isset($ciniki['tenant']['modules']['ciniki.lapt']) ) {
        return array('stat'=>'404', 'err'=>array('code'=>'ciniki.lapt.41', 'msg'=>"I'm sorry, the page you requested does not exist."));
    }
    $page = array(
        'title'=>$args['page_title'],
        'breadcrumbs'=>$args['breadcrumbs'],
        'blocks'=>array(),
        );

    //
    // Setup the base url as the base url for this page. This may be altered below
    // as the uri_split is processed, but we do not want to alter the original passed in.
    //
    $base_url = $args['base_url'];
    $uri_split = $args['uri_split'];

    //
    // The following display screens are handled in this file
    //
    // types - Display the list of types as a icon gallery
    // typelist - Display the list of documents of a document type
    // categories - Display the list of categories as icon gallery
    // categorylist - Display the list documents for a document category
    // list - Display a list of all documents
    //
    $display = 'list';
    $ciniki['response']['head']['og']['url'] = $args['domain_base_url'];

    //
    // Setup titles
    //
    if( count($page['breadcrumbs']) == 0 ) {
        $page['breadcrumbs'][] = array('name'=>'Library', 'url'=>$args['base_url']);
    }

    //
    // Check for the type
    //
    $type_permalink = '';
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.lapt', 0x01) ) {
        //
        // Get the list of types
        //
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
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
        $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.lapt', array(
            array('container'=>'types', 'fname'=>'permalink', 'fields'=>array('tag_name')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.lapt.59', 'msg'=>'Unable to load types', 'err'=>$rc['err']));
        }
        $types = isset($rc['types']) ? $rc['types'] : array();

        $m = explode('.', $args['module_page']);
        if( isset($m[2]) && $m[2] != '' ) {
            //
            // The type was specified in the module_page setting for the calling page.
            // It is assumed a submenu item is linking directly to a type and now submenu should
            // be added, or a breadcrump added.
            //
            if( isset($types[$m[2]]['tag_name']) ) {
                $type_permalink = $m[2];
                $display = 'typelist';
            } else {
                return array('stat'=>'404', 'err'=>array('code'=>'ciniki.lapt.43', 'msg'=>'Page does not exist.'));
            }
        } else {
            //
            // The document type is specified in the URL and should then be included in breadcrumbs
            //
            foreach($types as $permalink => $type) {
                $page['submenu'][] = array('name'=>$type['tag_name'], 'url'=>$args['base_url'] . '/' . $permalink);
            }
            $display = 'types';
            if( isset($uri_split[0]) && isset($types[$uri_split[0]]) ) {
                $type_permalink = $uri_split[0];
            } 
            if( $type_permalink == '' && count($types) > 0 ) {
                $type_permalink = key($types);
            }
            if( $type_permalink != '' ) {
                $base_url .= '/' . $type_permalink;
                $ciniki['response']['head']['og']['url'] .= '/' . $type_permalink;
                $page['breadcrumbs'][] = array('name'=>$types[$type_permalink]['tag_name'], 'url'=>$base_url);
                array_shift($uri_split);
                $display = 'typelist';
            }
        }
    }

    //
    // Check for a category permalink in the url
    //
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.lapt', 0x02) ) {
        //
        // Get the settings for the tag categories
        //
        $strsql = "SELECT detail_key, detail_value "
            . "FROM ciniki_lapt_settings "
            . "WHERE detail_key like 'tag-40-%' "
            . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQueryList2');
        $rc = ciniki_core_dbQueryList2($ciniki, $strsql, 'ciniki.lapt', 'settings');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $category_settings = isset($rc['settings']) ? $rc['settings'] : array();

        //
        // Get the list of categories
        //
        if( isset($type_permalink) && $type_permalink != '' ) {
            $strsql = "SELECT categories.tag_name, "
                . "categories.permalink "
                . "FROM ciniki_lapt_tags AS types "
                . "INNER JOIN ciniki_lapt_tags AS categories ON ("
                    . "types.document_id = categories.document_id "
                    . "AND categories.tag_type = 40 "
                    . "AND categories.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                    . ") "
                . "INNER JOIN ciniki_lapt_documents AS documents ON ("
                    . "categories.document_id = documents.id "
                    . "AND documents.status = 50 "
                    . "AND documents.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                    . ") "
                . "WHERE types.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . "AND types.permalink = '" . ciniki_core_dbQuote($ciniki, $type_permalink) . "' "
                . "AND types.tag_type = 20 "
                . "";
        } else {
            $strsql = "SELECT categories.tag_name, "
                . "categories.permalink "
                . "FROM ciniki_lapt_tags AS categories "
                . "INNER JOIN ciniki_lapt_documents AS documents ON ("
                    . "categories.document_id = documents.id "
                    . "AND documents.status = 50 "
                    . "AND documents.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                    . ") "
                . "WHERE categories.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . "AND categories.tag_type = 40 "
                . "";
        }
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
        $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.lapt', array(
            array('container'=>'categories', 'fname'=>'permalink', 'fields'=>array('name'=>'tag_name', 'permalink')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.lapt.42', 'msg'=>'Unable to load categories', 'err'=>$rc['err']));
        }
        $categories = isset($rc['categories']) ? $rc['categories'] : array();

        foreach($categories as $permalink => $category) {
            $categories[$permalink]['image_id'] = 0;
            if( isset($category_settings["tag-40-image-{$permalink}"]) && $category_settings["tag-40-image-{$permalink}"] != '' ) {
                $categories[$permalink]['image_id'] = $category_settings["tag-40-image-{$permalink}"];
            }
            if( isset($category_settings["tag-40-content-{$permalink}"]) && $category_settings["tag-40-content-{$permalink}"] != '' ) {
                $categories[$permalink]['content'] = $category_settings["tag-40-content-{$permalink}"];
            }
        }
      
        $display = 'categories';
        if( isset($uri_split[0]) && isset($categories[$uri_split[0]]) ) {
            $category_permalink = $uri_split[0];
            $base_url .= '/' . $category_permalink;
            $ciniki['response']['head']['og']['url'] .= '/' . $category_permalink;
            $page['breadcrumbs'][] = array('name'=>$categories[$category_permalink]['name'], 'url'=>$base_url);
            array_shift($uri_split);
            $display = 'categorylist';
        }
    }

    //
    // Check for a document
    //
    if( isset($uri_split[0]) && $uri_split[0] != '' ) {
        $document_permalink = $uri_split[0];
        $display = 'document';
        //
        // Check for gallery pic request, or download request
        //
        if( isset($uri_split[1]) && $uri_split[1] == 'gallery' && isset($uri_split[2]) && $uri_split[2] != '' ) {
            $image_permalink = $uri_split[2];
            $display = 'documentpic';
        } elseif( isset($uri_split[1]) && $uri_split[1] == 'download' && isset($uri_split[2]) && $uri_split[2] != '' ) {
            $file_permalink = $uri_split[2];
            $display = 'documentdownload';
        } 
        $ciniki['response']['head']['og']['url'] .= '/' . $document_permalink;
        $base_url .= '/' . $document_permalink;
    }

    //
    // The following sections are different screens used to display the document
    //

    //
    // Display a list of documents
    //
    if( $display == 'list' || $display == 'typelist' || $display == 'categorylist' ) {
        if( isset($category_permalink) && isset($categories[$category_permalink]['content']) && $categories[$category_permalink]['content'] != '' ) {
            if( isset($categories[$category_permalink]['image_id']) && $categories[$category_permalink]['image_id'] > 0 ) {
                $page['blocks'][] = array('type'=>'image', 'section'=>'primary-image', 'primary'=>'yes', 
                    'image_id'=>$categories[$category_permalink]['image_id'], 
                    'base_url'=>$base_url,
                    'title'=>$document['title'],
                    );
            }
            if( isset($categories[$category_permalink]['content']) && $categories[$category_permalink]['content'] != '' ) {
                $page['blocks'][] = array('type'=>'content', 'section'=>'content', 'title'=>'', 
                    'content'=>$categories[$category_permalink]['content'],
                    );
            }
        }

        //
        // Display list as thumbnails
        //
        if( isset($type_permalink) && $type_permalink != '' && isset($category_permalink) && $category_permalink != '' ) {
            $strsql = "SELECT documents.id, "
                . "documents.title, "
                . "documents.permalink, "
                . "documents.image_id, "
                . "documents.synopsis, "
                . "'yes' AS is_details "
                . "FROM ciniki_lapt_tags AS types "
                . "INNER JOIN ciniki_lapt_tags AS categories ON ("
                    . "types.document_id = categories.document_id "
                    . "AND categories.permalink = '" . ciniki_core_dbQuote($ciniki, $category_permalink) . "' "
                    . "AND categories.tag_type = 40 "
                    . "AND categories.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                    . ") "
                . "INNER JOIN ciniki_lapt_documents AS documents ON ("
                    . "categories.document_id = documents.id "
                    . "AND documents.status = 50 "
                    . "AND documents.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                    . ") "
                . "WHERE types.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . "AND types.permalink = '" . ciniki_core_dbQuote($ciniki, $type_permalink) . "' "
                . "AND types.tag_type = 20 "
                . "ORDER BY documents.doc_date DESC, documents.title "
                . "";
            
        } elseif( isset($type_permalink) && $type_permalink != '' ) {
            $strsql = "SELECT documents.id, "
                . "documents.title, "
                . "documents.permalink, "
                . "documents.image_id, "
                . "documents.synopsis, "
                . "'yes' AS is_details "
                . "FROM ciniki_lapt_tags AS types "
                . "LEFT JOIN ciniki_lapt_documents AS documents ON ("
                    . "types.document_id = documents.id "
                    . "AND documents.status = 50 "
                    . "AND documents.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                    . ") "
                . "WHERE types.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . "AND types.permalink = '" . ciniki_core_dbQuote($ciniki, $type_permalink) . "' "
                . "AND types.tag_type = 20 "
                . "ORDER BY documents.doc_date DESC, documents.title "
                . "";
        } elseif( isset($category_permalink) && $category_permalink != '' ) {
            $strsql = "SELECT documents.id, "
                . "documents.title, "
                . "documents.permalink, "
                . "documents.image_id, "
                . "documents.synopsis, "
                . "'yes' AS is_details "
                . "FROM ciniki_lapt_tags AS categories "
                . "LEFT JOIN ciniki_lapt_documents AS documents ON ("
                    . "categories.document_id = documents.id "
                    . "AND documents.status = 50 "
                    . "AND documents.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                    . ") "
                . "WHERE categories.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . "AND categories.permalink = '" . ciniki_core_dbQuote($ciniki, $category_permalink) . "' "
                . "AND categories.tag_type = 40 "
                . "ORDER BY documents.doc_date DESC, documents.title "
                . "";
        } else {
            $strsql = "SELECT documents.id, "
                . "documents.title, "
                . "documents.permalink, "
                . "documents.image_id, "
                . "documents.synopsis, "
                . "'yes' AS is_details "
                . "FROM ciniki_lapt_documents AS documents "
                . "WHERE documents.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . "AND documents.status = 50 "
                . "ORDER BY documents.doc_date DESC, documents.title "
                . "";
        }
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.lapt', 'document');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( !isset($rc['rows']) || count($rc['rows']) == 0 ) {
            $page['blocks'][] = array('type'=>'content', 'content'=>"There are currently no document available. Please check back soon.");
        } else {
            $page['blocks'][] = array('type'=>'imagelist', 'noimage'=>'yes', 'base_url'=>$base_url, 'list'=>$rc['rows']);
        }
    }

    elseif( $display == 'categories' ) {
        $page['blocks'][] = array('type'=>'tagimages', 'base_url'=>$base_url, 'tags'=>$categories);
    }

    elseif( $display == 'document' || $display == 'documentpic' || $display == 'documentdownload' ) {
        if( isset($category) ) {
            $ciniki['response']['head']['links'][] = array('rel'=>'canonical', 'href'=>$args['base_url'] . '/' . $document_permalink);
        }
        ciniki_core_loadMethod($ciniki, 'ciniki', 'lapt', 'private', 'documentLoad');
        $rc = ciniki_lapt_documentLoad($ciniki, $tnid, $document_permalink);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'404', 'err'=>array('code'=>'ciniki.lapt.60', 'msg'=>'Invalid document requested'));
        }
        if( isset($rc['document']) && $rc['document']['status'] != 50 ) {
            return array('stat'=>'404', 'err'=>array('code'=>'ciniki.lapt.55', 'msg'=>"We're sorry, the page you requested is not available."));
        }
        if( !isset($rc['document']) ) {
            return array('stat'=>'404', 'err'=>array('code'=>'ciniki.lapt.56', 'msg'=>"We're sorry, the page you requested is not available."));
        } else {
            $document = $rc['document'];
            
            //
            // Add the primary image to the secondary image list
            //
            $primary_image_permalink = '';
            if( isset($document['image_id']) && $document['image_id'] > 0 ) {
                if( isset($document['images']) && count($document['images']) > 0 ) {
                    foreach($document['images'] as $img) {
                        if( $img['image_id'] == $document['image_id'] ) {
                            $primary_image_permalink = 'gallery/' . $img['permalink'];
                            break;
                        }
                    }
                }
                if( $primary_image_permalink == '' ) {
                    $primary_image_permalink = 'gallery/primary';
                    if( !isset($document['images']) ) {
                        $document['images'] = array();
                    }
//                    $document['images'][] = array('id'=>0, 'permalink'=>'primary', 'title'=>'', 'image_id'=>$document['image_id'], 'description'=>'', 'flags'=>1);
                }
            }

            $page['title'] = $document['title'];
            $page['breadcrumbs'][] = array('name'=>$document['title'], 'url'=>$base_url);
            if( $display == 'documentpic' ) {
                $page['title'] = "<a href='$base_url'>" . $document['title'] . "</a>";
                ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'galleryFindNextPrev');
                $rc = ciniki_web_galleryFindNextPrev($ciniki, $document['images'], $image_permalink);
                if( $rc['stat'] != 'ok' ) {
                    return $rc;
                }
                if( $rc['img'] == NULL ) {
                    $page['blocks'][] = array('type'=>'message', 'content'=>"I'm sorry, but we can't seem to find the image you requested.");
                } else {
                    $page['breadcrumbs'][] = array('name'=>$rc['img']['title'], 'url'=>$base_url . '/gallery/' . $image_permalink);
                    if( $rc['img']['title'] != '' ) {
                        $page['title'] .= ' - ' . $rc['img']['title'];
                    }
                    $block = array('type'=>'galleryimage', 'section'=>'gallery-primary-image', 'primary'=>'yes', 'image'=>$rc['img']);
                    if( $rc['prev'] != null ) {
                        $block['prev'] = array('url'=>$base_url . '/gallery/' . $rc['prev']['permalink'], 'image_id'=>$rc['prev']['image_id']);
                    }
                    if( $rc['next'] != null ) {
                        $block['next'] = array('url'=>$base_url . '/gallery/' . $rc['next']['permalink'], 'image_id'=>$rc['next']['image_id']);
                    }
                    $page['blocks'][] = $block;
                    if( count($document['images']) > 1 ) {
                        $page['blocks'][] = array('type'=>'gallery', 'title'=>'Additional Images', 'section'=>'gallery-images', 'base_url'=>$base_url . '/gallery', 'images'=>$document['images']);
                    }
                }
            } elseif( $display == 'documentdownload' ) {
                $file_permalink = preg_replace("/\.[^\.]+$/", '', $file_permalink);
                if( isset($document['files']) ) { 
                    foreach($document['files'] as $fid => $file) {
                        if( $file['permalink'] == $file_permalink && ($file['flags']&0x01) == 0x01 ) {
                            //
                            // Get the tenant storage directory
                            //
                            ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'hooks', 'storageDir');
                            $rc = ciniki_tenants_hooks_storageDir($ciniki, $tnid, array());
                            if( $rc['stat'] != 'ok' ) {
                                return $rc;
                            }
                            $tenant_storage_dir = $rc['storage_dir'];
                            //
                            // Get the storage filename
                            //
                            $storage_filename = $tenant_storage_dir . '/ciniki.lapt/files/' . $file['uuid'][0] . '/' . $file['uuid'];
                            if( file_exists($storage_filename) ) {
                                $file['binary_content'] = file_get_contents($storage_filename);
                            }

                            return array('stat'=>'ok', 'download'=>$file);
                        }
                    }
                } 
                $page['blocks'][] = array('type'=>'message', 'content'=>"I'm sorry, but we can't seem to find the file you requested.");

            } else {
                if( isset($document['image_id']) && $document['image_id'] > 0 ) {
                    $page['blocks'][] = array('type'=>'image', 'section'=>'primary-image', 'primary'=>'yes', 'image_id'=>$document['image_id'], 
                        'base_url'=>$base_url, 'permalink'=>$primary_image_permalink,
                        'title'=>$document['title']);
                }
                if( isset($document['content']) && $document['content'] != '' ) {
                    $page['blocks'][] = array('type'=>'content', 'section'=>'content', 'title'=>'', 'content'=>$document['content']);
                } elseif( isset($document['synopsis']) && $document['synopsis'] != '' ) {
                    $page['blocks'][] = array('type'=>'content', 'section'=>'content', 'title'=>'', 'content'=>$document['synopsis']);
                }
                if( isset($document['links']) && count($document['links']) > 0 ) {
                    $page['blocks'][] = array('type'=>'links', 'section'=>'links', 'title'=>'Links', 'links'=>$document['links']);
                }
                if( isset($document['files']) && count($document['files']) > 0 ) {
                    //
                    // Remove non-public images
                    //
                    foreach($document['files'] as $fid => $file) {
                        if( ($file['flags']&0x01) == 0 ) {
                            unset($document['files'][$fid]);
                        }
                    }
                    $page['blocks'][] = array('type'=>'files', 
                        'section'=>'files', 
                        'title'=> 'Downloads',
                        'base_url'=>$base_url . '/download',
                        'files'=>$document['files']);
                }
                // Add share buttons  
                if( !isset($settings['page-lapt-share-buttons']) || $settings['page-lapt-share-buttons'] == 'yes' ) {
                    $page['blocks'][] = array('type'=>'sharebuttons', 'section'=>'share', 'pagetitle'=>$document['title'], 'tags'=>array());
                }
                // Add gallery
                if( isset($document['images']) 
                    && (($document['image_id'] > 0 && count($document['images']) > 1 ) || ($document['image_id'] == 0 && count($document['images']) > 0)) ) {
                    //
                    // Remove non-public images
                    //
                    foreach($document['images'] as $iid => $image) {
                        if( ($image['flags']&0x01) == 0 ) {
                            unset($document['images'][$iid]);
                        }
                    }
                    $page['blocks'][] = array('type'=>'gallery', 'title'=>'Additional Images', 'section'=>'additional-images', 'base_url'=>$base_url . '/gallery', 'images'=>$document['images']);
                }
            }
        }
    }

    //
    // Return error if nothing found to display
    //
    else {
        return array('stat'=>'404', 'err'=>array('code'=>'ciniki.lapt.35', 'msg'=>"We're sorry, the page you requested is not available."));
    }

    return array('stat'=>'ok', 'page'=>$page);
}
?>
