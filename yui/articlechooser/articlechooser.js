YUI.add('moodle-mod_coursereadings-articlechooser', function(Y) {

var CourseReadingsArticleListDragDrop = function() {
    CourseReadingsArticleListDragDrop.superclass.constructor.apply(this, arguments);
};
Y.extend(CourseReadingsArticleListDragDrop, M.core.dragdrop, {
    initializer : function(params) {
        // Set group for parent class
        var self = this;
        this.groups = ['coursereadingsarticlelist'+self.get('fieldName')];
        this.samenodeclass = 'coursereadings-article';
        this.parentnodeclass = 'coursereadings-article-list';

        // Initialise sections dragging
        // Make each li element in the list of sections draggable
        var del = new Y.DD.Delegate({
            container: self.get('listselector'),
            nodes: 'div.'+self.samenodeclass,
            target: true,
            handles: ['.drag-handle'],
            dragConfig: {groups: self.groups}
        });
        del.dd.plug(Y.Plugin.DDProxy, {
            // Don't move the node at the end of the drag
            moveOnEnd: false,
            cloneNode: true
        });
        del.dd.plug(Y.Plugin.DDConstrained, {
            // Keep it inside the .course-content
            constrain: self.get('listselector'),
            stickY: true
        });
        del.dd.plug(Y.Plugin.DDWinScroll);
    },

    /*
     * Drag-dropping related functions
     */
    drag_start : function(e) {
        // Get our drag object
        var drag = e.target;
        drag.get('dragNode').setContent(drag.get('node').get('innerHTML'));
    },

    drag_dropmiss : function(e) {
        // Missed the target, but we assume the user intended to drop it
        // on the last last ghost node location, e.drag and e.drop should be
        // prepared by global_drag_dropmiss parent so simulate drop_hit(e).
        this.drop_hit(e);
    },

    drop_hit : function(e) {
        var self = this;
        // Delayed so that drop has completed and cloned node is gone
        window.setTimeout(function(){M.mod_coursereadings.articlechooser.updateListOrder(self.get('fieldName'));}, 1);

    }

}, {
    NAME : 'mod_coursereadings-article-list-dragdrop',
    ATTRS : {
        fieldName : {
            value : null
        }
    }
});

/**
 * Article chooser - this is used by the custom form element of the same name.
 */
var ArticleChooser = function() {
    ArticleChooser.superclass.constructor.apply(this, arguments);
};
ArticleChooser.prototype = {
    fpoptions : {},
    articleChoosers : [],
    articleTemplate : '<div class="coursereadings-article" data-articleid="{id}"><span class="drag-handle"></span><span class="delete-btn"></span><strong>{title}</strong><br>{author}{year}<br><em>{sourcetitle}</em></div>',
    filednd_active_target : null,
    uploadpanels : [],
    initializer : function(config) {
        var self = this;

        if (this.articleChoosers.length) return; // Already initialised!

        var choosers = Y.all('div.coursereadings-article-chooser');
        choosers.each( function(el) {
            var fieldName = el.getData('fieldname');
            var field = el.ancestor('form').get(fieldName);
            var articleIds = field.get('value');
            var list = el.one('.coursereadings-article-list');
            var listSelector = '#'+el.get('id')+' .coursereadings-article-list';
            var content = '';
            var articles = Y.JSON.parse(el.one('.coursereadings-articles-json').getHTML());
            var dragdrop = new CourseReadingsArticleListDragDrop({'fieldName': fieldName, 'listselector': listSelector});
            var toolbar = self.getToolbar(fieldName);
            list.delegate('click', self.confirmRemoveArticle, '.delete-btn', self);
            el.delegate('click', self.launchSearch, '.add-article-btn', self);
            if (articleIds.length) {
                articleIds = articleIds.split(',');
            } else {
                articleIds = [];
            }
            if (articleIds.length) {
                for (var i in articleIds) {
                    content += self.addArticle(articles[i]);
                }
            }
            self.articleChoosers[fieldName] = {
                'field': field,
                'list': list,
                'searchResults': []
            };
            list.setHTML(content);
            el.append(toolbar);
        }, this);

        this.fpoptions = config.fpoptions;
    },
    addArticle : function(article) {
        var data = {};
        for (var i in article) {
            if (article.hasOwnProperty(i)) {
                data[i] = article[i];
            }
        }
        data.author = article.author || article.sourceauthor || article.editor || 'Unknown';
        data.year = article.year ? ' ('+article.year+')' : '';
        return Y.Lang.sub(this.articleTemplate, data);
    },
    addArticleById: function(chooser, articleid) {
        var self = this;
        var uri = M.cfg.wwwroot + '/mod/coursereadings/ajaxsearch.php?t=articlebyid&q='+articleid;

        // Define the configuration to send with the request.
        var article = {};
        var config = {
            method: 'GET',
            on: {
                success: function(tid, response) {
                    try {
                        article = Y.JSON.parse(response.responseText);
                        if (article.error) {
                            new M.core.ajaxException(responsetext);
                        } else {
                            chooser.list.append(self.addArticle(article));
                            self.updateListOrder(chooser.field.get('name'));
                            chooser.list.one('.coursereadings-article:last-child').scrollIntoView();
                        }
                    } catch (e) {}
                },
                failure : function(tid, response) {
                    new M.core.ajaxException(response);
                }
            },
            context: this
        }

        // Send the request
        Y.io(uri, config);
    },
    confirmRemoveArticle : function(evt) {
        M.util.show_confirm_dialog(evt, {'message': M.util.get_string('confirmRemoveArticle', 'mod_coursereadings'), 'callback': this.removeArticle, 'callbackargs': [evt.target], 'scope': this});
    },
    removeArticle : function(el) {
        var fieldName = el.ancestor('.coursereadings-article-chooser').getData('fieldname');
        el.ancestor('.coursereadings-article').remove();
        this.updateListOrder(fieldName);
    },
    getToolbar : function(fieldName) {
        return '<div class="coursereadings-article-chooser-toolbar"><button class="add-article-btn">Add article</button></div>';
    },
    getChooser : function(fieldName) {
        return this.articleChoosers[fieldName];
    },
    updateListOrder : function(fieldName) {
        var self = this;
        var ids = [];
        self.getChooser(fieldName).list.all('.coursereadings-article').each(function(el) {
            if (!el.hasClass('yui3-dd-drop')) {
                var drop = new Y.DD.Drop({
                    node: el,
                    groups: ['coursereadingsarticlelist'+fieldName]
                });
            }
            ids.push(el.getData('articleid'));
        }, this);
        self.getChooser(fieldName).field.set('value', ids.join(','));
    },
    makeSearchForm : function() {
        var timestamp = new Date().getTime();
        var uploadid = Math.round(Math.random()*100000)+'-'+timestamp;
        var mod = M.mod_coursereadings.dndupload;
        var content = '<div id="coursereadings_article_search_'+uploadid+'"><p>'+M.util.get_string('articlesearchintro', 'mod_coursereadings')+'</p>';
        content += '<table style="width:100%" class="article_search">';
        content += mod.makeTableRow('title_of_article', 'title', uploadid);
        content += mod.makeTableRow('doi_or_url', 'doi_or_url', uploadid);
        content += mod.makeTableRow('title_of_source', 'source', uploadid);
        content += mod.makeTableRow('isbn', 'isbn', uploadid);
        content += '</table>';
        content += '<div class="article_search_results">&nbsp;</div>';
        content += '<div class="article_search_noresults" style="display:none;">'+M.util.get_string('noresults', 'mod_coursereadings')+'</div>';
        content += '<div class="article_search_noresults_external" style="display:none;">'+M.util.get_string('noresults_external', 'mod_coursereadings')+'</div>';
        content += '</div>';
        return [uploadid, content];
    },
    launchSearch : function(e) {
        e.preventDefault();
        var detailForm = this.makeSearchForm();
        var uploadid = detailForm[0], content = detailForm[1];
        var fieldName = e.target.ancestor('.coursereadings-article-chooser').getData('fieldname');

        var self = this;
        var panel = new Y.Panel({
            bodyContent: content,
            width: 580,
            zIndex: 5,
            centered: true,
            modal: true,
            visible: true,
            render: true,
            buttons: [{
                value: M.util.get_string('addarticle', 'mod_coursereadings'),
                action: function(e) {
                    e.preventDefault();
                    var id = panel.bodyNode.one('.article_search_results .coursereadings-article.selected').getData('articleid');
                    var results = self.getChooser(fieldName).searchResults;
                    for (var i=0;i<results.length;i++) {
                        if (results[i].id == id) {
                            self.getChooser(fieldName).list.append(self.addArticle(results[i]));
                            break;
                        }
                    }

                    self.updateListOrder(fieldName);
                    panel.hide();
                },
                section: Y.WidgetStdMod.FOOTER,
                disabled: true,
                id: 'search_add_article_'+uploadid
            },{
                value: M.util.get_string('cancel', 'moodle'),
                action: function(e) {
                    e.preventDefault();
                    panel.hide();
                },
                section: Y.WidgetStdMod.FOOTER
            }]
        });

        var el = Y.one('#dndupload_handler_title'+uploadid);
        var listel = el.ancestor('div').one('.article_search_results');
        el.plug(Y.Plugin.AutoComplete, {
          source: M.cfg.wwwroot + '/mod/coursereadings/ajaxsearch.php?t=article&q={query}',
          resultTextLocator: function(result) {
            return result.articletitle + ' (' + result.title + (!!result.year?', ' + result.year:'') + ')';
        },
          resultFormatter: M.mod_coursereadings.dndupload.articleFormatter,
          on: {
            select: function(evt) {
                var article = evt.result.raw;
                var result = [{
                    id: article.articleid,
                    author: article.periodicalauthor,
                    sourceauthor: article.author,
                    editor: article.editor,
                    year: article.year,
                    sourcetitle: article.title,
                    title: article.articletitle
                }];
                self.addSearchResults(result, listel, fieldName);
            },
            query: function(){
                this.get('inputNode').addClass('loading');
            },
            results: function(evt) {
                self.checkForResults(evt.results, listel);
                this.get('inputNode').removeClass('loading');
            }
          }
        });
        el.on('focus', function() {
            Y.one('#dndupload_handler_doi_or_url'+uploadid).set('value', '');
            Y.one('#dndupload_handler_source'+uploadid).set('value', '');
            Y.one('#dndupload_handler_isbn'+uploadid).set('value', '');
        })

        var el = Y.one('#dndupload_handler_doi_or_url'+uploadid);
        el.plug(Y.Plugin.AutoComplete, {
          source: M.cfg.wwwroot + '/mod/coursereadings/ajaxsearch.php?t=articledoi&q={query}',
          resultTextLocator: function(result) {
            return result.articletitle + ' (' + result.title + (!!result.year?', ' + result.year:'') + ')';
        },
          resultFormatter: M.mod_coursereadings.dndupload.articleFormatter,
          on: {
            select: function(evt) {
                var article = evt.result.raw;
                var result = [{
                    id: article.articleid,
                    author: article.periodicalauthor,
                    sourceauthor: article.author,
                    editor: article.editor,
                    year: article.year,
                    sourcetitle: article.title,
                    title: article.articletitle
                }];
                self.addSearchResults(result, listel, fieldName);
            },
            query: function(){
                this.get('inputNode').addClass('loading');
            },
            results: function(evt) {
                self.checkForResults(evt.results, listel, true);
                this.get('inputNode').removeClass('loading');
            }
          }
        });
        el.on('focus', function() {
            Y.one('#dndupload_handler_title'+uploadid).set('value', '');
            Y.one('#dndupload_handler_source'+uploadid).set('value', '');
            Y.one('#dndupload_handler_isbn'+uploadid).set('value', '');
        })

        el = Y.one('#dndupload_handler_source'+uploadid);
        el.plug(Y.Plugin.AutoComplete, {
          source: M.cfg.wwwroot + '/mod/coursereadings/ajaxsearch.php?t=source&q={query}',
          resultTextLocator: function(result) {
            return result.title + (!!result.year?' (' + result.year + ')':'');
        },
          resultFormatter: M.mod_coursereadings.dndupload.sourceFormatter,
          on: {
            select: function(evt) {self.getSourceArticles(evt, listel, fieldName);},
            query: function(){this.get('inputNode').addClass('loading');},
            results: function(evt) {
                self.checkForResults(evt.results, listel);
                this.get('inputNode').removeClass('loading');
            }
          }
        });
        el.on('focus', function() {
            Y.one('#dndupload_handler_doi_or_url'+uploadid).set('value', '');
            Y.one('#dndupload_handler_title'+uploadid).set('value', '');
            Y.one('#dndupload_handler_isbn'+uploadid).set('value', '');
        })

        el = Y.one('#dndupload_handler_isbn'+uploadid);
        el.plug(Y.Plugin.AutoComplete, {
          source: M.cfg.wwwroot + '/mod/coursereadings/ajaxsearch.php?t=isbn&q={query}',
          resultTextLocator: function(result) {
            return result.title + (!!result.year?' (' + result.year + ')':'');
        },
          resultFormatter: M.mod_coursereadings.dndupload.sourceFormatter,
          on: {
            select: function(evt) {self.getSourceArticles(evt, listel, fieldName);},
            query: function(){this.get('inputNode').addClass('loading');},
            results: function(evt) {
                self.checkForResults(evt.results, listel);
                this.get('inputNode').removeClass('loading');
            }
          }
        });
        el.on('focus', function() {
            Y.one('#dndupload_handler_doi_or_url'+uploadid).set('value', '');
            Y.one('#dndupload_handler_title'+uploadid).set('value', '');
            Y.one('#dndupload_handler_source'+uploadid).set('value', '');
        })

        listel.delegate('click', function(evt) {self.selectSearchResult(evt.target)}, '.coursereadings-article', self);
        el = listel.siblings('.article_search_noresults').shift();
        if (el != null) {
            // Use event delegation in case the language string does not include a button.
            el.delegate('click', function(evt) {self.launchUpload(fieldName); panel.hide();}, 'button', self);
        }
        extel = listel.siblings('.article_search_noresults_external').shift();
        if (extel != null) {
            // Use event delegation in case the language string does not include a button.
            extel.delegate('click', function(evt) {self.launchExternal(fieldName, uploadid); panel.hide();}, 'button', self);
        }

        // When the panel is hidden, destroy it.
        panel.after("visibleChange", function(e) {
            if (!panel.get('visible')) {
                panel.destroy(true);
            }
        });
        // Focus on the 'name' box.
        Y.one('#dndupload_handler_title'+uploadid).focus();
        return false;
    },
    checkForResults : function(results, listel, external) {
        var otherel = listel.siblings('.article_search_noresults'+(external?'':'_external')).shift();
        var el = listel.siblings('.article_search_noresults'+(external?'_external':'')).shift();
        if (otherel != null) {
            otherel.setStyle('display', 'none');
        }
        if (el != null) {
            el.setStyle('display', (results.length>0)?'none':'');
        }
        listel.empty();
        listel.ancestor('.yui3-panel-content').one('.yui3-widget-ft .yui3-button').set('disabled', true);
    },
    addSearchResults : function(articles, listel, fieldName) {
        listel.empty();
        for (var i in articles) {
            listel.append(this.addArticle(articles[i]));
        }

        this.getChooser(fieldName).searchResults = articles;

        if (articles.length == 1) {
            // Only one result - pre-select it.
            this.selectSearchResult(listel.one('.coursereadings-article'));
        } else {
            // Disable "Add article" button until something is selected.
            listel.ancestor('.yui3-panel-content').one('.yui3-widget-ft .yui3-button').set('disabled', true);
        }
        listel.ancestor().one('.article_search_noresults').setStyle('display', (articles.length?'none':''));
        this.getChooser(fieldName).selectedResult = -1;
    },
    selectSearchResult : function(article) {
        article.ancestor('.article_search_results').all('.coursereadings-article.selected').removeClass('selected');
        article.addClass('selected');
        article.ancestor('.yui3-panel-content').one('.yui3-widget-ft .yui3-button').set('disabled', false);
    },
    getSourceArticles : function(evt, listel, fieldName) {
        var id = evt.result.raw.id;

        var uri = M.cfg.wwwroot + '/mod/coursereadings/ajaxsearch.php?t=sourcearticles&q='+id;

        // Define the configuration to send with the request
        var articles = [];
        var config = {
            method: 'GET',
            on: {
                success: function(tid, response) {
                    try {
                        articles = Y.JSON.parse(response.responseText);
                        if (articles.error) {
                            new M.core.ajaxException(responsetext);
                        }
                    } catch (e) {}
                },
                failure : function(tid, response) {
                    new M.core.ajaxException(response);
                }
            },
            context: this,
            sync: true
        }

        // Send the request
        Y.io(uri, config);
        this.addSearchResults(articles, listel, fieldName);
    },
    getUploadPanel : function(uploadid) {
        var self = this;
        return self.uploadpanels[uploadid];
    },
    setUploadPanel : function(uploadid, panel) {
        var self = this;
        self.uploadpanels[uploadid] = panel;
    },
    makeUploadForm : function(fieldName) {
        var mod = M.mod_coursereadings.dndupload;
        var detailForm = mod.makeDetailForm();
        var uploadid = detailForm[0];
        var content = '<div id="coursereadings_article_upload_'+uploadid+'"><p>'+M.util.get_string('articleuploadintro', 'mod_coursereadings')+'</p>';
        content += '<div id="article_upload_filename_'+uploadid+'" class="article_upload_filename" style="display:none;"><strong></strong></div>';
        content += '<button id="article_upload_choosefile_'+uploadid+'" type="button">'+M.util.get_string('openpicker', 'repository')+'</button>';
        content += '<div id="article_upload_filepicker_'+uploadid+'" class="article_upload_drop_target filepicker-filelist" style="display:none;" data-uploadid="'+uploadid+'">\
                    <div class="filepicker-filename">\
                        <div class="filepicker-container"><div class="dndupload-message">'+M.util.get_string('dndenabled_inbox', 'moodle')+' <br>\
                        <div class="dndupload-arrow"></div></div></div>\
                    </div>\
                    <div><div class="dndupload-target">Drop files here to upload<br><div class="dndupload-arrow"></div></div></div>\
                    </div>';
        content += '<table class="coursereadings_article_link_fields"><tbody>';
        content += '<tr><th colspan="2">'+M.util.get_string('articlelinkintro', 'mod_coursereadings')+'</th></tr>';
        content += mod.makeTableRow('externalurl', 'externalurl', uploadid, 440);
        content += mod.makeTableRow('doi', 'doi', uploadid, 440);
        content += '</tbody></table>';
        content += '<div id="article_upload_details_'+uploadid+'" data-fieldname="'+fieldName+'" style="display:none;">'+detailForm[1]+'</div>';
        content += '</div>';
        return [uploadid, content];
    },
    launchUpload : function(fieldName) {
        var uploadForm = this.makeUploadForm(fieldName);
        var uploadid = uploadForm[0], content = uploadForm[1];

        var self = this;
        var panel = new Y.Panel({
            bodyContent: content,
            width: 580,
            zIndex: 5,
            centered: true,
            modal: true,
            visible: true,
            render: true,
            buttons: [{
                value: M.util.get_string('next', 'moodle'),
                action: function(e) {
                    e.preventDefault();
                    var form = Y.one('#dndupload_handler_article_'+uploadid);
                    var title = form.get('dndupload_handler_title').get('value');
                    var filename = Y.one('#article_upload_filename_'+uploadid+' > strong').get('text');
                    var linkdetails = Y.one('#coursereadings_article_upload_'+uploadid+' .coursereadings_article_link_fields');
                    var doi = linkdetails.one('[name=dndupload_handler_doi]').get('value');
                    if (!title.length) {
                        // File has been specified, move to the next step
                        linkdetails.setStyle('display', 'none');
                        Y.one('#article_upload_choosefile_'+uploadid).setStyle('display', 'none');
                        Y.one('#article_upload_filepicker_'+uploadid).setStyle('display', 'none');
                        Y.one('#article_upload_filename_'+uploadid).setStyle('display', '');
                        Y.one('#article_upload_details_'+uploadid).setStyle('display', '');
                        Y.one('#coursereadings_article_upload_'+uploadid+' > p').setStyle('display', 'none');
                        Y.one('#article_upload_choosefile_'+uploadid).setStyle('display', 'none');
                        Y.one('#article_upload_details_'+uploadid).ancestor('.yui3-panel-content').one('.yui3-widget-ft .yui3-button').setHTML(M.util.get_string('upload', 'moodle'));
                        form.get('dndupload_handler_title').set('value', filename.replace(/\.pdf$/, ''));
                        // Disable "upload" button and attach monitor to enable when required fields filled.
                        Y.one('#dndupload_handler_article_'+uploadid).ancestor('.yui3-panel-content').one('.yui3-widget-ft .yui3-button').set('disabled', true);
                        form.get('dndupload_handler_externalurl').set('value', linkdetails.one('[name=dndupload_handler_externalurl]').get('value'));
                        if (doi) {
                            self.run_crossref_lookup(doi, uploadid, panel);
                        }
                        form.get('dndupload_handler_doi').set('value', doi);
                        if (filename.length) {
                            // Set file upload class as appropriate.
                            var details = form.one('.dndupload_handler_source_details');
                            details.removeClass('no-file-upload').addClass('has-file-upload');
                        }
                        M.mod_coursereadings.dndupload.attachRequiredMonitor(uploadid);
                        return;
                    }
                    var formData = {
                        data: [],
                        append: function(field, value) {
                            this.data.push({f:field,v:value});
                        },
                        toString: function() {
                            var s = '';
                            for (var i=0;i<this.data.length;i++) {
                                if (i) s += '&';
                                s += this.data[i].f + '=' + encodeURIComponent(this.data[i].v);
                            }
                            return s;
                        }
                    }
                    formData.append('articleid', form.get('dndupload_handler_articleid').get('value'));
                    formData.append('author_of_periodical', form.get('dndupload_handler_periodicalAuthor').get('value'));
                    formData.append('title_of_article', form.get('dndupload_handler_title').get('value'));
                    formData.append('page_range', form.get('dndupload_handler_page_range').get('value'));
                    formData.append('sourceid', form.get('dndupload_handler_sourceid').get('value'));
                    formData.append('source_type', form.one('input[name=dndupload_handler_sourceType]:checked').get('value'));
                    formData.append('title_of_source', form.get('dndupload_handler_source').get('value'));
                    formData.append('author_of_periodical', form.get('dndupload_handler_periodicalAuthor').get('value'));
                    formData.append('author_of_source', form.get('dndupload_handler_sourceAuthor').get('value'));
                    formData.append('editor_of_source', form.get('dndupload_handler_sourceEditor').get('value'));
                    formData.append('year_of_publication', form.get('dndupload_handler_published').get('value'));
                    formData.append('volume_number', form.get('dndupload_handler_volume').get('value'));
                    formData.append('edition', form.get('dndupload_handler_edition').get('value'));
                    formData.append('publisher', form.get('dndupload_handler_publisher').get('value'));
                    formData.append('isbn', form.get('dndupload_handler_isbn').get('value'));
                    formData.append('pages', form.get('dndupload_handler_pages').get('value'));

                    formData.append('draftitemid', self.fpoptions.itemid);
                    formData.append('externalurl', form.get('dndupload_handler_externalurl').get('value'));
                    formData.append('doi', form.get('dndupload_handler_doi').get('value'));
                    formData.append('filename', filename);
                    formData.append('fieldname', form.ancestor('div').getData('fieldname'));
                    panel.hide();
                    // Do the upload
                    self.uploadArticle(formData);
                },
                section: Y.WidgetStdMod.FOOTER,
                disabled: true,
                id: 'search_add_article_'+uploadid
            },{
                value: M.util.get_string('cancel', 'moodle'),
                action: function(e) {
                    e.preventDefault();
                    panel.hide();
                },
                section: Y.WidgetStdMod.FOOTER
            }]
        });

        var el = Y.one('#article_upload_choosefile_'+uploadid);
        el.on('click', function(evt) {
            YUI().use('core_filepicker', function (Y) {
                var options = self.fpoptions;
                options.accepted_types = ['.pdf'];
                options.client_id = uploadid;

                self.filepicker_init(Y, options);
                M.core_filepicker.instances[uploadid].show();
            });
        })

        if (self.filednd_browser_supported()) {
            // Initialise drop target for files
            var dt = Y.one('.article_upload_drop_target');
            dt.setStyle('display', '');
            dt.addClass('dndsupported');
            self.init_filednd_events(dt);
        }

        Y.all('#coursereadings_article_upload_'+uploadid+' .coursereadings_article_link_fields input').on(['change','keyup'], function(e) {
            var fields = Y.all('#coursereadings_article_upload_'+uploadid+' .coursereadings_article_link_fields input');
            var hasvalue = false;
            var validate;
            switch (e.target.get('name')) {
                case 'dndupload_handler_externalurl':
                    validate = this.validate_url;
                    break;
                case 'dndupload_handler_doi':
                    validate = this.validate_doi;
                    break;
            }
            if (validate(e.target.get('value'))) {
                // Field is non-empty and looks roughly valid; remove error flag if present.
                hasvalue = true;
                e.target.removeClass('error');
            } else if (e.target.get('value').length) {
                // Field is non-empty and invalid; flag as error.
                e.target.addClass('error');
            } else {
                // Field is empty, don't flag it as an error.
                e.target.removeClass('error');
            }
            fields.each(function(node, i, nodes) {
                if ((node !== e.target) && (e.target.get('value').length > 0)) {
                    node.set('value', '');
                    node.removeClass('error');
                }
            }, self);
            Y.one('#article_upload_details_'+uploadid).ancestor('.yui3-panel-content').one('.yui3-widget-ft .yui3-button').set('disabled', !hasvalue);
        }, self);

        M.mod_coursereadings.dndupload.attachSourceAutocomplete(uploadid);

        // When the panel is hidden, destroy it.
        panel.after("visibleChange", function(e) {
            if (!panel.get('visible')) {
                panel.destroy(true);
            }
        });

        self.setUploadPanel(uploadid, panel);

        return false;
    },
    launchExternal : function(fieldName, searchid) {
        var self = this;
        var mod = M.mod_coursereadings.dndupload;
        var detailForm = mod.makeDetailForm();
        var uploadid = detailForm[0], content = detailForm[1];
        var form;
        var query = Y.one('#dndupload_handler_doi_or_url'+searchid).get('value');

        content = '<div id="article_upload_details_'+uploadid+'" data-fieldname="'+fieldName+'">'+content+'</div>';

        var panel = new Y.Panel({
            bodyContent: content,
            width: 580,
            zIndex: 5,
            centered: true,
            modal: true,
            visible: true,
            render: true,
            buttons: [{
                value: M.util.get_string('next', 'moodle'),
                action: function(e) {
                    e.preventDefault();
                    var form = Y.one('#dndupload_handler_article_'+uploadid);
                    var formData = {
                        data: [],
                        append: function(field, value) {
                            this.data.push({f:field,v:value});
                        },
                        toString: function() {
                            var s = '';
                            for (var i=0;i<this.data.length;i++) {
                                if (i) s += '&';
                                s += this.data[i].f + '=' + encodeURIComponent(this.data[i].v);
                            }
                            return s;
                        }
                    }
                    formData.append('articleid', form.get('dndupload_handler_articleid').get('value'));
                    formData.append('author_of_periodical', form.get('dndupload_handler_periodicalAuthor').get('value'));
                    formData.append('title_of_article', form.get('dndupload_handler_title').get('value'));
                    formData.append('page_range', form.get('dndupload_handler_page_range').get('value'));
                    formData.append('sourceid', form.get('dndupload_handler_sourceid').get('value'));
                    formData.append('source_type', form.one('input[name=dndupload_handler_sourceType]:checked').get('value'));
                    formData.append('title_of_source', form.get('dndupload_handler_source').get('value'));
                    formData.append('author_of_periodical', form.get('dndupload_handler_periodicalAuthor').get('value'));
                    formData.append('author_of_source', form.get('dndupload_handler_sourceAuthor').get('value'));
                    formData.append('editor_of_source', form.get('dndupload_handler_sourceEditor').get('value'));
                    formData.append('year_of_publication', form.get('dndupload_handler_published').get('value'));
                    formData.append('volume_number', form.get('dndupload_handler_volume').get('value'));
                    formData.append('edition', form.get('dndupload_handler_edition').get('value'));
                    formData.append('publisher', form.get('dndupload_handler_publisher').get('value'));
                    formData.append('isbn', form.get('dndupload_handler_isbn').get('value'));
                    formData.append('pages', form.get('dndupload_handler_pages').get('value'));

                    formData.append('externalurl', form.get('dndupload_handler_externalurl').get('value'));
                    formData.append('doi', self.clean_doi(form.get('dndupload_handler_doi').get('value')));
                    formData.append('fieldname', form.ancestor('div').getData('fieldname'));
                    panel.hide();
                    // Do the upload
                    self.uploadArticle(formData);
                },
                section: Y.WidgetStdMod.FOOTER,
                disabled: true,
                id: 'search_add_article_'+uploadid
            },{
                value: M.util.get_string('cancel', 'moodle'),
                action: function(e) {
                    e.preventDefault();
                    panel.hide();
                },
                section: Y.WidgetStdMod.FOOTER
            }]
        });

        // Remove known DOI resolvers, leaving just the DOI.
        query = self.clean_doi(query);

        form = Y.one('#dndupload_handler_article_'+uploadid);
        if (self.validate_doi(query)) {
            // Search was for a valid-looking DOI.
            if (self.validate_url(query)) {
                // Search looks
            }
            form.get('dndupload_handler_doi').set('value', query);
            self.run_crossref_lookup(query, uploadid, panel);
        } else {
            // Assume search was for a URL.
            form.get('dndupload_handler_externalurl').set('value', query);
        }

        M.mod_coursereadings.dndupload.attachSourceAutocomplete(uploadid);
        M.mod_coursereadings.dndupload.attachRequiredMonitor(uploadid);

        // When the panel is hidden, destroy it.
        panel.after("visibleChange", function(e) {
            if (!panel.get('visible')) {
                panel.destroy(true);
            }
        });

        return false;
    },

    run_crossref_lookup: function(query, uploadid, panel) {
        var self = this;
        var uri = M.cfg.wwwroot + '/mod/coursereadings/ajaxsearch.php?t=xreflookup&q='+query;

        // Display modal loading panel while crossref lookup runs.
        M.mod_coursereadings.loadingpanel.show(panel);
        var config = {
            method: 'GET',
            on: {
                success: function(tid, response) {
                    try {
                        metadata = Y.JSON.parse(response.responseText);
                        if (metadata.error) {
                            M.mod_coursereadings.loadingpanel.hide(panel);
                            new M.core.ajaxException(metadata.error);
                        } else {
                            var form = Y.one('#dndupload_handler_article_'+uploadid);
                            var source = metadata.source;
                            var article = metadata.article;

                            form.one('.dndupload_handler_sourceType input[value='+source.type+']').set('checked', true).set('disabled', true);
                            form.all('.dndupload_handler_sourceType input:not([value='+source.type+'])').set('disabled', true);
                            M.mod_coursereadings.dndupload.toggleSourceType(uploadid);
                            if (source.id) {
                                disabled = true;
                                form.get('dndupload_handler_sourceid').set('value', source.id);
                                form.addClass('dndupload_handler_source_selected');
                            } else {
                                disabled = false;
                            }

                            form.get('dndupload_handler_source').set('value', source.title).set('disabled', disabled);
                            form.get('dndupload_handler_sourceAuthor').set('disabled', disabled);
                            form.get('dndupload_handler_sourceEditor').set('disabled', disabled);
                            form.get('dndupload_handler_published').set('value', source.year).set('disabled', disabled);
                            form.get('dndupload_handler_volume').set('value', source.volume).set('disabled', disabled);
                            form.get('dndupload_handler_edition').set('value', source.edition).set('disabled', disabled);
                            form.get('dndupload_handler_publisher').set('value', source.publisher).set('disabled', disabled);
                            form.get('dndupload_handler_isbn').set('value', source.isbn).set('disabled', disabled);
                            form.get('dndupload_handler_pages').set('disabled', disabled);

                            if (source.type == 'other') {
                                form.get('dndupload_handler_subtype').get('options').each(function() {
                                    if(this.get('value') == source.subtype) {
                                        this.set('selected', 'selected');
                                    }
                                });
                            }
                            form.get('dndupload_handler_subtype').set('disabled', disabled);
                            form.get('dndupload_handler_sourceurl').set('disabled', disabled);
                            form.get('dndupload_handler_furtherinfo').set('disabled', disabled);
                            M.mod_coursereadings.dndupload.checkRequiredFields(uploadid);

                            form.get('dndupload_handler_title').set('value', article.title);
                            form.get('dndupload_handler_page_range').set('value', article.pagerange);
                            form.get('dndupload_handler_periodicalAuthor').set('value', article.author);

                            form.ancestor('.yui3-panel-content').one('.yui3-widget-ft .yui3-button').set('disabled', false);

                            M.mod_coursereadings.loadingpanel.hide(panel);
                        }
                    } catch (e) {}
                },
                failure : function(tid, response) {
                    M.mod_coursereadings.loadingpanel.hide(panel);
                    new M.core.ajaxException(response);
                }
            },
            context: self,
            sync: false
        }

        // Send the request
        Y.io(uri, config);
    },

    // Rudimentary URL validation - check that it looks vaguely like a URL, but don't do anything fancy.
    validate_url: function(inStr) {
        return /^(https?:\/\/.{2}|www\..)/.test(inStr);
    },

    // Rudimentary DOI validation - check that the basic structure is right for a DOI.
    validate_doi: function(inStr) {
        return /^(doi:)?10\..+\/./.test(inStr);
    },

    // Rudimentary DOI cleaning - strip off known DOI resolvers.
    clean_doi: function(inStr) {
        // Remove known DOI resolvers, if present.
        var match = /https?:\/\/(?:dx\.)?doi.org[^\/]*\/(?:doi:)?(10\..+\/.+)/i.exec(inStr);
        if (!!match) {
            return match[1];
        }
        // Remove doi: prefix if present.
        match = /^doi:(10\..+\/.+)/i.exec(inStr);
        if (!!match) {
            return match[1];
        }
        return inStr;
    },

    /**
     * Check the browser has the required functionality
     * @return true if browser supports drag/drop upload
     */
    filednd_browser_supported: function() {
        if (typeof FileReader == 'undefined') {
            return false;
        }
        if (typeof FormData == 'undefined') {
            return false;
        }
        return true;
    },

    /**
     * Initialise drag events on node container, all events need
     * to be processed for drag and drop to work
     * @param el the element to add events to
     */
    init_filednd_events: function(el) {
        this.filednd_active_target = el;
        Y.on('dragenter', this.filednd_drag_enter, el, this);
        Y.on('dragleave', this.filednd_drag_leave, el, this);
        Y.on('dragover',  this.filednd_drag_over,  el, this);
        Y.on('drop',      this.filednd_drop,       el, this);
    },

    /**
     * Check if the event includes data of the given type
     * @param e the event details
     * @param type the data type to check for
     * @return true if the data type is found in the event data
     */
    filednd_types_includes: function(e, type) {
        var i;
        var types = e._event.dataTransfer.types;
        for (i=0; i<types.length; i++) {
            if (types[i] == type) {
                return true;
            }
        }
        return false;
    },

    /**
     * Look through the event data, checking it against the registered data types
     * (in order of priority) and return details of the first matching data type
     * @param e the event details
     * @return boolean true if files are being dragged
     */
    filednd_has_file: function(e) {
        // Check there is some data attached.
        if (e._event.dataTransfer === null) {
            return false;
        }
        if (e._event.dataTransfer.types === null) {
            return false;
        }
        if (e._event.dataTransfer.types.length == 0) {
            return false;
        }

        // Check for files first.
        if (this.filednd_types_includes(e, 'Files')) {
            if (e.type != 'drop' || e._event.dataTransfer.files.length != 0) {
                return true;
            }
        }

        return false; // No types we can handle
    },

    /**
     * Check the content of the drag/drop includes a type we can handle, then, if
     * it is, notify the browser that we want to handle it
     * @param event e
     * @return boolean true if we are to handle this drag
     */
    filednd_check_drag: function(e) {
        var has_file = this.filednd_has_file(e);
        if (has_file) {
            // Notify browser that we will handle this drag/drop
            e.stopPropagation();
            e.preventDefault();
        }
        return has_file;
    },

    /**
     * Handle a dragenter event: add a suitable 'add here' message
     * when a drag event occurs, containing a registered data type
     * @param e event data
     * @return false to prevent the event from continuing to be processed
     */
    filednd_drag_enter: function(e) {
        this.filednd_check_drag(e);

        return false;
    },

    /**
     * Handle a dragleave event: remove the 'add here' message (if present)
     * @param e event data
     * @return false to prevent the event from continuing to be processed
     */
    filednd_drag_leave: function(e) {
        this.filednd_check_drag(e);

        return false;
    },

    /**
     * Handle a dragover event: just prevent the browser default (necessary
     * to allow drag and drop handling to work)
     * @param e event data
     * @return false to prevent the event from continuing to be processed
     */
    filednd_drag_over: function(e) {
        this.filednd_check_drag(e);
        return false;
    },

    /**
     * Handle a drop event: hide the 'add here' message, check the attached
     * data type and start the upload process
     * @param e event data
     * @return false to prevent the event from continuing to be processed
     */
    filednd_drop: function(e) {
        if (!this.filednd_check_drag(e)) {
            return false;
        }

        // Process the first file (ignore any extras)
        var files = e._event.dataTransfer.files;
        this.filednd_handle_file(files[0]);

        return false;
    },
    filednd_handle_file: function(file) {
        var extension = '';
        var dotpos = file.name.lastIndexOf('.');
        if (dotpos != -1) {
            extension = file.name.substr(dotpos+1, file.name.length);
        }

        if (extension !== 'pdf') {
            alert ('Please choose a PDF file instead!');
            return false;
        }

        this.filednd_upload_file(file);

    },

    /**
     * Do the file upload: show the dummy element, use an AJAX call to send the data
     * to the server, update the progress bar for the file, then replace the dummy
     * element with the real information once the AJAX call completes
     * @param file the details of the file, taken from the FileList in the drop event
     */
    filednd_upload_file: function(file) {

        // This would be an ideal place to use the Y.io function
        // however, this does not support data encoded using the
        // FormData object, which is needed to transfer data from
        // the DataTransfer object into an XMLHTTPRequest
        // This can be converted when the YUI issue has been integrated:
        // http://yuilibrary.com/projects/yui3/ticket/2531274
        var xhr = new XMLHttpRequest();
        var self = this;

        // Wait for the AJAX call to complete, then update the
        // dummy element with the returned details
        xhr.onreadystatechange = function() {
            if (xhr.readyState == 4) {
                if (xhr.status == 200) {
                    var result = JSON.parse(xhr.responseText);
                    if (result) {
                        if (result.error == 0) {
                            if (!!result.articleid) {
                                // Matched to an existing article - insert it.
                                var uploadid = self.filednd_active_target.getData('uploadid');
                                var form = Y.one('#dndupload_handler_article_'+uploadid);
                                var fieldname = form.ancestor('div').getData('fieldname');
                                var chooser = self.getChooser(fieldname);
                                self.addArticleById(chooser, result.articleid);
                                self.getUploadPanel(uploadid).hide();
                            } else {
                                // All OK, new file - add link to draft into drop area.
                                var dt = self.filednd_active_target;
                                self.filepicker_set_file(result, dt, dt.getData('uploadid'));
                            }
                        } else {
                            // Error
                            alert(result.error);
                        }
                    }
                } else {
                    alert(M.util.get_string('servererror', 'moodle'));
                }
            }
        };

        // Prepare the data to send
        var formData = new FormData();
        formData.append('repo_upload_file', file);
        formData.append('sesskey', M.cfg.sesskey);
        formData.append('type', 'Files');
        formData.append('draftitemid', self.fpoptions.itemid);

        // Send the AJAX call
        xhr.open("POST", M.cfg.wwwroot + '/mod/coursereadings/dnduploaddraft.php', true);
        xhr.send(formData);
    },
    filepicker_set_file : function(file, el, uploadid) {
        var link = '<a href="'+file.url+'" target="_blank">'+file.file+'</a>';
        el.removeClass('dndsupported');
        el.empty();
        el.setHTML(link);

        Y.one('#article_upload_filename_'+uploadid).setHTML('Uploaded file:<br /><strong>'+file.file+'</strong><br /><br />').setStyle('display','');
        Y.one('#article_upload_details_'+uploadid).ancestor('.yui3-panel-content').one('.yui3-widget-ft .yui3-button').set('disabled', false);
    },
    filepicker_callback : function(params) {
        var self = this;
        var uploadid = params['client_id']
        var uploadpanel = self.getUploadPanel(uploadid);
        var el = Y.one('#article_upload_filepicker_'+uploadid);
        this.filepicker_set_file(params, el, uploadid);
        M.mod_coursereadings.loadingpanel.show(uploadpanel);;

        // Check if the file matches an existing article in the database.
        var uri = M.cfg.wwwroot + '/mod/coursereadings/ajaxsearch.php?t=draftfile&q='+params.id+'&f='+encodeURIComponent(params.file);
        var config = {
            method: 'GET',
            on: {
                success: function(tid, response) {
                    try {
                        result = Y.JSON.parse(response.responseText);

                        if (result.error) {
                            new M.core.ajaxException(responsetext);
                        } else {
                            if (!!result.articleid) {
                                // Matches an existing article - insert that and close upload panel.
                                var form = Y.one('#dndupload_handler_article_'+uploadid);
                                var fieldname = form.ancestor('div').getData('fieldname');
                                var chooser = self.getChooser(fieldname);
                                self.addArticleById(chooser, result.articleid);
                                uploadpanel.hide();
                                M.mod_coursereadings.loadingpanel.hide();
                            } else {
                                // Hide loading panel, return to upload panel.
                                M.mod_coursereadings.loadingpanel.hide(uploadpanel);
                            }
                        }
                    } catch (e) {}
                },
                failure : function(tid, response) {
                    new M.core.ajaxException(response);
                }
            },
            context: this
        }

        // Send the request
        Y.io(uri, config);

    },
    filepicker_init : function(Y, options) {
        options.formcallback = this.filepicker_callback;
        options.magicscope = this;
        if (!M.core_filepicker.instances[options.client_id]) {
            M.core_filepicker.init(Y, options);
        }
    },
    uploadArticle : function(formData) {
        var self = this;

        // Add extra details to data to send
        formData.append('sesskey', M.cfg.sesskey);

        Y.io(M.cfg.wwwroot + '/mod/coursereadings/inlineupload.php', {
            method: 'POST',
            data: formData.toString().replace(/%20/g,'+'),
            on: {
                success: function(state, xhr) {
                    var result = Y.JSON.parse(xhr.responseText);
                    if (result.error == 0) {
                        var chooser = self.getChooser(result.fieldname);
                        chooser.list.append(self.addArticle(result));
                        self.updateListOrder(result.fieldname);
                    } else {
                        // Error.
                        alert(result.error);
                    }
                },
                failure: function(state, xhr) {
                    alert(M.util.get_string('servererror', 'moodle'));
                }
            },
            context: self
        });
    }
};

Y.extend(ArticleChooser, Y.Base, ArticleChooser.prototype, {
    NAME : 'Copyright Materials article chooser',
    ATTRS : {
        // No attributes at present
    }
});

M.mod_coursereadings = M.mod_coursereadings || {};
M.mod_coursereadings.articlechooser = M.mod_coursereadings.articlechooser || {};
M.mod_coursereadings.articlechooser.addArticle = ArticleChooser.prototype.addArticle;
M.mod_coursereadings.articlechooser.removeArticle = ArticleChooser.prototype.removeArticle;
M.mod_coursereadings.articlechooser.updateListOrder = ArticleChooser.prototype.updateListOrder;
M.mod_coursereadings.articlechooser.getChooser = ArticleChooser.prototype.getChooser;
M.mod_coursereadings.articlechooser.articleChoosers = ArticleChooser.prototype.articleChoosers;
M.mod_coursereadings.articlechooser.init_filepicker = ArticleChooser.prototype.init_filepicker;
M.mod_coursereadings.articlechooser.init = function(cfg) {
    return new ArticleChooser(cfg);
}

}, '@VERSION@', {requires:['base','node', 'io', 'json-parse', 'dd', 'dd-scroll', 'dd-drop', 'moodle-core-dragdrop', 'moodle-mod_coursereadings-dndupload', 'moodle-mod_coursereadings-loadingpanel']});