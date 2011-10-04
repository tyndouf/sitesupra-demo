//Invoke strict mode
"use strict";

/**
 * Main manager action, initiates all other actions
 */
Supra(function (Y) {

	//Shortcut
	var Manager = Supra.Manager;
	var Action = Manager.Action;
	
	/**
	 * Default page toolbar buttons
	 * @type {Object}
	 */
	var DEFAULT_TOOLBAR = {
		'Root': [
			{
				'id': 'sitemap',
				'title': SU.Intl.get(['sitemap', 'button']),
				'icon': '/cms/lib/supra/img/toolbar/icon-sitemap.png',
				'action': 'Root',
				'actionFunction': 'routeSiteMapSave',
				'type': 'button'	/* Default is 'toggle' */
			}
		],
		'Page': [
			{
				'id': 'blockbar',
				'title': SU.Intl.get(['insertblock', 'button']),
				'icon': '/cms/lib/supra/img/toolbar/icon-insert.png',
				'action': 'PageInsertBlock',
				'permissions': ['block', 'insert']
			},
			{
				'id': 'history',
				'title': SU.Intl.get(['history', 'button']),
				'icon': '/cms/lib/supra/img/toolbar/icon-history.png',
				'action': 'PageHistory'
			},
			{
				'id': 'settings',
				'title': SU.Intl.get(['settings', 'button']),
				'icon': '/cms/lib/supra/img/toolbar/icon-settings.png',
				'action': 'PageSettings'
			},
			{
				'id': 'blocksview',
				'title': SU.Intl.get(['blocks', 'button']),
				'icon': '/cms/lib/supra/img/toolbar/icon-blocks.png',
				'action': 'BlocksView'
			}
		]
	};
	
	/**
	 * Default buttons
	 * @type {Object}
	 */
	var DEFAULT_BUTTONS = {
		'Root': [
			{
				'id': 'edit',
				'callback': function () {
					if (Manager.Page.isPage()) {
						Manager.Page.lockPage();
					} else {
						Manager.Template.lockTemplate();
					}
				}
			},
			{
				'id': 'unlock',
				'visible': false,
				'callback': function () {
					//Force page unlock
					Manager.Page.unlockPage(true);
				}
			}
		]
	};
	
	Supra.Manager.getAction('PageToolbar').set('buttons', DEFAULT_TOOLBAR);
	Supra.Manager.getAction('PageButtons').set('buttons', DEFAULT_BUTTONS);
	
	//Create Action class
	new Action({
		
		/**
		 * Unique action name
		 * @type {String}
		 */
		NAME: 'Root',
		
		/**
		 * Dependancies
		 * @type {Array}
		 */
		DEPENDANCIES: ['Page', 'Template', 'PageToolbar', 'PageButtons', 'PageContent'],
		
		/**
		 * Action doesn't have stylesheet
		 * @type {Boolean}
		 * @private
		 */
		HAS_STYLESHEET: false,
		
		/**
		 * Action doesn't have template
		 * @type {Boolean}
		 * @private
		 */
		HAS_TEMPLATE: false,
		
		
		ROUTE_SITEMAP:		'/h/sitemap',
		ROUTE_PAGE:			'/h/page/:page_id',
		ROUTE_PAGE_EDIT:	'/h/page/:page_id/edit',
		ROUTE_PAGE_CONT:	'/h/page/:page_id/edit/:block_id',
		
		ROUTE_PAGE_R: 		/^\/h\/page\/([^\/]+)$/,
		ROUTE_PAGE_EDIT_R: 	/^\/h\/page\/([^\/]+)\/edit$/,
		ROUTE_PAGE_CONT_R: 	/^\/h\/page\/([^\/]+)\/edit\/([^\/]+)$/,
		
		
		/**
		 * Y.Controller routers
		 */
		initialize: function () {
			//Routes
			this.route('/', 'routePage');
			this.route(this.ROUTE_SITEMAP, 'routeSitemap');
			this.route(this.ROUTE_PAGE, 'routePage');
		},
		
		/**
		 * Load page
		 */
		routePage: function (req) {
			//Close sitemap if needed
			if (Manager.getAction('SiteMap').get('visible')) {
				Manager.getAction('SiteMap').hide();
			} else {
				this.execute();
			}
			
			//Load page
			var page_id = req.params.page_id,
				page_data = Manager.Page.getPageData();
			
			if (page_id && ( ! page_data || page_id != page_data.id)) {
				//Open page; evt.data is in format  {'id': 1}
				Manager.getAction('Page').execute({'id': page_id});
			}
			
			//Make sure other routes are also executed
			req.next();
		},
		/**
		 * Open sitemap
		 */
		routeSitemap: function (req) {
			Supra.Manager.executeAction('SiteMap');
			
			//Make sure other routes are also executed
			req.next();
		},
		
		/**
		 * Change route to sitemap
		 */
		routeSiteMapSave: function () {
			this.save(this.ROUTE_SITEMAP);
		},
		
		
		
		
		/**
		 * Bind Actions together
		 */
		render: function () {
			this.addChildAction('Page');
			
			//Show loading screen until content is loaded (last executed action)
			Y.one('body').addClass('loading');
			
			SU.Manager.getAction('PageContent').after('iframeReady', function () {
				Y.one('body').removeClass('loading');
			});
			
			//On page unload destroy everything???
			Y.on('beforeunload', function () {
			    this.destroy();
			}, this);
			
			//Load page after execute
			this.on('render', function () {
				//Search in path "/r/page/:page_id"
				var page_id = this.getPath().match(this.ROUTE_PAGE_R);
				if (page_id) {
					page_id = {'id': page_id[1]};
				} else {
					page_id = Supra.data.get('page', {'id': 0});
				}
				 
				SU.Manager.executeAction('Page', page_id);
				SU.Manager.executeAction('Template');
				
				//Search /h/sitemap in path
				if (this.getPath() == this.ROUTE_SITEMAP) {
					SU.Manager.executeAction('SiteMap');
				}
			});
			
			this.bindSiteMap();
		},
		
		/**
		 * Bind SiteMap action to Page
		 */
		bindSiteMap: function () {
			//When page is selected in sitemap load it
			Manager.getAction('SiteMap').on('page:select', function (evt) {
				//Change path
				this.save(this.ROUTE_PAGE.replace(':page_id', evt.data.id));
			}, this);
		},
		
		
		/**
		 * Execute action
		 */
		execute: function () {
			var toolbar = Manager.getAction('PageToolbar'),
				buttons = Manager.getAction('PageButtons'),
				content = Manager.getAction('PageContent');
			
			if (toolbar.get('created')) {
				toolbar.setActiveAction(this.NAME);
			}
			if (buttons.get('created')) {
				buttons.setActiveAction(this.NAME);
			}
			
			if (content.get('created')) { 
				content.stopEditing();
			}
		}
	});
	
});