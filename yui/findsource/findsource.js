YUI.add('moodle-mod_coursereadings-findsource', function(Y) {

/**
 * Dashboard interface - edit article.
 */
var FindSource = function() {
    FindSource.superclass.constructor.apply(this, arguments);
};
FindSource.prototype = {
    title : null,
    list : null,
    source : null,
    isbn : null,
    noresults : null,
    sourceTemplate : '<div class="coursereadings-article"><em><a href="'+M.cfg.wwwroot+'/mod/coursereadings/manage/edit-source.php?id={id}">{title}</a></em><br>{author} ({year})</div>',
    initializer : function(config) {
        var self = this;
        self.list = Y.one('#article_search_results');
        self.title = Y.one('#id_sourcetitle');
        self.isbn = Y.one('#id_isbn');
        self.noresults = Y.one('#article_search_noresults');
        self.title.plug(Y.Plugin.AutoComplete, {
          source: M.cfg.wwwroot + '/mod/coursereadings/ajaxsearch.php?t=source&q={query}'+self.getExtraSearchParams(),
          resultTextLocator: function(result) {return result.title + ' (' + result.year + ')';},
          resultFormatter: M.mod_coursereadings.dashboard.sourceFormatter,
          on: {
            select: function(evt) {
                var source = evt.result.raw;
                var result = [source];
                self.addSearchResults(result);
            },
            query: function(){
                self.title.addClass('loading');
            },
            results: function(evt) {
                self.checkForResults(evt.results);
                self.title.removeClass('loading');
            }
          }
        });
        self.title.on('focus', function() {
            self.isbn.set('value', '');
        })

        self.isbn.plug(Y.Plugin.AutoComplete, {
          source: M.cfg.wwwroot + '/mod/coursereadings/ajaxsearch.php?t=isbn&q={query}'+self.getExtraSearchParams(),
          resultTextLocator: function(result) {return result.title + ' (' + result.year + ')';},
          resultFormatter: M.mod_coursereadings.dashboard.sourceFormatter,
          on: {
            select: function(evt) {
                var source = evt.result.raw;
                var result = [source];
                self.addSearchResults(result);
            },
            results: function(evt) {self.checkForResults(evt.results);}
          }
        });
        self.isbn.on('focus', function() {
            self.title.set('value', '');
        })

        self.title.focus();
        return false;
    },
    getExtraSearchParams : function() {
        return '';
    },
    checkForResults : function(results) {
        this.noresults.setStyle('display', (results.length>0)?'none':'');
        this.list.setStyle('display', (results.length>0)?'':'none');
        this.list.empty();
    },
    addSearchResults : function(sources) {
        this.list.empty();
        for (var i in sources) {
            this.list.append(this.addSource(sources[i]));
        }
    },
    addSource : function(source) {
        return Y.Lang.sub(this.sourceTemplate, source);
    }
};

Y.extend(FindSource, Y.Base, FindSource.prototype, {
    NAME : 'Copyright Materials dashboard UI - source search',
    ATTRS : {
        // No attributes at present
    }
});

M.mod_coursereadings = M.mod_coursereadings || {};
M.mod_coursereadings.findsource = M.mod_coursereadings.findsource || {};
M.mod_coursereadings.findsource.init = function(cfg) {
    return new FindSource(cfg);
}
M.mod_coursereadings.findsource.module = FindSource;

}, '@VERSION@', {requires:['base','node','autocomplete','moodle-mod_coursereadings-dndupload','moodle-mod_coursereadings-dashboard']});