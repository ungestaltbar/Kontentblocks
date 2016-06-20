var tpl = require('templates/backend/status/settings/loggedInOnly.hbs');
var ControlView = require('./ControlView');
module.exports = ControlView.extend({
  render: function () {
    this.$el.append(tpl({model: this.model.toJSON()}));
    return this.$el;
  },
  getOverrideValue: function (event) {
    return this.$('input').is(':checked');
  }
});