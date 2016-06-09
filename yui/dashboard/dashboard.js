YUI.add('moodle-mod_coursereadings-dashboard', function(Y) {

/**
 * Dashboard interface.
 */
var Dashboard = function() {
    Dashboard.superclass.constructor.apply(this, arguments);
};
Dashboard.prototype = {
    initializer : function(config) {
        var self = this;

        var wrap = Y.one('div.coursereadings_dashboard');
        wrap.delegate('click', function(e) {
            e.stopPropagation();
            e.preventDefault();
            self.handle_button(e.currentTarget);
        }, '.item-controls > img', self);
        wrap.delegate('click', function(e) {
            e.stopPropagation();
            e.preventDefault();
            self.editArticle(e.currentTarget.getData('articleid'));
        }, '.coursereadings-source .dashboard-item-usage li > a', self);
        var breaches = wrap.one('.coursereadings-dashboard-breaches');
        if (breaches) {
            breaches.delegate('click', function(e) {
                e.stopPropagation();
                e.preventDefault();
                self.toggleBreachNotes(e.currentTarget.ancestor('.coursereadings-dashboard-item'));
            }, '.coursereadings-breach-notetoggle', self);
        }
    },
    handle_button : function(el) {
        var item = el.ancestor('.coursereadings-dashboard-item');
        switch(el.get('className')) {
            case 'btn-approve':
                this.approve(item);
                break;
            case 'btn-edit':
                if (item.hasClass('coursereadings-source')) {
                    this.editSource(item.getData('sourceid'));
                } else if (item.hasClass('coursereadings-article')) {
                    this.editArticle(item.getData('articleid'));
                } else {
                    this.manageBreach(item.getData('courseid'), item.getData('sourceid'));
                }
                break;
            case 'btn-flag':
                this.flag(item);
                break;
            case 'btn-delete':
                this.confirmDelete(item);
                break;
            case 'btn-addnote':
                this.add_breach_notes(item);
                break;
        }
    },
    makeSourceForm : function (source) {
        var timestamp = new Date().getTime();
        var uploadid = Math.round(Math.random()*100000)+'-'+timestamp;
        var content = '<h3>Edit source</h3>';
        content += '<form id="dashboard_source_'+uploadid+'" data-uploadid="'+uploadid+'" class="dashboard_'+source.type+'">';
        content += '<div style="display:none;"><input type="hidden" name="dashboard_sourceid" value="'+source.id+'" /></div>';
        content += '<label for="dashboard_sourceType'+uploadid+'">'+M.util.get_string('source_type_editing', 'mod_coursereadings')+'</label>';
        content += '<ul class="'+'dashboard_sourceType">';
        content += '<li class="'+'dashboard_sourceType_book"><label for="dashboard_sourceType'+uploadid+'_book"><input '+(source.type=='book'?'checked ':'')+'onchange="M.mod_coursereadings.dashboard.toggleSourceType(\''+uploadid+'\');" type="radio" name="dashboard_sourceType" id="dashboard_sourceType'+uploadid+'_book" value="book" />'+M.util.get_string('source_book', 'mod_coursereadings')+'</label></li>';
        content += '<li class="'+'dashboard_sourceType_journal"><label for="dashboard_sourceType'+uploadid+'_journal"><input '+(source.type=='journal'?'checked ':'')+'onchange="M.mod_coursereadings.dashboard.toggleSourceType(\''+uploadid+'\');" type="radio" name="dashboard_sourceType" id="dashboard_sourceType'+uploadid+'_journal" value="journal" />'+M.util.get_string('source_journal', 'mod_coursereadings')+'</label></li>';
        content += '<li class="'+'dashboard_sourceType_other"><label for="dashboard_sourceType'+uploadid+'_other"><input '+(source.type=='journal' || source.type =='book'?'':'checked ')+'onchange="M.mod_coursereadings.dashboard.toggleSourceType(\''+uploadid+'\');" type="radio" name="dashboard_sourceType" id="dashboard_sourceType'+uploadid+'_other" value="other" />'+M.util.get_string('source_other', 'mod_coursereadings')+'</label></li></ul>';
        content += '<table style="width:100%" class="dashboard_source_details">';
        content += this.makeTableRow('source_subtype', 'subtype', uploadid, source.subtype);
        content += this.makeTableRow('title_of_source', 'source', uploadid, source.title);
        content += this.makeTableRow('author_of_source', 'sourceAuthor', uploadid, source.author);
        content += this.makeTableRow('editor_of_source', 'sourceEditor', uploadid, source.editor);
        content += this.makeTableRow('year_of_publication', 'published', uploadid, source.year);
        content += this.makeTableRow('volume_number', 'volume', uploadid, source.volume);
        content += this.makeTableRow('edition', 'edition', uploadid, source.edition);
        content += this.makeTableRow('publisher', 'publisher', uploadid, source.publisher);
        content += this.makeTableRow('isbn', 'isbn', uploadid, source.isbn);
        content += this.makeTableRow('pages', 'pages', uploadid, source.pages);
        content += this.makeTableRow('furtherinfo', 'furtherinfo', uploadid, source.furtherinfo);
        content += '</table></form>';
        return [uploadid, content];
    },
    makeTableRow : function (label, name, uploadid, value) {
        var prefix = 'dashboard_';
        var content = '<tr class="'+prefix+name+'"><td><label for="'+prefix+name+uploadid+'">'+M.util.get_string(label, 'mod_coursereadings')+'</label></td>';
        content += '<td>';
        if (name === 'subtype') {
            var subtypes = M.util.get_string('source_subtypes', 'mod_coursereadings').split(',');
            content += '<select style="width:250px;" name="'+prefix+name+'" id="'+prefix+name+uploadid+'"><option>Please select</option>';
            for (var i=0;i<subtypes.length;i++) {
                content += '<option value="'+subtypes[i]+'"'+(value == subtypes[i] ? ' selected' : '')+'>'+subtypes[i]+'</option>';
            }
            content += '</select>';
        } else {
            if(value == null) {
                value = '';
            } else {
                value = value.replace(/"/g, '&quot;');
            }
            content += '<input style="width:250px;" type="text" name="'+prefix+name+'" id="'+prefix+name+uploadid+'" value="'+value+'" />';
        }
        content += '</td></tr>';
        return content;
    },
    toggleSourceType : function (uploadid) {
        var form = Y.one('#dashboard_source_'+uploadid);
        switch(form.one('input[name=dashboard_sourceType]:checked').get('value')) {
            case 'book':
                form.removeClass('dashboard_journal');
                form.removeClass('dashboard_other');
                form.addClass('dashboard_book');
                break;
            case 'journal':
                form.removeClass('dashboard_book');
                form.removeClass('dashboard_other');
                form.addClass('dashboard_journal');
                break;
            case 'other':
                form.removeClass('dashboard_book');
                form.removeClass('dashboard_journal');
                form.addClass('dashboard_other');
                break;
        }
    },
    editSource : function(sourceid) {
        var self = this;
        var formData = new FormData();
        formData.append('t', 'source');
        formData.append('q', sourceid);

        var callback = function(result) {
            var sourceForm = self.makeSourceForm(result);
            var uploadid = sourceForm[0], content = sourceForm[1];
            var panel = new Y.Panel({
                bodyContent: content,
                width: 580,
                zIndex: 5,
                centered: true,
                modal: true,
                visible: false,
                render: false,
                buttons: [{
                    value: M.util.get_string('dashboard_mergesource', 'mod_coursereadings'),
                    action: function(e) {
                        e.preventDefault();
                        var ret = 'dashboard';
                        if (document.body.id === 'page-mod-coursereadings-manage-flagged-sources') {
                            ret = 'flagged';
                        }
                        Y.config.win.location = M.cfg.wwwroot + '/mod/coursereadings/manage/merge-source.php?id=' + result.id + '&return=' + ret;
                    },
                    section: Y.WidgetStdMod.FOOTER
                },{
                    value: M.util.get_string('delete', 'moodle'),
                    action: function(e) {
                        e.preventDefault();
                        self.confirmDeleteSource(sourceid);
                    },
                    section: Y.WidgetStdMod.FOOTER,
                    classNames: 'coursereadings-delete-button',
                    disabled: (result.usage>0)
                },{
                    value: M.util.get_string('savechanges', 'moodle'),
                    action: function(e) {
                        e.preventDefault();
                        var form = Y.one('#dashboard_source_'+uploadid);
                        var formData = new FormData();
                        formData.append('sourceid', result.id);
                        formData.append('source_type', form.one('input[name=dashboard_sourceType]:checked').get('value'));
                        formData.append('title_of_source', form.get('dashboard_source').get('value'));
                        formData.append('author_of_source', form.get('dashboard_sourceAuthor').get('value'));
                        formData.append('editor_of_source', form.get('dashboard_sourceEditor').get('value'));
                        formData.append('year_of_publication', form.get('dashboard_published').get('value'));
                        formData.append('volume_number', form.get('dashboard_volume').get('value'));
                        formData.append('edition', form.get('dashboard_edition').get('value'));
                        formData.append('publisher', form.get('dashboard_publisher').get('value'));
                        formData.append('isbn', form.get('dashboard_isbn').get('value'));
                        formData.append('pages', form.get('dashboard_pages').get('value'));
                        self.saveSource(formData, Y.one('.coursereadings-dashboard-item[data-sourceid='+result.id+']'));
                        panel.hide();
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
            panel.render().show();
        }

        self.send_request(formData, callback);
    },
    saveSource : function(formData, el) {
        var self = this;
        formData.append('t', 'editsource');
        formData.append('q', el.getData('queueid'));

        var callback = function(result) {
            self.remove(el);
        }

        self.send_request(formData, callback);
    },
    makeArticleForm : function (article) {
        var timestamp = new Date().getTime();
        var uploadid = Math.round(Math.random()*100000)+'-'+timestamp;
        var content = '<h3>Edit article</h3>';
        content += '<form id="dashboard_article_'+uploadid+'" data-uploadid="'+uploadid+'">';
        content += '<div style="display:none;"><input type="hidden" name="dashboard_articleid" value="'+article.id+'" /><input type="hidden" name="dashboard_article_sourceid" value="'+article.source+'" /></div>';
        content += '<table style="width:100%" class="dashboard_article_details">';
        content += this.makeTableRow('title_of_article', 'title', uploadid, article.title);
        content += this.makeTableRow('author_of_periodical', 'periodicalAuthor', uploadid, article.periodicalauthor);
        content += this.makeTableRow('page_range', 'page_range', uploadid, article.pagerange);
        content += this.makeTableRow('total_pages', 'total_pages', uploadid, article.totalpages);
        content += this.makeTableRow('externalurl', 'externalurl', uploadid, article.externalurl);
        content += this.makeTableRow('doi', 'doi', uploadid, article.doi);
        content += this.makeTableRow('source', 'source_name', uploadid, article.sourcetitle + (article.year?' (' + article.year + ')':''));
        content += '</table>';
        content += '</form>';
        return [uploadid, content];
    },
    editArticle : function(articleid) {
        var self = this;
        var formData = new FormData();
        formData.append('t', 'article');
        formData.append('q', articleid);

        var callback = function(result) {
            var articleForm = self.makeArticleForm(result);
            var uploadid = articleForm[0], content = articleForm[1];
            var panel = new Y.Panel({
                bodyContent: content,
                width: 580,
                zIndex: 5,
                centered: true,
                modal: true,
                visible: false,
                render: false,
                buttons: [{
                    value: M.util.get_string('dashboard_mergearticle', 'mod_coursereadings'),
                    action: function(e) {
                        e.preventDefault();
                        var ret = 'dashboard';
                        if (document.body.id === 'page-mod-coursereadings-manage-flagged-articles') {
                            ret = 'flagged';
                        }
                        Y.config.win.location = M.cfg.wwwroot + '/mod/coursereadings/manage/merge-article.php?id=' + result.id + '&return=' + ret;
                    },
                    section: Y.WidgetStdMod.FOOTER
                },{
                    value: M.util.get_string('delete', 'moodle'),
                    action: function(e) {
                        e.preventDefault();
                        self.confirmDeleteArticle(articleid, panel);
                    },
                    section: Y.WidgetStdMod.FOOTER,
                    classNames: 'coursereadings-delete-button',
                    disabled: (result.usage>0)
                },{
                    value: M.util.get_string('savechanges', 'moodle'),
                    action: function(e) {
                        e.preventDefault();
                        var form = Y.one('#dashboard_article_'+uploadid);
                        var formData = new FormData();
                        formData.append('articleid', result.id);
                        formData.append('title', form.get('dashboard_title').get('value'));
                        formData.append('author', form.get('dashboard_periodicalAuthor').get('value'));
                        formData.append('page_range', form.get('dashboard_page_range').get('value'));
                        formData.append('total_pages', form.get('dashboard_total_pages').get('value'));
                        formData.append('externalurl', form.get('dashboard_externalurl').get('value'));
                        formData.append('doi', form.get('dashboard_doi').get('value'));
                        formData.append('source', form.get('dashboard_article_sourceid').get('value'));
                        self.saveArticle(formData, Y.one('.coursereadings-dashboard-item[data-articleid='+result.id+']'));
                        panel.hide();
                    },
                    section: Y.WidgetStdMod.FOOTER,
                    classNames: 'save_article'
                },{
                    value: M.util.get_string('cancel', 'moodle'),
                    action: function(e) {
                        e.preventDefault();
                        panel.hide();
                    },
                    section: Y.WidgetStdMod.FOOTER
                }]
            });
            panel.render().show();
            self.attachSourceAutocomplete(uploadid);
        }

        self.send_request(formData, callback);
    },
    attachSourceAutocomplete : function (uploadid) {
        var self = this;
        var el = Y.one('#dashboard_source_name'+uploadid);
        el.plug(Y.Plugin.AutoComplete, {
          source: M.cfg.wwwroot + '/mod/coursereadings/ajaxsearch.php?t=source&q={query}',
          resultTextLocator: function(result) {return result.title + (result.year?' (' + result.year + ')':'');},
          resultFormatter: self.sourceFormatter,
          on: {
            select: self.selectSource,
            query: function(){el.addClass('loading')},
            results: function(){el.removeClass('loading')}
          }
        });
        el.set('disabled', true);
        el.insert('<img class="source_clear" src="'+M.util.image_url('i/invalid')+'" />', 'after').next('img.source_clear').on('click', function() {
            el.set('value', '').set('disabled', false).focus();
            el.get('form').get('dashboard_article_sourceid').set('value', '');
            el.ancestor('.yui3-panel-content').one('button.save_article').set('disabled', true);
        });
    },
    sourceFormatter : function (query, results) {
        return Y.Array.map(results, function (result) {
            var open = (result.raw.queueid == null) ? '<div class="source-approved">' : '';
            var close = (open.length) ? '</div>' : '';
            return open + '<em>' + result.raw.title + '</em><br>' + result.raw.author + (result.raw.year?' (' + result.raw.year + ')':'') + close;
        });
    },
    selectSource : function (evt) {
        var form = evt.target.get('inputNode').get('form');
        var source = evt.result.raw;

        form.get('dashboard_article_sourceid').set('value', source.id);
        form.get('dashboard_source_name').set('disabled', true);
        form.ancestor('.yui3-panel-content').one('button.save_article').set('disabled', false);
    },
    saveArticle : function(formData, el) {
        var self = this;
        formData.append('t', 'editarticle');
        if (el) {
            // Add queue ID if we're editing it from the "new articles" queue.
            formData.append('q', el.getData('queueid'));
        }

        var callback = function(result, el) {
            if (el) {
                // Updated from "new articles" queue - remove it from the queue.
                self.remove(el);
            } else {
                // Updated from the "flagged sources" list - only remove it if the source has changed.
                var el = Y.one('.dashboard-item-usage a[data-articleid='+result.articleid+']').ancestor();
                var list = el.ancestor();
                var item = list.ancestor('.coursereadings-dashboard-item');
                if (result.sourceid != item.getData('sourceid')) {
                    self.removeFlaggedSourceUsage(el, list, item);
                }
            }
        }

        self.send_request(formData, callback, el);
    },
    removeFlaggedSourceUsage : function(el, list, item) {
        el.remove(true);
        if (list.all('li').size() == 0) {
            // No articles using this source any more.
            list.replace('<p>Not currently in use.</p>');
            item.addClass('coursereadings-deletable');
        }
    },
    approve : function(item) {
        var self = this;
        var queueid = item.getData('queueid');
        var formData = new FormData();
        formData.append('t', 'approveitem');
        formData.append('q', queueid);

        var callback = function(result) {
            var el = Y.one('.coursereadings-dashboard-item[data-queueid='+queueid+']');
            self.remove(el);
        }

        self.send_request(formData, callback);
    },
    flag : function(el) {
        var self = this;
        var timestamp = new Date().getTime();
        var uploadid = Math.round(Math.random()*100000)+'-'+timestamp;
        var notes = '';
        var notesel = el.one('.dashboard-item-notes pre');
        var content = '<h3>Flag ' + (el.hasClass('coursereadings-article') ? 'Article' : 'Source') + '</h3>';
        content += '<form id="dashboard_flag_'+uploadid+'" data-uploadid="'+uploadid+'"><div>';
        content += '<input type="hidden" name="queueid" value="'+el.getData('queueid')+'" />';
        content += '<label for="flag_notes">Notes:</label><br />';
        if (notesel !== null) {
            notes = notesel.getHTML();
        }
        content += '<textarea name="flag_notes" class="coursereadings-flag-notes">'+notes+'</textarea>';
        content += '</div></form>'
        var panel = new Y.Panel({
            bodyContent: content,
            width: 580,
            zIndex: 5,
            centered: true,
            modal: true,
            visible: false,
            render: false,
            buttons: [{
                value: M.util.get_string('savechanges', 'moodle'),
                action: function(e) {
                    e.preventDefault();
                    var form = Y.one('#dashboard_flag_'+uploadid);
                    var formData = new FormData();
                    var queueid = form.get('queueid').get('value');
                    formData.append('q', queueid);
                    formData.append('notes', form.get('flag_notes').get('value'));
                    self.saveNotes(formData, Y.one('.coursereadings-dashboard-item[data-queueid='+queueid+']'));
                    panel.hide();
                },
                section: Y.WidgetStdMod.FOOTER,
                classNames: 'save_article'
            },{
                value: M.util.get_string('cancel', 'moodle'),
                action: function(e) {
                    e.preventDefault();
                    panel.hide();
                },
                section: Y.WidgetStdMod.FOOTER
            }]
        });
        panel.render().show();
        Y.one('#dashboard_flag_'+uploadid+' .coursereadings-flag-notes').focus();
    },
    saveNotes : function(formData, el) {
        var self = this;
        formData.append('t', 'flag');

        var callback = function(result) {
            var notesel = el.one('.dashboard-item-notes pre');
            if (notesel !== null) {
                notesel.setHTML(result.notes);
                return; // We're viewing flagged items, so shouldn't hide it when adding/updating notes.
            }
            self.remove(el);
        }

        self.send_request(formData, callback);
    },
    confirmDelete : function(item, editpanel) {
        var self = this;
        var type = item.hasClass('coursereadings-article') ? 'article' : 'source';
        var content = '<h3>Are you sure you want to delete this '+type+'?</h3>';
        var panel = new Y.Panel({
            bodyContent: content,
            width: 580,
            zIndex: 6,
            centered: true,
            modal: true,
            visible: false,
            render: false,
            buttons: [{
                value: M.util.get_string('delete', 'moodle'),
                action: function(e) {
                    e.preventDefault();
                    self['delete'](item, editpanel); // IE8 doesn't like self.delete because delete is reserved.
                    panel.hide();
                },
                section: Y.WidgetStdMod.FOOTER,
                classNames: 'delete_item'
            },{
                value: M.util.get_string('cancel', 'moodle'),
                action: function(e) {
                    e.preventDefault();
                    panel.hide();
                },
                section: Y.WidgetStdMod.FOOTER
            }]
        });
        panel.render().show();
    },
    'delete' : function(item, editpanel) { // Quoted to appease IE8.
        var self = this;
        var formData = new FormData();
        formData.append('t', 'delete');
        formData.append('q', item.getData('queueid'));

        var callback = function(result) {
            self.remove(item);
            if (editpanel) {
                editpanel.hide();
            }
        }

        self.send_request(formData, callback);
    },
    confirmDeleteSource : function(sourceid, editpanel) {
        var el = Y.one('.coursereadings-dashboard-item[data-sourceid='+sourceid+']');
        this.confirmDelete(el, editpanel);
    },
    confirmDeleteArticle : function(articleid, editpanel) {
        var el = Y.one('.coursereadings-dashboard-item[data-articleid='+articleid+']');
        if (el) {
            // Deleted directly (not via source usage list), so we should delete the queue item.
            this.confirmDelete(el, editpanel);
        } else {
            // Deleted from source usage list, so we need to do our own prompt so we can remove it from the list properly.
            var self = this;
            var content = '<h3>Are you sure you want to delete this article?</h3>';
            var panel = new Y.Panel({
                bodyContent: content,
                width: 580,
                zIndex: 6,
                centered: true,
                modal: true,
                visible: false,
                render: false,
                buttons: [{
                    value: M.util.get_string('delete', 'moodle'),
                    action: function(e) {
                        e.preventDefault();
                        self.deleteArticle(articleid, editpanel);
                        panel.hide();
                    },
                    section: Y.WidgetStdMod.FOOTER,
                    classNames: 'delete_item'
                },{
                    value: M.util.get_string('cancel', 'moodle'),
                    action: function(e) {
                        e.preventDefault();
                        panel.hide();
                    },
                    section: Y.WidgetStdMod.FOOTER
                }]
            });
            panel.render().show();
        }
    },
    deleteArticle : function(articleid, editpanel) {
        var self = this;
        var formData = new FormData();
        formData.append('t', 'deletearticle');
        formData.append('q', articleid);

        var callback = function(result) {
            var el = Y.one('.dashboard-item-usage a[data-articleid='+articleid+']').ancestor();
            var list = el.ancestor();
            var item = list.ancestor('.coursereadings-dashboard-item');
            self.removeFlaggedSourceUsage(el, list, item);
            editpanel.hide();
        }

        self.send_request(formData, callback);
    },
    makeBreachTableRow : function (label, name, value) {
        if (value == null) return '';
        var prefix = 'dashboard_';
        var content = '<tr class="'+prefix+name+'"><td><label>'+M.util.get_string(label, 'mod_coursereadings')+'</label></td>';
        content += '<td>'+value+'</td></tr>';
        return content;
    },
    manageBreach : function(courseid, sourceid) {
        var self = this;
        var formData = new FormData();
        formData.append('t', 'breach');
        formData.append('c', courseid);
        formData.append('s', sourceid);

        var callback = function(result) {
            // Calculate total scanned pages.
            var totalscanned = 0;
            var allscanned = true;
            for (var id in result.articles) {
                var article = result.articles[id];
                if (article.totalpages != null && article.totalpages > 0) {
                    totalscanned += parseInt(article.totalpages);
                } else {
                    allscanned = false;
                }
            }
            if (!allscanned) {
                var notall = M.util.get_string('scanned_notall', 'mod_coursereadings');
                totalscanned += ' <img src="' + M.util.image_url('i/warning')+'" title="' + notall + '" alt="' + notall + '">';
            }

            var content = '<div class="dashboard_breach_detail dashboard_'+result.source.type+'">';
            content += '<h3>'+result.course.shortname+'</h3>';
            content += '<h4><a target="_blank" href="'+M.cfg.wwwroot+'/course/view.php?id='+result.course.id+'">'+result.course.fullname+'</a></h4><br />';
            content += M.util.get_string('source_type_editing', 'mod_coursereadings');
            content += '<ul class="'+'dashboard_sourceType">';
            content += '<li class="'+'dashboard_sourceType_book">'+M.util.get_string('source_book', 'mod_coursereadings')+'</li>';
            content += '<li class="'+'dashboard_sourceType_journal">'+M.util.get_string('source_journal', 'mod_coursereadings')+'</li></ul>';
            content += '<table style="width:100%" class="dashboard_source_details">';
            content += self.makeBreachTableRow('title_of_source', 'source', result.source.title);
            content += self.makeBreachTableRow('author_of_source', 'sourceAuthor', result.source.author);
            content += self.makeBreachTableRow('editor_of_source', 'sourceEditor', result.source.editor);
            content += self.makeBreachTableRow('year_of_publication', 'published', result.source.year);
            content += self.makeBreachTableRow('volume_number', 'volume', result.source.volume);
            content += self.makeBreachTableRow('edition', 'edition', result.source.edition);
            content += self.makeBreachTableRow('publisher', 'publisher', result.source.publisher);
            content += self.makeBreachTableRow('isbn', 'isbn', result.source.isbn);
            content += self.makeBreachTableRow('pages', 'pages', result.source.pages);
            content += self.makeBreachTableRow('scanned', 'pages', totalscanned);
            content += '</table></form>';
            content += '<h4>Articles used:</h4>';
            content += '<ul class="articles">';
            for (var id in result.articles) {
                var article = result.articles[id];
                var classes = article.approved ? 'coursereadings-approved' : 'coursereadings-unapproved';
                classes += article.withinlimits ? ' coursereadings-withinlimits' : '';
                content += '<li class="'+classes+'">';
                content += article.link;
                if (article.pagerange != null && article.pagerange.length) {
                    content += ' '+article.pagerange;
                }
                if (article.totalpages != null && article.totalpages > 0) {
                    content += ' ['+article.totalpages+' total]';
                }
                content += '</li>';
            }
            content += '</ul>';
            content += '</div>';
            var panel = new Y.Panel({
                bodyContent: content,
                width: 580,
                zIndex: 5,
                centered: true,
                modal: true,
                visible: false,
                render: false,
                buttons: [{
                    value: M.util.get_string('approve_within_limits', 'mod_coursereadings'),
                    action: function(e) {
                        e.preventDefault();
                        self.approve_within_limits(result, panel);
                    },
                    section: Y.WidgetStdMod.FOOTER
                },{
                    value: M.util.get_string('approve_with_notes', 'mod_coursereadings'),
                    action: function(e) {
                        e.preventDefault();
                        self.approve_with_notes(result, panel);
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
            panel.render().show();
        }

        self.send_request(formData, callback);
    },
    approve_within_limits : function(result, panel) {
        var self = this;
        var formData = new FormData();
        formData.append('t', 'approvebreach');
        formData.append('c', result.course.id);
        formData.append('s', result.source.id);
        formData.append('w', 1); // Within limits
        var approvalid = 0;
        for (var id in result.articles) {
            var article = result.articles[id];
            if (article.approved) {
                if (article.withinlimits) {
                    approvalid = article.approvalid;
                }
                continue;
            }
            formData.append('a[]', id);
        }
        if (approvalid) {
            formData.append('id', approvalid);
        }
        self.send_request(formData, function() {
            panel.hide();
            self.remove(Y.one('.coursereadings-breach[data-courseid='+result.course.id+'][data-sourceid='+result.source.id+']'));
        })
    },
    approve_with_notes : function(result, panel) {
        var self = this;
        var timestamp = new Date().getTime();
        var uniqueid = Math.round(Math.random()*100000)+'-'+timestamp;
        var content = '<h3>Approve usage</h3>';
        content += 'Notes:<br />';
        content += '<textarea id="approval_notes_'+uniqueid+'" class="coursereadings-approval-notes"></textarea><br />';
        content += 'Approval is for any amount of content:';
        content += '<input type="checkbox" id="approval_blanket_'+uniqueid+'" />';
        var formData = new FormData();
        formData.append('t', 'approvebreach');
        formData.append('c', result.course.id);
        formData.append('s', result.source.id);
        var approvalid = 0;
        for (var id in result.articles) {
            var article = result.articles[id];
            if (article.approved) {
                if (!article.withinlimits) {
                    approvalid = article.approvalid;
                }
                continue;
            }
            formData.append('a[]', id);
        }
        if (approvalid) {
            formData.append('id', approvalid);
            var formData = new FormData();
            formData.append('t', 'approvalnotes');
            formData.append('q', approvalid);
            self.send_request(formData, function(response) {
                Y.one('#approval_blanket_'+uniqueid).setHTML(response.notes);
                self.remove(Y.one('.coursereadings-breach[data-courseid='+result.course.id+'][data-sourceid='+result.source.id+']'));
            })
        }
        var notespanel = new Y.Panel({
            bodyContent: content,
            width: 580,
            zIndex: 10,
            centered: true,
            modal: true,
            visible: false,
            render: false,
            buttons: [{
                value: M.util.get_string('approve', 'mod_coursereadings'),
                action: function(e) {
                    e.preventDefault();
                    formData.append('notes', Y.one('#approval_notes_'+uniqueid).get('value'));
                    if (Y.one('#approval_blanket_'+uniqueid).get('checked')) {
                        formData.append('b', 1);
                    }
                    self.send_request(formData, function() {
                        notespanel.hide();
                        panel.hide();
                        self.remove(Y.one('.coursereadings-breach[data-courseid='+result.course.id+'][data-sourceid='+result.source.id+']'));
                    })
                },
                section: Y.WidgetStdMod.FOOTER
            },{
                value: M.util.get_string('cancel', 'moodle'),
                action: function(e) {
                    e.preventDefault();
                    notespanel.hide();
                },
                section: Y.WidgetStdMod.FOOTER
            }]
        });
        notespanel.render().show();
    },
    add_breach_notes : function(el) {
        var self = this;
        var timestamp = new Date().getTime();
        var uploadid = Math.round(Math.random()*100000)+'-'+timestamp;
        var notes = '';
        var content = '<h3>Add notes</h3>';
        content += '<div id="dashboard_breachnotes_view_'+uploadid+'" class="coursereadings-breach-notes-view"></div>';
        content += '<form id="dashboard_breachnotes_'+uploadid+'" data-uploadid="'+uploadid+'"><div>';
        content += '<input type="hidden" name="courseid" value="'+el.getData('courseid')+'" />';
        content += '<input type="hidden" name="sourceid" value="'+el.getData('sourceid')+'" />';
        content += '<label for="breach_notes">'+M.util.get_string('addnewnote', 'notes')+':</label><br />';
        content += '<textarea name="breach_notes" class="coursereadings-breach-notes"></textarea>';
        content += '</div></form>'
        var panel = new Y.Panel({
            bodyContent: content,
            width: 550,
            zIndex: 5,
            centered: true,
            modal: true,
            visible: false,
            render: false,
            buttons: [{
                value: M.util.get_string('savechanges', 'moodle'),
                action: function(e) {
                    e.preventDefault();
                    var form = Y.one('#dashboard_breachnotes_'+uploadid);
                    var formData = new FormData();
                    formData.append('c', form.get('courseid').get('value'));
                    formData.append('s', form.get('sourceid').get('value'));
                    formData.append('notes', form.get('breach_notes').get('value'));
                    self.saveBreachNotes(formData);
                    panel.hide();
                },
                section: Y.WidgetStdMod.FOOTER,
                classNames: 'save_article'
            },{
                value: M.util.get_string('cancel', 'moodle'),
                action: function(e) {
                    e.preventDefault();
                    panel.hide();
                },
                section: Y.WidgetStdMod.FOOTER
            }]
        });
        panel.render().show();
        self.getBreachNotes(el.getData('courseid'), el.getData('sourceid'), uploadid);
        Y.one('#dashboard_breachnotes_'+uploadid+' .coursereadings-breach-notes').focus();
    },
    getBreachNotes : function(courseid, sourceid, uploadid) {
        var self = this;
        var formData = new FormData();
        formData.append('c', courseid);
        formData.append('s', sourceid);
        formData.append('t', 'breachnotes');

        var callback = function(result) {
            Y.one('#dashboard_breachnotes_view_'+uploadid).setHTML(result.html);
        }

        self.send_request(formData, callback);
    },
    saveBreachNotes : function(formData) {
        var self = this;
        formData.append('t', 'addbreachnote');

        var callback = function(result) {}

        self.send_request(formData, callback);
    },
    toggleBreachNotes : function(el) {
        var toggle = el.one('.coursereadings-breach-notetoggle');
        if (toggle.hasClass('expanded')) {
            el.one('.coursereadings-breach-notes').empty();
            toggle.removeClass('expanded');
        } else {
            this.displayBreachNotesInline(el);
            toggle.addClass('expanded');
        }
    },
    displayBreachNotesInline : function(el) {
        var self = this;
        var formData = new FormData();
        formData.append('c', el.getData('courseid'));
        formData.append('s', el.getData('sourceid'));
        formData.append('t', 'breachnotes');
        el.one('.coursereadings-breach-notes').addClass('loading');

        var callback = function(result) {
            el.one('.coursereadings-breach-notes').removeClass('loading').setHTML(result.html);
        }

        self.send_request(formData, callback);
    },
    remove : function(el) {
        el.transition({
            easing: 'ease-out',
            duration: 0.5,
            height: '0px'
        }, function() {
            this.remove();
        });
    },
    send_request : function(formData, callback, extra) {
        var xhr = new XMLHttpRequest();
        var self = this;

        xhr.onreadystatechange = function() {
            if (xhr.readyState == 4) {
                if (xhr.status == 200) {
                    var result = JSON.parse(xhr.responseText);
                    if (result) {
                        if (result.error == 0) {
                            callback(result, extra);
                        } else {
                            alert(result.error);
                        }
                    }
                } else {
                    alert(M.util.get_string('servererror', 'moodle'));
                }
            }
        };

        formData.append('sesskey', M.cfg.sesskey);

        // Send the AJAX call
        xhr.open("POST", M.cfg.wwwroot + '/mod/coursereadings/manage/ajax.php', true);
        xhr.send(formData);
    }
};

Y.extend(Dashboard, Y.Base, Dashboard.prototype, {
    NAME : 'Copyright Materials dashboard UI',
    ATTRS : {
        // No attributes at present
    }
});

M.mod_coursereadings = M.mod_coursereadings || {};
M.mod_coursereadings.dashboard = M.mod_coursereadings.dashboard || {};
M.mod_coursereadings.dashboard.toggleSourceType = Dashboard.prototype.toggleSourceType;
M.mod_coursereadings.dashboard.sourceFormatter = Dashboard.prototype.sourceFormatter;
M.mod_coursereadings.dashboard.clearSource = Dashboard.prototype.clearSource;
M.mod_coursereadings.dashboard.resetSource = Dashboard.prototype.resetSource;
M.mod_coursereadings.dashboard.init = function(cfg) {
    return new Dashboard(cfg);
}

}, '@VERSION@', {requires:['base','node','selector-css3','transition','autocomplete']});