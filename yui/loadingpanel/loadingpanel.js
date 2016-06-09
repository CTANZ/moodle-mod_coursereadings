YUI.add('moodle-mod_coursereadings-loadingpanel', function(Y) {

/**
 * Loading panel - modal box which displays a loading spinner during processing.
 */
var CourseReadingsLoadingPanel = function() {
    CourseReadingsLoadingPanel.superclass.constructor.apply(this, arguments);
};
CourseReadingsLoadingPanel.prototype = {
    initializer : function(config) {
        // Nothing to do here.
    },
    getLoadingPanel : function() {
        return new Y.Panel({
            bodyContent: '<div style="text-align:center;"><h3>'+M.util.get_string('loading', 'mod_coursereadings')+'</h3><img src="'+M.util.image_url('i/loading')+'"></div>',
            width: 280,
            zIndex: 100,
            centered: true,
            modal: true,
            visible: false,
            render: true,
            buttons: [], // No buttons required or desired.
            hideOn: [] // Disable all default ways to close/hide the panel.
        });
    },
    /**
     * Displays modal loading panel.  Optionally removes modal attribute from another
     * Panel, if necessary to avoid stacking issues.
     * @param {Y.Panel} otherpanel Panel to remove modal attribute from.
     */
    show: function(otherpanel) {
        if (otherpanel) {
            otherpanel.set('modal', false);
        }
        M.mod_coursereadings.loadingpanel.loadingpanel.show();
    },
    /**
     * Hides modal loading panel.  Optionally reinstates modal attribute from another
     * Panel, if necessary to avoid stacking issues.
     * @param {Y.Panel} otherpanel Panel to reinstate modal attribute on.
     */
    hide: function(otherpanel) {
        if (otherpanel) {
            otherpanel.set('modal', true);
        }
        M.mod_coursereadings.loadingpanel.loadingpanel.hide();
    }
};

Y.extend(CourseReadingsLoadingPanel, Y.Base, CourseReadingsLoadingPanel.prototype, {
    NAME : 'Copyright Materials loading panel',
    ATTRS : {
        // No attributes at present
    }
});

M.mod_coursereadings = M.mod_coursereadings || {};
M.mod_coursereadings.loadingpanel = M.mod_coursereadings.loadingpanel || {};
M.mod_coursereadings.loadingpanel.loadingpanel = CourseReadingsLoadingPanel.prototype.getLoadingPanel();
M.mod_coursereadings.loadingpanel.show = CourseReadingsLoadingPanel.prototype.show;
M.mod_coursereadings.loadingpanel.hide = CourseReadingsLoadingPanel.prototype.hide;
M.mod_coursereadings.loadingpanel.init = function(cfg) {
    return new CourseReadingsLoadingPanel(cfg);
}

}, '@VERSION@', {requires:['base', 'node', 'panel']});