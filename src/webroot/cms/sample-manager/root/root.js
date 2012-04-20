SU('supra.datatable', function (Y) {

	//Shortcut
	var Action = Supra.Manager.Action;
	
	//Create Action class
	new Action(Action.PluginPanel, {
		
		/**
		 * Unique action name
		 * @type {String}
		 */
		NAME: 'Root',
		
		/**
		 * Load stylesheet
		 * @type {Boolean}
		 * @private
		 */
		HAS_STYLESHEET: true,
		
		/**
		 * Load template
		 * @type {Boolean}
		 * @private
		 */
		HAS_TEMPLATE: true,
		
		
		
		/**
		 * Set configuration/properties, bind listeners, etc.
		 */
		initialize: function () {
			//Dimensions
			this.panel.setAttrs({
				'width': 800
			});
			
			//Data table
			this.datatable = new Supra.DataTable({
				'srcNode': this.getContainer().one('div.datatable'),
				'requestURI': this.getDataPath()
			});
			
			//Add checkboxes, must be called before adding rows
			//otherwise checkbox will be at the end
			this.datatable.plug(Supra.DataTable.CheckboxPlugin);
			
			//Set columns
			this.datatable.addColumns([
				{'id': 'id', 'title': 'ID'},
				{'id': 'title', 'title': 'Title'},
				{'id': 'text', 'title': 'Text'},
				{'id': 'custom', 'title': 'Custom', 'hasData': false}
			]);
			
			//Set request params
			this.datatable.requestParams.set('limit', 10);
			
		},
		
		updateStatus: function (event) {
			var label = (event.newVal ? 'visible' : 'hidden');
			var node = this.getContainer().one('span.status');
			node.set('innerHTML', 'Popup is ' + label);
		},
		
		render: function () {
			//Add listener to links 'click' event,
			//execute Popup action
			var link = this.getContainer().one('a');
			link.on('click', function () {
				Supra.Manager.executeAction('Popup');
			});
			
			//When popup opens change status message
			var popup = Supra.Manager.getAction('Popup');
			popup.on('visibleChange', this.updateStatus, this);
			
			//Render data table
			this.datatable.render();
			
			//Add sample manager to the header
			Supra.Manager.Header.addItem(this.NAME, {
				'title': 'Sample Manager',
				'icon': '/cms/supra/img/apps/content_32x32.png'
			});
		}
	});
	
});