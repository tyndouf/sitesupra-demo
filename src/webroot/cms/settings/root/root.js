//Invoke strict mode
"use strict";

/**
 * Main manager action, initiates all other actions
 */
Supra(
	
	'supra.button-group',
	'supra.slideshow',
	
function (Y) {

	//Shortcut
	var Manager = Supra.Manager;
	var Action = Manager.Action;
	
	
	//Create Action class
	new Action(Action.PluginMainContent, {
		
		/**
		 * Unique action name
		 * @type {String}
		 */
		NAME: 'Root',
		
		/**
		 * Action has stylesheet
		 * @type {Boolean}
		 * @private
		 */
		HAS_STYLESHEET: true,
		
		/**
		 * Action has template
		 * @type {Boolean}
		 * @private
		 */
		HAS_TEMPLATE: true,
		
		/**
		 * Dependancies
		 * @type {Array}
		 */
		DEPENDANCIES: ['Header', 'PageToolbar', 'PageButtons'],
		
		
		
		widgets: {
			//Tab button group
			'tabs': null,
			//Tab slideshow
			'slideshow': null,
			
			//"Site name" tab form
			'formName': null,
			//"Site name" tab footer
			'footerName': null,
			
			//Domain tab form
			'formDomain': null,
			//Domain tab footer
			'footerDomain': null,
			
			//Email tab form
			'formEmail': null,
			//Email tab footer
			'footerEmail': null,
			
			//Analytics tab form
			'formAnalytics': null,
			//Analytics tab footer
			'footerAnalytics': null,
			
			//Delete site button
			'buttonDeleteSite': null
		},
		
		
		
		/**
		 * 
		 * 
		 * @private
		 */
		initialize: function () {
			this.widgets.tabs = new Supra.ButtonGroup({
				'srcNode': this.one('div.nav-tabs')
			});
			this.widgets.slideshow = new Supra.Slideshow({
				'srcNode': this.one('div.slideshow')
			});
			this.widgets.buttonDeleteSite = new Supra.Button({
				'srcNode': this.one('button.delete-site')
			});
		},
		
		/**
		 * Bind Actions together
		 * 
		 * @private
		 */
		render: function () {
			
			this.widgets.tabs.render();
			this.widgets.tabs.on('selectionChange', function (evt) {
				this.widgets.slideshow.set('slide', evt.newVal[0].id);
			}, this);
			
			this.widgets.slideshow.render();
			
			this.widgets.buttonDeleteSite.render();
			this.widgets.buttonDeleteSite.on('click', this.deleteSite, this);
			
			this.renderSiteNameContent();
			this.renderDomainContent();
			this.renderEmailContent();
			this.renderAnalyticsContent();
			
			this.loadData();
		},
		
		/**
		 * Load all settings data
		 * 
		 * @private
		 */
		loadData: function () {
			Supra.io(this.getDataPath('dev/load'), function (data, success) {
				
				if (data) {
					var name	= null,
						group	= null,
						form	= null;
					
					for(var group in data) {
						name = group.substr(0, 1).toUpperCase() + group.substr(1);
						form = this.widgets['form' + name];
						
						if (form) {
							form.setValues(data[group]);
						}
					}
				}
				
			}, this);
		},
		
		/**
		 * Render form
		 * 
		 * @private
		 */
		renderForm: function (name) {
			var container = Y.one('#tabContent' + name);
			
			var form = this.widgets['form' + name] = new Supra.Form({
				'srcNode': container.one('form')
			});
			
			var footer = this.widgets['footer' + name] = new Supra.Footer({
				'srcNode': container.one('div.footer')
			});
			
			form.render();
			footer.render();
			
			form.on('submit', this.saveForm, this, {'name': name});
		},
		
		/**
		 * Save form values
		 */
		saveForm: function (e, params) {
			var name	= params.name,
				form	= e.target,
				footer	= this.widgets['footer' + name],
				
				uri		= this.getDataPath('dev/save'),
				data	= {};
			
			data[name.toLowerCase()] = form.getSaveValues('name');
			
			form.set('disabled', true);
			footer.getButton('save').set('loading', true);
			
			Supra.io(uri, {
				'method': 'post',
				'data': data,
				'context': this,
				'on': {
					'complete': function () {
						form.set('disabled', false);
						footer.getButton('save').set('loading', false);
					}
				}
			});
		},
		
		/**
		 * Create site name tab content widgets
		 * 
		 * @private
		 */
		renderSiteNameContent: function () {
			this.renderForm('Name');
		},
		
		/**
		 * Create domain tab content widgets
		 * 
		 * @private
		 */
		renderDomainContent: function () {
			this.renderForm('Domain');
		},
		
		/**
		 * Create domain tab content widgets
		 * 
		 * @private
		 */
		renderEmailContent: function () {
			this.renderForm('Email');
		},
		
		/**
		 * Create domain tab content widgets
		 * 
		 * @private
		 */
		renderAnalyticsContent: function () {
			this.renderForm('Analytics');
			var form = this.widgets.formAnalytics;
			
			//On type change show/hide key and source inputs
			form.getInput('type').on('valueChange', function (e) {
				if (e.newVal == '1') {
					this.getInput('key').show();
					this.getInput('source').hide();
				} else {
					this.getInput('key').hide();
					this.getInput('source').show();
				}
			}, form);
			
			form.getInput('source').hide();
		},
		
		
		/**
		 * Delete site
		 */
		deleteSite: function () {
			Supra.Manager.executeAction('Confirmation', {
				'message': Supra.Intl.get(['settings', 'delete_site_confirmation']),
				'buttons': [
					{'id': 'yes', 'style': 'small-red', 'click': this.deleteSiteConfirmed, 'context': this},
					{'id': 'no'}
				]
			});
		},
		
		/**
		 * Actually delete site after confirmation
		 * @private
		 */
		deleteSiteConfirmed: function () {
			//Disable all widgets
			this.setDisabled(true);
			this.widgets.buttonDeleteSite.set('loading', true);
			
			//Send request
			Supra.io(this.getDataPath('dev/delete'), {
				'method': 'post',
				'context': this,
				'on': {
					'complete': this.deleteSiteComplete
				}
			})
		},
		
		/**
		 * Handle delete site request response
		 * 
		 * @param {Object} data Response data
		 * @param {Boolean} status Response status
		 * @private
		 */
		deleteSiteComplete: function (data, status) {
			if (status) {
				
				//@TODO
				
			} else {
				this.setDisabled(false);
				this.widgets.buttonDeleteSite.set('loading', false);
			}
		},
		
		/**
		 * Disable or enable all widgets
		 * 
		 * @param {Boolean} disabled If true all widgets will be disabled, otherwise enabled
		 */
		setDisabled: function (disabled) {
			//Enable all widgets
			var widgets	= this.widgets,
				widget	= null,
				i		= null;
			
			for(var i in widgets) {
				widget = widgets[i];
				if ('disabled' in widget.getAttrs()) {
					widget.set('disabled', disabled);
				}
			}
		},
		
		
		
		/**
		 * Execute action
		 */
		execute: function () {
			//Remove loading icon
			Y.one('body').removeClass('loading');
			
			this.show();
		}
	});
	
});