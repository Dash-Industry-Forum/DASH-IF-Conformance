dataProcessor.prototype._sendData = function(a1,rowId){
	if (!a1) return; //nothing to send
	if (!this.callEvent("onBeforeDataSending",rowId?[rowId,this.getState(rowId),a1]:[null, null, a1])) return false;				
	if (rowId)
		this._in_progress[rowId]=(new Date()).valueOf();
	if (this.serverProcessor.$proxy) {
		var mode = this._tMode!="POST" ? 'get' : 'post';
		var to_send = [];
		for (var i in a1)
			to_send.push({ id: i, data: a1[i], operation: this.getState(i)});
		this.serverProcessor._send(to_send, mode, this);
		return;
	}

	var a2=new dtmlXMLLoaderObject(this.afterUpdate,this,true);
	var a3 = this.serverProcessor+(this._user?(getUrlSymbol(this.serverProcessor)+["dhx_user="+this._user,"dhx_version="+this.obj.getUserData(0,"version")].join("&")):"");
	if (this._tMode!="POST")
		a2.loadXML(a3+((a3.indexOf("?")!=-1)?"&":"?")+this.serialize(a1,rowId));
	else
		a2.loadXML(a3,true,this.serialize(a1,rowId));
	this._waitMode++;
};

dataProcessor.prototype._updatesToParams = function(items) {
	var stack = {};
	for (var i = 0; i < items.length; i++)
		stack[items[i].id] = items[i].data;
	return this.serialize(stack);
};

dataProcessor.prototype._processResult = function(text, xml, loader) {
	if (loader.status != 200) {
		for (var i in this._in_progress) {
			var state = this.getState(i);
			this.afterUpdateCallback(i, i, state, null);
		}
		return;
	}
	xml = new dtmlXMLLoaderObject(function() {},this,true);
	xml.loadXMLString(text);
	xml.xmlDoc = loader;

	this.afterUpdate(this, null, null, null, xml);
};

if (window.dataProcessor && !dataProcessor.prototype.offline_init_original){
	dataProcessor.prototype.offline_init_original = dataProcessor.prototype.init;
	dataProcessor.prototype.init=function(obj){
		this.offline_init_original(obj);
		obj._dataprocessor=this;

		this.setTransactionMode("POST",true);
		if (!this.serverProcessor.$proxy)
			this.serverProcessor+=(this.serverProcessor.indexOf("?")!=-1?"&":"?")+"editing=true";
		
		if(this._sendData)
			this._sendData = dataProcessor.prototype._sendData;
	};
}




if (typeof(dhtmlXGridObject) !== "undefined") {
	dhtmlXGridObject.prototype.load_original = dhtmlXGridObject.prototype.load;
	dhtmlXGridObject.prototype.load = function(url, call, type){
		if (url.$proxy) {
			if (arguments.length == 2 && typeof call != "function") type=call;
			type=type||"xml";
			url.load(this, type);
			return;
		}
		return this.load_original.call(this, arguments);
	};
}

if (typeof(dhtmlXTreeObject) !== "undefined") {
	dhtmlXTreeObject.prototype.loadXML_original = dhtmlXTreeObject.prototype.loadXML;
	dhtmlXTreeObject.prototype.loadXML = function(url, call){
		if (url.$proxy) {
			url.load(this, "xml");
			return;
		}
		return this.loadXML_original.call(this, arguments);
	};
	dhtmlXTreeObject.prototype.parse = function(data, driver) {
		this.loadXMLString(data);
	};
}

if (typeof(scheduler) !== "undefined") {
	scheduler.load=function(url,call){
		if (typeof call == "string"){
			if (call !== "xml") {
				this._process=call;
				var type = call;
			} else
				type = null;
			
			call = arguments[2];
		}
		this._load_url=url;
		this._after_call=call;
		if (url.$proxy) {
			url.load(this, type);
			return;
		}
		return this._load(url,this._date);
	};
}


if (typeof(dhtmlXDataView) !== "undefined") {
	dhtmlXDataView.prototype.load_original = dhtmlx.DataLoader.load;
	dhtmlx.DataLoader.load = function(url,call) {
		var type = (typeof call == "string") ? call: null;
		if (url.$proxy) {
			url.load(this, type);
			return;
		}
		this.load_original.call(this, arguments);
	}
}