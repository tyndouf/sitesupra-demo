/*
 * @version 1.0.1
 */
"use strict";

(function () {
	var definition = function ($) {

		var _super = $.app.module.proto;
		
		$.app.AjaxContent = $.app.module({
			
			/**
			 * Default method for 'trigger'
			 * @type {String}
			 */
			'default_method': 'reload',
			
			/**
			 * Initial content
			 * @type {String}
			 */
			'default_content': '',
			
			/**
			 * Request URL
			 * @type {String}
			 * @private
			 */
			'url': '',
			
			/**
			 * Request method
			 * @type {String}
			 */
			'method': 'get',
			
			/**
			 * Request response data type
			 * @type {String}
			 */
			'reload_response_type': 'html',
			
			/**
			 * Initialize module
			 * 
			 * @param {Object} element
			 * @param {Object} options
			 * @constructor
			 */
			'init': function (element, options) {
				_super.init.apply(this, arguments);
				
				this.default_content = this.element.html();
				this.url = this.options.url;
			},
			
			/**
			 * Reload HTML content from the server
			 * 
			 * @param {Object} params Additional request parameters which will be sent to server. Optional
			 */
			'reload': function (params) {
				//Reload content
				this.beforeReload();
				
				$.ajax(this.url, {
					'cache': false,
					'type': this.method,
					'data': params,
					'dataType': this.reload_response_type
				}).done(this.proxy(this.onReload));
				
				//Prevent default behaviour
				return false;
			},
			
			/**
			 * Reset HTML content to the default/initial HTML
			 */
			'reset': function () {
				this.beforeReload();
				this.element.html(this.default_content);
				this.afterReload();
			},
			
			/**
			 * Before reload destroy children element module instances
			 * 
			 * @private
			 */
			'beforeReload': function () {
				$.app.cleanup(this.element);
			},
			
			/**
			 * On reload response set html
			 * 
			 * @private
			 */
			'onReload': function (html) {
				this.element.html(html);
				this.afterReload();
			},
			
			/**
			 * On reload complete instantiate modules inside new content
			 * 
			 * @private
			 */
			'afterReload': function () {
				$.app.parse(this.element);
			}
			
		});
		
		return $.app.AjaxContent;

	};

	// AMD suppot
	if (typeof define === 'function' && define.amd) {
		define(['jquery', 'app/app'], definition);
	} else {
		definition(jQuery);
	}
})();