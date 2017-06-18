//KB.Backbone.Backend.ModuleStatus
var BaseView = require('backend/Views/BaseControlView');
var Checks = require('common/Checks');
module.exports = BaseView.extend({
  id: 'toggle',
  initialize: function (options) {
    this.options = options || {};
    this.parent = options.parent;
    this.listenTo(this.parent, 'toggle.open', this.toggleBody)
    if (store.get(this.parent.model.get('mid') + '_open')) {
      this.toggleBody();
      this.parent.open = true;
    } else {
      if (!this.parent.model.get('globalModule')){
        this.parent.open = false;
      }
    }
  },
  events: {
    'click': 'toggleBody'
  },
  className: 'ui-toggle kb-toggle block-menu-icon',
  isValid: function () {
    if (!this.model.get('settings').disabled && !this.model.get('submodule') &&
      Checks.userCan('edit_kontentblocks')) {
      return true;
    } else {
      return false;
    }
  },
  // show/hide handler
  toggleBody: function (speed) {
    var duration = speed || 400;
    if (Checks.userCan('edit_kontentblocks')) {
      this.parent.$body.slideToggle(duration);
      this.parent.$el.toggleClass('kb-open');
      // set current module to prime object property
      KB.currentModule = this.model;
      this.setOpenStatus();
    }
  },
  setOpenStatus: function () {
    this.parent.open =  !this.parent.open;
    store.set(this.parent.model.get('mid') + '_open', this.parent.open);
    this.parent.trigger('kb.module.view.open', this.parent.open);
    this.parent.model.trigger('kb.module.view.open', this.parent.open);
  }
});