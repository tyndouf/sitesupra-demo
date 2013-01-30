YUI.add('supra.page-content-gallery', function (Y) {
	//Invoke strict mode
	"use strict";
	
	/*
	 * Shortcuts
	 */
	var Manager = Supra.Manager,
		Page = Manager.Page,
		PageContent = Manager.PageContent;
	
	/**
	 * Content block which has editable properties
	 */
	function ContentGallery () {
		ContentGallery.superclass.constructor.apply(this, arguments);
	}
	
	ContentGallery.NAME = 'page-content-gallery';
	ContentGallery.CLASS_NAME = Y.ClassNameManager.getClassName(ContentGallery.NAME);
	
	Y.extend(ContentGallery, PageContent.Editable, {
		
		/**
		 * Data drag and drop object, PluginDropTarget instance
		 * @type {Object}
		 */
		drop: null,
		
		/**
		 * Gallery manage/add buttons
		*/
		buttons: {},
		
		
		renderUISettings: function () {
			//Add toolbar buttons
			var toolbar = Manager.PageToolbar,
				buttons = Manager.PageButtons;
			
			if (!toolbar.hasActionButtons(ContentGallery.NAME)) {
				
				toolbar.addActionButtons(ContentGallery.NAME, [
					{
						'id': 'gallery_block_manage',
						'type': 'button',
						'title': Supra.Intl.get(['gallerymanager', 'label_button']),
						'icon': '/cms/lib/supra/img/toolbar/icon-pages.png',
						'action': this,
						'actionFunction': 'openExternalManager'
					}
				]);
				
				//Add "Done" button
				buttons.addActionButtons(ContentGallery.NAME, [
					{
						'id': 'done',
						'context': this,
						'callback': Y.bind(function () {
							var active_content = Manager.PageContent.getContent().get('activeChild');
							if (active_content) {
								active_content.fire('block:save');
								return;
							}
							
							Manager.getAction('PageButtons').unsetActiveAction(this.NAME);
						}, this)
					}
				]);
			}
			
			//Initialize
			var properties = this.getProperties(),
				page_data = Page.getPageData(),
				data = this.get('data');
			
			//If editing template, then set "__locked__" property value
			if (page_data.type != 'page') {
				data.properties = data.properties || {};
				data.properties.__locked__ = {
					shared: false,
					value: data.locked
				}
			}
			
			//Add properties plugin (creates form)
			this.plug(PageContent.PluginProperties, {
				'data': data,
				//Not using default group
				'toolbarGroupId': ContentGallery.NAME
			});
			
			//Manage button is placed in block settings if there are no property groups
			//and sidebar is opened on block edit and block is saved (stop editing) when
			//sidebar is closed 
			if (!this.properties.hasTopGroups()) {
				toolbar.getActionButton('gallery_block_manage').hide();
				this.renderManageButton();
				
				//Save and close block on property save (sidebar close)
				this.on('properties:save', function () {
					this.fire('block:save');
				});
				this.on('properties:cancel', function () {
					this.fire('block:cancel');
				});
			} else {
				toolbar.getActionButton('gallery_block_manage').show();
			}
			
			//Find all inline and HTML properties, initialize
			this.findInlineInputs();
			
			//Handle block save / cancel
			this.on('block:save', this.savePropertyChanges, this);
			this.on('block:cancel', this.cancelPropertyChanges, this);
			
			//Render buttons
			this.bindDnD();
			
			//Bind to content click
			this.bindItemClick();
		},
		
		renderManageButton: function () {
			//Add "Manage images" button
			var form = this.properties.get('form'),
				content = form.get('boundingBox').one('.su-slide-content > div') || form.get('contentBox'),
				button = new Supra.Button({
											'style': 'small-gray',
											'label': Supra.Intl.get(['gallerymanager', 'label_button'])
										 });
			
			button.render(content);
			button.addClass('button-section');
			
			content.prepend(button.get('boundingBox'));
			content.prepend(Y.Node.create('<p class="label">' + Supra.Intl.get(['gallerymanager', 'label']) + '</p>'));
			
			button.on('click', this.openExternalManager, this);
		},
		
		/**
		 * Returns true if blocks has property groups, otherwise false
		 * If there are property groups then "Manage images" is placed in toolbar,
		 * otherwise it's placed in block settings
		 * 
		 * @returns {Boolean} True if blocks has property groups, otherwise false
		 * @private
		 */
		hasPropertyGroups: function () {
			var info = this.getBlockInfo();
			if (info.property_groups && info.property_groups.length) {
				return true;
			} else {
				return false;
			}
		},
		
		/**
		 * Bind drag & drop to allow droping images from media library
		 * 
		 * @private
		 */
		bindDnD: function () {
			var srcNode = this.getNode(),
				doc = this.get('doc');
			
			//On drop add image or images
			srcNode.on('dataDrop', this.onDrop, this);
			
			//Enable drag & drop
			this.drop = new Manager.PageContent.PluginDropTarget({
				'srcNode': srcNode,
				'doc': doc
			});
		},
		
		/**
		 * Bind clicking on one of the items as a trigger for opening gallery manager
		 * 
		 * @private
		 */
		bindItemClick: function () {
			
			// Find template
			var node = this.getNode(),
				template = node.one('script[type="text/supra-template"], script[type="text/template"]'),
				listSelector = 'ul, ol';
			
			if (template) {
				// As template attribute should be set container node selector
				listSelector = template.getAttribute('data-supra-container-selector') || listSelector;
			}
			
			if (listSelector) {
				node.delegate('click', function (e) {
					var target = e.target;
					if (target.closest(listSelector) && !target.test(listSelector)) {
						// User clicked on list item
						this.openExternalManager();
					}
				}, listSelector, this);
			}
			
		},
		
		bindUI: function () {
			ContentGallery.superclass.bindUI.apply(this, arguments);
		},
		
		onEditingStart: function () {
			ContentGallery.superclass.onEditingStart.apply(this, arguments);
			
			if (this.properties.hasTopGroups()) {
				Manager.PageToolbar.setActiveAction(ContentGallery.NAME);
				Manager.PageButtons.setActiveAction(ContentGallery.NAME);
			}
		},
		
		onEditingEnd: function () {
			ContentGallery.superclass.onEditingEnd.apply(this, arguments);
			
			Manager.PageToolbar.unsetActiveAction(ContentGallery.NAME);
			Manager.PageButtons.unsetActiveAction(ContentGallery.NAME);
		},
		
		/**
		 * On image or folder drop add images to the gallery
		 * 
		 * @param {Event} e Event
		 * @private
		 */
		onDrop: function (e) {
			if (!Manager.MediaSidebar) {
				//If media sidebar is not loaded, then user didn't droped image from there
				//Prevent default (which is insert folder thumbnail image) 
				if (e.halt) e.halt();
				return false;
			}
			
			var item_id = e.drag_id,
				dataObject = Manager.MediaSidebar.dataObject(),
				replace_id = null;
			
			if (e.drop.closest('b')) {
				//Image was dropped on existing item
				var node_li = e.drop.closest('li.gallery-item');
				if (node_li) {
					node_li.removeClass('gallery-item-over');
					replace_id = node_li.getData('imageId');
				}
			}
			
			//Unmark list
			this.list.removeClass('gallery-over');
			
			//Load data
			dataObject.any(item_id, true).done(function (data) {
				
				if (!Y.Lang.isArray(data)) {
					data = [data];
				}
				
				var folderHasImages = false,
					image = null;
				
				for (var i=0, ii=data.length; i<ii; i++) {
					image = data[i];
					
					if (image.type == Supra.MediaLibraryList.TYPE_IMAGE) {
						
						dataObject.one(image.id, true).done(function (data) {
							if (replace_id) {
								//Replace with first image, all other add to the list
								this.replaceImage(replace_id, data);
								replace_id = null;
							} else {
								this.addImage(data);
							}
						}, this);
						
						folderHasImages = true;
					}
				}
				
				//folder was without images
				if ( ! folderHasImages) {
					Supra.Manager.executeAction('Confirmation', {
						'message': '{#medialibrary.validation_error.empty_folder_drop#}',
						'useMask': true,
						'buttons': [
							{'id': 'delete', 'label': 'OK'}
						]
					});

					return;
				}
				
			}, this);
			
			this.reloadContent();
			
			//Prevent default (which is insert folder thumbnail image) 
			if (e.halt) e.halt();
			
			return false;
		},
		
		/**
		 * Open gallery manager and update data when it closes
		 * @private
		 */
		openExternalManager: function (force) {
			//Since toolbar is created by single instance of gallery
			//keyword "this" may have incorrect reference
			var self = Manager.PageContent.getContent().get('activeChild'),
				shared = self.properties.isPropertyShared('images'),
				imageProperties = self.getImageProperties();
				
			if ( ! imageProperties.length && shared) {
				var localeId = self.properties._shared_properties.images.locale,
					locale = Supra.data.getLocale(localeId),
					localeTitle = locale ? locale.title : localeId;

				Supra.Manager.executeAction('Confirmation', {
					'message': Supra.Intl.get(['form', 'shared_gallery_unavailable']).replace('{{ localeTitle }}', localeTitle),
					'useMask': true,
					'buttons': [{
						'id': 'ok', 
						'label': 'OK'
					}]
				});
				
				return;
			}
			
			// if gallery is based on shared properties, then we will output a notice about that
			if (force !== true && shared) {
				Supra.Manager.executeAction('Confirmation', {
					'message': Supra.Intl.get(['form', 'shared_property_notice']),
					'useMask': true,
					'buttons': [
						{
							'id': 'yes', 
							'label': Supra.Intl.get(['buttons', 'continue']),
							'click': function() {
								self.openExternalManager(true);
							}
						},
						{'id': 'no', 'label': Supra.Intl.get(['buttons', 'cancel'])}
					]
				});
				
				return;
			}
			
			if (!self.properties.hasTopGroups()) {
				self.properties.hidePropertiesForm({
					'keepToolbarButtons': true
				});
			} else {
				self.properties.hidePropertiesForm();
			}
			
			//Data
			var gallery_data = self.properties.getValues();
			gallery_data.images = gallery_data.images || [];
			
			//Show gallery
			Supra.Manager.executeAction('GalleryManager', {
				'data': gallery_data,
				'properties': self.getImageProperties(),
				'context': self,
				'shared': shared,
				'callback': function (data, changed) {
					if (changed) {
						this.unresolved_changes = true;
						
						//Show settings
						if (!this.properties.hasTopGroups()) {
							//Manager.PageContentSettings.set('frozen', false);
							self.properties.showPropertiesForm();
						}
						
						//Update data
						this.properties.setValues(data);
						this.reloadContent();
					}
				}
			});
		},
		
		/**
		 * Open media library sidebar
		 * @private
		 */
		openMediaLibrary: function () {
			
			Manager.getAction('MediaSidebar').execute({
				'onselect': Y.bind(function (event) {
					this.addImage(event.image);
					this.reloadContent();
				}, this),
				'onclose': Y.bind(function () {
					this.properties.showPropertiesForm();
					this.itemlist.blurInlineEditor();
					//button.set('loading', false);
				}, this)
			});
			
		},
		
		/**
		 * Hide settings form
		 * 
		 * @private
		 */
		hideSettingsForm: function () {
			var form = this.properties.get('form');
			if (form && form.get('visible')) {
				Manager.PageContentSettings.hide();
			}
		},	
		
		/**
		 * Add image to the gallery
		 */	
		addImage: function (image_data) {
			var values = this.properties.getValues(),
				images = (values && Y.Lang.isArray(values.images)) ? values.images : [],
				properties = this.getImageProperties(),
				property = null,
				image  = {'image': image_data, 'id': image_data.id, 'properties': {}};
			
			//Check if image doesn't exist in data already
			for(var i=0,ii=images.length; i<ii; i++) {
				if (images[i].image.id == image_data.id) return;
			}
			
			for(var i=0,ii=properties.length; i<ii; i++) {
				property = properties[i].id;
				image.properties[property] = image_data[property] || properties[i].value || '';
			}
			
			image.title = image_data.filename;
			
			images.push(image);
			
			this.properties.setValues({
				'images': images
			});
		},
		
		/**
		 * Returns image properties
		 * 
		 * @return List of image properties
		 * @type {Array}
		 * @private
		 */
		getImageProperties: function () {
			var block = this.getBlockInfo(),
				properties = block.properties,
				i = 0,
				ii = properties.length;
			
			for (; i<ii; i++) {
				if (properties[i].type === 'Gallery') {
					return properties[i].properties || [];
				}
			}
			
			return properties;
		},
		
		/**
		 * Process data and remove all unneeded before it's sent to server
		 * Called before save
		 * 
		 * @param {String} id Data ID
		 * @param {Object} data Data
		 * @return Processed data
		 * @type {Object}
		 */
		processData: function (data) {
			
			if (this.properties.isPropertyShared('images')) {
				data.images = [];
				
				return data; 
			}
			
			var images = [],
				image = {},
				properties = this.getImageProperties(),
				kk = properties.length;
			
			//Default data
			data.images = data.images || [];
			
			//Extract only image ID and properties, remove all other data
			for(var i=0,ii=data.images.length; i<ii; i++) {
				// deep clone
				image = Supra.mix({'properties': {}}, data.images[i], true);
				
				if (image.image && image.image.image) {
					// There is cropping and size information, leave it intact
					// only replace image info with id
					image.image.image = image.image.image.id;
				} else {
					// No cropping or size information
					delete(image.image);
				}
				
				images.push(image);
				for(var k=0; k<kk; k++) {
					image.properties[properties[k].id] = data.images[i].properties[properties[k].id] || '';
				}
			}
			
			if (images.length == 0) {
				images = 0;
			}
			
			data.images = images;
			return data;
		},
		
		reloadContent: function () {
			this.set('loading', true);
			this.properties.get('host').reloadContentHTML(
				function(editable) { 
					editable.set('loading', false);
				}
			);
		}
	});
	
	PageContent.Gallery = ContentGallery;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires:['supra.page-content-editable', 'supra.page-content-droptarget']});