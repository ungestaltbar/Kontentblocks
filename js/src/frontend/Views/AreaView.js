//KB.Backbone.AreaView
var AreaLayout = require('frontend/Views/AreaLayout');
var ModuleBrowser = require('frontend/ModuleBrowser/ModuleBrowserExt');
var Config = require('common/Config');
var Notice = require('common/Notice');
var Ajax = require('common/Ajax');
var tplPlaceholder = require('templates/frontend/area-empty-placeholder.hbs');
module.exports = Backbone.View.extend({
  isSorting: false,
  events: {
    'click .kb-area__empty-placeholder': 'openModuleBrowser'
  },
  initialize: function () {
    this.attachedModuleViews = {};
    this.renderSettings = this.model.get('renderSettings');
    this.listenTo(KB.Events, 'editcontrols.show', this.showPlaceholder);
    this.listenTo(KB.Events, 'editcontrols.hide', this.removePlaceholder);
    this.listenToOnce(KB.Events, 'frontend.init', this.setupUi);
    this.listenTo(this, 'kb.module.deleted', this.removeModule);
    this.model.View = this;

  },
  showPlaceholder: function () {
    if (_.size(this.attachedModuleViews) === 0) {
      this.$el.append(tplPlaceholder());
    }
  },
  removePlaceholder: function () {
    this.$('.kb-area__empty-placeholder').remove();
  },
  setupUi: function () {
    this.Layout = new AreaLayout({
      model: new Backbone.Model(this.renderSettings),
      AreaView: this
    });

    // Sortable
    if (this.model.get('sortable')) {
      this.setupSortables();
    }
  },
  openModuleBrowser: function () {
    if (!this.ModuleBrowser) {
      this.ModuleBrowser = new ModuleBrowser({
        area: this
      });
    }
    this.ModuleBrowser.render();
    return this.ModuleBrowser;
  },
  attachModuleView: function (moduleModel) {
    this.attachedModuleViews[moduleModel.get('mid')] = moduleModel; // add module
    this.listenTo(moduleModel, 'change:area', this.removeModule); // add listener

    if (this.getNumberOfModules() > 0) {
      this.removePlaceholder();
      this.$el.removeClass('kb-area__empty');
    }
    this.trigger('kb.module.created', moduleModel);
    moduleModel.trigger('module.created');
  },

  getNumberOfModules: function () {
    return _.size(this.attachedModuleViews);
  },
  getAttachedModules: function () {
    return this.attachedModuleViews;
  },
  setupSortables: function () {
    var that = this;
    if (this.Layout.hasLayout) {
      this.$el.sortable(
        {
          handle: ".kb-module-control--move",
          items: ".kb-wrap",
          helper: "clone",
          opacity: 0.5,
          forcePlaceholderSize: true,
          delay: 150,
          placeholder: "kb-front-sortable-placeholder",
          start: function (e, ui) {
            //ui.placeholder.width('100%');
            that.isSorting = true;

            if (ui.helper.hasClass('ui-draggable-dragging')) {
              ui.helper.addClass('kb-wrap');
            }
            ui.placeholder.attr('class', ui.helper.attr('class'));
            ui.placeholder.addClass('kb-front-sortable-placeholder');
            ui.placeholder.append("<div class='module kb-dummy'></div>");
            jQuery('.module', ui.helper).addClass('ignore');
            ui.helper.addClass('ignore');
            that.Layout.applyClasses();
            that.Layout.render(ui);
          },
          receive: function (e, ui) {
            // model is set in the sidebar areaList single module item
            var module = ui.item.data('module');
            // callback is handled by that view object
            that.isSorting = false;
            module.create(ui);
          },
          beforeStop: function (e, ui) {
            that.Layout.applyClasses();
            jQuery('.ignore', ui.helper).removeClass('ignore');
          },
          stop: function (e, ui) {
            var serializedData = {};
            that.isSorting = false;
            serializedData[that.model.get('id')] = that.$el.sortable('serialize', {
              attribute: 'rel'
            });
            return Ajax.send({
              action: 'resortModules',
              data: serializedData,
              _ajax_nonce: Config.getNonce('update')
            }, function () {
              Notice.notice('Order was updated successfully', 'success');
              that.Layout.render(ui);
            }, that);
          },
          change: function (e, ui) {
            that.Layout.applyClasses();
            that.Layout.render(ui);
          },
          over: function (ui) {
            that.Layout.applyClasses();
            that.Layout.render(ui);
          }
        });
    } else {
      this.$el.sortable(
        {
          handle: ".kb-module-control--move",
          items: ".module",
          helper: "clone",
          //helper: function(){
          //  //return jQuery('.kb-sidebar-drop-helper');
          //},
          //out:function(e, ui){
          //    ui.helper[0].detach();
          //},
          //appendTo: document.body,
          //opacity: 0.5,
          cursorAt: {
            top: 5,
            left: 5
          },
          ////axis: "y",
          delay: 150,
          forceHelperSize: true,
          forcePlaceholderSize: true,
          placeholder: "kb-front-sortable-placeholder",
          start: function () {
            that.isSorting = true;
          },
          receive: function (e, ui) {
            // model is set in the sidebar areaList single module item
            var module = ui.item.data('module');
            // callback is handled by that view object
            that.isSorting = false;
            module.create(ui);
          },
          stop: function () {
            if (that.isSorting) {
              that.isSorting = false;
              that.resort(that.model)
            }
          },
          change: function () {
            that.Layout.applyClasses();
          }
        });
    }
  },
  changeLayout: function (l) {
    this.Layout.model.set('layout', l);
    this.$el.sortable('destroy');
    this.setupSortables();
  }
  ,
  removeModule: function (ModuleView) {
    var id = ModuleView.model.get('mid');
    if (this.attachedModuleViews[id]) {
      delete this.attachedModuleViews[id];
    }
    if (this.getNumberOfModules() < 1) {
      this.$el.addClass('kb-area__empty');
      this.showPlaceholder();
    }
  },
  resort: function (area) {
    var serializedData = {};
    serializedData[area.get('id')] = area.View.$el.sortable('serialize', {
      attribute: 'rel'
    });

    return Ajax.send({
      action: 'resortModules',
      postId: area.get('envVars').postId,
      data: serializedData,
      _ajax_nonce: Config.getNonce('update')
    }, function () {
      Notice.notice('Order was updated successfully', 'success');
      area.trigger('area.resorted');
    }, null);
  }

});