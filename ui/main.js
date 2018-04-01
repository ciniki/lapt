//
// This is the main app for the lapt module
//
function ciniki_lapt_main() {
    //
    // The panel to list the document
    //
    this.menu = new M.panel('Library', 'ciniki_lapt_main', 'menu', 'mc', 'medium narrowaside', 'sectioned', 'ciniki.lapt.main.menu');
    this.menu.data = {};
    this.menu.type_tag = '';
    this.menu.category = '';
    this.menu.sections = {
        'types':{'label':'Document Types', 'type':'simplegrid', 'aside':'yes', 'num_cols':2,
            'visible':'no',
            'noData':'No categories',
            'cellClasses':['', 'alignright'],
            },
        'categories':{'label':'Categories', 'type':'simplegrid', 'aside':'yes', 'num_cols':2,
            'visible':'no',
            'noData':'No categories',
            'cellClasses':['', 'alignright'],
            },
        'search':{'label':'', 'type':'livesearchgrid', 'livesearchcols':1,
            'cellClasses':[''],
            'hint':'Search document',
            'noData':'No document found',
            },
        'documents':{'label':'Documents', 'type':'simplegrid', 'num_cols':1,
            'noData':'No document',
            'addTxt':'Add Document',
            'addFn':'M.ciniki_lapt_main.document.open(\'M.ciniki_lapt_main.menu.open();\',0,null);'
            },
    }
    this.menu.liveSearchCb = function(s, i, v) {
        if( s == 'search' && v != '' ) {
            M.api.getJSONBgCb('ciniki.lapt.documentSearch', {'tnid':M.curTenantID, 'start_needle':v, 'limit':'25'}, function(rsp) {
                M.ciniki_lapt_main.menu.liveSearchShow('search',null,M.gE(M.ciniki_lapt_main.menu.panelUID + '_' + s), rsp.documents);
                });
        }
    }
    this.menu.liveSearchResultValue = function(s, f, i, j, d) {
        return d.title;
    }
    this.menu.liveSearchResultRowFn = function(s, f, i, j, d) {
        return 'M.ciniki_lapt_main.document.open(\'M.ciniki_lapt_main.menu.open();\',\'' + d.id + '\');';
    }
    this.menu.cellValue = function(s, i, j, d) {
        if( s == 'types' ) {
            switch (j) {
                case 0: return d.name;
                case 1: return '<button onclick=\'event.stopPropagation();M.ciniki_lapt_main.menu.editTag(20,"' + d.permalink + '");\'>Edit</button>';
            }
        }
        if( s == 'categories' ) {
            switch (j) {
                case 0: return d.name;
                case 1: return '<button onclick=\'event.stopPropagation();M.ciniki_lapt_main.menu.editTag(40,"' + d.permalink + '");\'>Edit</button>';
            }
        }
        if( s == 'documents' ) {
            switch(j) {
                case 0: return d.title;
            }
        }
    }
    this.menu.rowClass = function(s, i, d) {
        if( s == 'types' && d.permalink == this.type_tag ) {
            return 'highlight';
        } else if( s == 'categories' && d.permalink == this.category ) {
            return 'highlight';
        }
    }
    this.menu.rowFn = function(s, i, d) {
        if( s == 'types' ) {
            return 'M.ciniki_lapt_main.menu.switchType("' + d.permalink + '");';
        }
        if( s == 'categories' ) {
            return 'M.ciniki_lapt_main.menu.switchCategory("' + d.permalink + '");';
        }
        if( s == 'documents' ) {
            return 'M.ciniki_lapt_main.document.open(\'M.ciniki_lapt_main.menu.open();\',\'' + d.id + '\',);';
        }
    }
    this.menu.editTag = function(t,p) {
        M.ciniki_lapt_main.tag.open('M.ciniki_lapt_main.menu.open();',t,p);
    }
    this.menu.switchCategory = function(c) {
        this.category = c;
        this.open();
    }
    this.menu.switchType = function(t) {
        this.type_tag = t;
        this.category = '';
        this.open();
    }
    this.menu.open = function(cb) {
        M.api.getJSONCb('ciniki.lapt.documentList', {'tnid':M.curTenantID, 'type':this.type_tag, 'category':this.category, 'tags':'yes'}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_lapt_main.menu;
            p.data = rsp;
            p.sections.categories.visible = 'no';
            if( (rsp.types != null && rsp.types.length > 0) || (rsp.categories != null && rsp.categories.length > 0) ) {
                p.size = 'medium narrowaside';
                p.sections.types.visible = rsp.types != null && rsp.types.length > 0 ? 'yes':'no';
                p.sections.categories.visible = rsp.categories != null && rsp.categories.length > 0 ? 'yes':'no';
            } else {
                p.size = 'medium';
            }
            p.refresh();
            p.show(cb);
        });
    }
    this.menu.addClose('Back');

    //
    // The panel to edit a tag details
    //
    this.tag = new M.panel('Tag', 'ciniki_lapt_main', 'tag', 'mc', 'medium mediumaside', 'sectioned', 'ciniki.lapt.main.tag');
    this.tag.data = null;
    this.tag.tag_type = '';
    this.tag.permalink = '';
    this.tag.sections = {
        '_image':{'label':'Image', 'type':'imageform', 'aside':'yes', 'fields':{
            'image':{'label':'', 'type':'image_id', 'hidelabel':'yes', 'controls':'all', 'history':'no',
                'addDropImage':function(iid) {
                    M.ciniki_lapt_main.tag.setFieldValue('image', iid);
                    return true;
                    },
                'addDropImageRefresh':'',
                },
            }},
//        'general':{'label':'Category Details', 'aside':'yes', 'fields':{
//            'title':{'label':'Title', 'type':'text'},
//            'sequence':{'label':'Sequence', 'type':'text', 'size':'small'},
//            }},
        '_content':{'label':'Content', 'fields':{
            'content':{'label':'', 'hidelabel':'yes', 'hint':'', 'size':'large', 'type':'textarea'},
            }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_lapt_main.tag.save();'},
            }},
        };
    this.tag.fieldValue = function(s, i, d) { return this.data[i]; }
    this.tag.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.lapt.tagHistory', 'args':{'tnid':M.curTenantID, 'permalink':this.permalink, 'field':i}};
    }
    this.tag.open = function(cb, t, p) {
        if( t != null ) { this.tag_type = t; }
        if( p != null ) { this.permalink = p; }
        M.api.getJSONCb('ciniki.lapt.tagGet', {'tnid':M.curTenantID, 'tag_type':this.tag_type, 'permalink':this.permalink}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_lapt_main.tag;
            p.data = rsp.tag;
            p.refresh();
            p.show(cb);
        });
    }
    this.tag.save = function(cb) {
        if( cb == null ) { cb = 'M.ciniki_lapt_main.tag.close();'; }
        var c = this.serializeForm('no');
        if( c != '' ) {
            M.api.postJSONCb('ciniki.lapt.tagUpdate', {'tnid':M.curTenantID, 'tag_type':this.tag_type, 'permalink':this.permalink}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                eval(cb);
            });
        } else {
            eval(cb);
        }
    }
    this.tag.addButton('save', 'Save', 'M.ciniki_lapt_main.tag.save();');
    this.tag.addClose('Cancel');

    //
    // The panel to edit Document
    //
    this.document = new M.panel('Document', 'ciniki_lapt_main', 'document', 'mc', 'medium mediumaside', 'sectioned', 'ciniki.lapt.main.document');
    this.document.data = null;
    this.document.document_id = 0;
    this.document.sections = {
        'general':{'label':'', 'aside':'yes', 'fields':{
            'title':{'label':'Title', 'required':'yes', 'type':'text'},
            'status':{'label':'Status', 'type':'toggle', 'toggles':{'20':'Draft', '50':'Published', '90':'Archived'}},
            'doc_date':{'label':'Date', 'type':'date'},
//            'flags':{'label':'Options', 'type':'text'},
            }},
        '_types':{'label':'Document Type', 'aside':'yes', 
            'visible':function() {return M.modFlagSet('ciniki.lapt', 0x01);},
            'fields':{
                'types':{'label':'', 'hidelabel':'yes', 'type':'tags', 'tags':[], 'hint':'Enter a new document type: '},
            }},
        '_categories':{'label':'Categories', 'aside':'yes', 
            'visible':function() {return M.modFlagSet('ciniki.lapt', 0x02);},
            'fields':{
                'categories':{'label':'', 'hidelabel':'yes', 'type':'tags', 'tags':[], 'hint':'Enter a new category: '},
            }},
        '_tags':{'label':'Tags', 'aside':'yes', 
            'visible':function() {return M.modFlagSet('ciniki.lapt', 0x04);},
            'fields':{
                'tags':{'label':'', 'hidelabel':'yes', 'type':'tags', 'tags':[], 'hint':'Enter a new document tag: '},
            }},
        '_image_id':{'label':'', 'type':'imageform', 'aside':'yes', 'fields':{
            'image_id':{'label':'', 'type':'image_id', 'hidelabel':'yes', 'controls':'all', 'history':'no',
                'addDropImage':function(iid) {
                    M.ciniki_lapt_main.document.setFieldValue('image_id', iid);
                    return true;
                    },
                'addDropImageRefresh':'',
             },
        }},
        '_synopsis':{'label':'Synopsis', 'aside':'yes', 'fields':{
            'synopsis':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'small'},
            }},
        '_content':{'label':'Content', 'fields':{
            'content':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'large'},
            }},
        'files':{'label':'Files', 'type':'simplegrid', 'num_cols':1,
            'cellClasses':['multiline'],
            'addTxt':'Add File',
            'addFn':'M.ciniki_lapt_main.document.save("M.ciniki_lapt_main.file.open(\'M.ciniki_lapt_main.document.open();\',0,M.ciniki_lapt_main.document.document_id);");',
            },
        'links':{'label':'Links', 'type':'simplegrid', 'num_cols':1,
            'cellClasses':['multiline'],
            'addTxt':'Add Link',
            'addFn':'M.ciniki_lapt_main.document.save("M.ciniki_lapt_main.link.open(\'M.ciniki_lapt_main.document.open();\',0,M.ciniki_lapt_main.document.document_id);");',
            },
        'images':{'label':'Gallery', 'type':'simplethumbs'},
        '_images':{'label':'', 'type':'simplegrid', 'num_cols':1,
            'addTxt':'Add Image',
            'addFn':'M.ciniki_lapt_main.document.save("M.ciniki_lapt_main.image.open(\'M.ciniki_lapt_main.document.open();\',0,M.ciniki_lapt_main.document.document_id);");',
            },
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_lapt_main.document.save();'},
            'delete':{'label':'Delete', 
                'visible':function() {return M.ciniki_lapt_main.document.document_id > 0 ? 'yes' : 'no'; },
                'fn':'M.ciniki_lapt_main.document.remove();'},
            }},
        };
    this.document.fieldValue = function(s, i, d) { return this.data[i]; }
    this.document.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.lapt.documentHistory', 'args':{'tnid':M.curTenantID, 'document_id':this.document_id, 'field':i}};
    }
    this.document.cellValue = function(s, i, j, d) {
        if( s == 'files' || s == 'links' ) {
            return d.name;
        }
    }
    this.document.thumbFn = function(s, i, d) {
        return 'M.ciniki_lapt_main.document.save("M.ciniki_lapt_main.image.open(\'M.ciniki_lapt_main.document.open();\',' + d.id + ',M.ciniki_lapt_main.document.document_id);");';
    };
    this.document.rowFn = function(s, i, d) {
        if( s == 'files' ) {
            return 'M.ciniki_lapt_main.document.save("M.ciniki_lapt_main.file.open(\'M.ciniki_lapt_main.document.open();\',' + d.id + ',M.ciniki_lapt_main.document.document_id);");';
        }
        if( s == 'links' ) {
            return 'M.ciniki_lapt_main.document.save("M.ciniki_lapt_main.link.open(\'M.ciniki_lapt_main.document.open();\',' + d.id + ',M.ciniki_lapt_main.document.document_id);");';
        }
    }
    this.document.open = function(cb, did) {
        if( did != null ) { this.document_id = did; }
        M.api.getJSONCb('ciniki.lapt.documentGet', {'tnid':M.curTenantID, 'document_id':this.document_id, 'tags':'yes'}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            console.log(rsp);
            var p = M.ciniki_lapt_main.document;
            p.data = rsp.document;
            p.sections._types.fields.types.tags = rsp.types != null ? rsp.types : [];
            p.sections._categories.fields.categories.tags = rsp.categories != null ? rsp.categories : [];
//            p.sections._tags.fields.tags.tags = rsp.tags != null ? rsp.tags : [];
            console.log(rsp);
            p.refresh();
            p.show(cb);
        });
    }
    this.document.save = function(cb) {
        if( cb == null ) { cb = 'M.ciniki_lapt_main.document.close();'; }
        if( !this.checkForm() ) { return false; }
        if( this.document_id > 0 ) {
            var c = this.serializeForm('no');
            if( c != '' ) {
                M.api.postJSONCb('ciniki.lapt.documentUpdate', {'tnid':M.curTenantID, 'document_id':this.document_id}, c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    eval(cb);
                });
            } else {
                eval(cb);
            }
        } else {
            var c = this.serializeForm('yes');
            M.api.postJSONCb('ciniki.lapt.documentAdd', {'tnid':M.curTenantID}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_lapt_main.document.document_id = rsp.id;
                eval(cb);
            });
        }
    }
    this.document.remove = function() {
        if( confirm('Are you sure you want to remove document?') ) {
            M.api.getJSONCb('ciniki.lapt.documentDelete', {'tnid':M.curTenantID, 'document_id':this.document_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_lapt_main.document.close();
            });
        }
    }
    this.document.addButton('save', 'Save', 'M.ciniki_lapt_main.document.save();');
    this.document.addClose('Cancel');

    //
    // The panel to edit Link
    //
    this.link = new M.panel('Link', 'ciniki_lapt_main', 'link', 'mc', 'medium', 'sectioned', 'ciniki.lapt.main.link');
    this.link.data = null;
    this.link.document_id = 0;
    this.link.link_id = 0;
    this.link.sections = {
        'general':{'label':'', 'fields':{
            'name':{'label':'Name', 'required':'yes', 'type':'text'},
            'url':{'label':'URL', 'required':'yes', 'type':'text'},
            }},
        '_description':{'label':'Description', 'fields':{
            'description':{'label':'', 'hidelabel':'yes', 'type':'textarea'},
            }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_lapt_main.link.save();'},
            'delete':{'label':'Delete', 
                'visible':function() {return M.ciniki_lapt_main.link.link_id > 0 ? 'yes' : 'no'; },
                'fn':'M.ciniki_lapt_main.link.remove();'},
            }},
        };
    this.link.fieldValue = function(s, i, d) { return this.data[i]; }
    this.link.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.lapt.linkHistory', 'args':{'tnid':M.curTenantID, 'link_id':this.link_id, 'field':i}};
    }
    this.link.open = function(cb, lid, did) {
        if( lid != null ) { this.link_id = lid; }
        if( did != null ) { this.document_id = did; }
        M.api.getJSONCb('ciniki.lapt.linkGet', {'tnid':M.curTenantID, 'link_id':this.link_id}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_lapt_main.link;
            p.data = rsp.link;
            p.refresh();
            p.show(cb);
        });
    }
    this.link.save = function(cb) {
        if( cb == null ) { cb = 'M.ciniki_lapt_main.link.close();'; }
        if( !this.checkForm() ) { return false; }
        if( this.link_id > 0 ) {
            var c = this.serializeForm('no');
            if( c != '' ) {
                M.api.postJSONCb('ciniki.lapt.linkUpdate', {'tnid':M.curTenantID, 'link_id':this.link_id}, c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    eval(cb);
                });
            } else {
                eval(cb);
            }
        } else {
            var c = this.serializeForm('yes');
            M.api.postJSONCb('ciniki.lapt.linkAdd', {'tnid':M.curTenantID, 'document_id':this.document_id}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_lapt_main.link.link_id = rsp.id;
                eval(cb);
            });
        }
    }
    this.link.remove = function() {
        if( confirm('Are you sure you want to remove link?') ) {
            M.api.getJSONCb('ciniki.lapt.linkDelete', {'tnid':M.curTenantID, 'link_id':this.link_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_lapt_main.link.close();
            });
        }
    }
    this.link.addButton('save', 'Save', 'M.ciniki_lapt_main.link.save();');
    this.link.addClose('Cancel');

    //
    // The panel to edit File
    //
    this.file = new M.panel('File', 'ciniki_lapt_main', 'file', 'mc', 'medium', 'sectioned', 'ciniki.lapt.main.file');
    this.file.data = null;
    this.file.file_id = 0;
    this.file.document_id = 0;
    this.file.sections = {
        'general':{'label':'', 'fields':{
            'name':{'label':'Name', 'required':'yes', 'type':'text'},
            'flags':{'label':'Website', 'type':'flags', 'join':'yes', 'flags':{'1':{'name':'Visible'}}},
            }},
        '_file':{'label':'File', 'active':'no', 'fields':{
            'uploadfile':{'label':'', 'type':'file', 'hidelabel':'yes'},
            }},
        '_description':{'label':'Description', 'fields':{
            'description':{'label':'', 'hidelabel':'yes', 'type':'textarea'},
            }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_lapt_main.file.save();'},
            'download':{'label':'Download', 
                'visible':function() {return M.ciniki_lapt_main.file.file_id > 0 ? 'yes' : 'no'; },
                'fn':'M.ciniki_lapt_main.file.download(M.ciniki_lapt_main.file.file_id);',
                },
            'delete':{'label':'Delete', 
                'visible':function() {return M.ciniki_lapt_main.file.file_id > 0 ? 'yes' : 'no'; },
                'fn':'M.ciniki_lapt_main.file.remove();',
                },
            }},
        };
    this.file.fieldValue = function(s, i, d) { return this.data[i]; }
    this.file.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.lapt.fileHistory', 'args':{'tnid':M.curTenantID, 'file_id':this.file_id, 'field':i}};
    }
    this.file.download = function(fid) {
        M.api.openFile('ciniki.lapt.fileDownload', {'tnid':M.curTenantID, 'file_id':fid});
    };
    this.file.open = function(cb, fid, did) {
        if( fid != null ) { this.file_id = fid; }
        if( did != null ) { this.document_id = did; }
        if( this.file_id > 0 ) {
            this.sections._file.active = 'no';
        } else {
            this.sections._file.active = 'yes';
        }
        M.api.getJSONCb('ciniki.lapt.fileGet', {'tnid':M.curTenantID, 'file_id':this.file_id}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_lapt_main.file;
            p.data = rsp.file;
            p.refresh();
            p.show(cb);
        });
    }
    this.file.save = function(cb) {
        if( cb == null ) { cb = 'M.ciniki_lapt_main.file.close();'; }
        if( !this.checkForm() ) { return false; }
        if( this.file_id > 0 ) {
            var c = this.serializeForm('no');
            if( c != '' ) {
                M.api.postJSONCb('ciniki.lapt.fileUpdate', {'tnid':M.curTenantID, 'file_id':this.file_id}, c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    eval(cb);
                });
            } else {
                eval(cb);
            }
        } else {
            var c = this.serializeFormData('yes');
            M.api.postJSONFormData('ciniki.lapt.fileAdd', {'tnid':M.curTenantID, 'document_id':this.document_id}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_lapt_main.file.file_id = rsp.id;
                eval(cb);
            });
        }
    }
    this.file.remove = function() {
        if( confirm('Are you sure you want to remove file?') ) {
            M.api.getJSONCb('ciniki.lapt.fileDelete', {'tnid':M.curTenantID, 'file_id':this.file_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_lapt_main.file.close();
            });
        }
    }
    this.file.addButton('save', 'Save', 'M.ciniki_lapt_main.file.save();');
    this.file.addClose('Cancel');

    //
    // The panel to edit Image
    //
    this.image = new M.panel('Image', 'ciniki_lapt_main', 'image', 'mc', 'medium mediumaside', 'sectioned', 'ciniki.lapt.main.image');
    this.image.data = null;
    this.image.document_id = 0;
    this.image.document_image_id = 0;
    this.image.sections = {
        '_image_id':{'label':'', 'type':'imageform', 'aside':'yes', 'fields':{
            'image_id':{'label':'', 'type':'image_id', 'hidelabel':'yes', 'controls':'all', 'history':'no',
                'addDropImage':function(iid) {
                    M.ciniki_lapt_main.image.setFieldValue('image_id', iid);
                    return true;
                    },
                'addDropImageRefresh':'',
             },
        }},
        'general':{'label':'', 'fields':{
            'name':{'label':'Name', 'type':'text'},
            'flags':{'label':'Website', 'type':'flags', 'join':'yes', 'flags':{'1':{'name':'Visible'}}},
            }},
        '_description':{'label':'', 'fields':{
            'description':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'medium'},
            }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_lapt_main.image.save();'},
            'delete':{'label':'Delete', 
                'visible':function() {return M.ciniki_lapt_main.image.document_image_id > 0 ? 'yes' : 'no'; },
                'fn':'M.ciniki_lapt_main.image.remove();'},
            }},
        };
    this.image.fieldValue = function(s, i, d) { return this.data[i]; }
    this.image.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.lapt.imageHistory', 'args':{'tnid':M.curTenantID, 'document_image_id':this.document_image_id, 'field':i}};
    }
    this.image.open = function(cb, did, docid) {
        if( did != null ) { this.document_image_id = did; }
        if( docid != null ) { this.document_id = docid; }
        M.api.getJSONCb('ciniki.lapt.imageGet', {'tnid':M.curTenantID, 'document_image_id':this.document_image_id}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_lapt_main.image;
            p.data = rsp.image;
            p.refresh();
            p.show(cb);
        });
    }
    this.image.save = function(cb) {
        if( cb == null ) { cb = 'M.ciniki_lapt_main.image.close();'; }
        if( !this.checkForm() ) { return false; }
        if( this.document_image_id > 0 ) {
            var c = this.serializeForm('no');
            if( c != '' ) {
                M.api.postJSONCb('ciniki.lapt.imageUpdate', {'tnid':M.curTenantID, 'document_image_id':this.document_image_id}, c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    eval(cb);
                });
            } else {
                eval(cb);
            }
        } else {
            var c = this.serializeForm('yes');
            M.api.postJSONCb('ciniki.lapt.imageAdd', {'tnid':M.curTenantID, 'document_id':this.document_id}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_lapt_main.image.document_image_id = rsp.id;
                eval(cb);
            });
        }
    }
    this.image.remove = function() {
        if( confirm('Are you sure you want to remove image?') ) {
            M.api.getJSONCb('ciniki.lapt.imageDelete', {'tnid':M.curTenantID, 'document_image_id':this.document_image_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_lapt_main.image.close();
            });
        }
    }
    this.image.addButton('save', 'Save', 'M.ciniki_lapt_main.image.save();');
    this.image.addClose('Cancel');


    //
    // Start the app
    // cb - The callback to run when the user leaves the main panel in the app.
    // ap - The application prefix.
    // ag - The app arguments.
    //
    this.start = function(cb, ap, ag) {
        args = {};
        if( ag != null ) {
            args = eval(ag);
        }
        
        //
        // Create the app container
        //
        var ac = M.createContainer(ap, 'ciniki_lapt_main', 'yes');
        if( ac == null ) {
            alert('App Error');
            return false;
        }
        
        this.menu.open(cb);
    }
}
