YUI.add('moodle-mod_coursereadings-pickfile', function(Y) {

/**
 * Dashboard interface - pick which file to use when merging articles.
 */
var PickFile = function() {
    PickFile.superclass.constructor.apply(this, arguments);
};
PickFile.prototype = {
    initializer : function(config) {
        var self = this;
        var wrap = Y.one('#fgroup_id_keepfilegrp');
        wrap.delegate('click', function(e) {
            e.stopPropagation();
            e.preventDefault();
            self.handle_button(e.currentTarget);
        }, 'button', self);
        wrap.all('button').each(function(btn) {
            if (btn.ancestor('.keepfile').hasClass('keepfile-selected')) {
                // Can't re-select current file.
                btn.set('disabled', true);
            } else {
                // Enable button now that we've attached the (delegated) handler.
                btn.set('disabled', false);
            }
        }, self);
    },
    handle_button : function(button) {
        var file = button.ancestor('.keepfile');
        var keep = button.get('form').get('keepfile');
        var type = file.hasClass('keepfile-canonical') ? 'canonical' : 'duplicate';
        keep.set('value', type);
        file.siblings('.keepfile').each(function (el) {
            el.removeClass('keepfile-selected');
            el.one('button').set('disabled', false);
        });
        file.addClass('keepfile-selected');
        button.set('disabled', true);
    }
};

Y.extend(PickFile, Y.Base, PickFile.prototype, {
    NAME : 'Copyright Materials dashboard UI - article merge',
    ATTRS : {
        // No attributes at present
    }
});

M.mod_coursereadings = M.mod_coursereadings || {};
M.mod_coursereadings.pickfile = M.mod_coursereadings.pickfile || {};
M.mod_coursereadings.pickfile.init = function(cfg) {
    return new PickFile(cfg);
}

}, '@VERSION@', {requires:['base','node']});