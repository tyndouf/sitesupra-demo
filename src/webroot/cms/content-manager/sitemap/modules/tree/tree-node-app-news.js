//Invoke strict mode
"use strict";

YUI().add('website.sitemap-tree-node-app-news', function (Y) {
	
	//Shortcuts
	var Action = Supra.Manager.getAction('SiteMap');
	
	
	/**
	 * News application tree node
	 */
	function Node(config) {
		Node.superclass.constructor.apply(this, arguments);
	}
	
	Node.NAME = 'TreeNodeAppNews';
	Node.APP = 'news';
	Node.CSS_PREFIX = 'su-tree-node';
	Node.ATTRS = {};
	
	Y.extend(Node, Action.TreeNodeApp, {
		
		/**
		 * Attach event listeners
		 * 
		 * @private
		 */
		'bindUI': function () {
			Node.superclass.bindUI.apply(this, arguments);
			
			//Prevent adding new children directly inside News application
			this.on('child:add', function (e) {
				e.node.set('dropablePlaces', {'inside': true, 'before': false, 'after': false});
			}, this);
		},
		
		/**
		 * Render children tree nodes
		 * 
		 * @private
		 */
		'_renderChildren': function () {
			if (this.get('childrenRendered')) return;
			Node.superclass._renderChildren.apply(this, arguments);
			
			//Prevent adding new children directly inside News application
			this.children().forEach(function (node) {
				node.set('dropablePlaces', {'inside': true, 'before': false, 'after': false});
			});
		},
		
		/**
		 * Instead of expanding children show news list popup
		 * 
		 * @private
		 */
		'_setExpandedExpand': function () {
			var returnValue = Node.superclass._setExpandedExpand.apply(this, arguments);
			
			var children = this.children(),
				i = 0,
				size = children.length;
			
			for(; i<size; i++) {
				if (children[i].isInstanceOf('TreeNodeList')) {
					children[i].expand(); break;
				}
			}
			
			return returnValue;
		}
	});
	
	
	Action.TreeNodeApp.News = Node;
	
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {'requires': ['website.sitemap-tree-node-app']});