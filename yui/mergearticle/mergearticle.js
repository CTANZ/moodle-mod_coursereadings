YUI.add('moodle-mod_coursereadings-mergearticle', function(Y) {

/**
 * Dashboard interface - merge article.
 */
var MergeArticle = function() {
    MergeArticle.superclass.constructor.apply(this, arguments);
};
MergeArticle.prototype = {
    articleTemplate : '<div class="coursereadings-article"><strong><a href="'+M.cfg.wwwroot+'/mod/coursereadings/manage/merge-article.php?id={dupeid}&return={returnto}&target={id}">{title}</a></strong><br>{author} ({year})<br><em>{sourcetitle}</em></div>',

    addArticle : function(article) {
        article.dupeid = Y.one('#mform1').get('id').get('value');
        article.returnto = Y.one('#mform1').get('return').get('value');
        article.author = article.periodicalauthor || article.author || article.editor || 'Unknown';
        return Y.Lang.sub(this.articleTemplate, article);
    },
    getExtraSearchParams : function() {
        var id = Y.one('#mform1').get('id').get('value');
        return '&x='+id;
    }
};

Y.extend(MergeArticle, M.mod_coursereadings.findarticle.module, MergeArticle.prototype, {
    NAME : 'Copyright Materials dashboard UI - article merge',
    ATTRS : {
        // No attributes at present
    }
});

M.mod_coursereadings = M.mod_coursereadings || {};
M.mod_coursereadings.mergearticle = M.mod_coursereadings.mergearticle || {};
M.mod_coursereadings.mergearticle.init = function(cfg) {
    return new MergeArticle(cfg);
}

}, '@VERSION@', {requires:['base','node','autocomplete','moodle-mod_coursereadings-dndupload','moodle-mod_coursereadings-dashboard','moodle-mod_coursereadings-findarticle']});