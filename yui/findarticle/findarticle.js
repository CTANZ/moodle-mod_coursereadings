YUI.add('moodle-mod_coursereadings-findarticle', function(Y) {

/**
 * Dashboard interface - edit article.
 */
var FindArticle = function() {
    FindArticle.superclass.constructor.apply(this, arguments);
};
FindArticle.prototype = {
    title : null,
    list : null,
    source : null,
    isbn : null,
    noresults : null,
    noarticles : null,
    articleTemplate : '<div class="coursereadings-article"><strong><a href="'+M.cfg.wwwroot+'/mod/coursereadings/manage/edit-article.php?id={id}">{title}</a></strong><br>{author} ({year})<br><em>{sourcetitle}</em></div>',
    initializer : function(config) {
        var self = this;
        var articlehandlers = {
            select: function(evt) {
                var article = evt.result.raw;
                var result = [{
                    id: article.articleid,
                    periodicalauthor: article.author,
                    author: article.sourceauthor,
                    editor: article.editor,
                    year: article.year,
                    sourcetitle: article.title,
                    title: article.articletitle
                }];
                self.addSearchResults(result);
            },
            query: function(){
                self.title.addClass('loading');
            },
            results: function(evt) {
                self.checkForResults(evt.results);
                self.title.removeClass('loading');
            }
        };
        self.title = Y.one('#id_title');
        self.external = Y.one('#id_external');
        self.list = Y.one('#article_search_results');
        self.source = Y.one('#id_sourcetitle');
        self.isbn = Y.one('#id_isbn');
        self.noresults = Y.one('#article_search_noresults');
        self.noarticles = Y.one('#article_search_noarticles');

        self.title.plug(Y.Plugin.AutoComplete, {
          source: M.cfg.wwwroot + '/mod/coursereadings/ajaxsearch.php?t=article&q={query}'+self.getExtraSearchParams(),
          resultTextLocator: function(result) {return result.articletitle + ' (' + result.title + ', ' + result.year + ')';},
          resultFormatter: M.mod_coursereadings.dndupload.articleFormatter,
          on: articlehandlers
        });
        self.title.on('focus', function() {
            self.external.set('value', '');
            self.source.set('value', '');
            self.isbn.set('value', '');
        })

        self.external.plug(Y.Plugin.AutoComplete, {
          source: M.cfg.wwwroot + '/mod/coursereadings/ajaxsearch.php?t=articledoi&q={query}'+self.getExtraSearchParams(),
          resultTextLocator: function(result) {return result.articletitle + ' (' + result.title + ', ' + result.year + ')';},
          resultFormatter: M.mod_coursereadings.dndupload.articleFormatter,
          on: articlehandlers
        });
        self.external.on('focus', function() {
            self.title.set('value', '');
            self.source.set('value', '');
            self.isbn.set('value', '');
        })

        self.source.plug(Y.Plugin.AutoComplete, {
          source: M.cfg.wwwroot + '/mod/coursereadings/ajaxsearch.php?t=source&q={query}',
          resultTextLocator: function(result) {return result.title + ' (' + result.year + ')';},
          resultFormatter: M.mod_coursereadings.dashboard.sourceFormatter,
          on: {
            select: function(evt) {self.getSourceArticles(evt);},
            results: function(evt) {self.checkForResults(evt.results);}
          }
        });
        self.source.on('focus', function() {
            self.title.set('value', '');
            self.external.set('value', '');
            self.isbn.set('value', '');
        })

        self.isbn.plug(Y.Plugin.AutoComplete, {
          source: M.cfg.wwwroot + '/mod/coursereadings/ajaxsearch.php?t=isbn&q={query}',
          resultTextLocator: function(result) {return result.title + ' (' + result.year + ')';},
          resultFormatter: M.mod_coursereadings.dashboard.sourceFormatter,
          on: {
            select: function(evt) {self.getSourceArticles(evt);},
            results: function(evt) {self.checkForResults(evt.results);}
          }
        });
        self.isbn.on('focus', function() {
            self.title.set('value', '');
            self.external.set('value', '');
            self.source.set('value', '');
        })

        // Focus on the 'name' box.
        Y.one('#id_title').focus();
        return false;
    },
    checkForResults : function(results, fromSource) {
        this.noresults.setStyle('display', (fromSource || results.length > 0)?'none':'');
        this.noarticles.setStyle('display', (fromSource && results.length == 0)?'':'none');
        this.list.setStyle('display', (results.length > 0)?'':'none');
        this.list.empty();
    },
    addSearchResults : function(articles) {
        this.list.empty();
        for (var i in articles) {
            this.list.append(this.addArticle(articles[i]));
        }
    },
    addArticle : function(article) {
        article.author = article.periodicalauthor || article.author || article.editor || 'Unknown';
        return Y.Lang.sub(this.articleTemplate, article);
    },
    getSourceArticles : function(evt) {
        var self = this;
        var id = evt.result.raw.id;

        var uri = M.cfg.wwwroot + '/mod/coursereadings/ajaxsearch.php?t=sourcearticles&q='+id+self.getExtraSearchParams();

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
        this.checkForResults(articles, true);
        this.addSearchResults(articles);
    },
    getExtraSearchParams : function() {
        return '';
    }
};

Y.extend(FindArticle, Y.Base, FindArticle.prototype, {
    NAME : 'Copyright Materials dashboard UI - article search',
    ATTRS : {
        // No attributes at present
    }
});

M.mod_coursereadings = M.mod_coursereadings || {};
M.mod_coursereadings.findarticle = M.mod_coursereadings.findarticle || {};
M.mod_coursereadings.findarticle.init = function(cfg) {
    return new FindArticle(cfg);
}
M.mod_coursereadings.findarticle.module = FindArticle;

}, '@VERSION@', {requires:['base','node','autocomplete','moodle-mod_coursereadings-dndupload','moodle-mod_coursereadings-dashboard']});