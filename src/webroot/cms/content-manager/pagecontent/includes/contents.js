//Invoke strict mode
"use strict";

/*
 * SU.Manager.PageContent.Iframe
 */
YUI.add('supra.iframe-contents', function (Y) {
	
	//Shortcut
	var Manager = SU.Manager,
		Action = Manager.PageContent,
		Root = Manager.getAction('Root');
	
	/*
	 * Editable content
	 */
	function IframeContents (config) {
		IframeContents.superclass.constructor.apply(this, arguments);
		this.init.apply(this, arguments);
	}
	
	IframeContents.NAME = 'page-iframe-contents';
	IframeContents.CLASS_NAME = Y.ClassNameManager.getClassName(IframeContents.NAME);
	IframeContents.ATTRS = {
		'iframe': {
			value: null
		},
		'win': {
			value: null,
		},
		'doc': {
			value: null,
		},
		'body': {
			value: null
		},
		'contentData': {
			value: null
		},
		'disabled': {
			value: false
		},
		'activeChild': {
			value: null
		},
		/*
		 * Highlight list nodes
		 */
		'highlight': {
			value: false,
			setter: '_setHighlight'
		}
	};
	
	Y.extend(IframeContents, Y.Base, {
		children: {},
		
		/**
		 * On URI change to /22/edit unset active content
		 *
		 * @param {Object} req Routing request
		 */
		routeMain: function (req) {
			if (this.get('activeChild')) {
				this.set('activeChild', null);
			}
			
			if (req && req.next) req.next();
		},
		
		/**
		 * On URI change to /22/edit/111 set active content
		 *
		 * @param {Object} req Routing request
		 */
		routeBlock: function (req) {
			var block_id = req.params.block_id;
			var block_old = this.get('activeChild');
			var block_new = block_id ? this.getChildById(block_id) : null;
			
			if (block_old && block_old.get('data').id != block_id) {
				this.set('activeChild', block_new);
			} else if (!block_old && block_new) {
				this.set('activeChild', block_new);
			}
			
			if (req && req.next) req.next();
		},
		
		
		bindUI: function () {
			
			//Set 'editing' attribute after content changes
			this.after('activeChildChange', function (evt) {
				if (evt.newVal !== evt.prevVal) {
					if (evt.prevVal && evt.prevVal.get('editing')) {
						evt.prevVal.set('editing', false);
						
						//Route to /22/edit
						if (!evt.newVal || !evt.newVal.get('editable')) {
							var uri = Root.ROUTE_PAGE_EDIT.replace(':page_id', Manager.Page.getPageData().id);
							Root.save(uri);
						}
					}
					if (evt.newVal && !evt.newVal.get('editing') && evt.newVal.get('editable')) {
						evt.newVal.set('editing', true);
						
						//Route to /22/edit/111
						var uri = Root.ROUTE_PAGE_CONT.replace(':page_id', Manager.Page.getPageData().id)
													  .replace(':block_id', evt.newVal.get('data').id);
						
						Root.save(uri);
					}
				}
			});
			
			//Routing
			Root.route(Root.ROUTE_PAGE_EDIT, Y.bind(this.routeMain, this));
			Root.route(Root.ROUTE_PAGE_CONT, Y.bind(this.routeBlock, this));
			
			//Restore state
			this.get('iframe').on('ready', function () {
				var m = null;
				if (m = Root.getPath().match(Root.ROUTE_PAGE_CONT_R)) {
					var block = this.getChildById(m[1]);
					if (block) {
						//Need delay to make sure editing state is correctly set
						//needed only if settings immediately after load
						Y.later(1, this, function () {
							this.set('activeChild', block);
						});
					}
				}
			}, this);
			
			
			//Bind block D&D
			this.on('block:dragend', function (e) {
				if (e.block) {
					var region = Y.DOM._getRegion(e.position[1], e.position[0]+88, e.position[1]+88, e.position[0]);
					for(var i in this.children) {
						var node = this.children[i].getNode(),
							intersect = node.intersect(region);
						
						if (intersect.inRegion && this.children[i].isChildTypeAllowed(e.block.id)) {
							return this.children[i].fire('dragend:hit', {dragnode: e.dragnode, block: e.block});
						}
					}
				}
			}, this);
			
			this.on('block:dragstart', function (e) {
				//Only if dragging block
				if (e.block) {
					this.set('highlight', true);
					var type = e.block.id;
					
					for(var i in this.children) {
						if (this.children[i].isChildTypeAllowed(type)) {
							this.children[i].set('highlight', true);
						}
					}
				}
			}, this);
			
			this.once('destroy', this.beforeDestroy, this);
			
			//Fix context
			var win = this.get('iframe').get('win');
			this.onResize = Y.throttle(Y.bind(this.onResize, this), 50);
			Y.on('resize', this.onResize, win);
		},
		
		/**
		 * On resize sync overlay position
		 */
		onResize: function () {
			for(var i in this.children) {
				this.children[i].syncOverlayPosition();
			}
		},
		
		/**
		 * Create children
		 * 
		 * @param {Object} data
		 * @private
		 */
		createChildren: function (data) {
			var data = data || this.get('contentData');
			if (data) {
				var body = this.get('body');
				var doc = this.get('doc');
				var win = this.get('win');
				
				for(var i=0,ii=data.length; i<ii; i++) {
					
					var type = data[i].type;
					var properties = Manager.Blocks.getBlock(type);
					var classname = properties && properties.classname ? properties.classname : type[0].toUpperCase() + type.substr(1);
					
					if (classname in Action) {
						var block = this.children[data[i].id] = new Action[classname]({
							'doc': doc,
							'win': win,
							'body': body,
							'data': data[i],
							'parent': null,
							'super': this,
							'dragable': !data[i].locked,
							'editable': !data[i].locked
						});
						block.render();
					} else {
						Y.error('Class "' + classname + '" for content "' + data[i].id + '" is missing.');
					}
					
				}
			}
		},
		
		renderUI: function () {
			this.createChildren();
			this.get('body').addClass('yui3-editable');
		},
		
		render: function () {
			this.renderUI();
			this.bindUI();
		},
		
		/**
		 * Loads and returns block data
		 * 
		 * @param {Object} data Block information
		 * @param {Function} callback Callback function
		 * @param {Object} context
		 */
		getBlockInsertData: function (data, callback, context) {
			var url = Manager.PageContent.getDataPath('insertblock');
			var page_info = Manager.Page.getPageData();
			
			data = Supra.mix({
				'page_id': page_info.id,
				'locale': Supra.data.get('locale')
			}, data);
			
			Supra.io(url, {
				'data': data,
				'method': 'post',
				'on': {
					'success': callback
				},
				'context': context
			});
		},
		
		/**
		 * Send block delete request
		 * 
		 * @param {Object} block
		 */
		sendBlockDelete: function (block, callback, context) {
			var url = Manager.PageContent.getDataPath('deleteblock');
			var page_info = Manager.Page.getPageData();
			var data = {
				'page_id': page_info.id,
				'block_id': block.getId(),
				'locale': Supra.data.get('locale')
			};
			
			Supra.io(url, {
				'data': data,
				'method': 'post',
				'on': {
					'success': callback
				},
				'context': context
			});
		},
		
		/**
		 * Save block order request
		 * 
		 * @param {Object} block
		 * @param {Object} order
		 */
		sendBlockOrder: function (block, order) {
			var url = Manager.PageContent.getDataPath('orderblocks');
			var page_info = Manager.Page.getPageData();
			var data = {
				'page_id': page_info.id,
				
				'place_holder_id': block.getId(),
				'order': order,
				
				'locale': Supra.data.get('locale')
			};
			
			Supra.io(url, {
				'data': data,
				'method': 'post'
			});
		},
		
		/**
		 * Save block properties
		 * 
		 * @param {Object} block Block
		 * @param {Function} callback Callback function
		 * @param {Object} context Callback context
		 */
		sendBlockProperties: function (block, callback, context) {
			var url = Manager.PageContent.getDataPath('save'),
				page_data = Manager.Page.getPageData(),
				values = block.properties.getValues();
			
			//Some inputs (like InlineHTML) needs data to be processed before saving it
			var save_values = block.properties.getSaveValues();
			
			//Allow block to modify data before saving it
			save_values = block.processData(save_values);
			
			var post_data = {
				'page_id': page_data.id,
				'block_id': block.getId(),
				'locale': Supra.data.get('locale'),
				'properties': save_values
			};
			
			Supra.io(url, {
				'data': post_data,
				'method': 'post',
				'on': {'success': callback}
			}, context);
		},
		
		/**
		 * Remove child Supra.Manager.PageContent.Proto object
		 * 
		 * @param {Object} child
		 */
		removeChild: function (child) {
			for(var i in this.children) {
				if (this.children[i] === child) {
					
					//Send request
					this.sendBlockDelete(child, function () {
						delete(this.children[i]);
						child.destroy();
					}, this);
				}
			}
		},
		
		/**
		 * highlight attribute setter
		 * 
		 * @param {Boolean} value If true highlight will be shown
		 * @private
		 */
		_setHighlight: function (value) {
			if (value) {
				this.set('disabled', true);
				this.get('body').removeClass('yui3-editable');
				this.get('body').addClass('yui3-highlight');
				
				this.set('activeChild', null);
			} else {
				this.set('disabled', false);
				this.get('body').addClass('yui3-editable');
				this.get('body').removeClass('yui3-highlight');
				
				for (var i in this.children) {
					this.children[i].set('highlight', false);
				}
			}
			
			return !!value;
		},
		
		/**
		 * Returns child block by ID
		 *
		 * @param {String} block_id Block ID
		 * @return Child block
		 * @type {Object}
		 */
		getChildById: function (block_id) {
			var blocks = this.children,
				block = null;
			
			if (block_id in blocks) return blocks[block_id];
			
			for(var i in blocks) {
				block = blocks[i].getChildById(block_id);
				if (block) return block;
			}
			
			return null;
		},
		
		/**
		 * Returns children blocks
		 *
		 * @return Children blocks
		 * @type {Object}
		 */
		getChildren: function () {
			var blocks = {},
				children = this.children;
			
			for(var child_id in children) {
				blocks[child_id] = children[child_id];
			}
			
			return blocks;
		},
		
		/**
		 * Returns all children blocks
		 *
		 * @return All children blocks
		 * @type {Object}
		 */
		getAllChildren: function () {
			var blocks = {},
				children = this.children;
			
			for(var child_id in children) {
				blocks[child_id] = children[child_id];
				children[child_id].getAllChildren(blocks);
			}
			
			return blocks;
		},
		
		beforeDestroy: function () {
			//Destroy children
			var child = null,
				blocks = this.children;
			
			for(var i in blocks) {
				child = blocks[i];
				delete(blocks[i]);
				child.destroy();
			}
			
			//Unsubscribe resize
			var win = this.get('iframe').get('win');
			Y.unsubscribe('resize', this.onResize, win);
		}
	});
	
	
	Manager.PageContent.IframeContents = IframeContents;
	
	
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires:[
	'widget',
	'supra.page-content-list',
	'supra.page-content-editable',
	'supra.page-content-gallery'
]});