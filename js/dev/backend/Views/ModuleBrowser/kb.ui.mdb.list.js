KB.Backbone.ModuleBrowserListItem = Backbone.View.extend({
    tagName: 'li',
    className: 'modules-list-item',
    initialize: function () {
        // listen to browser close event
//        this.options.parent.options.browser.on('browser:close', this.close, this);
    },
    // render list
    render: function (el) {
        if (this.model.get('tpldef')) {
            console.log(this.model.get('tpldef'));
            this.$el.html(KB.Templates.render('backend/modulebrowser/module-template-list-item', {module: this.model.toJSON()}));
        } else {
            this.$el.html(KB.Templates.render('backend/modulebrowser/module-list-item', {module: this.model.toJSON()}));
        }
        el.append(this.$el);
    },
    events: {
        'click': 'loadDetails',
        'click .kb-js-create-module': 'createModule'
    },
    loadDetails: function () {
        this.options.parent.trigger('loadDetails', this.model);
    },
    createModule: function () {
        this.options.parent.trigger('createModule', this.model);

    },
    close: function () {
        this.remove();
//        delete this.$el;
//        delete this.el;
    }
});

KB.Backbone.ModuleBrowserModulesList = Backbone.View.extend({
    modules: {},
    subviews: {},
    // set modules to render
    setModules: function (modules) {
        this.modules = modules;
        return this;
    },
    // render current modules to list
    update: function () {
        var that = this;
        // flag the first
        var first = false;
        this.$el.empty();
        _.each(this.modules, function (module) {
               that.subviews[module.cid] = new KB.Backbone.ModuleBrowserListItem({model: module, parent: that});

//            if (!that.subviews[module.cid]) {
//                console.log('create new view li');
//                that.subviews[module.cid] = new KB.Backbone.ModuleBrowserListItem({model: module, parent: that});
//            }
            if (first === false) {
                that.options.browser.loadDetails(module);
                first = !first;
            }
            that.$el.append(that.subviews[module.cid].render(that.$el));
        });
    }
});