YUI.add('moodle-mod_coursereadings-mergesource', function(Y) {

/**
 * Dashboard interface - merge source.
 */
var MergeSource = function() {
    MergeSource.superclass.constructor.apply(this, arguments);
};
MergeSource.prototype = {
    sourceTemplate : '<div class="coursereadings-article"><em><a href="'+M.cfg.wwwroot+'/mod/coursereadings/manage/merge-source.php?id={dupeid}&return={returnto}&target={id}">{title}</a></em><br>{author} ({year})</div>',

    addSource : function(source) {
        source.dupeid = Y.one('#mform1').get('id').get('value');
        source.returnto = Y.one('#mform1').get('return').get('value');
        return Y.Lang.sub(this.sourceTemplate, source);
    },
    getExtraSearchParams : function() {
        var id = Y.one('#mform1').get('id').get('value');
        return '&x='+id;
    }
};

Y.extend(MergeSource, M.mod_coursereadings.findsource.module, MergeSource.prototype, {
    NAME : 'Copyright Materials dashboard UI - source merge',
    ATTRS : {
        // No attributes at present
    }
});

M.mod_coursereadings = M.mod_coursereadings || {};
M.mod_coursereadings.mergesource = M.mod_coursereadings.mergesource || {};
M.mod_coursereadings.mergesource.init = function(cfg) {
    return new MergeSource(cfg);
}

}, '@VERSION@', {requires:['base','node','autocomplete','moodle-mod_coursereadings-dndupload','moodle-mod_coursereadings-dashboard','moodle-mod_coursereadings-findsource']});