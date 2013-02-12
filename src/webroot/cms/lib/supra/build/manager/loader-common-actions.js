YUI.add('supra.manager-loader-actions', function (Y) {
	//Invoke strict mode
	"use strict";
	
	/**
	 * Set common action base paths
	 * 
	 * Define here all actions which are reusable between 'managers' to allow them loading
	 * without specifying path each time
	 */
	Supra.Manager.Loader.setActionBasePaths({
		'Header': '/content-manager',
		'SiteMap': '/content-manager',
		'SiteMapRecycle': '/content-manager',
		'MediaLibrary': '/media-library',
		'MediaSidebar': '/media-library',
		'PageToolbar': '/content-manager',
		'PageButtons': '/content-manager',
		'EditorToolbar': '/content-manager',
		'Page': '/content-manager',
		'PageContentSettings': '/content-manager',
		'PageSourceEditor': '/content-manager',
		'LayoutContainers': '/content-manager',
		'Confirmation': '/content-manager',
		'Login': '/login',
		'MyPassword': '/login',
		'LinkManager': '/content-manager',
		'UserAvatar': '/internal-user-manager',
		'Applications': '/dashboard',
		'BrowserSupport': '/dashboard',
		'Blog': '/blog-manager',
		'Sites': '-local/site-list-manager' // This is rather hacky.
	});

	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires: ['supra.manager-loader']});