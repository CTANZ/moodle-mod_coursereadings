YUI.add('moodle-mod_coursereadings-daysselect', function(Y) {

/**
 * Select to change number of days in report.
 */
var DaysSelect = function() {
    DaysSelect.superclass.constructor.apply(this, arguments);
};
DaysSelect.prototype = {
    initializer : function(config) {
        var self = this;
        var el = Y.one('#coursereadings_newfiles_days');
        el.on('change', function() {
            var flagged = /flagged=1/.test(window.location.href) ? 'flagged=1&' : '';
            window.location.href = M.cfg.wwwroot+'/mod/coursereadings/manage/migrate-new-files.php?'+flagged+'days='+el.get('value');
        }, self);
    }
};

Y.extend(DaysSelect, Y.Base, DaysSelect.prototype, {
    NAME : 'Copyright Materials content migration "days" selector',
    ATTRS : {
        // No attributes at present
    }
});

M.mod_coursereadings = M.mod_coursereadings || {};
M.mod_coursereadings.daysselect = M.mod_coursereadings.daysselect || {};
M.mod_coursereadings.daysselect.init = function(cfg) {
    return new DaysSelect(cfg);
}

}, '@VERSION@', {requires:['base','node']});