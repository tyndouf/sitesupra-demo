//Invoke strict mode
"use strict";

YUI.add('supra.header', function(Y) {
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires:['supra.header.appdock', 'supra.header-css']});