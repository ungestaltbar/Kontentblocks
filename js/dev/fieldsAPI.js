/*! Kontentblocks DevVersion 2014-04-27 */
KB.FieldsAPI = function() {
    return {
        fields: {},
        register: function(id, obj) {
            this.fields[id] = obj;
        },
        get: function(field) {
            return new this.fields[field.type](field);
        }
    };
}();

KB.FieldsAPI.FieldStdModel = Backbone.Model.extend({});

KB.FieldsAPI.Field = Backbone.View.extend({
    initialize: function(config) {
        this.defaults = this.defaults || {};
        this.config = _.defaults(config, this.defaults);
        this.model = new KB.FieldsAPI.FieldStdModel({
            value: this.get("std")
        });
        this.model.view = this;
        this.baseId = this.prepareBaseId();
    },
    get: function(key) {
        if (!_.isUndefined(this.config[key])) {
            return this.config[key];
        } else {
            return null;
        }
    },
    set: function(key, value) {
        this.config[key] = value;
    },
    setValue: function(val) {
        this.model.set("value", val);
    },
    prepareBaseId: function() {
        return this.config.moduleId + "[" + this.config.fieldKey + "]";
    }
});

KB.FieldsAPI.Editor = KB.FieldsAPI.Field.extend({
    $editorWrap: null,
    templatePath: "fields/Editor",
    defaults: {
        std: "some textvalue",
        label: "Field label",
        description: "A description",
        value: "",
        key: null
    },
    initialize: function(config) {
        KB.FieldsAPI.Field.prototype.initialize.call(this, config);
    },
    setValue: function(value) {
        this.model.set("value", value);
    },
    render: function(index) {
        this.index = index;
        return KB.Templates.render(this.templatePath, {
            config: this.config,
            baseId: this.baseId,
            index: index,
            model: this.model.toJSON()
        });
    },
    postRender: function() {
        var name = this.baseId + "[" + this.index + "]" + "[" + this.get("key") + "]";
        var edId = this.get("moduleId") + "_" + this.get("key") + "_editor_" + this.index;
        this.$editorWrap = jQuery(".kb-ff-editor-wrapper", this.$container);
        KB.TinyMCE.remoteGetEditor(this.$editorWrap, name, edId, this.model.get("value"), 5, false);
    }
});

KB.FieldsAPI.register("editor", KB.FieldsAPI.Editor);

KB.FieldsAPI.Image = KB.FieldsAPI.Field.extend({
    $currentWrapper: null,
    $currentFrame: null,
    templatePath: "fields/Image",
    initialize: function(config) {
        var that = this;
        KB.FieldsAPI.Field.prototype.initialize.call(this, config);
        this.config.$parent.on("click", ".flexible-fields--js-add-image", function() {
            that.$currentWrapper = jQuery(this).closest(".field-api-image");
            that.$currentFrame = jQuery(".field-api-image--frame", that.$currentWrapper);
            that.$IdInput = jQuery(".field-api-image--image-id", that.$currentWrapper);
            new KB.Utils.MediaWorkflow({
                title: "Hello",
                select: _.bind(that.handleAttachment, that)
            });
        });
    },
    defaults: {
        std: "",
        label: "Image",
        description: "Awesome image",
        value: {
            url: "",
            id: "",
            caption: "",
            title: ""
        },
        key: null
    },
    render: function(index) {
        return KB.Templates.render(this.templatePath, {
            config: this.config,
            baseId: this.baseId,
            index: index,
            model: this.model.toJSON(),
            i18n: _.extend(KB.i18n.Refields.image, KB.i18n.Refields.common)
        });
    },
    setValue: function(value) {
        _K.info("FF Model", value);
        var that = this;
        var args = {
            width: 150,
            height: 150,
            upscale: false,
            crop: true
        };
        if (!value.id) {
            return;
        }
        this.model.set("value", value);
        jQuery.ajax({
            url: ajaxurl,
            data: {
                action: "fieldGetImage",
                args: args,
                id: value.id,
                _ajax_nonce: kontentblocks.nonces.get
            },
            type: "GET",
            dataType: "json",
            async: false,
            success: function(res) {
                var attrs = that.model.get("value");
                attrs.url = res;
            },
            error: function() {
                _K.error("Unable to get image");
            }
        });
    },
    handleAttachment: function(media) {
        var att = media.get("selection").first();
        if (att.get("sizes").thumbnail) {
            this.$currentFrame.empty().append('<img src="' + att.get("sizes").thumbnail.url + '" >');
            this.$IdInput.val(att.get("id"));
        }
    }
});

KB.FieldsAPI.register("image", KB.FieldsAPI.Image);

KB.FieldsAPI.Text = KB.FieldsAPI.Field.extend({
    templatePath: "fields/Text",
    defaults: {
        std: "some textvalue",
        label: "Field label",
        description: "A description",
        value: "",
        key: null
    },
    render: function(index) {
        return KB.Templates.render(this.templatePath, {
            config: this.config,
            baseId: this.baseId,
            index: index,
            model: this.model.toJSON()
        });
    }
});

KB.FieldsAPI.register("text", KB.FieldsAPI.Text);

KB.FieldsAPI.Textarea = KB.FieldsAPI.Field.extend({
    defaults: {
        std: "some textvalue",
        label: "Field label",
        description: "A description",
        value: "",
        key: null
    },
    templatePath: "fields/Textarea",
    render: function(index) {
        return KB.Templates.render(this.templatePath, {
            config: this.config,
            baseId: this.baseId,
            index: index,
            model: this.model.toJSON()
        });
    }
});

KB.FieldsAPI.register("textarea", KB.FieldsAPI.Textarea);