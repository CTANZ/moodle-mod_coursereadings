YUI.add('moodle-mod_coursereadings-dndupload', function(Y) {

/**
 * Drag-n-Drop upload handler for Copyright Materials module
 * Initialise this class by calling M.mod_coursereadings.dndupload.init
 */
var DndUpload = function() {
    DndUpload.superclass.constructor.apply(this, arguments);
};
DndUpload.prototype = {
    hasOverriddenDndUpload: false,
    initializer : function(config) {
        var self = this;

        // Horribly nasty hack, since nothing in the dndupload chain fires any events we can listen for.
        // Since the dndupload module isn't there when we initialise, override its add_editing function
        // when we first see a "drop" on a section.
        // (could also be done with delays/setTimeout, but that's less reliable due to timing issues)
        var sections = Y.all('li.section.main');
        sections.each( function(el) {
            Y.on('drop', function(el) {
                if (!this.hasOverriddenDndUpload) {
                    M.course_dndupload.core_file_handler_dialog = M.course_dndupload.file_handler_dialog;
                    self = this;
                    M.course_dndupload.currentdraftid = null;
                    M.course_dndupload.file_handler_dialog = function(handlers, extension, file, section, sectionnumber) {
                        if (extension !== 'pdf') {
                            // We're not interested, just pass it straight through to the core handler.
                            return M.course_dndupload.core_file_handler_dialog(handlers, extension, file, section, sectionnumber);
                        }

                        if (this.uploaddialog) {
                            var details = new Object();
                            details.isfile = true;
                            details.handlers = handlers;
                            details.extension = extension;
                            details.file = file;
                            details.section = section;
                            details.sectionnumber = sectionnumber;
                            this.uploadqueue.push(details);
                            return;
                        }
                        this.uploaddialog = true;

                        var timestamp = new Date().getTime();
                        var uploadid = Math.round(Math.random()*100000)+'-'+timestamp;
                        var content = '';
                        var sel;
                        if (extension in this.lastselected) {
                            sel = this.lastselected[extension];
                        } else {
                            sel = handlers[0].module;
                        }
                        content += '<p>'+M.util.get_string('actionchoice', 'moodle', file.name)+'</p>';
                        content += '<div id="dndupload_handlers'+uploadid+'" class="dndupload_coursereadings_options">';
                        for (var i=0; i<handlers.length; i++) {
                            var id = 'dndupload_handler'+uploadid+handlers[i].module;
                            var checked = (handlers[i].module == sel) ? 'checked="checked" ' : '';
                            content += '<input type="radio" name="handler" value="'+handlers[i].module+'" id="'+id+'" '+checked+'/>';
                            content += ' <label for="'+id+'">';
                            content += M.util.get_string('dndupload_'+handlers[i].module, 'mod_coursereadings');
                            content += '</label><br/>';
                        }
                        content += '</div>';

                        var Y = this.Y;
                        var self = this;
                        var panel = new Y.Panel({
                            bodyContent: content,
                            width: 400,
                            zIndex: 5,
                            centered: true,
                            modal: true,
                            visible: false,
                            render: true,
                            buttons: [{
                                value: M.util.get_string('upload', 'moodle'),
                                action: function(e) {
                                    e.preventDefault();
                                    // Find out which module was selected
                                    var module = false;
                                    var div = Y.one('#dndupload_handlers'+uploadid);
                                    div.all('input').each(function(input) {
                                        if (input.get('checked')) {
                                            module = input.get('value');
                                        }
                                    });
                                    if (!module) {
                                        return;
                                    }
                                    panel.hide();
                                    // Remember this selection for next time
                                    self.lastselected[extension] = module;
                                    // Do the upload
                                    self.upload_coursereading(file, section, sectionnumber, module);
                                },
                                section: Y.WidgetStdMod.FOOTER
                            },{
                                value: M.util.get_string('cancel', 'moodle'),
                                action: function(e) {
                                    e.preventDefault();
                                    panel.hide();
                                },
                                section: Y.WidgetStdMod.FOOTER
                            }]
                        });
                        // When the panel is hidden - destroy it and then check for other pending uploads
                        panel.after("visibleChange", function(e) {
                            if (!panel.get('visible')) {
                                panel.destroy(true);
                                self.check_upload_queue();
                            }
                        });

                        // Display modal loading panel while uploading draft, in case the file is already known.
                        M.mod_coursereadings.loadingpanel.show(panel);
                        // Send draft file to server, so we can check whether it's already in the system.
                        this.upload_draft_file(file, panel, section, sectionnumber);
                    };
                    M.course_dndupload.upload_draft_file = function(file, panel, section, sectionnumber) {
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
                                            // All OK - file uploaded successfully.
                                            if (!!result.articleid) {
                                                // Article already exists - insert an instance pointing to it.
                                                var formData = new FormData();
                                                formData.append('articleid', result.articleid);
                                                self.do_upload_coursereading(file, formData, section, sectionnumber, 'coursereadings');
                                                panel.destroy(true);
                                                self.check_upload_queue();
                                            } else {
                                                // New file uploaded - store draft ID and proceed.
                                                console.log('Draft file ID: '+result.id);
                                                self.currentdraftid = result.id;
                                                panel.set('visible', true);
                                            }
                                            M.mod_coursereadings.loadingpanel.hide(panel);
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

                        // Send the AJAX call
                        xhr.open("POST", M.cfg.wwwroot + '/mod/coursereadings/dnduploaddraft.php', true);
                        xhr.send(formData);
                    };
                    M.course_dndupload.upload_coursereading = function(file, section, sectionnumber, module) {
                        var self = this;

                        if (file.size > self.maxbytes) {
                            alert("'"+file.name+"' "+M.util.get_string('filetoolarge', 'moodle'));
                            return;
                        }

                        if (module === 'coursereadings') {

                            var detailForm = M.mod_coursereadings.dndupload.makeDetailForm(file);
                            var uploadid = detailForm[0], content = detailForm[1];

                            var Y = self.Y;
                            var panel = new Y.Panel({
                                bodyContent: content,
                                width: 580,
                                zIndex: 5,
                                centered: true,
                                modal: true,
                                visible: true,
                                render: true,
                                buttons: [{
                                    value: M.util.get_string('upload', 'moodle'),
                                    action: function(e) {
                                        e.preventDefault();
                                        var form = Y.one('#dndupload_handler_article_'+uploadid);
                                        var formData = new FormData();
                                        formData.append('articleid', form.get('dndupload_handler_articleid').get('value'));
                                        formData.append('author_of_periodical', form.get('dndupload_handler_periodicalAuthor').get('value'));
                                        formData.append('title_of_article', form.get('dndupload_handler_title').get('value'));
                                        formData.append('page_range', form.get('dndupload_handler_page_range').get('value'));
                                        formData.append('externalurl', form.get('dndupload_handler_externalurl').get('value'));
                                        formData.append('doi', form.get('dndupload_handler_doi').get('value'));
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
                                        formData.append('sourceurl', form.get('dndupload_handler_sourceurl').get('value'));
                                        var index = form.get('dndupload_handler_subtype').get('selectedIndex');
                                        var subtype = form.get('dndupload_handler_subtype').get("options").item(index).get('value');
                                        formData.append('subtype', subtype);
                                        formData.append('furtherinfo', form.get('dndupload_handler_furtherinfo').get('value'));
                                        panel.hide();
                                        // Do the upload
                                        self.do_upload_coursereading(file, formData, section, sectionnumber, module);
                                    },
                                    section: Y.WidgetStdMod.FOOTER,
                                    disabled: true
                                },{
                                    value: M.util.get_string('cancel', 'moodle'),
                                    action: function(e) {
                                        e.preventDefault();
                                        panel.hide();
                                    },
                                    section: Y.WidgetStdMod.FOOTER
                                }]
                            });

                            M.mod_coursereadings.dndupload.attachSourceAutocomplete(uploadid);
                            M.mod_coursereadings.dndupload.attachRequiredMonitor(uploadid);

                            // When the panel is hidden - destroy it and then check for other pending uploads
                            panel.after("visibleChange", function(e) {
                                if (!panel.get('visible')) {
                                    panel.destroy(true);
                                    self.check_upload_queue();
                                }
                            });
                            // Focus on the 'name' box
                            Y.one('#dndupload_handler_title'+uploadid).focus();
                        } else {
                            var formData = new FormData();
                            self.do_upload_coursereading(file, formData, section, sectionnumber, module);
                        }
                    };
                    M.course_dndupload.do_upload_coursereading = function(file, formData, section, sectionnumber, module) {

                        // This would be an ideal place to use the Y.io function
                        // however, this does not support data encoded using the
                        // FormData object, which is needed to transfer data from
                        // the DataTransfer object into an XMLHTTPRequest
                        // This can be converted when the YUI issue has been integrated:
                        // http://yuilibrary.com/projects/yui3/ticket/2531274
                        var xhr = new XMLHttpRequest();
                        var self = this;

                        // Add the file to the display
                        var resel = this.add_resource_element(file.name, section);

                        // Update the progress bar as the file is uploaded
                        xhr.upload.addEventListener('progress', function(e) {
                            if (e.lengthComputable) {
                                var percentage = Math.round((e.loaded * 100) / e.total);
                                resel.progress.style.width = percentage + '%';
                            }
                        }, false);

                        // Wait for the AJAX call to complete, then update the
                        // dummy element with the returned details
                        xhr.onreadystatechange = function() {
                            if (xhr.readyState == 4) {
                                if (xhr.status == 200) {
                                    var result = JSON.parse(xhr.responseText);
                                    if (result) {
                                        if (result.error == 0) {
                                            // All OK - update the dummy element
                                            resel.li.outerHTML = result.fullcontent;
                                            if (self.Y.UA.gecko > 0) {
                                                // Fix a Firefox bug which makes sites with a '~' in their wwwroot
                                                // log the user out when clicking on the link (before refreshing the page).
                                                resel.li.outerHTML = unescape(resel.li.outerHTML);
                                            }
                                            self.add_editing(result.elementid);
                                        } else {
                                            // Error - remove the dummy element
                                            resel.parent.removeChild(resel.li);
                                            alert(result.error);
                                        }
                                    }
                                } else {
                                    alert(M.util.get_string('servererror', 'moodle'));
                                }
                            }
                        };

                        // Add extra details to data to send
                        formData.append('draftitemid', self.currentdraftid);
                        formData.append('sesskey', M.cfg.sesskey);
                        formData.append('course', this.courseid);
                        formData.append('section', sectionnumber);
                        formData.append('module', module);
                        formData.append('type', 'Files');

                        // Send the AJAX call
                        xhr.open("POST", M.cfg.wwwroot + '/mod/coursereadings/dndupload.php', true);
                        xhr.send(formData);
                    }
                    this.hasOverriddenDndUpload = true;
                }
            }, el, this);
        }, this);
    },
    makeDetailForm : function (file, includearticletotal) {
        var timestamp = new Date().getTime();
        var uploadid = Math.round(Math.random()*100000)+'-'+timestamp;
        var mod = M.mod_coursereadings.dndupload;
        var content = file?'Uploaded file: <strong>'+file.name+'</strong><br /><br />':'';
        var sourceclass = file?'has-file-upload':'no-file-upload';
        content += '<form id="dndupload_handler_article_'+uploadid+'" class="dndupload_handler_book" data-uploadid="'+uploadid+'">';
        content += '<div style="display:none;"><input type="hidden" name="dndupload_handler_articleid" value="0" /><input type="hidden" name="dndupload_handler_sourceid" value="0" /></div>';
        content += '<table style="width:100%" class="dndupload_handler_article_details">';
        content += mod.makeTableRow('title_of_article', 'title', uploadid);
        content += mod.makeTableRow('author_of_periodical', 'periodicalAuthor', uploadid);
        content += mod.makeTableRow('page_range', 'page_range', uploadid);
        content += mod.makeTableRow('externalurl', 'externalurl', uploadid);
        content += mod.makeTableRow('doi', 'doi', uploadid);
        if (includearticletotal) {
            content += mod.makeTableRow('total_pages', 'total_pages', uploadid);
        }
        content += '</table>';
        content += '<label for="dndupload_handler_sourceType'+uploadid+'">'+M.util.get_string('source_type', 'mod_coursereadings')+'</label>';
        content += '<ul class="'+'dndupload_handler_sourceType">';
        content += '<li class="'+'dndupload_handler_sourceType_book"><label for="dndupload_handler_sourceType'+uploadid+'_book"><input checked onchange="M.mod_coursereadings.dndupload.toggleSourceType(\''+uploadid+'\');" type="radio" name="dndupload_handler_sourceType" id="dndupload_handler_sourceType'+uploadid+'_book" value="book" />'+M.util.get_string('source_book', 'mod_coursereadings')+'</label></li>';
        content += '<li class="'+'dndupload_handler_sourceType_journal"><label for="dndupload_handler_sourceType'+uploadid+'_journal"><input onchange="M.mod_coursereadings.dndupload.toggleSourceType(\''+uploadid+'\');" type="radio" name="dndupload_handler_sourceType" id="dndupload_handler_sourceType'+uploadid+'_journal" value="journal" />'+M.util.get_string('source_journal', 'mod_coursereadings')+'</label></li>';
        content += '<li class="'+'dndupload_handler_sourceType_other"><label for="dndupload_handler_sourceType'+uploadid+'_other"><input onchange="M.mod_coursereadings.dndupload.toggleSourceType(\''+uploadid+'\');" type="radio" name="dndupload_handler_sourceType" id="dndupload_handler_sourceType'+uploadid+'_other" value="other" />'+M.util.get_string('source_other', 'mod_coursereadings')+'</label></li></ul>';
        content += '<button class="dndupload_handler_source_button dndupload_handler_source_reset" onclick="M.mod_coursereadings.dndupload.resetSource(event, \''+uploadid+'\');">Reset</button>';
        content += '<button class="dndupload_handler_source_button dndupload_handler_source_editasnew" onclick="M.mod_coursereadings.dndupload.editSourceAsNew(event, \''+uploadid+'\');">Edit as new</button>';
        content += '<table style="width:100%" class="dndupload_handler_source_details '+sourceclass+'">';
        content += '<tr class="dndupload_handler_journal_notice"><td colspan="2">'+M.util.get_string('journal_notice', 'mod_coursereadings')+'</td></tr>';
        content += mod.makeTableRow('source_subtype', 'subtype', uploadid);
        content += mod.makeTableRow('title_of_source', 'source', uploadid);
        content += mod.makeTableRow('author_of_source', 'sourceAuthor', uploadid);
        content += mod.makeTableRow('editor_of_source', 'sourceEditor', uploadid);
        content += mod.makeTableRow('year_of_publication', 'published', uploadid);
        content += mod.makeTableRow('volume_number', 'volume', uploadid);
        content += mod.makeTableRow('edition', 'edition', uploadid);
        content += mod.makeTableRow('publisher', 'publisher', uploadid);
        content += mod.makeTableRow('isbn', 'isbn', uploadid);
        content += mod.makeTableRow('pages', 'pages', uploadid);
        content += mod.makeTableRow('furtherinfo', 'furtherinfo', uploadid);
        content += mod.makeTableRow('sourceurl', 'sourceurl', uploadid);
        content += '</table></form>';
        return [uploadid, content];
    },
    makeTableRow : function (label, name, uploadid, width) {
        var prefix = 'dndupload_handler_';
        var requiredFields = ['title','source'];
        var required = false;
        var width = width || 250;
        for(var i=0;i<requiredFields.length;i++) {
            if (requiredFields[i] == name) {
                required = true;
                break;
            }
        }
        var content = '<tr class="'+prefix+name+(required?' required':'')+'"><td><label for="'+prefix+name+uploadid+'">'+M.util.get_string(label, 'mod_coursereadings')+'</label></td>';
        content += '<td>';
        if (name === 'subtype') {
            var subtypes = M.util.get_string('source_subtypes', 'mod_coursereadings').split(',');
            content += '<select style="width:250px;" name="'+prefix+name+'" id="'+prefix+name+uploadid+'"><option>Please select</option>';
            for (var i=0;i<subtypes.length;i++) {
                content += '<option value="'+subtypes[i]+'">'+subtypes[i]+'</option>';
            }
            content += '</select>';
        } else {
            content += '<input style="width:'+width+'px;" type="text" name="'+prefix+name+'" id="'+prefix+name+uploadid+'" value="" />';
        }
        content += '</td></tr>';
        return content;
    },
    attachRequiredMonitor : function (uploadid) {
        var self = this;
        Y.all('#dndupload_handler_article_'+uploadid+' tr.required input').on(['change','keyup'], function(e) {
            M.mod_coursereadings.dndupload.checkRequiredFields(uploadid);
        }, self);
    },
    checkRequiredFields : function(uploadid) {
        var self = this;
        var fields = Y.all('#dndupload_handler_article_'+uploadid+' tr.required input');
        var missing = false;
        fields.each(function(node, i, nodes) {
            if (node.get('value').length == 0) {
                missing = true;
            }
        }, self);
        Y.one('#dndupload_handler_article_'+uploadid).ancestor('.yui3-panel-content').one('.yui3-widget-ft .yui3-button').set('disabled', missing);
    },
    attachSourceAutocomplete : function (uploadid, sourceFormatter) {
        var self = this;
        sourceFormatter = sourceFormatter || M.mod_coursereadings.dndupload.sourceFormatter;
        Y.one('#dndupload_handler_title'+uploadid).plug(Y.Plugin.AutoComplete, {
          source: M.cfg.wwwroot + '/mod/coursereadings/ajaxsearch.php?t=article&q={query}',
          resultTextLocator: function(result) {return result.articletitle + ' (' + result.title + ', ' + result.year + ')';},
          resultFormatter: M.mod_coursereadings.dndupload.articleFormatter,
          on: {
            select: M.mod_coursereadings.dndupload.selectArticle,
            query: function(){this.get('inputNode').addClass('loading');},
            results: function(){this.get('inputNode').removeClass('loading');}
          }
        });
        Y.one('#dndupload_handler_source'+uploadid).plug(Y.Plugin.AutoComplete, {
          source: M.cfg.wwwroot + '/mod/coursereadings/ajaxsearch.php?t=source&q={query}',
          resultTextLocator: function(result) {return result.title + (result.year?' (' + result.year + ')':'');},
          resultFormatter: sourceFormatter,
          on: {
            select: M.mod_coursereadings.dndupload.selectSource,
            query: function(){this.get('inputNode').addClass('loading');},
            results: function(){this.get('inputNode').removeClass('loading');}
          }
        });
        Y.one('#dndupload_handler_isbn'+uploadid).plug(Y.Plugin.AutoComplete, {
          source: M.cfg.wwwroot + '/mod/coursereadings/ajaxsearch.php?t=isbn&q={query}',
          resultTextLocator: function(result) {return result.title + (result.year?' (' + result.year + ')':'');},
          resultFormatter: sourceFormatter,
          on: {
            select: M.mod_coursereadings.dndupload.selectSource,
            query: function(){this.get('inputNode').addClass('loading');},
            results: function(){this.get('inputNode').removeClass('loading');}
          }
        });
        Y.one('#dndupload_handler_title'+uploadid).on('change', function() {
            M.mod_coursereadings.dndupload.resetArticleID(uploadid);
        }, self);
        Y.one('#dndupload_handler_periodicalAuthor'+uploadid).on('change', function() {
            M.mod_coursereadings.dndupload.resetArticleID(uploadid);
        }, self);
        Y.one('#dndupload_handler_page_range'+uploadid).on('change', function() {
            M.mod_coursereadings.dndupload.resetArticleID(uploadid);
        }, self);
    },
    resetArticleID : function(uploadid) {
        var form = Y.one('#dndupload_handler_article_'+uploadid);
        form.get('dndupload_handler_articleid').set('value', 0);
    },
    toggleSourceType : function (uploadid) {
        var form = Y.one('#dndupload_handler_article_'+uploadid);
        switch(form.one('input[name=dndupload_handler_sourceType]:checked').get('value')) {
            case 'book':
                form.removeClass('dndupload_handler_journal');
                form.removeClass('dndupload_handler_other');
                form.addClass('dndupload_handler_book');
                break;
            case 'journal':
                form.removeClass('dndupload_handler_book');
                form.removeClass('dndupload_handler_other');
                form.addClass('dndupload_handler_journal');
                break;
            case 'other':
                form.removeClass('dndupload_handler_book');
                form.removeClass('dndupload_handler_journal');
                form.addClass('dndupload_handler_other');
                break;
        }
    },
    articleFormatter : function (query, results) {
        return Y.Array.map(results, function (result) {
            return result.raw.articletitle + '<br><em>' + result.raw.title + '</em>' + (!!result.raw.year ? ' (' + result.raw.year + ')' : '');
        });
    },
    sourceFormatter : function (query, results) {
        return Y.Array.map(results, function (result) {
            return '<em>' + result.raw.title + '</em><br>' + result.raw.author + (!!result.raw.year ? ' (' + result.raw.year + ')' : '');
        });
    },
    selectArticle : function (evt) {
        var form = evt.target.get('inputNode').get('form');
        var article = evt.result.raw;

        M.mod_coursereadings.dndupload.selectSource(evt);

        form.get('dndupload_handler_articleid').set('value', article.articleid);
        form.get('dndupload_handler_title').set('value', article.articletitle);
        form.get('dndupload_handler_page_range').set('value', article.pagerange);
        form.get('dndupload_handler_periodicalAuthor').set('value', article.periodicalauthor);

        form.ancestor('.yui3-panel-content').one('.yui3-widget-ft .yui3-button').set('disabled', false);
    },
    selectSource : function (evt) {
        var form = evt.target.get('inputNode').get('form');
        var source = evt.result.raw;
        var uploadid = form.getData('uploadid');

        evt.preventDefault();
        evt.target.hide();
        form.one('.dndupload_handler_sourceType input[value='+source.type+']').set('checked', true).set('disabled', true);
        form.all('.dndupload_handler_sourceType input:not([value='+source.type+'])').set('disabled', true);
        M.mod_coursereadings.dndupload.toggleSourceType(uploadid);
        form.get('dndupload_handler_sourceid').set('value', source.id);
        form.get('dndupload_handler_source').set('value', source.title).set('disabled', true);
        form.get('dndupload_handler_sourceAuthor').set('value', source.author).set('disabled', true);
        form.get('dndupload_handler_sourceEditor').set('value', source.editor).set('disabled', true);
        form.get('dndupload_handler_published').set('value', source.year).set('disabled', true);
        form.get('dndupload_handler_volume').set('value', source.volume).set('disabled', true);
        form.get('dndupload_handler_edition').set('value', source.edition).set('disabled', true);
        form.get('dndupload_handler_publisher').set('value', source.publisher).set('disabled', true);
        form.get('dndupload_handler_isbn').set('value', source.isbn).set('disabled', true);
        form.get('dndupload_handler_pages').set('value', source.pages).set('disabled', true);
        form.get('dndupload_handler_subtype').get('options').each(function() {
            if(this.get('value') == source.subtype) {
                this.set('selected', 'selected');
            }
        });
        form.get('dndupload_handler_subtype').set('disabled', true);
        form.get('dndupload_handler_sourceurl').set('value', source.externalurl).set('disabled', true);
        form.get('dndupload_handler_furtherinfo').set('value', source.furtherinfo).set('disabled', true);
        form.addClass('dndupload_handler_source_selected');
        M.mod_coursereadings.dndupload.checkRequiredFields(uploadid);
    },
    resetSource : function (evt, uploadid) {
        evt.preventDefault();
        var form = Y.one('#dndupload_handler_article_'+uploadid);
        form.one('.dndupload_handler_sourceType input[value=book]').set('disabled', false);
        form.one('.dndupload_handler_sourceType input[value=journal]').set('disabled', false);
        form.one('.dndupload_handler_sourceType input[value=other]').set('disabled', false);
        M.mod_coursereadings.dndupload.toggleSourceType(form.getData('uploadid'));
        form.get('dndupload_handler_sourceid').set('value', 0);
        form.get('dndupload_handler_articleid').set('value', 0);
        form.get('dndupload_handler_source').set('value', '').set('disabled', false);
        form.get('dndupload_handler_sourceAuthor').set('value', '').set('disabled', false);
        form.get('dndupload_handler_sourceEditor').set('value', '').set('disabled', false);
        form.get('dndupload_handler_published').set('value', '').set('disabled', false);
        form.get('dndupload_handler_volume').set('value', '').set('disabled', false);
        form.get('dndupload_handler_edition').set('value', '').set('disabled', false);
        form.get('dndupload_handler_publisher').set('value', '').set('disabled', false);
        form.get('dndupload_handler_isbn').set('value', '').set('disabled', false);
        form.get('dndupload_handler_pages').set('value', '').set('disabled', false);
        form.get('dndupload_handler_subtype').get('options').item(0).set('selected', 'selected');
        form.get('dndupload_handler_subtype').set('disabled', false);
        form.get('dndupload_handler_sourceurl').set('value', '').set('disabled', false);
        form.get('dndupload_handler_furtherinfo').set('disabled', false);
        form.removeClass('dndupload_handler_source_selected');
        form.ancestor('.yui3-panel-content').one('.yui3-widget-ft .yui3-button').set('disabled', true);
    },
    editSourceAsNew : function (evt, uploadid) {
        evt.preventDefault();
        var form = Y.one('#dndupload_handler_article_'+uploadid);
        form.one('.dndupload_handler_sourceType input[value=book]').set('disabled', false);
        form.one('.dndupload_handler_sourceType input[value=journal]').set('disabled', false);
        form.one('.dndupload_handler_sourceType input[value=other]').set('disabled', false);
        M.mod_coursereadings.dndupload.toggleSourceType(form.getData('uploadid'));
        form.get('dndupload_handler_sourceid').set('value', 0);
        form.get('dndupload_handler_articleid').set('value', 0);
        form.get('dndupload_handler_source').set('disabled', false);
        form.get('dndupload_handler_sourceAuthor').set('disabled', false);
        form.get('dndupload_handler_sourceEditor').set('disabled', false);
        form.get('dndupload_handler_published').set('disabled', false);
        form.get('dndupload_handler_volume').set('disabled', false);
        form.get('dndupload_handler_edition').set('disabled', false);
        form.get('dndupload_handler_publisher').set('disabled', false);
        form.get('dndupload_handler_isbn').set('disabled', false);
        form.get('dndupload_handler_pages').set('disabled', false);
        form.get('dndupload_handler_subtype').set('disabled', false);
        form.get('dndupload_handler_furtherinfo').set('disabled', false);
        form.get('dndupload_handler_sourceurl').set('disabled', false);
        form.removeClass('dndupload_handler_source_selected');
    }
};

Y.extend(DndUpload, Y.Base, DndUpload.prototype, {
    NAME : 'Copyright Materials drag-n-drop upload handler',
    ATTRS : {
        // No attributes at present
    }
});

M.mod_coursereadings = M.mod_coursereadings || {};
M.mod_coursereadings.dndupload = M.mod_coursereadings.dndupload || {};
M.mod_coursereadings.dndupload.makeDetailForm = DndUpload.prototype.makeDetailForm;
M.mod_coursereadings.dndupload.makeTableRow = DndUpload.prototype.makeTableRow;
M.mod_coursereadings.dndupload.attachSourceAutocomplete = DndUpload.prototype.attachSourceAutocomplete;
M.mod_coursereadings.dndupload.attachRequiredMonitor = DndUpload.prototype.attachRequiredMonitor;
M.mod_coursereadings.dndupload.checkRequiredFields = DndUpload.prototype.checkRequiredFields;
M.mod_coursereadings.dndupload.toggleSourceType = DndUpload.prototype.toggleSourceType;
M.mod_coursereadings.dndupload.articleFormatter = DndUpload.prototype.articleFormatter;
M.mod_coursereadings.dndupload.sourceFormatter = DndUpload.prototype.sourceFormatter;
M.mod_coursereadings.dndupload.selectArticle = DndUpload.prototype.selectArticle;
M.mod_coursereadings.dndupload.selectSource = DndUpload.prototype.selectSource;
M.mod_coursereadings.dndupload.resetSource = DndUpload.prototype.resetSource;
M.mod_coursereadings.dndupload.editSourceAsNew = DndUpload.prototype.editSourceAsNew;
M.mod_coursereadings.dndupload.resetArticleID = DndUpload.prototype.resetArticleID;
M.mod_coursereadings.dndupload.init = function(cfg) {
    return new DndUpload(cfg);
}

}, '@VERSION@', {requires:['base','node','autocomplete','moodle-mod_coursereadings-loadingpanel']});