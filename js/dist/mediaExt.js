/*! Kontentblocks ProdVersion 2015-02-21 06:02 */
!function(a){a&&a.media&&(media=a.media,l10n=media.view.l10n="undefined"==typeof _wpMediaViewsL10n?{}:_wpMediaViewsL10n,media.view.KBGallery=media.view.MediaFrame.Select.extend({initialize:function(){_.defaults(this.options,{selection:[],library:{},multiple:!1,state:"gallery",content:"library"}),media.view.MediaFrame.prototype.initialize.apply(this,arguments),this.createSelection(),this.createStates(),this.bindHandlers()},bindHandlers:function(){var a,b;media.view.MediaFrame.Select.prototype.bindHandlers.apply(this,arguments),this.on("activate",this.activate,this),b=_.find(this.counts,function(a){return 0===a.count}),"undefined"!=typeof b&&this.listenTo(media.model.Attachments.all,"change:type",this.mediaTypeCounts),this.on("menu:create:gallery",this.createMenu,this),this.on("toolbar:create:main-gallery",this.createToolbar,this),a={content:{"edit-image":"editImageContent","edit-selection":"editSelectionContent"},toolbar:{"main-gallery":"mainGalleryToolbar","gallery-edit":"galleryEditToolbar","gallery-add":"galleryAddToolbar"}},_.each(a,function(a,b){_.each(a,function(a,c){this.on(b+":render:"+c,this[a],this)},this)},this)},reset:function(){return this.states.invoke("trigger","reset"),this.createSelection(),this},createStates:function(){var a=this.options;this.states.add([new media.controller.Library({id:"gallery",title:l10n.createGalleryTitle,priority:40,toolbar:"main-gallery",filterable:"uploaded",multiple:"add",editable:!1,library:media.query(_.defaults({type:"image"},a.library))}),new media.controller.GalleryEdit({library:a.selection,editing:a.editing,menu:"gallery"}),new media.controller.GalleryAdd])},mainGalleryToolbar:function(a){var b=this;this.selectionStatusToolbar(a),a.set("gallery",{style:"primary",text:l10n.createNewGallery,priority:60,requires:{selection:!0},click:function(){var a=b.state().get("selection"),c=b.state("gallery-edit"),d=a.where({type:"image"});c.set("library",new media.model.Selection(d,{props:a.props.toJSON(),multiple:!0})),this.controller.setState("gallery-edit"),this.controller.modal.focusManager.focus()}})},galleryEditToolbar:function(){var a=this.state().get("editing");this.toolbar.set(new media.view.Toolbar({controller:this,items:{insert:{style:"primary",text:a?l10n.updateGallery:l10n.insertGallery,priority:80,requires:{library:!0},click:function(){var a=this.controller,b=a.state();a.close(),b.trigger("update",b.get("library")),a.setState(a.options.state),a.reset()}}}}))},galleryAddToolbar:function(){this.toolbar.set(new media.view.Toolbar({controller:this,items:{insert:{style:"primary",text:l10n.addToGallery,priority:80,requires:{selection:!0},click:function(){var a=this.controller,b=a.state(),c=a.state("gallery-edit");c.get("library").add(b.get("selection").models),b.trigger("reset"),a.setState("gallery-edit")}}}}))},selectionStatusToolbar:function(a){var b=this.state().get("editable");a.set("selection",new media.view.Selection({controller:this,collection:this.state().get("selection"),priority:-40,editable:b&&function(){this.controller.content.mode("edit-selection")}}).render())}}))}(window.wp,jQuery);