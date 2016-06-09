YUI.add('moodle-mod_coursereadings-editarticle', function(Y) {

/**
 * Dashboard interface - edit article.
 */
var EditArticle = function() {
    EditArticle.superclass.constructor.apply(this, arguments);
};
EditArticle.prototype = {
    initializer : function(config) {
        var self = this;
        var el = Y.one('#id_sourcedisplay');
        el.plug(Y.Plugin.AutoComplete, {
          source: M.cfg.wwwroot + '/mod/coursereadings/ajaxsearch.php?t=source&q={query}',
          resultTextLocator: function(result) {return result.title + ' (' + result.year + ')';},
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
            Y.one('#mform1').get('source').set('value', '');
            Y.one('#id_submitbutton').set('disabled', true);
        });
    },
    sourceFormatter : function (query, results) {
        return Y.Array.map(results, function (result) {
            var open = (result.raw.queueid == null) ? '<div class="source-approved">' : '';
            var close = (open.length) ? '</div>' : '';
            return open + '<em>' + result.raw.title + '</em><br>' + result.raw.author + ' (' + result.raw.year + ')' + close;
        });
    },
    selectSource : function (evt) {
        var form = evt.target.get('inputNode').get('form');
        var source = evt.result.raw;

        Y.one('#mform1').get('source').set('value', source.id);
        Y.one('#id_sourcedisplay').set('disabled', true);
        Y.one('#id_submitbutton').set('disabled', false);
    }
};

Y.extend(EditArticle, Y.Base, EditArticle.prototype, {
    NAME : 'Copyright Materials dashboard UI - article editor',
    ATTRS : {
        // No attributes at present
    }
});

M.mod_coursereadings = M.mod_coursereadings || {};
M.mod_coursereadings.editarticle = M.mod_coursereadings.editarticle || {};
M.mod_coursereadings.editarticle.init = function(cfg) {
    return new EditArticle(cfg);
}

}, '@VERSION@', {requires:['base','node','autocomplete']});