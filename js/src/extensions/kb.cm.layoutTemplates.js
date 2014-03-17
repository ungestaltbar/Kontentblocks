(function ($) {

    var LayoutTemplates = {
        el: $('#layout-templates'),
        init: function () {
            if (KB.appData.config.frontend){
                _K.info('Layout Templates stopped');
                return false;
            }

            _K.debug('Layout Templates loaded');

            if (this.el.length === 0) {
                return false;
            }

            this.options = {};
            this.areaConfig = this._areaConfig();
            this.selectContainer = this._selectContainer();
            this.selectMenuEl = this._createSelectMenu();
            this.loadButton = this._loadButton();
            this.deleteButton = this._deleteButton();
            this.createContainer = this._createContainer();
            this.createInput = this._createInput();
            this.createButton = this._createButton();

            this.update();
        },
        _selectContainer: function () {
            return $("<div class='select-container'></div>").appendTo(this.el);
        },
        _createSelectMenu: function () {
            $('<select name="layout-template"></select>').appendTo(this.selectContainer);
            return $('select', this.el);
        },
        update: function () {
            var that = this;


            KB.Ajax.send(
                {
                    action: 'get_layout_templates',
                    data: {
                        areaConfig: this.areaConfig
                    }
                },
                function (response) {
                    that.options = response;
                    that.renderSelectMenu(response);
                });

        },
        save: function () {
            var that = this;
            var value = this.createInput.val();

            if (_.isEmpty(value)) {
                KB.notice('Please enter a Name for the template', 'error');
                return false;
            }

            KB.Ajax.send
            (
                {
                    action: 'set_layout_template',
                    data: {
                        areaConfig: this.areaConfig,
                        name: value
                    }
                },
                function (response) {
                    that.update();
                    that.createInput.val('');
                    KB.notice('Saved', 'success');
                });

        },
        delete: function () {
            var that = this;
            var value = this.selectMenuEl.val();

            if (_.isEmpty(value)) {
                KB.notice('Please chose a template to delete', 'error');
                return false;
            }

            KB.Ajax.send(
                {
                    action: 'delete_layout_template',
                    data: {
                        areaConfig: this.areaConfig,
                        name: value
                    }
                },
                function (response) {
                    that.update();
                    KB.notice('Saved', 'success');
                });

        },
        renderSelectMenu: function (data) {
            var that = this;
            that.selectMenuEl.empty();
            _.each(data, function (item, key, s) {
                that.selectMenuEl.append(_.template("<option value='<%= data.key %>'><%= data.name %></option>", {data: {
                    key: key,
                    name: item.name
                }}));
            });
        },
        _areaConfig: function () {

            var concat = '';

            if (KB.payload.Areas) {
                _.each(KB.payload.Areas, function (context) {
                    concat += context.id;
                    _K.debug('Layout Templates: Concat', concat);
                });
            }
            return this.hash(concat.replace(',', ''));
        },
        hash: function (s) {
            return s.split("").reduce(function (a, b) {
                a = ((a << 5) - a) + b.charCodeAt(0);
                return a & a
            }, 0);

        },
        _createContainer: function () {
            return ($("<div class='create-container'></div>").appendTo(this.el));
        },
        _createInput: function () {
            return $("<input type='text' >").appendTo(this.createContainer);
        },
        _createButton: function () {
            var that = this;
            var button = $("<a class='button'>Save</a>").appendTo(this.createContainer);
            button.on('click', function (e) {
                e.preventDefault();
                that.save();
            })
            return button;
        },
        _loadButton: function () {
            var that = this;
            var button = $("<a class='button'>Load</a>").appendTo(this.selectContainer);
            button.on('click', function (e) {
                e.preventDefault();
                that.load();
            })
            return button;
        },
        _deleteButton: function () {
            var that = this;
            var button = $("<a class='delete-js'>delete</a>").appendTo(this.selectContainer);
            button.on('click', function (e) {
                e.preventDefault();
                that.delete();
            });
            return button;
        },
        load: function () {
            var location = window.location.href + '&load_template=' + this.selectMenuEl.val() + '&post_id=' + $('#post_ID').val() + '&config=' + this.areaConfig;
            window.location = location;
        }

    };

    LayoutTemplates.init();

}(jQuery));