//Invoke strict mode
"use strict";

YUI.add('supra.page-content-properties', function (Y) {
	
	//Shortcuts
	var Manager = SU.Manager,
		Action = Manager.Action;
	
	
	/*
	 * Container action
	 * Used to insert form into LayoutRightContainer, automatically adjusts layout and
	 * shows / hides other LayoutRightContainer child actions when action is shown / hidden
	 */
	
	//Add as right bar child
	Manager.getAction('LayoutRightContainer').addChildAction('PageContentSettings');
	
	new Action({
		// Unique action name
		NAME: 'PageContentSettings',
		
		// No need for template
		HAS_TEMPLATE: false,
		
		// Load stylesheet
		HAS_STYLESHEET: false,
		
		
		
		//Options
		options: null,
		
		// Form instance
		form: null,
		
		// Done button callback
		callback: null,
		
		// Editor toolbar was visible
		editor_toolbar_visible: false,
		
		// Set page button visibility
		tooglePageButtons: function (visible) {
			var buttons = SU.Manager.PageButtons.buttons[this.NAME];
			for(var i=0,ii=buttons.length; i<ii; i++) buttons[i].set('visible', visible);
		},
		
		// Render action container
		render: function () {
			var node = Y.Node.create('<div></div>');
			this.getPlaceHolder().append(node);
			this.set('srcNode', new Y.NodeList(node));
			
			//Create toolbar buttons
			Manager.getAction('PageToolbar').addActionButtons(this.NAME, []);
			Manager.getAction('PageButtons').addActionButtons(this.NAME, [{
				'id': 'done',
				'context': this,
				'callback': function () {
					if (Y.Lang.isFunction(this.callback)) {
						this.callback();
					}
				}
			}]);
		},
		
		// Hide
		hide: function () {
			Action.Base.prototype.hide.apply(this, arguments);
			
			//Hide buttons
			Manager.getAction('PageToolbar').unsetActiveAction(this.NAME);
			Manager.getAction('PageButtons').unsetActiveAction(this.NAME);
			
			//Hide action
			Manager.getAction('LayoutRightContainer').unsetActiveAction(this.NAME);
			
			//Hide form
			if (this.form) {
				if (this.editor_toolbar_visible) {
					Manager.EditorToolbar.execute();
				}
				
				this.form.hide();
				this.form = null;
				this.callback = null;
				this.editor_toolbar_visible = false;
			}
			
		},
		
		// Execute action
		execute: function (form, options) {
			var options = this.options = options || {
				'doneCallback': null,
				'hideEditorToolbar': false
			};
			
			//Show buttons
			Manager.getAction('PageToolbar').setActiveAction(this.NAME);
			Manager.getAction('PageButtons').setActiveAction(this.NAME);
			
			//Show action
			Manager.getAction('LayoutRightContainer').setActiveAction(this.NAME);
			
			//Set form
			if (form) {
				if (this.form) this.form.hide();
				this.form = form;
				this.callback = options.doneCallback;
				form.show();
				
				this.tooglePageButtons(!!options.doneCallback);
				
				if (options.hideEditorToolbar) {
					this.editor_toolbar_visible = Manager.EditorToolbar.get('visible');
					if (this.editor_toolbar_visible) {
						Manager.EditorToolbar.hide();
					}
				}
			}
		}
	});
	
	/*
	 * Properties plugin
	 */
	function Properties () {
		Properties.superclass.constructor.apply(this, arguments);
		this._original_values = null;
	}
	
	Properties.NAME = 'page-content-properties';
	Properties.NS = 'properties';
	Properties.ATTRS = {
		/*
		 * Property values
		 */
		'data': {
			'value': {},
			'setter': '_setData',
			'getter': '_getData'
		},
		
		/*
		 * List of editable properties
		 */
		'properties': {
			'value': {}
		},
		
		/*
		 * Supra.Form instance
		 */
		'form': {
			'value': null
		},
		
		/**
		 * Supra.Slideshow instance, used for grouping inputs
		 */
		'slideshow': {
			'value': null
		},
		
		/*
		 * Automatically show form when content is being edited
		 */
		'showOnEdit': {
			'value': false
		},
		
		/*
		 * Normal property has changed
		 */
		'normalChanged': {
			'value': false
		},
		
		/*
		 * Inline property has changed
		 */
		'inlineChanged': {
			'value': false
		}
	};
	
	Y.extend(Properties, Y.Plugin.Base, {
		
		_node_content: null,
		
		_button_delete: false,
		
		_original_values: null,
		
		destructor: function () {
			var form = this.get('form');
			
			this.get('host').unsubscribe('block:save', this.onBlockSaveCancel, this);
			this.get('host').unsubscribe('block:cancel', this.onBlockSaveCancel, this);
			
			form.destroy();
		},
		
		initializer: function (config) {
			var data = this.get('host').get('data');
			if (!data || !('type' in data)) return;
			
			var type = data.type,
				//Make sure orginal block data is not modified
				block = Supra.mix({}, Manager.Blocks.getBlock(type), true);
			
			if (!block) return;
			this.set('properties', block.properties);
			
			//Create right bar container action if it doesn't exist
			var PageContentSettings = Manager.getAction('PageContentSettings');
			PageContentSettings.execute();
			PageContentSettings.hide();
			
			//Bind to editing-start and editing-end events
			if (this.get('showOnEdit')) {
				this.get('host').on('editing-start', this.showPropertiesForm, this);
				setTimeout(Y.bind(this.showPropertiesForm, this), 50);
			}
			
			//Hide form when editing ends
			this.get('host').on('editing-end', this.hidePropertiesForm, this);
			
			//Properties form
			this.initializeProperties();
			var form = this.get('form');
			
			//On block save/cancel update 'changed' attributes
			this.get('host').on('block:save', this.onBlockSaveCancel, this);
			this.get('host').on('block:cancel', this.onBlockSaveCancel, this);
			
			//Form heading
			var title = block.title;
			if (!title) {
				//Convert ID into title
				title = this.get('host').getId();
				title = title.replace(/[\-\_\.]/g, ' ');
				title = title.substr(0,1).toUpperCase() + title.substr(1);
			}
			
			if (this.get('host').isInstanceOf('page-content-list')) {
				title += ' ' + SU.Intl.get(['page', 'placeholder_properties']);
			} else {
				title += ' ' + SU.Intl.get(['page', 'block_properties']);
			}
			
			var heading = Y.Node.create('<h2>' + Y.Escape.html(title) + '</h2>');
			form.get('contentBox').insert(heading, 'before');
			
			
			//Buttons
			var footer = Y.Node.create('<div class="yui3-sidebar-footer"></div>');
			
			form.get('contentBox').addClass('yui3-sidebar-content')
								  .addClass('scrollable')
								  .addClass('has-header')
								  .addClass('has-footer')
								  .insert(footer, 'after');
			
			//Delete button
			var btn = new Supra.Button({'label': SU.Intl.get(['buttons', 'delete']), 'style': 'mid-red'});
				btn.render(footer).on('click', this.deleteContent, this);
			
			if (!Supra.Authorization.isAllowed(['block', 'delete'], true)) {
				btn.hide();
				form.get('contentBox') .removeClass('has-footer');
			}
			
			//Don't show delete button if block or placeholder is closed
			var host = this.get('host');
			if (host.isClosed() || host.isParentClosed()) {
				btn.hide();
			}
		},
		
		/**
		 * Initialize properties form
		 */
		initializeProperties: function () {
			var form_config = {'autoDiscoverInputs': false, 'inputs': [], 'style': 'vertical'},
				properties = this.get('properties'),
				host = this.get('host'),
				host_node = host.getNode();
			
			var host_properties = {
				'doc': host.get('doc'),
				'win': host.get('win'),
				'toolbar': Supra.Manager.EditorToolbar.getToolbar()
			};
			
			//Slideshow is used for grouping properties
			var slideshow = this.initializeSlideshow(),
				slide_main = null,
				slide_id = 'propertySlideMain',
				slide = slide_main = slideshow.getSlide(slide_id);
			
			//Properties
			for(var i=0, ii=properties.length; i<ii; i++) {
				if (properties[i].inline) {
					//Find inside container (#content_html_111) inline element (#content_html_111_html1)
					host_properties.srcNode = host_node.one('#' + host_node.getAttribute('id') + '_' + properties[i].id);
					host_properties.contentBox = host_properties.srcNode;
					host_properties.boundingBox = host_properties.srcNode;
					form_config.inputs.push(SU.mix({}, host_properties, properties[i]));
				} else {
					//Grouping
					if (properties[i].group) {
						//Get slide or create it
						slide_id = properties[i].group.replace(/[^a-z0-9\-\_]/ig, '');
						slide = slideshow.getSlide(slide_id);
					} else {
						slide_id = 'propertySlideMain';
						slide = slide_main;
					}
					
					//Create slide and add button to inputs
					if (!slide && properties[i].group) {
						slide = slideshow.addSlide(slide_id);
						form_config.inputs.push({
							'id': slide_id + '_button',
							'label': properties[i].group,
							'type': 'Button',
							'slideshow': slideshow,
							'slideId': slide_id,
							'containerNode': slide_main
						});
					}
					
					//Set input container node to that slide
					properties[i].containerNode = slide;
					form_config.inputs.push(properties[i]);
				}
			}
			
			//Add back button for slideshow
			this.initializeButtons(slideshow);
			
			//Create form
			var form = this.initializeForm(form_config);
			
			//Bind to change event
			var inputs = form.getInputs();
			for(var id in inputs) {
				inputs[id].on('change', this.onPropertyChange, this);
			}
		},
		
		initializeButtons: function (slideshow) {
			var container = Manager.PageContentSettings.getContainer(),
				back_button = null;
			
			container.append('<div class="yui3-sidebar-buttons hidden"><button>' + Supra.Intl.get(['buttons', 'back']) + '</button></div>');
			
			back_button = new Supra.Button({'srcNode': container.one('button')});
			back_button.on('click', slideshow.scrollBack, slideshow);
			back_button.render();
			
			//On slideshow slide change update button
			slideshow.on('slideChange', this.onSlideshowSlideChange, this);
		},
		
		/**
		 * Create slideshow
		 */
		initializeSlideshow: function () {
			//Slideshow is used for grouping properties
			var slideshow = new Supra.Slideshow(),
				slide_id = null,
				slide = slideshow.addSlide('propertySlideMain'),
				slide_main = slide;
			
			this.set('slideshow', slideshow);
			return slideshow;
		},
		
		/**
		 * Create form
		 */
		initializeForm: function (form_config) {
			var form = new Supra.Form(form_config),
				data = this.get('data').properties,
				slideshow = this.get('slideshow');
			
			form.render(Manager.PageContentSettings.getContainer());
			form.get('boundingBox').addClass('yui3-form-properties');
			form.hide();
			
			slideshow.render(form.get('contentBox'));
			
			form.setValues(data, 'id');
			
			this.set('form', form);
			return form;
		},
		
		/**
		 * On Slideshow slide change show
		 */
		onSlideshowSlideChange: function (evt) {
			var container = Manager.PageContentSettings.getContainer();
			if (evt.newVal == 'propertySlideMain') {
				container.one('.yui3-sidebar-content').removeClass('has-buttons');
				container.one('.yui3-sidebar-buttons').addClass('hidden');
			} else {
				container.one('.yui3-sidebar-content').addClass('has-buttons');
				container.one('.yui3-sidebar-buttons').removeClass('hidden');
			}
			
		},
		
		/**
		 * Destroy property and recreate it
		 *
		 * @param {String} id Property ID
		 * @param {Object} value New value
		 */
		resetProperty: function (id, value) {
			var form = this.get('form'),
				properties = this.get('properties'),
				i = 0,
				ii = properties.length,
				inputs = form.getInputs(),
				
				host = this.get('host'),
				host_node = host.getNode(),
				
				property = null,
				config = {};
			
			for(; i<ii; i++) {
				if (properties[i].id == id) {
					property = properties[i];
					break;
				}
			}
			
			if (property && id in inputs) {
				var srcNode = host_node.one('#' + host_node.getAttribute('id') + '_' + property.id);
				
				//Get input config
				config = Supra.mix({
					'doc': host.get('doc'),
					'win': host.get('win'),
					'toolbar': Supra.Manager.EditorToolbar.getToolbar(),
					'srcNode': srcNode,
					'contentBox': srcNode,
					'boundingBox': srcNode
				}, property, {
					'value': value ? value : property.value
				});
				
				//Destroy old input
				inputs[id].destroy();
				
				//Create new input 
				inputs[id] = form.factoryField(config);
				inputs[id].render();
				
				//Restore value
				inputs[id].set('value', value);
				
				return inputs[id];
			}
			
			return null;
		},
		
		/**
		 * On property change update state
		 * 
		 * @param {Object} evt
		 */
		onPropertyChange: function (evt) {
			var normalChanged = this.get('normalChanged'),
				inlineChanged = this.get('inlineChanged');
			
			if (normalChanged && inlineChanged) return;
			
			var input = evt.target,
				id = input.get('id'),
				properties = this.get('properties');
			
			for(var i=0,ii=properties.length; i<ii; i++) {
				if (properties[i].id == id) {
					if (properties[i].inline) {
						if (!inlineChanged) {
							this.set('inlineChanged', true);
						}
					} else {
						if (!normalChanged) {
							this.set('normalChanged', true);
						}
					}
					break;
				}
			}
		},
		
		/**
		 * Save changes
		 */
		savePropertyChanges: function () {
			this._original_values = this.getValues();
			this.get('host').fire('properties:save');
			
			this.set('normalChanged', false);
			SU.Manager.PageContentSettings.hide();
			
			//Property which affects inline content may have
			//changed, need to reload block content 
			this.get('host').reloadContentHTML();
		},
		
		/**
		 * On block save/cancel
		 */
		onBlockSaveCancel: function () {
			this.set('normalChanged', false);
			this.set('inlineChanged', false);
		},
		
		/**
		 * Delete content
		 */
		deleteContent: function () {
			Supra.Manager.executeAction('Confirmation', {
				'message': SU.Intl.get(['page', 'delete_block_confirmation']),
				'useMask': true,
				'buttons': [
					{'id': 'delete', 'label': Supra.Intl.get(['buttons', 'yes']), 'context': this, 'click': function () {
						var host = this.get('host');
						var parent = host.get('parent');
						
						//Discard all changes
						host.unresolved_changes = false;
						
						//Close form
						this.hidePropertiesForm();
						
						//Trigger event; plugins or other contents may use this
						this.fire('delete');
						
						//Remove content
						parent.removeChild(host);
						
					}},
					{'id': 'no'}
				]
			});
		},
		
		/**
		 * Show properties form
		 */
		showPropertiesForm: function () {
			var form = this.get('form');
			Manager.getAction('PageContentSettings').execute(form, {
				'doneCallback': Y.bind(this.savePropertyChanges, this),
				'hideEditorToolbar': true
			});
		},
		
		/**
		 * Hide properties form
		 */
		hidePropertiesForm: function () {
			this.get('form').hide();
			Manager.getAction('PageContentSettings').hide();
		},
		
		/**
		 * Property data setter
		 * 
		 * @param {Object} data
		 * @private
		 */
		_setData: function (data) {
			var form = this.get('form'),
				data = Supra.mix({}, data);
			
			this._original_values = data.properties;
			this.setValues(data.properties);
			return data;
		},
		
		/**
		 * Property data getter
		 * 
		 * @return Property data
		 * @type {Object]
		 * @private
		 */
		_getData: function (data) {
			var data = data || {};
			data.properties = this.getValues();
			return data;
		},
		
		/**
		 * Returns all property values
		 * 
		 * @return Values
		 * @type {Object}
		 */
		getValues: function () {
			var form = this.get('form');
			if (form) {
				return form.getValues('id');
			} else {
				return {};
			}
		},
		
		/**
		 * Returns all non-inline property values
		 * 
		 * @return Values
		 * @type {Object}
		 */
		getNonInlineValues: function (values) {
			var values = values ? values : this.getValues(),
				properties = this.get('properties'),
				out = {};
			
			for(var i=0,ii=properties.length; i<ii; i++) {
				if (!properties[i].inline) out[properties[i].id] = values[properties[i].id];
			}
			
			return out;
		},
		
		setValues: function (values) {
			var form = this.get('form');
			
			if (form) {
				var page_data = Manager.Page.getPageData(),
					locked_input = form.getInput('__locked__');
				
				if (page_data.type != 'page') {
					//Templates have "locked" input
					locked_input.set('disabled', false).set('visible', true);
				} else {
					//Pages don't have "locked" input
					locked_input.set('disabled', true).set('visible', false);
				}
				
				form.setValuesObject(values, 'id');
			}
		},
		
		setNonInlineValues: function (values) {
			var values = this.getNonInlineValues(values);
			this.setValues(values);
		},
		
		/**
		 * Returns all property values processed for saving
		 * 
		 * @return Values
		 * @type {Object}
		 */
		getSaveValues: function () {
			var form = this.get('form');
			if (form) {
				return form.getValues('id', true);
			} else {
				return {};
			}
		},
		
		/**
		 * Returns all non-inline property values for saving
		 * 
		 * @return Values
		 * @type {Object}
		 */
		getNonInlineSaveValues: function (values) {
			var values = values ? values : this.getSaveValues(),
				properties = this.get('properties'),
				out = {};
			
			for(var i=0,ii=properties.length; i<ii; i++) {
				if (!properties[i].inline) out[properties[i].id] = values[properties[i].id];
			}
			
			return out;
		}
		
	});
	
	Manager.PageContent.PluginProperties = Properties;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires:['widget', 'plugin', 'supra.button', 'supra.input', 'supra.input-inline-html', 'supra.input-inline-string', 'supra.slideshow']});