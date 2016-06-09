YUI.add('moodle-mod_coursereadings-contentmigration', function(Y) {

/**
 * Content migration interface.
 */
var ContentMigration = function() {
    ContentMigration.superclass.constructor.apply(this, arguments);
};
ContentMigration.prototype = {
    activeinstance : null,
    numinstances : 0,
    articleTemplate : '<strong>{link}</strong><br>{author} ({year})<br><em>{sourcetitle}</em>',
    initializer : function(config) {
        var self = this;

        var list = Y.one('div.coursereadings_content_migration_list');
        var form = Y.one('.coursereadings_content_migration_form');
        list.delegate('click', function(e) {
            if (e.target.get('nodeName') == 'A') return;
            e.stopPropagation();
            e.preventDefault();
            self.select_instance(e.currentTarget);
        }, '.coursereadings_resource_instance', self);
        self.select_instance(list.one('.coursereadings_resource_instance'));
        self.numinstances = list.all('.coursereadings_resource_instance').size();

        list.delegate('click', function(e) {
            e.stopPropagation();
            e.preventDefault();
            form.one('.cancel_btn').set('disabled', false);
            self.migrate_instance(e.currentTarget.ancestor('.coursereadings_resource_instance'));
        }, 'button.is_copyright', self);

        list.delegate('click', function(e) {
            e.stopPropagation();
            e.preventDefault();
            self.mark_not_copyright(e.currentTarget.ancestor('.coursereadings_resource_instance'));
        }, 'button.not_copyright', self);

        list.delegate('click', function(e) {
            e.stopPropagation();
            e.preventDefault();
            self.add_notes(e.currentTarget.ancestor('.coursereadings_resource_instance'));
        }, 'button.add_notes', self);

        form.one('.cancel_btn').on('click', function() {
            form.setStyle('display', 'none');
            Y.one('.coursereadings_content_migration_list').setStyle('display', 'block');
            Y.one('.yui3-widget-ft .yui3-button').set('disabled', true);
        });
        form.one('.save_btn').on('click', function() {
            var el = Y.one('.coursereadings_content_migration_list #coursereadings_resource_instance_'+self.activeinstance);
            var articleid = form.one('form').get('dndupload_handler_articleid').get('value');
            var instanceid = el.getData('instanceid');

            // Disable save button to prevent double-clicking.
            form.one('.save_btn').set('disabled', true);
            form.one('.cancel_btn').set('disabled', true);

            if(articleid > 0) {
                self.migrateArticle(instanceid, articleid);
            } else {
                self.saveArticle()
            }
            el.addClass('migrated');
            self.select_next(el);
            form.setStyle('display', 'block');
            Y.one('.coursereadings_content_migration_list').setStyle('display', 'none');
        });
        form.one('.instance_filematch > button').on('click', function() {
            el = Y.one('.coursereadings_content_migration_list #coursereadings_resource_instance_'+self.activeinstance);
            self.migrateArticle(el.getData('instanceid'), el.getData('articleid'));
            el.addClass('migrated');
            self.select_next(el);
        });
    },
    select_instance : function(el) {
        var instanceid = el.getData('instanceid');
        var preview = Y.one('div.coursereadings_content_migration_preview object');
        var newobj = Y.Node.create('<object data="myfile.pdf" type="application/pdf" width="100%" height="100%"></object>');
        if (instanceid == this.activeinstance) {
            return;
        }
        if (this.activeinstance) {
            Y.one('.coursereadings_content_migration_list #coursereadings_resource_instance_'+this.activeinstance).removeClass('active');
        }

        newobj.setHTML(preview.getHTML());
        newobj.setAttribute('data', el.getData('file-url'));
        preview.ancestor().replaceChild(newobj, preview);
        preview.setStyle('display', '');

        el.addClass('active');
        this.activeinstance = instanceid;
    },
    select_next : function(el) {
        if(el.getData('index') < (this.numinstances - 1)) {
            this.select_instance(el.next('.coursereadings_resource_instance'));
        }
    },
    migrate_instance : function(el) {
        // Lots to do here!
        this.launchMigrate(el);
    },
    mark_not_copyright : function(el) {
        var self = this;
        var formData = new FormData();
        formData.append('t', 'notcopyright');
        formData.append('q', el.getData('instanceid'));

        var callback = function(result) {
            self.select_next(el);
        }

        el.addClass('not_migrated');
        self.send_request(formData, callback);
    },
    add_notes : function(el) {
        var self = this;
        var timestamp = new Date().getTime();
        var uploadid = Math.round(Math.random()*100000)+'-'+timestamp;
        var notes = '';
        var content = '<h3>Migration notes</h3>';
        content += '<div id="dashboard_migrationnotes_view_'+uploadid+'" class="coursereadings-migration-notes-view"></div>';
        content += '<form id="dashboard_migrationnotes_'+uploadid+'" data-uploadid="'+uploadid+'"><div>';
        content += '<input type="hidden" name="resourceid" value="'+el.getData('instanceid')+'" />';
        content += '<label for="migration_notes">'+M.util.get_string('addnewnote', 'notes')+':</label><br />';
        content += '<textarea name="migration_notes" class="coursereadings-migration-notes"></textarea>';
        content += '</div></form>'
        var panel = new Y.Panel({
            bodyContent: content,
            width: 550,
            zIndex: 5,
            centered: true,
            modal: true,
            visible: true,
            render: true,
            buttons: [{
                value: M.util.get_string('savechanges', 'moodle'),
                action: function(e) {
                    e.preventDefault();
                    var form = Y.one('#dashboard_migrationnotes_'+uploadid);
                    var formData = new FormData();
                    var resourceid = form.get('resourceid').get('value');
                    formData.append('q', resourceid);
                    formData.append('notes', form.get('migration_notes').get('value'));
                    self.saveNotes(formData);
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
        self.getNotes(el.getData('instanceid'), uploadid);
        Y.one('#dashboard_migrationnotes_'+uploadid+' .coursereadings-migration-notes').focus();
    },
    getNotes : function(resourceid, uploadid) {
        var self = this;
        var formData = new FormData();
        formData.append('q', resourceid);
        formData.append('t', 'migrationnotes');

        var callback = function(result) {
            Y.one('#dashboard_migrationnotes_view_'+uploadid).setHTML(result.html);
        }

        self.send_request(formData, callback);
    },
    saveNotes : function(formData) {
        var self = this;
        formData.append('t', 'addmigrationnote');

        var callback = function(result) {}

        self.send_request(formData, callback);
    },
    makeMigrateForm : function() {
        var mod = M.mod_coursereadings.dndupload;
        var detailForm = mod.makeDetailForm(null, true);
        var uploadid = detailForm[0];
        /*var content = '<div id="coursereadings_article_upload_'+uploadid+'"><p>'+M.util.get_string('articleuploadintro', 'mod_coursereadings')+'</p>';
        content += '<div id="article_upload_filename_'+uploadid+'" class="article_upload_filename" style="display:none;"></div>';
        content += '<button id="article_upload_choosefile_'+uploadid+'" type="button">'+M.util.get_string('openpicker', 'repository')+'</button>';
        content += '<div id="article_upload_filepicker_'+uploadid+'" class="article_upload_drop_target filepicker-filelist" style="display:none;" data-uploadid="'+uploadid+'">\
                    <div class="filepicker-filename">\
                        <div class="filepicker-container"><div class="dndupload-message">'+M.util.get_string('dndenabled_inbox', 'moodle')+' <br>\
                        <div class="dndupload-arrow"></div></div></div>\
                    </div>\
                    <div><div class="dndupload-target">Drop files here to upload<br><div class="dndupload-arrow"></div></div></div>\
                    </div>';
        content += '<div id="article_upload_details_'+uploadid+'" data-fieldname="'+fieldName+'" style="display:none;">'+detailForm[1]+'</div>';
        content += '</div>';*/
        return [uploadid, detailForm[1]];
    },
    launchMigrate : function(el) {
        var uploadForm = this.makeMigrateForm();
        var uploadid = uploadForm[0], content = uploadForm[1];

        var self = this;
        var container = Y.one('.coursereadings_content_migration_form');
        container.one('.instance_meta').setHTML(el.cloneNode(true));
        container.one('.instance_form').setHTML(content);
        container.setStyle('display', 'block');
        Y.one('.coursereadings_content_migration_list').setStyle('display', 'none');

        M.mod_coursereadings.dndupload.attachSourceAutocomplete(uploadid, M.mod_coursereadings.dashboard.sourceFormatter);
        M.mod_coursereadings.dndupload.attachRequiredMonitor(uploadid);

        if (el.getData('articleid') > 0) {
            // File's contenthash matches an article already in the system!
            var formData = new FormData();
            formData.append('t', 'article');
            formData.append('q', el.getData('articleid'));

            var callback = function(result) {
                result.author = result.author || result.sourceauthor || result.editor || 'Unknown';
                var article = Y.Lang.sub(self.articleTemplate, result);
                Y.one('.coursereadings_content_migration_form .instance_filematch .coursereadings-article').setHTML(article);
            }

            self.send_request(formData, callback);

            container.one('.instance_filematch').setStyle('display', 'block');
            container.one('.instance_filematch .coursereadings-article').addClass('loading');
        }

        return false;
    },
    saveArticle : function() {
        var self = this;
        var form = Y.one('.coursereadings_content_migration_form .instance_form form');
        var el = Y.one('.coursereadings_content_migration_form .instance_meta > div');
        var formData = new FormData();
        formData.append('t', 'addarticle');
        formData.append('fileid', el.getData('fileid'));
        formData.append('author_of_periodical', form.get('dndupload_handler_periodicalAuthor').get('value'));
        formData.append('title_of_article', form.get('dndupload_handler_title').get('value'));
        formData.append('page_range', form.get('dndupload_handler_page_range').get('value'));
        formData.append('total_pages', form.get('dndupload_handler_total_pages').get('value'));
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

        var callback = function(result) {
            var el = Y.one('.coursereadings_content_migration_form .instance_meta > div');
            self.migrateArticle(el.getData('instanceid'), result.articleid);
        }

        this.send_request(formData, callback);
    },
    migrateArticle : function(resourceid, articleid) {
        var self = this;
        var formData = new FormData();
        formData.append('t', 'migrate');
        formData.append('r', resourceid);
        formData.append('q', articleid);

        var callback = function(result) {
            Y.one('.coursereadings_content_migration_form').setStyle('display', 'none');
            Y.one('.coursereadings_content_migration_list').setStyle('display', 'block');
            el = Y.one('.coursereadings_content_migration_list #coursereadings_resource_instance_'+self.activeinstance);
        }

        self.send_request(formData, callback);
    },
    send_request : function(formData, callback) {
        var xhr = new XMLHttpRequest();
        var self = this;

        xhr.onreadystatechange = function() {
            if (xhr.readyState == 4) {
                if (xhr.status == 200) {
                    var result = JSON.parse(xhr.responseText);
                    if (result) {
                        if (result.error == 0) {
                            callback(result);
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

Y.extend(ContentMigration, Y.Base, ContentMigration.prototype, {
    NAME : 'Copyright Materials content migration UI',
    ATTRS : {
        // No attributes at present
    }
});

M.mod_coursereadings = M.mod_coursereadings || {};
M.mod_coursereadings.contentmigration = M.mod_coursereadings.contentmigration || {};
M.mod_coursereadings.contentmigration.init = function(cfg) {
    return new ContentMigration(cfg);
}

}, '@VERSION@', {requires:['base','node','moodle-mod_coursereadings-dndupload','moodle-mod_coursereadings-dashboard','panel']});