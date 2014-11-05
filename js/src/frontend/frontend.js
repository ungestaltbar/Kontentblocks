KB.currentModule = {};
KB.currentArea = {};

// ---------------
// Collections
// ---------------

/*
 * Views, not a Backbone collection
 * simple getter/setter access point to views
 */
KB.Views = {
    Modules: new KB.ViewsCollection(),
    Areas: new KB.ViewsCollection(),
    Context: new KB.ViewsCollection()
};

/*
 * All Modules are collected here
 * Get by 'instance_id'
 */
KB.Modules = new Backbone.Collection([], {
    model: KB.Backbone.ModuleModel
});

/*
 *  All Areas are collected in this Backbone Collection
 *  Get by 'id'
 */
KB.Areas = new Backbone.Collection([], {
    model: KB.Backbone.AreaModel
});

/*
 * Init function
 * Register event listeners
 * Create Views for areas and modules
 * None of this functions is meant to be used directly
 * from outside the function itself.
 * Use events on the backbone items instead
 * handle UI specific actions
 */
KB.App = function ($) {

    function init() {
        // create toolbar container for tinymce inline editors
        var $toolbar = jQuery('<div id="kb-toolbar"></div>').appendTo('body');
        $toolbar.hide();
        if (KB.appData.config.useModuleNav) {
            KB.ModuleNav = new KB.Backbone.ModuleNavView();
        }

        // Register events on collections
        KB.Modules.on('add', createModuleViews);
        KB.Areas.on('add', createAreaViews);
        KB.Modules.on('remove', removeModule);

        // Create views
        addViews();

        // get the UI on track
        KB.Ui.init();

    }

    function shutdown() {
        var model;

        _.each(KB.Modules.toArray(), function (item) {
            KB.Modules.remove(item);
        });

        jQuery('.editable').each(function (i, el) {
            tinymce.remove('#' + el.id);
        });

        jQuery('body').off('click', '.editable-image');
        jQuery('body').off('click', '.editable-link');
    }

    /**
     * Iterate through raw areas as they were
     * output by toJSON() method on each area upon
     * server side page creation
     *
     * Modules are taken from the raw areas and
     * collected seperatly in their own collection
     *
     * View generation is handled by the 'add' event callback
     * as registered above
     * @returns void
     */
    function addViews() {

        if (KB.appData.config.preview) {
            return false;
        }

        // iterate over raw areas
        _.each(KB.payload.Areas, function (area) {
            // create new area model
            KB.Areas.add(new KB.Backbone.AreaModel(area));
        });

        // create models from already attached modules
        _.each(KB.payload.Modules, function (module) {
            KB.Modules.add(module);
        });
        // @TODO events:refactor
        KB.trigger('kb:moduleControlsAdded');

        // new event
        KB.Events.trigger('KB::frontend-init');
    }


    /**
     * Create views for modules and add them
     * to the custom collection
     * @param module Backbone Model
     * @returns void
     */
    function createModuleViews(module) {

        // assign the full corresponding area model to the module model
        module.setArea(KB.Areas.get(module.get('area')));
        module.bind('change:area', module.areaChanged);

        // create view
        KB.Views.Modules.add(module.get('instance_id'), new KB.Backbone.ModuleView({
            model: module,
            el: '#' + module.get('instance_id')
        }));

        // re-init tabs
        // TODO: don't re-init globally
        KB.Ui.initTabs();
    }


    /**
     *
     * @param area Backbone Model
     * @returns void
     */
    function createAreaViews(area) {
        KB.Views.Areas.add(area.get('id'), new KB.Backbone.AreaView({
            model: area,
            el: '#' + area.get('id')
        }));

    }

    /**
     * Removes a view from the collection.
     * The collection will destroy corresponding views
     * @param model Backbone Model
     * @returns void
     */
    function removeModule(model) {
        KB.Views.Modules.remove(model.get('instance_id'));
    }


    // revealing module pattern
    return {
        init: init,
        shutdown: shutdown
    };

}(jQuery);

// get started
KB.App.init();


jQuery(document).ready(function () {

    if (KB.appData && KB.appData.config.frontend) {
        _K.info('Frontend Modules Ready Event fired');
        KB.Views.Modules.readyOnFront();
    }

    KB.Events.trigger('KB::ready');

    jQuery(window)
        .on('resize DOMNodeInserted', function () {
//            jQuery( '.mce-text' ).removeAttr( 'style' );
        });

    // force user cookie to tinymce
    setUserSetting('editor', 'tinymce');


});
