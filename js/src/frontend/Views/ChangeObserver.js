var Notice = require('common/Notice');
var tplChangeObserver = require('templates/frontend/change-observer.hbs');
module.exports = Backbone.View.extend({
  models: new Backbone.Collection(),
  className: 'kb-change-observer',
  initialize: function () {
    this.listenTo(KB.Modules, 'add', this.attachHandler);
    this.render();
  },
  events:{
      'click .kb-button' : 'saveAll'
  },
  render: function () {
    this.$el.append(tplChangeObserver({}));
    this.$el.appendTo('body');
  },
  attachHandler: function (model) {
    this.listenTo(model, 'change:moduleData', this.add);
    this.listenTo(model, 'module.model.updated', this.remove);
  },
  add: function (model) {
    this.models.add(model);
    this.handleState();
  },
  remove: function (model) {
    this.models.remove(model, {silent:true});
    this.handleState();
  },
  getModels: function () {
    return this.models;
  },
  saveAll: function () {
    _.each(this.models.models, function (model) {
      model.sync(true);
    })
  },
  handleState: function () {

    var l = this.models.models.length;

    if ( l > 0){
      this.$el.addClass('show');
    } else {
      this.$el.removeClass('show');
      Notice.notice('all saved', 'success');
    }


  }


});