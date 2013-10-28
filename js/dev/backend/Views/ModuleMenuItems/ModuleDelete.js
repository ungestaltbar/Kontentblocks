'use strict';

var KB = KB || {};
KB.Backbone = KB.Backbone || {};

KB.Backbone.ModuleDelete = KB.Backbone.ModuleMenuItemView.extend({
    className: 'kb-delete block-menu-icon',
    initialize: function() {
        _.bindAll(this, "yes", "no");
    },
    events: {
        'click': 'deleteModule'
    },
    deleteModule: function() {
        KB.Notice.confirm('Really?', this.yes, this.no);
    },
    isValid: function() {
        if (!this.model.get('predefined') &&
                !this.model.get('disabled') &&
                KB.Caps.userCan('delete_kontentblocks')) {
            return true;
        } else {
            return false;
        }
    },
    yes: function() {
        KB.Ajax.send({
            action: 'removeModules',
            module: this.model.get('instance_id')
        }, this.success.call(this));
    },
    no: function() {
        return false;
    },
    success: function() {
        
        KB.Notice.notice('Good bye', 'success');
        this.options.parent.$el.hide(500);
    }
}); 