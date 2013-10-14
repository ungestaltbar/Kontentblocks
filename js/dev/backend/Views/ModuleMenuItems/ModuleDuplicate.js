'use strict';
var KB = KB || {};
KB.Backbone = KB.Backbone || {};

KB.Backbone.ModuleDuplicate = KB.Backbone.ModuleMenuItemView.extend({
    className: 'kb-duplicate block-menu-icon',
    events: {
        'click': 'duplicateModule'
    },
    duplicateModule: function() {
        KB.Notice.notice('Duplicate Module', 'success', 4500);
    },
    isValid: function() {
        var settings = this.model.get('settings');
        if (!settings.predefined &&
                !settings.disabled &&
                KB.Caps.userCan('edit_kontentblocks')) {
            return true;
        } else {
            return false;
        }
    }
}); 