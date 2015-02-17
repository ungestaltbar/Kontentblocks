KB.Fields.register('Image', (function ($) {
  'use strict';
  var self;

  self = {
    selector: '.kb-js-add-image',
    reset: '.kb-js-reset-image',
    _frame: null,
    $container: null,
    $wrapper: null,
    $id: null,
    $title: null,
    $caption: null,
    init: function () {
      var that = this;
      var $body = $('body');
      $body.on('click', this.selector, function (e) {
        e.preventDefault();
        that.setupInputs(this);
        that.settings = that.getSettings(this);
        that.openModal();
      });

      $body.on('click', this.reset, function (e) {
        that.setupInputs(this);
        that.resetInputs();
      });

    },
    setupInputs: function (anchor) {

      this.$wrapper = $(anchor).closest('.kb-field-image-wrapper');
      this.$container = $('.kb-field-image-container', this.$wrapper);
      this.$id = $('.kb-js-image-id', this.$wrapper);
      this.$title = $('.kb-js-image-title', this.$wrapper);
      this.$description = $('.kb-js-image-description', this.$wrapper);
    },
    getSettings: function (el) {
      var parent = $(el).closest('.kb-field-wrapper');
      var id = parent.attr('id');
      if (KB.payload.Fields && KB.payload.Fields[id]) {
        return KB.payload.Fields[id];
      }
    },
    frame: function () {
      if (this._frame)
        return this._frame;
    },
    openModal: function () {

      // opens dialog if not already declared
      if (this._frame) {
        this._frame.open();
        return;
      }

      this._frame = wp.media({
        // Custom attributes
        title: KB.i18n.Refields.image.modalTitle,
        button: {
          text: KB.i18n.Refields.common.select
        },
        multiple: false,
        library: {
          type: 'image'
        }
      });

      this._frame.state('library').on('select', this.select);
      this._frame.open();
      return this._frame;

    },
    select: function () {
      var attachment = this.get('selection').first();
      self.handleAttachment(attachment);
    },
    handleAttachment: function (attachment) {
      var that = this;
      var url, args, src;
      if (this.settings && this.settings.previewSize) {

        args = {
          width: this.settings.previewSize[0],
          height: this.settings.previewSize[1],
          crop: true,
          upscale: false
        };

        jQuery.ajax({
          url: ajaxurl,
          data: {
            action: "fieldGetImage",
            args: args,
            id: attachment.get('id'),
            _ajax_nonce: KB.Config.getNonce('read')
          },
          type: "GET",
          dataType: "json",
          success: function (res) {
            that.$container.html('<img src="' + res + '" >');
//                        KB.Util.stex.set(attachment.get('id') + args.width + args.height, res, 60 * 1000 * 60);
          },
          error: function () {
          }
        });
      } else {
        src = (attachment.get('sizes').thumbnail) ? attachment.get('sizes').thumbnail.url : attachment.get('sizes').full.url;
        this.$container.html('<img src="' + src + '" >');
      }
      this.$id.val(attachment.get('id'));
      //this.$title.val(attachment.get('title'));
      //this.$description.val(attachment.get('caption'));
      KB.Events.trigger('kb.modal.preview', this);

    },
    resetInputs: function () {
      this.$container.empty();
      this.$id.val('');
      this.$title.val('');
      this.$description('');
    },
    update: function () {
      this.init();
    },
    updateFront: function () {
      this.init();
    }
  };
  return self;


}(jQuery)));