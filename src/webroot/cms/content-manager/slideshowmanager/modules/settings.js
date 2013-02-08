YUI.add('slideshowmanager.settings', function (Y) {
	//Invoke strict mode
	"use strict";
	
	//Shortcut
	var Manager = Supra.Manager,
		Action = Manager.PageContent;
	
	/**
	 * Settings form
	 */
	function Settings (config) {
		Settings.superclass.constructor.apply(this, arguments);
	}
	
	Settings.NAME = 'slideshowmanager-settings';
	Settings.NS = 'settings';
	
	Settings.ATTRS = {
		// Visibility
		'visible': {
			'value': false,
			'setter': '_setVisible',
			'getter': '_getVisible'
		},
		'activeItemId': {
			'value': null,
			'setter': '_setActiveItemId'
		}
	};
	
	Y.extend(Settings, Y.Plugin.Base, {
		
		/**
		 * Widgets
		 * @type {Object}
		 * @private
		 */
		widgets: null,
		
		/**
		 * Value update in progress, don't fire events
		 * @type {Boolean}
		 * @private
		 */
		silentUpdatingValues: false,
		
		
		/**
		 * Automatically called by Base, during construction
		 * 
		 * @param {Object} config
		 * @private
		 */
		initializer: function(config) {
			this.widgets = {
				'form': null,
				'deleteButton': null
			};
			
			//Toolbar buttons
			Manager.getAction('PageToolbar').addActionButtons(Settings.NAME, []);
			Manager.getAction('PageButtons').addActionButtons(Settings.NAME, []);
		},
		
		/**
		 * Automatically called by Base, during destruction
		 * 
		 * @private
		 */
		destructor: function () {
			this.resetAll();
			this.destroyForm();
		},
		
		/**
		 * Reset cache, clean up
		 */
		resetAll: function () {
		},
		
		/**
		 * Fire remove event
		 * 
		 * @private
		 */
		fireRemoveEvent: function () {
			var id = this.get('activeItemId');
			this.fire('removeClick', {'data': {'id': id}})
		},
		
		
		/* ---------------------------- Form --------------------------- */
		
		
		/**
		 * Returns settings form
		 * 
		 * @returns {Object} Form
		 */
		getForm: function () {
			return this.widgets.form || this.createForm();
		},
		
		/**
		 * Returns non-inline properties
		 * 
		 * @returns {Array} List of non-inline properties
		 */
		getProperties: function () {
			var properties = this.get('host').options.properties,
				iframe = this.get('host').view.get('iframe'),
				filtered = [],
				i = 0,
				ii = properties.length;
			
			for (; i<ii; i++) {
				if (!properties[i].inline) {
					filtered.push(properties[i]);
				}
			}
			
			return filtered;
		},
		
		/**
		 * Create settigns form
		 * 
		 * @private
		 */
		createForm: function () {
			if (this.widgets.form) return this.widgets.form;
			
			//Get form placeholder
			var content = Manager.getAction('PageContentSettings').get('contentInnerNode');
			if (!content) return;
			
			//Properties form
			var properties = this.getProperties(),
				form_config = {
					'inputs': properties,
					'style': 'vertical',
					'slideshow': true
				};
			
			var form = new Supra.Form(form_config);
				form.render(content);
				form.hide();
				form.addClass('yui3-form-fill'),
				input = null;
			
			//Buttons input plugin
			input = form.getInput('buttons');
			if (input) {
				input.plug(Supra.SlideshowManagerViewButton, {});
			}
			
			//On input value change update inline inputs
			var ii = properties.length,
				i = 0,
				type = null,
				input = null;
			
			for (; i<ii; i++) {
				input = form.getInput(properties[i].id);
				input.after('valueChange', this._firePropertyChangeEvent, this, properties[i].id, input);
			}
			
			/*
			//Manage button
			var btn = this.widgets.manageButton = new Supra.Button({'label': Supra.Intl.get(['gallerymanager', 'manage']), 'style': 'small'});
				btn.render(form.get('contentBox'));
				btn.addClass('su-button-fill');
				btn.on('click', this.openMediaLibraryForReplace, this);
			*/
			
			//Delete button
			var button = this.widgets.deleteButton = new Supra.Button({'label': Supra.Intl.get(['slideshowmanager', 'delete_slide']), 'style': 'small-red'});
				button.render(form.getContentNode());
				button.addClass('su-button-delete');
				button.on('click', this.fireRemoveEvent, this);
			
			if (this.get('host').list.getItemCount() <= 1) {
				button.hide();
			}
			
			this.widgets.form = form;
			return form;
		},
		
		/**
		 * Destroy settings form
		 * 
		 * @private
		 */
		destroyForm: function () {
			if (this.settings_form) {
				var bounding = this.widgets.form.get('boundingBox');
				this.widgets.form.destroy();
				this.widgets.form = null;
				bounding.remove(true);
			}
		},
		
		/**
		 * Show form
		 * 
		 * @private
		 */
		showForm: function () {
			//Make sure PageContentSettings is rendered
			var form = this.getForm(),
				slideshow = form.get('slideshow'),
				action = Manager.getAction('PageContentSettings');
			
			if (!form) {
				if (action.get('loaded')) {
					if (!action.get('created')) {
						action.renderAction();
						this.showForm();
					}
				} else {
					action.once('loaded', function () {
						this.showForm();
					}, this);
					action.load();
				}
				return false;
			}
			
			action.execute(form, {
				'doneCallback': Y.bind(this.onSidebarDone, this),
				'toolbarActionName': Settings.NAME,
				
				'title': Supra.Intl.get(['slideshowmanager', 'sidebar_title']),
				'scrollable': false // we are using form slideshow, which will have scrollable
			});
			
			// Update form state
			slideshow.set('noAnimations', true);
			this.set('activeItemId', this.get('activeItemId'));
			slideshow.set('noAnimations', false);
		},
		
		/**
		 * When sidebar is closed stop editing associated input
		 * 
		 * @private
		 */
		onSidebarDone: function () {
			// Stop editing
			this.get('host').view.stopEditing();
			this.hide();
		},
		
		/**
		 * Hide form
		 * 
		 * @private
		 */
		hideForm: function () {
			var form = this.widgets.form;
			if (form && form.get('visible')) {
				Manager.PageContentSettings.hide();
			}
		},
		
		/**
		 * On item count change update UI
		 */
		updateItemCount: function () {
			var button = this.widgets.deleteButton,
				count  = this.get('host').list.getItemCount(); 
			
			if (button) {
				if (count > 1) {
					button.show();
				} else {
					button.hide();
				}
			}
		},
		
		
		/* ---------------------------- Data --------------------------- */
		
		
		/**
		 * activeItemId attribute setter
		 * 
		 * @param {String} id Active item ID
		 * @returns {String} New attribute value
		 * @private 
		 */
		_setActiveItemId: function (id) {
			var data   = this.get('host').data.getSlideById(id),
				form   = this.widgets.form;
			
			if (form) {
				this.silentUpdatingValues = true;
				form.setValues(data, 'id', true); // no encoding
				this.silentUpdatingValues = false;
			}
			
			this.updateItemCount();
			
			if (this.get('visible')) {
				var slideshow = form.get('slideshow'),
					rootSlideId = slideshow.getHistory()[0];
				
				// Scroll to root slide
				slideshow.set('slide', rootSlideId);
			}
			
			return id;
		},
		
		/**
		 * Fire data change event
		 * 
		 * @param {Object} event Event facade object
		 * @param {String} property Changed property name
		 * @param {Object} input Input which value changed
		 * @private
		 */
		_firePropertyChangeEvent: function (event, property, input) {
			if (this.silentUpdatingValues) return;
			
			var id   = this.get('activeItemId'),
				data = this.get('host').data,
				save = {},
				value = '';
			
			if (event.newVal !== event.prevVal && id && property) {
				save[property] = input.get('value');
				if (save[property]) {
					data.changeSlide(id, save);
				}
			}
		},
		
		
		/* ---------------------------- Show/hide --------------------------- */
		
		
		/**
		 * Show settings form
		 */
		show: function () {
			if (!this.get('visible')) {
				this.set('visible', true);
			}
		},
		
		/**
		 * Hide settings form
		 */
		hide: function () {
			if (this.get('visible')) {
				this.set('visible', false);
			}
		},
		
		/**
		 * Visible attribute setter
		 * 
		 * @param {Boolean} visible Visibility state
		 * @returns {Boolean} New visibility state
		 * @private
		 */
		_setVisible: function (visible) {
			if (visible) {
				this.showForm();
			} else {
				this.hideForm();
			}
			return true;
		},
		
		/**
		 * Visible attribute getter
		 * 
		 * @returns {Boolean} True if settings form is visible, otherwise false
		 * @private
		 */
		_getVisible: function () {
			var form = this.widgets.form;
			return form && form.get('visible');
		}
		
	});
	
	Supra.SlideshowManagerSettings = Settings;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires: ['plugin', 'supra.form']});