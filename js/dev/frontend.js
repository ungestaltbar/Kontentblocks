/*! Kontentblocks DevVersion 2014-03-24 */
KB.Templates = function($) {
    var tmpl_cache = {};
    function getTmplCache() {
        return tmpl_cache;
    }
    function render(tmpl_name, tmpl_data) {
        if (!tmpl_cache[tmpl_name]) {
            var tmpl_dir = kontentblocks.config.url + "js/templates";
            var tmpl_url = tmpl_dir + "/" + tmpl_name + ".html";
            var tmpl_string;
            $.ajax({
                url: tmpl_url,
                method: "GET",
                async: false,
                success: function(data) {
                    tmpl_string = data;
                }
            });
            tmpl_cache[tmpl_name] = _.template(tmpl_string);
        }
        return tmpl_cache[tmpl_name](tmpl_data);
    }
    return {
        render: render
    };
}(jQuery);

KB.Backbone.AreaModel = Backbone.Model.extend({
    idAttribute: "id"
});

KB.Backbone.ModuleModel = Backbone.Model.extend({
    idAttribute: "instance_id",
    destroy: function() {
        var that = this;
        KB.Ajax.send({
            action: "removeModules",
            instance_id: that.get("instance_id")
        }, that.destroyed);
    },
    destroyed: function() {},
    setArea: function(area) {
        this.area = area;
    },
    areaChanged: function() {
        this.view.updateModuleForm();
    },
    save: function(model) {
        var module = model.get("editableModel");
        var el = model.get("editable");
        var dataset = jQuery(el).data();
        dataset.data = jQuery(el).html();
        dataset.postId = module.get("post_id");
        jQuery.ajax({
            url: KBAppConfig.ajaxurl,
            data: {
                action: "saveInlineEdit",
                data: dataset
            },
            type: "POST",
            dataType: "json",
            cookie: encodeURIComponent(document.cookie),
            success: function(data) {
                console.log("sent");
            },
            error: function() {
                console.log("not sent");
            },
            complete: function() {
                console.log("no matter what");
            }
        });
    }
});

KB.Stuff = function($) {
    var self, attachment;
    self = {
        selector: ".editable-image",
        remove: ".kb-js-reset-file",
        img: null,
        init: function() {
            var that = this;
            var $body = $("body");
            $body.on("click", this.selector, function(e) {
                e.preventDefault();
                that.img = $(this);
                that.frame().open();
            });
            $body.on("click", this.remove, function(e) {
                e.preventDefault();
                that.container = $(".kb-field-file-wrapper", activeField);
                that.resetFields();
            });
        },
        frame: function() {
            if (this._frame) return this._frame;
            this._frame = wp.media({
                title: KB.i18n.Refields.file.modalTitle,
                button: {
                    text: KB.i18n.Refields.common.select
                },
                multiple: false,
                library: {
                    type: "image"
                }
            });
            this._frame.on("ready", this.ready);
            this._frame.state("library").on("select", this.select);
            return this._frame;
        },
        ready: function() {
            $(".media-modal").addClass(" smaller no-sidebar");
        },
        select: function() {
            attachment = this.get("selection").first();
            self.handleAttachment(attachment);
        },
        handleAttachment: function(attachment) {
            var that = this;
            var id = attachment.get("id");
            var mId = this.img.attr("data-module");
            var fkey = this.img.attr("data-key");
            var settings = KB.payload.FrontSettings[mId][fkey];
            var cModule = KB.Modules.get(mId);
            var moduleData = cModule.get("moduleData");
            moduleData[fkey] = _.extend(moduleData[fkey], {
                id: id
            });
            cModule.set("moduleData", moduleData);
            jQuery.ajax({
                url: ajaxurl,
                data: {
                    action: "fieldGetImage",
                    args: settings,
                    id: id,
                    _ajax_nonce: kontentblocks.nonces.get
                },
                type: "GET",
                dataType: "json",
                success: function(res) {
                    that.img.attr("src", res);
                },
                error: function() {}
            });
        },
        resetFields: function() {
            $(".kb-file-attachment-id", this.container).val("");
            this.container.hide(750);
            $(this.remove, activeField).hide();
        },
        update: function() {
            this.init();
        }
    };
    return self;
}(jQuery);

KB.Stuff.init();

var KB = KB || {};

KB.StuffBG = function($) {
    var self, attachment;
    self = {
        selector: ".editable-bg-image",
        remove: ".kb-js-reset-file",
        img: null,
        init: function() {
            var that = this;
            $("body").on("click", this.selector, function(e) {
                e.preventDefault();
                that.img = $(this);
                that.frame().open();
            });
            $("body").on("click", this.remove, function(e) {
                e.preventDefault();
                that.container = $(".kb-field-file-wrapper", activeField);
                that.resetFields();
            });
            this.renderControls();
        },
        renderControls: function() {
            var temp;
            $(this.selector).each(function(index, obj) {
                $(obj).hover(function() {
                    var pos = $(this).offset();
                    var width = $(this).width();
                    var mid = $(this).attr("data-module");
                    console.log(mid);
                    $(this).css("cursor", "pointer");
                    temp = $('<div style="padding: 5px; background-color: #333; color: #fff; opacity: .9; font-size:11px;">Click to change image</div>').appendTo($("#" + mid + " .controls-wrap")).css({
                        position: "absolute",
                        top: pos.top + 80 + "px",
                        left: width + pos.left - 135 + "px"
                    });
                }, function() {
                    $(this).css("cursor", "inherit");
                    temp.remove();
                });
            });
        },
        frame: function() {
            if (this._frame) return this._frame;
            this._frame = wp.media({
                title: "Hello",
                button: {
                    text: "Insert"
                },
                multiple: false,
                library: {
                    type: "image"
                }
            });
            this._frame.on("ready", this.ready);
            this._frame.state("library").on("select", this.select);
            return this._frame;
        },
        ready: function() {
            $(".media-modal").addClass(" smaller no-sidebar");
        },
        select: function() {
            attachment = this.get("selection").first();
            self.handleAttachment(attachment);
        },
        handleAttachment: function(attachment) {
            var that = this;
            var id = attachment.get("id");
            var mId = this.img.attr("data-module");
            var fkey = this.img.attr("data-key");
            var settings = KB.payload.FrontSettings[mId][fkey];
            var cModule = KB.Modules.get(mId);
            var moduleData = cModule.get("moduleData");
            moduleData[fkey] = _.extend(moduleData[fkey], {
                id: id
            });
            cModule.set("moduleData", moduleData);
            jQuery.ajax({
                url: ajaxurl,
                data: {
                    action: "fieldGetImage",
                    args: settings,
                    id: id,
                    _ajax_nonce: kontentblocks.nonces.get
                },
                type: "GET",
                dataType: "json",
                success: function(res) {
                    that.img.css("backgroundImage", "url('" + res + "')");
                },
                error: function() {}
            });
        },
        resetFields: function() {
            $(".kb-file-attachment-id", this.container).val("");
            this.container.hide(750);
            $(this.remove, activeField).hide();
        },
        update: function() {
            this.init();
        }
    };
    return self;
}(jQuery);

KB.StuffBG.init();

KB.ModuleLayoutControls = Backbone.View.extend({
    initialize: function(options) {
        this.options = options;
        this.targetEl = this.options.parent.$el;
        this.render();
    },
    events: {
        "click a.close-controls": "destroy"
    },
    render: function() {
        var that = this;
        this.targetEl.addClass("edit-active");
        this.$el.append(KB.Templates.render("frontend/module-layout-controls", {
            model: this.model.toJSON()
        }));
        var container = jQuery(".os-controls-container", this.$el);
        container.css("position", "absolute").draggable({
            handle: "h2",
            containment: "window",
            stop: function(eve, ui) {
                KB.OSConfig.Position = ui.position;
            }
        });
        if (KB.OSConfig.Position) {
            container.css({
                top: KB.OSConfig.Position.top,
                left: KB.OSConfig.Position.left
            });
        }
        jQuery("body").append(this.$el);
        this.$el.tabs();
        var mt = that.targetEl.css("marginTop");
        jQuery("#KBMarginTop").ionRangeSlider({
            from: parseInt(mt, 10),
            postfix: "px",
            onChange: function(obj) {
                that.targetEl.css("marginTop", obj.fromNumber);
            }
        });
        var mb = that.targetEl.css("marginBottom");
        jQuery("#KBMarginBottom").ionRangeSlider({
            from: parseInt(mb, 10),
            postfix: "px",
            onChange: function(obj) {
                that.targetEl.css("marginBottom", obj.fromNumber);
            }
        });
    },
    destroy: function() {
        this.targetEl.removeClass("edit-active");
        this.remove();
    }
});

KB.Backbone.AreaView = Backbone.View.extend({
    initialize: function() {}
});

KB.Backbone.FrontendEditView = Backbone.View.extend({
    $form: null,
    $formContent: null,
    timerId: null,
    initialize: function(options) {
        var that = this;
        this.options = options;
        this.view = options.view;
        this.model.on("change", this.test, this);
        this.on("recalibrate", this.recalibrate, this);
        jQuery(KB.Templates.render("frontend/module-edit-form", {
            model: this.model.toJSON()
        })).appendTo(this.$el);
        this.$form = jQuery("#onsite-form", this.$el);
        this.$formContent = jQuery("#onsite-content", this.$el);
        this.$inner = jQuery(".os-content-inner", this.$formContent);
        this.$el.css("position", "fixed").draggable({
            handle: "h2",
            containment: "window",
            stop: function(eve, ui) {
                KB.OSConfig.wrapPosition = ui.position;
                that.recalibrate(ui.position);
            }
        });
        jQuery(window).on("resize", function() {
            that.recalibrate();
        });
        jQuery("body").on("kontentblocks::tabsChange", function() {
            that.recalibrate();
        });
        if (KB.OSConfig.wrapPosition) {
            this.$el.css({
                top: KB.OSConfig.wrapPosition.top,
                left: KB.OSConfig.wrapPosition.left
            });
        }
        jQuery(document).on("newEditor", function(e, ed) {
            that.attachEditorEvents(ed);
        });
        jQuery(document).on("KB:osUpdate", function() {
            that.serialize(false);
        });
        jQuery(document).on("change", ".kb-observe", function() {
            that.serialize(false);
        });
        jQuery("body").append(this.$el);
        this.render();
    },
    test: function() {
        _K.info("Test Debug: Module Data changed");
    },
    events: {
        keyup: "delayInput",
        "click a.close-controls": "destroy",
        "click a.kb-save-form": "update",
        "click a.kb-preview-form": "preview"
    },
    preview: function() {
        this.serialize(false);
    },
    update: function() {
        this.serialize(true);
    },
    render: function() {
        var that = this;
        this.$el.show();
        this.applyControlsSettings(this.$el);
        KB.lastAddedModule = {
            view: that
        };
        jQuery.ajax({
            url: ajaxurl,
            data: {
                action: "getModuleOptions",
                module: that.model.toJSON(),
                _ajax_nonce: kontentblocks.nonces.read
            },
            type: "POST",
            dataType: "json",
            success: function(res) {
                that.$inner.empty();
                that.$inner.attr("id", that.view.model.get("instance_id"));
                that.$inner.append(res.html);
                if (res.json) {
                    var merged = _.extend(KB.payload, res.json);
                    KB.payload = merged;
                }
                KB.Ui.initTabs();
                KB.Ui.initToggleBoxes();
                KB.TinyMCE.addEditor();
                KB.Fields.trigger("update");
                var localView = _.clone(that.view);
                localView.$el = that.$inner;
                localView.parentView = that.view;
                that.view.trigger("kb:frontend::viewLoaded", localView);
                _K.info("Frontend Modal opened with view of:" + that.view.model.get("instance_id"));
                setTimeout(function() {
                    that.recalibrate();
                }, 1e3);
            },
            error: function() {
                console.log("e");
            }
        });
    },
    reload: function(moduleView) {
        this.unload();
        if (this.model && this.model.get("instance_id") === moduleView.model.get("instance_id")) {
            return false;
        }
        this.model = moduleView.model;
        this.options.view = moduleView;
        this.view = moduleView;
        this.render();
    },
    unset: function() {
        this.model = null;
        this.options.view = null;
    },
    recalibrate: function(pos) {
        var winH, conH, position, winDiff;
        winH = jQuery(window).height() - 40;
        conH = jQuery(".os-content-inner").height();
        position = this.$el.position();
        winDiff = conH + position.top - winH;
        if (winDiff > 0) {
            this.initScrollbars(conH - (winDiff + 30));
        } else if (conH - position.top < winH) {
            this.initScrollbars(conH);
        } else {
            this.initScrollbars(winH - position.top);
        }
        if (position.top < 40) {
            this.$el.css("top", "40px");
        }
        _K.info("Frontend Modal resizing done!");
    },
    initScrollbars: function(height) {
        jQuery(".nano", this.$el).height(height);
        jQuery(".nano").nanoScroller({
            preventPageScrolling: true
        });
        _K.info("Nano Scrollbars (re)initialized!");
    },
    serialize: function(save) {
        _K.info("Frontend Modal called serialize function. Savemode", save);
        var that = this;
        tinymce.triggerSave();
        jQuery.ajax({
            url: ajaxurl,
            data: {
                action: "updateModuleOptions",
                data: that.$form.serialize().replace(/\'/g, "%27"),
                module: that.model.toJSON(),
                editmode: save ? "update" : "preview",
                _ajax_nonce: kontentblocks.nonces.update
            },
            type: "POST",
            dataType: "json",
            success: function(res) {
                jQuery(".editable", that.options.view.$el).each(function(i, el) {
                    tinymce.remove("#" + el.id);
                });
                that.options.view.$el.html(res.html);
                that.model.set("moduleData", res.newModuleData);
                that.model.view.render();
                that.model.view.delegateEvents();
                that.model.view.trigger("kb:moduleUpdated");
                that.view.trigger("kb:frontend::viewUpdated");
                jQuery(window).trigger("kontentblocks::ajaxUpdate");
                jQuery(".editable", that.options.view.$el).each(function(i, el) {
                    initTinymce(el);
                });
                if (save) {
                    KB.Notice.notice(KB.i18n.jsFrontend.frontendModal.noticeDataSaved, "success");
                    that.$el.removeClass("isDirty");
                } else {
                    KB.Notice.notice(KB.i18n.jsFrontend.frontendModal.noticePreviewUpdated, "success");
                    that.$el.addClass("isDirty");
                }
                _K.info("Frontend Modal saved data for:" + that.model.get("instance_id"));
            },
            error: function() {
                console.log("e");
            }
        });
    },
    delayInput: function() {
        var that = this;
        if (this.options.timerId) {
            clearTimeout(this.options.timerId);
        }
        this.options.timerId = setTimeout(function() {
            that.options.timerId = null;
            that.serialize();
        }, 500);
    },
    attachEditorEvents: function(ed) {
        var that = this;
        ed.onKeyUp.add(function() {
            that.delayInput();
        });
    },
    destroy: function() {
        this.unload();
        this.unbind();
        this.remove();
        KB.FrontendEditModal = null;
    },
    unload: function() {
        this.unset();
        jQuery(".wp-editor-area", this.$el).each(function(i, item) {
            tinymce.remove("#" + item.id);
        });
    },
    applyControlsSettings: function($el) {
        var settings = this.model.get("settings");
        if (settings.controls && settings.controls.width) {
            $el.css("width", settings.controls.width + "px");
        }
    }
});

KB.Backbone.ModuleView = Backbone.View.extend({
    initialize: function() {
        var that = this;
        if (!KB.Checks.userCan("edit_kontentblocks")) {
            return;
        }
        this.model.bind("save", this.model.save);
        this.listenTo(this.model, "change", this.modelChange);
        this.model.view = this;
        this.render();
        this.setControlsPosition();
        jQuery(window).on("kontentblocks::ajaxUpdate", function() {
            that.setControlsPosition();
        });
    },
    modelChange: function() {
        this.$el.addClass("isDirty");
    },
    save: function() {},
    events: {
        "click a.os-edit-block": "openOptions",
        "click .editable": "reloadModal",
        "click .kb-js-inline-update": "updateModule",
        "click .kb-js-open-layout-controls": "openLayoutControls"
    },
    render: function() {
        this.$el.append(KB.Templates.render("frontend/module-controls", {
            model: this.model.toJSON()
        }));
    },
    openOptions: function() {
        if (KB.FrontendEditModal) {
            this.reloadModal();
            return false;
        }
        KB.FrontendEditModal = new KB.Backbone.FrontendEditView({
            tagName: "div",
            id: "onsite-modal",
            model: this.model,
            view: this
        });
        KB.focusedModule = this.model;
    },
    reloadModal: function() {
        if (KB.FrontendEditModal) {
            KB.FrontendEditModal.reload(this);
        }
        KB.CurrentModel = this.model;
        KB.focusedModule = this.model;
    },
    openLayoutControls: function() {
        if (KB.OpenedLayoutControls) {
            KB.OpenedLayoutControls.destroy();
        }
        KB.OpenedLayoutControls = new KB.ModuleLayoutControls({
            tagName: "div",
            id: "slider-unique",
            className: "slider-controls-wrapper",
            model: this.model,
            parent: this
        });
    },
    setControlsPosition: function() {
        var mSettings = this.model.get("settings");
        var $controls = jQuery(".os-controls", this.$el);
        var pos = this.$el.offset();
        var mwidth = this.$el.width() - 150;
        if (mSettings.controls && mSettings.controls.toolbar) {
            pos.top = mSettings.controls.toolbar.top;
            pos.left = mSettings.controls.toolbar.left;
        }
        $controls.offset({
            top: pos.top + 20,
            left: pos.left - 15,
            zIndex: 999999
        });
    },
    updateModule: function() {
        var that = this;
        var moduleData = {};
        var refresh = false;
        moduleData[that.model.get("instance_id")] = that.model.get("moduleData");
        jQuery.ajax({
            url: ajaxurl,
            data: {
                action: "updateModuleOptions",
                data: jQuery.param(moduleData).replace(/\'/g, "%27"),
                module: that.model.toJSON(),
                editmode: "update",
                refresh: refresh,
                _ajax_nonce: kontentblocks.nonces.update
            },
            type: "POST",
            dataType: "json",
            success: function(res) {
                if (refresh) {
                    that.$el.html(res.html);
                }
                tinymce.triggerSave();
                that.model.set("moduleData", res.newModuleData);
                that.model.view.render();
                that.model.view.trigger("kb:moduleUpdated");
                jQuery(window).trigger("kontentblocks::ajaxUpdate");
                KB.Notice.notice("Module saved successfully", "success");
                that.$el.removeClass("isDirty");
            },
            error: function() {
                console.log("e");
            }
        });
    }
});

KB.currentModule = {};

KB.currentArea = {};

KB.Views = {
    Modules: new KB.ViewsCollection(),
    Areas: new KB.ViewsCollection(),
    Context: new KB.ViewsCollection()
};

KB.Modules = new Backbone.Collection([], {
    model: KB.Backbone.ModuleModel
});

KB.Areas = new Backbone.Collection([], {
    model: KB.Backbone.AreaModel
});

KB.App = function($) {
    function init() {
        var $toolbar = jQuery('<div id="kb-toolbar"></div>').appendTo("body");
        $toolbar.hide();
        KB.Modules.on("add", createModuleViews);
        KB.Areas.on("add", createAreaViews);
        KB.Modules.on("remove", removeModule);
        addViews();
        KB.Ui.init();
    }
    function addViews() {
        if (KB.appData.config.preview) {
            return false;
        }
        _.each(KB.payload.Areas, function(area) {
            KB.Areas.add(new KB.Backbone.AreaModel(area));
        });
        _.each(KB.payload.Modules, function(module) {
            KB.Modules.add(module);
        });
        KB.trigger("kb:moduleControlsAdded");
    }
    function createModuleViews(module) {
        module.setArea(KB.Areas.get(module.get("area")));
        module.bind("change:area", module.areaChanged);
        KB.Views.Modules.add(module.get("instance_id"), new KB.Backbone.ModuleView({
            model: module,
            el: "#" + module.get("instance_id")
        }));
        KB.Ui.initTabs();
    }
    function createAreaViews(area) {
        KB.Views.Areas.add(area.get("id"), new KB.Backbone.AreaView({
            model: area,
            el: "#" + area.get("id") + "-container"
        }));
    }
    function removeModule(model) {
        KB.Views.Modules.remove(model.get("instance_id"));
    }
    return {
        init: init
    };
}(jQuery);

KB.App.init();

jQuery(document).ready(function() {
    if (KB.appData && KB.appData.config.frontend) {
        _K.info("Frontend Modules Ready Event fired");
        KB.Views.Modules.readyOnFront();
    }
});

function initTinymce(item) {
    if (!KB.Checks.userCan("edit_kontentblocks")) {
        return;
    }
    tinymce.init({
        selector: "#" + item.id,
        theme: "modern",
        skin: "lightgray",
        menubar: false,
        add_unload_trigger: false,
        fixed_toolbar_container: "#kb-toolbar",
        schema: "html5",
        inline: true,
        toolbar: "kbcancleinline | undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image     | print preview media",
        statusbar: false,
        setup: function(ed) {
            ed.on("init", function() {
                var data = jQuery(ed.bodyElement).data();
                var module = data.module;
                ed.module = KB.Modules.get(module);
                ed.kbDataRef = {
                    key: data.key,
                    index: data.index,
                    arrayKey: data.arraykey
                };
            });
            ed.on("focus", function(e) {
                jQuery("#kb-toolbar").show();
            });
            ed.addButton("kbcancleinline", {
                title: "Stop inline Edit",
                onClick: function() {
                    tinymce.activeEditor = null;
                    tinymce.focusedEditor = null;
                    document.activeElement.blur();
                    jQuery("#kb-toolbar").hide();
                }
            });
            ed.on("blur", function() {
                jQuery("#kb-toolbar").hide();
                var data = ed.kbDataRef;
                var value = ed.getContent();
                var moduleData = _.clone(ed.module.get("moduleData"));
                if (!_.isUndefined(data.index) && !_.isUndefined(data.arrayKey)) {
                    moduleData[data.arrayKey][data.index][data.key] = value;
                } else if (!_.isUndefined(data.index)) {
                    moduleData[data.index][data.key] = value;
                } else if (!_.isUndefined(data.arrayKey)) {
                    moduleData[data.arrayKey][data.key] = value;
                } else {
                    moduleData[data.key] = value;
                }
                ed.module.set("moduleData", moduleData);
            });
        }
    });
}

jQuery(document).ready(function() {
    jQuery("div.editable").each(function(i, item) {
        initTinymce(item);
    });
    jQuery(".editable-title").each(function(item) {
        if (!KB.Checks.userCan("edit_kontentblocks")) {
            return;
        }
        tinymce.init({
            selector: "#" + this.id,
            theme: "modern",
            skin: false,
            menubar: false,
            add_unload_trigger: false,
            schema: "html5",
            fixed_toolbar_container: "#kb-toolbar",
            inline: true,
            toolbar: "kbcancleinline",
            statusbar: false,
            setup: function(ed) {
                ed.on("init", function() {
                    var data = jQuery(ed.bodyElement).data();
                    var module = data.module;
                    ed.module = KB.Modules.get(module);
                    ed.kbDataRef = {
                        key: data.key,
                        index: data.index,
                        arrayKey: data.arraykey
                    };
                });
                ed.on("focus", function(e) {
                    jQuery("#kb-toolbar").show();
                });
                ed.addButton("kbcancleinline", {
                    title: "Stop inline Edit",
                    onClick: function() {
                        tinymce.activeEditor = null;
                        tinymce.focusedEditor = null;
                        document.activeElement.blur();
                        jQuery("#kb-toolbar").hide();
                    }
                });
                ed.on("blur", function() {
                    jQuery("#kb-toolbar").hide();
                    var data = ed.kbDataRef;
                    var value = ed.getContent();
                    var moduleData = _.clone(ed.module.get("moduleData"));
                    if (!_.isUndefined(data.index) && !_.isUndefined(data.arrayKey)) {
                        moduleData[data.arrayKey][data.index][data.key] = value;
                    } else if (!_.isUndefined(data.index)) {
                        moduleData[data.index][data.key] = value;
                    } else if (!_.isUndefined(data.arrayKey)) {
                        moduleData[data.arrayKey][data.key] = value;
                    } else {
                        moduleData[data.key] = value;
                    }
                    ed.module.set("moduleData", moduleData);
                });
            }
        });
    });
});