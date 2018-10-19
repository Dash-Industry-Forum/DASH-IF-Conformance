/*
2012 January 24
*/
/*DHX:Depend core/dhx.js*/
/*DHX:Depend core/assert.js*/
if (!window.dhx) 
	dhx={};

//check some rule, show message as error if rule is not correct
dhx.assert = function(test, message){
	if (!test)	dhx.error(message);
};
//entry point for analitic scripts
dhx.assert_core_ready = function(){
	if (window.dhx_on_core_ready)	
		dhx_on_core_ready();
};

//code below this point need to be reconsidered

dhx.assert_enabled=function(){ return false; };

//register names of event, which can be triggered by the object
dhx.assert_event = function(obj, evs){
	if (!obj._event_check){
		obj._event_check = {};
		obj._event_check_size = {};
	}
		
	for (var a in evs){
		obj._event_check[a.toLowerCase()]=evs[a];
		var count=-1; for (var t in evs[a]) count++;
		obj._event_check_size[a.toLowerCase()]=count;
	}
};
dhx.assert_method_info=function(obj, name, descr, rules){
	var args = [];
	for (var i=0; i < rules.length; i++) {
		args.push(rules[i][0]+" : "+rules[i][1]+"\n   "+rules[i][2].describe()+(rules[i][3]?"; optional":""));
	}
	return obj.name+"."+name+"\n"+descr+"\n Arguments:\n - "+args.join("\n - ");
};
dhx.assert_method = function(obj, config){
	for (var key in config)
		dhx.assert_method_process(obj, key, config[key].descr, config[key].args, (config[key].min||99), config[key].skip);
};
dhx.assert_method_process = function (obj, name, descr, rules, min, skip){
	var old = obj[name];
	if (!skip)
		obj[name] = function(){
			if (arguments.length !=	rules.length && arguments.length < min) 
				dhx.log("warn","Incorrect count of parameters\n"+obj[name].describe()+"\n\nExpecting "+rules.length+" but have only "+arguments.length);
			else
				for (var i=0; i<rules.length; i++)
					if (!rules[i][3] && !rules[i][2](arguments[i]))
						dhx.log("warn","Incorrect method call\n"+obj[name].describe()+"\n\nActual value of "+(i+1)+" parameter: {"+(typeof arguments[i])+"} "+arguments[i]);
			
			return old.apply(this, arguments);
		};
	obj[name].describe = function(){	return dhx.assert_method_info(obj, name, descr, rules);	};
};
dhx.assert_event_call = function(obj, name, args){
	if (obj._event_check){
		if (!obj._event_check[name])
			dhx.log("warn","Not expected event call :"+name);
		else if (dhx.isNotDefined(args))
			dhx.log("warn","Event without parameters :"+name);
		else if (obj._event_check_size[name] != args.length)
			dhx.log("warn","Incorrect event call, expected "+obj._event_check_size[name]+" parameter(s), but have "+args.length +" parameter(s), for "+name+" event");
	}		
};
dhx.assert_event_attach = function(obj, name){
	if (obj._event_check && !obj._event_check[name]) 
			dhx.log("warn","Unknown event name: "+name);
};
//register names of properties, which can be used in object's configuration
dhx.assert_property = function(obj, evs){
	if (!obj._settings_check)
		obj._settings_check={};
	dhx.extend(obj._settings_check, evs);		
};
//check all options in collection, against list of allowed properties
dhx.assert_check = function(data,coll){
	if (typeof data == "object"){
		for (var key in data){
			dhx.assert_settings(key,data[key],coll);
		}
	}
};
//check if type and value of property is the same as in scheme
dhx.assert_settings = function(mode,value,coll){
	coll = coll || this._settings_check;

	//if value is not in collection of defined ones
	if (coll){
		if (!coll[mode])	//not registered property
			return dhx.log("warn","Unknown propery: "+mode);
			
		var descr = "";
		var error = "";
		var check = false;
		for (var i=0; i<coll[mode].length; i++){
			var rule = coll[mode][i];
			if (typeof rule == "string")
				continue;
			if (typeof rule == "function")
				check = check || rule(value);
			else if (typeof rule == "object" && typeof rule[1] == "function"){
				check = check || rule[1](value);
				if (check && rule[2])
					dhx["assert_check"](value, rule[2]); //temporary fix , for sources generator
			}
			if (check) break;
		}
		if (!check )
			dhx.log("warn","Invalid configuration\n"+dhx.assert_info(mode,coll)+"\nActual value: {"+(typeof value)+"} "+value);
	}
};

dhx.assert_info=function(name, set){ 
	var ruleset = set[name];
	var descr = "";
	var expected = [];
	for (var i=0; i<ruleset.length; i++){
		if (typeof ruleset[i] == "string")
			descr = ruleset[i];
		else if (ruleset[i].describe)
			expected.push(ruleset[i].describe());
		else if (ruleset[i][1] && ruleset[i][1].describe)
			expected.push(ruleset[i][1].describe());
	}
	return "Property: "+name+", "+descr+" \nExpected value: \n - "+expected.join("\n - ");
};


if (dhx.assert_enabled()){
	
	dhx.assert_rule_color=function(check){
		if (typeof check != "string") return false;
		if (check.indexOf("#")!==0) return false;
		if (check.substr(1).replace(/[0-9A-F]/gi,"")!=="") return false;
		return true;
	};
	dhx.assert_rule_color.describe = function(){
		return "{String} Value must start from # and contain hexadecimal code of color";
	};
	
	dhx.assert_rule_template=function(check){
		if (typeof check == "function") return true;
		if (typeof check == "string") return true;
		return false;
	};
	dhx.assert_rule_template.describe = function(){
		return "{Function},{String} Value must be a function which accepts data object and return text string, or a sting with optional template markers";
	};
	
	dhx.assert_rule_boolean=function(check){
		if (typeof check == "boolean") return true;
		return false;
	};
	dhx.assert_rule_boolean.describe = function(){
		return "{Boolean} true or false";
	};
	
	dhx.assert_rule_object=function(check, sub){
		if (typeof check == "object") return true;
		return false;
	};
	dhx.assert_rule_object.describe = function(){
		return "{Object} Configuration object";
	};
	
	
	dhx.assert_rule_string=function(check){
		if (typeof check == "string") return true;
		return false;
	};
	dhx.assert_rule_string.describe = function(){
		return "{String} Plain string";
	};
	
	
	dhx.assert_rule_htmlpt=function(check){
		return !!dhx.toNode(check);
	};
	dhx.assert_rule_htmlpt.describe = function(){
		return "{Object},{String} HTML node or ID of HTML Node";
	};
	
	dhx.assert_rule_notdocumented=function(check){
		return false;
	};
	dhx.assert_rule_notdocumented.describe = function(){
		return "This options wasn't documented";
	};
	
	dhx.assert_rule_key=function(obj){
		var t = function (check){
			return obj[check];
		};
		t.describe=function(){
			var opts = [];
			for(var key in obj)
				opts.push(key);
			return  "{String} can take one of next values: "+opts.join(", ");
		};
		return t;
	};
	
	dhx.assert_rule_dimension=function(check){
		if (check*1 == check && !isNaN(check) && check >= 0) return true;
		return false;
	};
	dhx.assert_rule_dimension.describe=function(){
		return "{Integer} value must be a positive number";
	};
	
	dhx.assert_rule_number=function(check){
		if (typeof check == "number") return true;
		return false;
	};
	dhx.assert_rule_number.describe=function(){
		return "{Integer} value must be a number";
	};
	
	dhx.assert_rule_function=function(check){
		if (typeof check == "function") return true;
		return false;
	};
	dhx.assert_rule_function.describe=function(){
		return "{Function} value must be a custom function";
	};
	
	dhx.assert_rule_any=function(check){
		return true;
	};
	dhx.assert_rule_any.describe=function(){
		return "Any value";
	};
	
	dhx.assert_rule_mix=function(a,b){
		var t = function(check){
			if (a(check)||b(check)) return true;
			return false;
		};
		t.describe = function(){
			return a.describe();
		};
		return t;
	};

}

/*
	Common helpers
*/
dhx.version="3.0";
dhx.codebase="./";
dhx.name = "Core";

//coding helpers
dhx.copy = function(source){
	var f = dhx.copy._function;
	f.prototype = source;
	return new f();
};
dhx.copy._function = function(){};

//copies methods and properties from source to the target
dhx.extend = function(target, source, force){
	dhx.assert(target,"Invalid mixing target");
	dhx.assert(source,"Invalid mixing source");
	if (target._dhx_proto_wait)
		target = target._dhx_proto_wait[0];
	
	//copy methods, overwrite existing ones in case of conflict
	for (var method in source)
		if (!target[method] || force)
			target[method] = source[method];
		
	//in case of defaults - preffer top one
	if (source.defaults)
		dhx.extend(target.defaults, source.defaults);
	
	//if source object has init code - call init against target
	if (source.$init)	
		source.$init.call(target);
				
	return target;	
};

//copies methods and properties from source to the target from all levels
dhx.fullCopy = function(source){
	dhx.assert(source,"Invalid mixing target");
	var target =  (source.length?[]:{});
	if(arguments.length>1){
		target = arguments[0];
		source = arguments[1];
	}
	for (var method in source){
		if(source[method] && typeof source[method] == "object" && !dhx.isDate(source[method])){
			target[method] = (source[method].length?[]:{});
			dhx.fullCopy(target[method],source[method]);
		}else{
			target[method] = source[method];
		}
	}

	return target;	
};


dhx.single = function(source){ 
	var instance = null;
	var t = function(config){
		if (!instance)
			instance = new source({});
			
		if (instance._reinit)
			instance._reinit.apply(instance, arguments);
		return instance;
	};
	return t;
};

dhx.protoUI = function(){
	if (dhx.debug_proto)
		dhx.log("UI registered: "+arguments[0].name);
		
	var origins = arguments;
	var selfname = origins[0].name;
	
	var t = function(data){
		if (origins){
			var params = [origins[0]];
			
			for (var i=1; i < origins.length; i++){
				params[i] = origins[i];
				
				if (params[i]._dhx_proto_wait)
					params[i] = params[i].call(dhx);

				if (params[i].prototype && params[i].prototype.name)
					dhx.ui[params[i].prototype.name] = params[i];
			}
		
			dhx.ui[selfname] = dhx.proto.apply(dhx, params);
			if (t._dhx_type_wait)	
				for (var i=0; i < t._dhx_type_wait.length; i++)
					dhx.Type(dhx.ui[selfname], t._dhx_type_wait[i]);
				
			t = origins = null;	
		}
			
		if (this != dhx)
			return new dhx.ui[selfname](data);
		else 
			return dhx.ui[selfname];
	};
	t._dhx_proto_wait = arguments;
	return dhx.ui[selfname]=t;
};

dhx.proto = function(){
	
	if (dhx.debug_proto)
		dhx.log("Proto chain:"+arguments[0].name+"["+arguments.length+"]");
		
	var origins = arguments;
	var compilation = origins[0];
	var has_constructor = !!compilation.$init;
	var construct = [];
	
	dhx.assert(compilation,"Invalid mixing target");
		
	for (var i=origins.length-1; i>0; i--) {
		dhx.assert(origins[i],"Invalid mixing source");
		if (typeof origins[i]== "function")
			origins[i]=origins[i].prototype;
		if (origins[i].$init) 
			construct.push(origins[i].$init);
		if (origins[i].defaults){ 
			var defaults = origins[i].defaults;
			if (!compilation.defaults)
				compilation.defaults = {};
			for (var def in defaults)
				if (dhx.isNotDefined(compilation.defaults[def]))
					compilation.defaults[def] = defaults[def];
		}
		if (origins[i].type && compilation.type){
			for (var def in origins[i].type)
				if (!compilation.type[def])
					compilation.type[def] = origins[i].type[def];
		}
			
		for (var key in origins[i]){
			if (!compilation[key])
				compilation[key] = origins[i][key];
		}
	}
	
	if (has_constructor)
		construct.push(compilation.$init);
	
	
	compilation.$init = function(){
		for (var i=0; i<construct.length; i++)
			construct[i].apply(this, arguments);
	};
	var result = function(config){
		this.$ready=[];
		dhx.assert(this.$init,"object without init method");
		this.$init(config);
		if (this._parseSettings)
			this._parseSettings(config, this.defaults);
		for (var i=0; i < this.$ready.length; i++)
			this.$ready[i].call(this);
	};
	result.prototype = compilation;
	
	compilation = origins = null;
	return result;
};
//creates function with specified "this" pointer
dhx.bind=function(functor, object){ 
	return function(){ return functor.apply(object,arguments); };  
};

//loads module from external js file
dhx.require=function(module){
	if (!dhx._modules[module]){
		dhx.assert(dhx.ajax,"load module is required");
		
		//load and exec the required module
		dhx.exec( dhx.ajax().sync().get(dhx.codebase+module).responseText );
		dhx._modules[module]=true;	
	}
};
dhx._modules = {};	//hash of already loaded modules

//evaluate javascript code in the global scoope
dhx.exec=function(code){
	if (window.execScript)	//special handling for IE
		window.execScript(code);
	else window.eval(code);
};

dhx.wrap = function(code, wrap){
	if (!code) return wrap;
	return function(){
		var result = code.apply(this, arguments);
		wrap.apply(this,arguments);
		return result;
	};
};

/*
	creates method in the target object which will transfer call to the source object
	if event parameter was provided , each call of method will generate onBefore and onAfter events
*/
dhx.methodPush=function(object,method,event){
	return function(){
		var res = false;
		//if (!event || this.callEvent("onBefore"+event,arguments)){ //not used anymore, probably can be removed
			res=object[method].apply(object,arguments);
		//	if (event) this.callEvent("onAfter"+event,arguments);
		//}
		return res;	//result of wrapped method
	};
};
//check === undefined
dhx.isNotDefined=function(a){
	return typeof a == "undefined";
};
//delay call to after-render time
dhx.delay=function(method, obj, params, delay){
	return window.setTimeout(function(){
		var ret = method.apply(obj,(params||[]));
		method = obj = params = null;
		return ret;
	},delay||1);
};

//common helpers

//generates unique ID (unique per window, nog GUID)
dhx.uid = function(){
	if (!this._seed) this._seed=(new Date).valueOf();	//init seed with timestemp
	this._seed++;
	return this._seed;
};
//resolve ID as html object
dhx.toNode = function(node){
	if (typeof node == "string") return document.getElementById(node);
	return node;
};
//adds extra methods for the array
dhx.toArray = function(array){ 
	return dhx.extend((array||[]),dhx.PowerArray, true);
};
//resolve function name
dhx.toFunctor=function(str){ 
	return (typeof(str)=="string") ? eval(str) : str; 
};
/*checks where an object is instance of Array*/
dhx.isArray = function(obj) {
  return Object.prototype.toString.call(obj) === '[object Array]';
};
dhx.isDate = function(obj){
	return obj instanceof Date;
};

//dom helpers

//hash of attached events
dhx._events = {};
//attach event to the DOM element
dhx.event=function(node,event,handler,master){
	node = dhx.toNode(node);
	
	var id = dhx.uid();
	if (master) 
		handler=dhx.bind(handler,master);	
		
	dhx._events[id]=[node,event,handler];	//store event info, for detaching
		
	//use IE's of FF's way of event's attaching
	if (node.addEventListener)
		node.addEventListener(event, handler, false);
	else if (node.attachEvent)
		node.attachEvent("on"+event, handler);

	return id;	//return id of newly created event, can be used in eventRemove
};

//remove previously attached event
dhx.eventRemove=function(id){
	
	if (!id) return;
	dhx.assert(this._events[id],"Removing non-existing event");
		
	var ev = dhx._events[id];
	//browser specific event removing
	if (ev[0].removeEventListener)
		ev[0].removeEventListener(ev[1],ev[2],false);
	else if (ev[0].detachEvent)
		ev[0].detachEvent("on"+ev[1],ev[2]);
		
	delete this._events[id];	//delete all traces
};


//debugger helpers
//anything starting from error or log will be removed during code compression

//add message in the log
dhx.log = function(type,message,details){
	if (arguments.length == 1){
		message = type;
		type = "log";
	}
	/*jsl:ignore*/
	if (window.console && console.log){
		type=type.toLowerCase();
		if (window.console[type])
			window.console[type](message||"unknown error");
		else
			window.console.log(type +": "+message);
		if (details) 
			window.console.log(details);
	}	
	/*jsl:end*/
};
//register rendering time from call point 
dhx.log_full_time = function(name){
	dhx._start_time_log = new Date();
	dhx.log("Timing start ["+name+"]");
	window.setTimeout(function(){
		var time = new Date();
		dhx.log("Timing end ["+name+"]:"+(time.valueOf()-dhx._start_time_log.valueOf())/1000+"s");
	},1);
};
//register execution time from call point
dhx.log_time = function(name){
	var fname = "_start_time_log"+name;
	if (!dhx[fname]){
		dhx[fname] = new Date();
		dhx.log("Info","Timing start ["+name+"]");
	} else {
		var time = new Date();
		dhx.log("Info","Timing end ["+name+"]:"+(time.valueOf()-dhx[fname].valueOf())/1000+"s");
		dhx[fname] = null;
	}
};
//log message with type=error
dhx.error = function(message,details){
	dhx.log("error",message,details);
	if (dhx.debug !== false)
		debugger;
};
//event system
dhx.EventSystem={
	$init:function(){
		this._events = {};		//hash of event handlers, name => handler
		this._handlers = {};	//hash of event handlers, ID => handler
		this._map = {};
	},
	//temporary block event triggering
	blockEvent : function(){
		this._events._block = true;
	},
	//re-enable event triggering
	unblockEvent : function(){
		this._events._block = false;
	},
	mapEvent:function(map){
		dhx.extend(this._map, map, true);
	},
	on_setter:function(config){
		if(config){
			for(var i in config){
				if(typeof config[i] == 'function')
					this.attachEvent(i, config[i]);
			}
		}
	},
	//trigger event
	callEvent:function(type,params){
		if (this._events._block) return true;
		
		type = type.toLowerCase();
		dhx.assert_event_call(this, type, params);
		
		var event_stack =this._events[type.toLowerCase()];	//all events for provided name
		var return_value = true;

		if (dhx.debug)	//can slowdown a lot
			dhx.log("info","["+this.name+"] event:"+type,params);
		
		if (event_stack)
			for(var i=0; i<event_stack.length; i++)
				/*
					Call events one by one
					If any event return false - result of whole event will be false
					Handlers which are not returning anything - counted as positive
				*/
				if (event_stack[i].apply(this,(params||[]))===false) return_value=false;
				
		if (this._map[type] && !this._map[type].callEvent(type,params))
			return_value =	false;
			
		return return_value;
	},
	//assign handler for some named event
	attachEvent:function(type,functor,id){
		type=type.toLowerCase();
		dhx.assert_event_attach(this, type);
		
		id=id||dhx.uid(); //ID can be used for detachEvent
		functor = dhx.toFunctor(functor);	//functor can be a name of method

		var event_stack=this._events[type]||dhx.toArray();
		//save new event handler
		event_stack.push(functor);
		this._events[type]=event_stack;
		this._handlers[id]={ f:functor,t:type };
		
		return id;
	},
	//remove event handler
	detachEvent:function(id){
		if(!this._handlers[id]){
			return;
		}
		var type=this._handlers[id].t;
		var functor=this._handlers[id].f;
		
		//remove from all collections
		var event_stack=this._events[type];
		event_stack.remove(functor);
		delete this._handlers[id];
	},
	hasEvent:function(type){
		type=type.toLowerCase();
		return this._events[type]?true:false;
	}
};

dhx.extend(dhx, dhx.EventSystem);

//array helper
//can be used by dhx.toArray()
dhx.PowerArray={
	//remove element at specified position
	removeAt:function(pos,len){
		if (pos>=0) this.splice(pos,(len||1));
	},
	//find element in collection and remove it 
	remove:function(value){
		this.removeAt(this.find(value));
	},	
	//add element to collection at specific position
	insertAt:function(data,pos){
		if (!pos && pos!==0) 	//add to the end by default
			this.push(data);
		else {	
			var b = this.splice(pos,(this.length-pos));
  			this[pos] = data;
  			this.push.apply(this,b); //reconstruct array without loosing this pointer
  		}
  	},  	
  	//return index of element, -1 if it doesn't exists
  	find:function(data){ 
  		for (var i=0; i<this.length; i++) 
  			if (data==this[i]) return i; 	
  		return -1; 
  	},
  	//execute some method for each element of array
  	each:function(functor,master){
		for (var i=0; i < this.length; i++)
			functor.call((master||this),this[i]);
	},
	//create new array from source, by using results of functor 
	map:function(functor,master){
		for (var i=0; i < this.length; i++)
			this[i]=functor.call((master||this),this[i]);
		return this;
	}
};

dhx.env = {};

// dhx.env.transform 
// dhx.env.transition
(function(){
	if (navigator.userAgent.indexOf("Mobile")!=-1) 
		dhx.env.mobile = true;
	if (dhx.env.mobile || navigator.userAgent.indexOf("iPad")!=-1 || navigator.userAgent.indexOf("Android")!=-1)
		dhx.env.touch = true;
	if (navigator.userAgent.indexOf('Opera')!=-1)
		dhx.env.isOpera=true;
	else{
		//very rough detection, but it is enough for current goals
		dhx.env.isIE=!!document.all;
		dhx.env.isFF=!document.all;
		dhx.env.isWebKit=(navigator.userAgent.indexOf("KHTML")!=-1);
		dhx.env.isSafari=dhx.env.isWebKit && (navigator.userAgent.indexOf('Mac')!=-1);
	}
	if(navigator.userAgent.toLowerCase().indexOf("android")!=-1)
		dhx.env.isAndroid = true;
	dhx.env.transform = false;
	dhx.env.transition = false;
	var options = {};
	options.names = ['transform', 'transition'];
	options.transform = ['transform', 'WebkitTransform', 'MozTransform', 'OTransform', 'msTransform'];
	options.transition = ['transition', 'WebkitTransition', 'MozTransition', 'OTransition', 'msTransition'];
	
	var d = document.createElement("DIV");
	for(var i=0; i<options.names.length; i++) {
		var coll = options[options.names[i]];
		
		for (var j=0; j < coll.length; j++) {
			if(typeof d.style[coll[j]] != 'undefined'){
				dhx.env[options.names[i]] = coll[j];
				break;
			}
		}
	}
    d.style[dhx.env.transform] = "translate3d(0,0,0)";
    dhx.env.translate = (d.style[dhx.env.transform])?"translate3d":"translate";

	var prefix = ''; // default option
	var cssprefix = false;
	if(dhx.env.isOpera){
		prefix = '-o-';
		cssprefix = "O";
	}
	if(dhx.env.isFF)
		prefix = '-Moz-';
	if(dhx.env.isWebKit)
		prefix = '-webkit-';
	if(dhx.env.isIE)
		prefix = '-ms-';

    dhx.env.transformCSSPrefix = prefix;

	dhx.env.transformPrefix = cssprefix||(dhx.env.transformCSSPrefix.replace(/-/gi, ""));
	dhx.env.transitionEnd = ((dhx.env.transformCSSPrefix == '-Moz-')?"transitionend":(dhx.env.transformPrefix+"TransitionEnd"));
})();


dhx.env.svg = (function(){
		return document.implementation.hasFeature("http://www.w3.org/TR/SVG11/feature#BasicStructure", "1.1");
})();


//html helpers
dhx.html={
	index:function(node){
		var k=0;
		//must be =, it is not a comparation!
		while (node = node.previousSibling) k++;
		return k;
	},
	addStyle:function(rule){
		var style = document.createElement("style");
		style.setAttribute("type", "text/css");
		style.setAttribute("media", "screen"); 
		/*IE8*/
		if (style.styleSheet)
			style.styleSheet.cssText = rule;
		else
			style.appendChild(document.createTextNode(rule));
		document.getElementsByTagName("head")[0].appendChild(style);
	},
	create:function(name,attrs,html){
		attrs = attrs || {};
		var node = document.createElement(name);
		for (var attr_name in attrs)
			node.setAttribute(attr_name, attrs[attr_name]);
		if (attrs.style)
			node.style.cssText = attrs.style;
		if (attrs["class"])
			node.className = attrs["class"];
		if (html)
			node.innerHTML=html;
		return node;
	},
	//return node value, different logic for different html elements
	getValue:function(node){
		node = dhx.toNode(node);
		if (!node) return "";
		return dhx.isNotDefined(node.value)?node.innerHTML:node.value;
	},
	//remove html node, can process an array of nodes at once
	remove:function(node){
		if (node instanceof Array)
			for (var i=0; i < node.length; i++)
				this.remove(node[i]);
		else
			if (node && node.parentNode)
				node.parentNode.removeChild(node);
	},
	//insert new node before sibling, or at the end if sibling doesn't exist
	insertBefore: function(node,before,rescue){
		if (!node) return;
		if (before && before.parentNode)
			before.parentNode.insertBefore(node, before);
		else
			rescue.appendChild(node);
	},
	//return custom ID from html element 
	//will check all parents starting from event's target
	locate:function(e,id){
		if (e.tagName)
			var trg = e;
		else {
			e=e||event;
			var trg=e.target||e.srcElement;
		}
		
		while (trg){
			if (trg.getAttribute){	//text nodes has not getAttribute
				var test = trg.getAttribute(id);
				if (test) return test;
			}
			trg=trg.parentNode;
		}	
		return null;
	},
	//returns position of html element on the page
	offset:function(elem) {
		if (elem.getBoundingClientRect) { //HTML5 method
			var box = elem.getBoundingClientRect();
			var body = document.body;
			var docElem = document.documentElement;
			var scrollTop = window.pageYOffset || docElem.scrollTop || body.scrollTop;
			var scrollLeft = window.pageXOffset || docElem.scrollLeft || body.scrollLeft;
			var clientTop = docElem.clientTop || body.clientTop || 0;
			var clientLeft = docElem.clientLeft || body.clientLeft || 0;
			var top  = box.top +  scrollTop - clientTop;
			var left = box.left + scrollLeft - clientLeft;
			return { y: Math.round(top), x: Math.round(left) };
		} else { //fallback to naive approach
			var top=0, left=0;
			while(elem) {
				top = top + parseInt(elem.offsetTop,10);
				left = left + parseInt(elem.offsetLeft,10);
				elem = elem.offsetParent;
			}
			return {y: top, x: left};
		}
	},
	//returns position of event
	pos:function(ev){
		ev = ev || event;
        if(ev.pageX || ev.pageY)	//FF, KHTML
            return {x:ev.pageX, y:ev.pageY};
        //IE
        var d  =  ((dhx.env.isIE)&&(document.compatMode != "BackCompat"))?document.documentElement:document.body;
        return {
                x:ev.clientX + d.scrollLeft - d.clientLeft,
                y:ev.clientY + d.scrollTop  - d.clientTop
        };
	},
	//prevent event action
	preventEvent:function(e){
		if (e && e.preventDefault) e.preventDefault();
		return dhx.html.stopEvent(e);
	},
	//stop event bubbling
	stopEvent:function(e){
		(e||event).cancelBubble=true;
		return false;
	},
	//add css class to the node
	addCss:function(node,name){
        node.className+=" "+name;
    },
    //remove css class from the node
    removeCss:function(node,name){
        node.className=node.className.replace(RegExp(" "+name,"g"),"");
    }
};

dhx.ready = function(code){
	if (this._ready) code.call();
	else this._ready_code.push(code);
};
dhx._ready_code = [];

//autodetect codebase folder
(function(){
	var temp = document.getElementsByTagName("SCRIPT");	//current script, most probably
	dhx.assert(temp.length,"Can't locate codebase");
	if (temp.length){
		//full path to script
		temp = (temp[temp.length-1].getAttribute("src")||"").split("/");
		//get folder name
		temp.splice(temp.length-1, 1);
		dhx.codebase = temp.slice(0, temp.length).join("/")+"/";
	}
	dhx.event(window, "load", function(){
		dhx.callEvent("onReady",[]);
		dhx.delay(function(){
			dhx._ready = true;
			for (var i=0; i < dhx._ready_code.length; i++)
				dhx._ready_code[i].call();
			dhx._ready_code=[];
		});
	});
	
})();

if (!dhx.ui){
	dhx.ui={};
	dhx.locale={};
}
	
dhx.ui.zIndex = function(){
	return dhx.ui._zIndex++;
};
dhx.ui._zIndex = 1;

dhx.assert_core_ready();


dhx.ready(function(){
	dhx.event(document.body,"click", function(e){
		dhx.callEvent("onClick",[e||event]);
	});
});


/*DHX:Depend core/load.js*/
/* 
	ajax operations 
	
	can be used for direct loading as
		dhx.ajax(ulr, callback)
	or
		dhx.ajax().item(url)
		dhx.ajax().post(url)

*/

/*DHX:Depend core/dhx.js*/

dhx.ajax = function(url,call,master){
	//if parameters was provided - made fast call
	if (arguments.length!==0){
		var http_request = new dhx.ajax();
		if (master) http_request.master=master;
		return http_request.get(url,null,call);
	}
	if (!this.getXHR) return new dhx.ajax(); //allow to create new instance without direct new declaration
	
	return this;
};
dhx.ajax.count = 0;
dhx.ajax.prototype={
	//creates xmlHTTP object
	getXHR:function(){
		if (dhx.env.isIE)
		 return new ActiveXObject("Microsoft.xmlHTTP");
		else 
		 return new XMLHttpRequest();
	},
	/*
		send data to the server
		params - hash of properties which will be added to the url
		call - callback, can be an array of functions
	*/
	send:function(url,params,call){
		var x=this.getXHR();
		if (!dhx.isArray(call))
			call = [call];
		//add extra params to the url
		if (typeof params == "object"){
			var t=[];
			for (var a in params){
				var value = params[a];
				if (value === null || value === dhx.undefined)
					value = "";
				t.push(a+"="+encodeURIComponent(value));// utf-8 escaping
		 	}
			params=t.join("&");
		}
		if (params && !this.post){
			url=url+(url.indexOf("?")!=-1 ? "&" : "?")+params;
			params=null;
		}
		
		x.open(this.post?"POST":"GET",url,!this._sync);
		if (this.post)
			x.setRequestHeader('Content-type','application/x-www-form-urlencoded');
		 
		//async mode, define loading callback
		 var self=this;
		 x.onreadystatechange= function(){
			if (!x.readyState || x.readyState == 4){
				if (dhx.debug_time) dhx.log_full_time("data_loading");	//log rendering time
				dhx.ajax.count++;
				if (call && self){
					for (var i=0; i < call.length; i++)	//there can be multiple callbacks
						if (call[i]){
							var method = (call[i].success||call[i]);
							if (x.status >= 400 || (!x.status && !x.responseText))
								method = call[i].error;
							if (method)
								method.call((self.master||self),x.responseText,x.responseXML,x);
						}
				}
				self.master=null;
				call=self=null;	//anti-leak
			}
		 };
		
		x.send(params||null);
		return x; //return XHR, which can be used in case of sync. mode
	},
	//GET request
	get:function(url,params,call){
		this.post=false;
		return this.send(url,params,call);
	},
	//POST request
	post:function(url,params,call){
		this.post=true;
		return this.send(url,params,call);
	}, 
	sync:function(){
		this._sync = true;
		return this;
	}
};
/*submits values*/
dhx.send = function(url, values, method){
	var form = dhx.html.create("FORM",{"action":url, "method":(method||"POST")},"");
	for (var k in values) {
		var field = dhx.html.create("INPUT",{"type":"hidden","name": k,"value": values[k]},"");
		form.appendChild(field);
	}
	form.style.display = "none";
	document.body.appendChild(form);
	form.submit();
	document.body.removeChild(form);
};


dhx.AtomDataLoader={
	$init:function(config){
		//prepare data store
		this.data = {}; 
		if (config){
			this._settings.datatype = config.datatype||"json";
			this.$ready.push(this._load_when_ready);
		}
	},
	_load_when_ready:function(){
		this._ready_for_data = true;
		
		if (this._settings.url)
			this.url_setter(this._settings.url);
		if (this._settings.data)
			this.data_setter(this._settings.data);
	},
	url_setter:function(value){
		if (!this._ready_for_data) return value;
		this.load(value, this._settings.datatype);	
		return value;
	},
	data_setter:function(value){
		if (!this._ready_for_data) return value;
		this.parse(value, this._settings.datatype);
		return true;
	},
	//loads data from external URL
	load:function(url,call){
		if (url.$proxy) {
			url.load(this, typeof call == "string" ? call : "json");
			return;
		}

		this.callEvent("onXLS",[]);
		if (typeof call == "string"){	//second parameter can be a loading type or callback
			this.data.driver = dhx.DataDriver[call];
			call = arguments[2];
		}
		else
			this.data.driver = dhx.DataDriver["json"];

		//load data by async ajax call
		//loading_key - can be set by component, to ignore data from old async requests
		var callback = [{
			success: this._onLoad,
			error: this._onErrorLoad
		}];
		
		if (call){
			if (dhx.isArray(call))
				callback.push.apply(callback,call);
			else
				callback.push(call);
		}
			

		return dhx.ajax(url,callback,this);
	},
	//loads data from object
	parse:function(data,type){
		this.callEvent("onXLS",[]);
		this.data.driver = dhx.DataDriver[type||"json"];
		this._onLoad(data,null);
	},
	//default after loading callback
	_onLoad:function(text,xml,loader,key){
		var driver = this.data.driver;
		var top = driver.getRecords(driver.toObject(text,xml))[0];
		this.data=(driver?driver.getDetails(top):text);
		this.callEvent("onXLE",[]);
	},
	_onErrorLoad:function(){
		this.callEvent("onXLE",[]);
		this.callEvent("onLoadingError",arguments);
	},
	_check_data_feed:function(data){
		if (!this._settings.dataFeed || this._ignore_feed || !data) return true;
		var url = this._settings.dataFeed;
		if (typeof url == "function")
			return url.call(this, (data.id||data), data);
		url = url+(url.indexOf("?")==-1?"?":"&")+"action=get&id="+encodeURIComponent(data.id||data);
		this.callEvent("onXLS",[]);
		dhx.ajax(url, function(text,xml){
			this._ignore_feed=true;
			this.setValues(dhx.DataDriver.json.toObject(text)[0]);
			this._ignore_feed=false;
			this.callEvent("onXLE",[]);
		}, this);
		return false;
	}
};

/*
	Abstraction layer for different data types
*/

dhx.DataDriver={};
dhx.DataDriver.json={
	//convert json string to json object if necessary
	toObject:function(data){
		if (!data) data="[]";
		if (typeof data == "string"){
			eval ("dhx.temp="+data);
			data = dhx.temp;
		}
		if (data.data){
			var t = data.data;
			t.pos = data.pos;
			t.total_count = data.total_count;
			data = t;
		}

			
		return data;
	},
	//get array of records
	getRecords:function(data){
		if (data && !dhx.isArray(data))
		 return [data];
		return data;
	},
	//get hash of properties for single record
	getDetails:function(data){
		return data;
	},
	//get count of data and position at which new data need to be inserted
	getInfo:function(data){
		return { 
		 _size:(data.total_count||0),
		 _from:(data.pos||0)
		};
	}
};

dhx.DataDriver.json_ext={
	//convert json string to json object if necessary
	toObject:function(data){
		if (!data) data="[]";
		if (typeof data == "string"){
			var temp;
			eval ("temp="+data);
			dhx.temp = [];
			var header  = temp.header;
			for (var i = 0; i < temp.data.length; i++) {
				var item = {};
				for (var j = 0; j < header.length; j++) {
					if (typeof(temp.data[i][j]) != "undefined")
						item[header[j]] = temp.data[i][j];
				}
				dhx.temp.push(item);
			}
			return dhx.temp;
		}
		return data;
	},
	//get array of records
	getRecords:function(data){
		if (data && !dhx.isArray(data))
		 return [data];
		return data;
	},
	//get hash of properties for single record
	getDetails:function(data){
		return data;
	},
	//get count of data and position at which new data need to be inserted
	getInfo:function(data){
		return {
		 _size:(data.total_count||0),
		 _from:(data.pos||0)
		};
	}
};

dhx.DataDriver.html={
	/*
		incoming data can be
		 - collection of nodes
		 - ID of parent container
		 - HTML text
	*/
	toObject:function(data){
		if (typeof data == "string"){
		 var t=null;
		 if (data.indexOf("<")==-1)	//if no tags inside - probably its an ID
			t = dhx.toNode(data);
		 if (!t){
			t=document.createElement("DIV");
			t.innerHTML = data;
		 }
		 
		 return t.getElementsByTagName(this.tag);
		}
		return data;
	},
	//get array of records
	getRecords:function(data){
		if (data.tagName)
		 return data.childNodes;
		return data;
	},
	//get hash of properties for single record
	getDetails:function(data){
		return dhx.DataDriver.xml.tagToObject(data);
	},
	//dyn loading is not supported by HTML data source
	getInfo:function(data){
		return { 
		 _size:0,
		 _from:0
		};
	},
	tag: "LI"
};

dhx.DataDriver.jsarray={
	//eval jsarray string to jsarray object if necessary
	toObject:function(data){
		if (typeof data == "string"){
		 eval ("dhx.temp="+data);
		 return dhx.temp;
		}
		return data;
	},
	//get array of records
	getRecords:function(data){
		return data;
	},
	//get hash of properties for single record, in case of array they will have names as "data{index}"
	getDetails:function(data){
		var result = {};
		for (var i=0; i < data.length; i++) 
		 result["data"+i]=data[i];
		 
		return result;
	},
	//dyn loading is not supported by js-array data source
	getInfo:function(data){
		return { 
		 _size:0,
		 _from:0
		};
	}
};

dhx.DataDriver.csv={
	//incoming data always a string
	toObject:function(data){
		return data;
	},
	//get array of records
	getRecords:function(data){
		return data.split(this.row);
	},
	//get hash of properties for single record, data named as "data{index}"
	getDetails:function(data){
		data = this.stringToArray(data);
		var result = {};
		for (var i=0; i < data.length; i++) 
		 result["data"+i]=data[i];
		 
		return result;
	},
	//dyn loading is not supported by csv data source
	getInfo:function(data){
		return { 
		 _size:0,
		 _from:0
		};
	},
	//split string in array, takes string surrounding quotes in account
	stringToArray:function(data){
		data = data.split(this.cell);
		for (var i=0; i < data.length; i++)
		 data[i] = data[i].replace(/^[ \t\n\r]*(\"|)/g,"").replace(/(\"|)[ \t\n\r]*$/g,"");
		return data;
	},
	row:"\n",	//default row separator
	cell:","	//default cell separator
};

dhx.DataDriver.xml={
	//convert xml string to xml object if necessary
	toObject:function(text,xml){
		if (xml && (xml=this.checkResponse(text,xml)))	//checkResponse - fix incorrect content type and extra whitespaces errors
		 return xml;
		if (typeof text == "string"){
		 return this.fromString(text);
		}
		return text;
	},
	//get array of records
	getRecords:function(data){
		return this.xpath(data,this.records);
	},
	records:"/*/item",
	//get hash of properties for single record
	getDetails:function(data){
		return this.tagToObject(data,{});
	},
	//get count of data and position at which new data_loading need to be inserted
	getInfo:function(data){
		return { 
		 _size:(data.documentElement.getAttribute("total_count")||0),
		 _from:(data.documentElement.getAttribute("pos")||0)
		};
	},
	//xpath helper
	xpath:function(xml,path){
		if (window.XPathResult){	//FF, KHTML, Opera
		 var node=xml;
		 if(xml.nodeName.indexOf("document")==-1)
		 xml=xml.ownerDocument;
		 var res = [];
		 var col = xml.evaluate(path, node, null, XPathResult.ANY_TYPE, null);
		 var temp = col.iterateNext();
		 while (temp){ 
			res.push(temp);
			temp = col.iterateNext();
		}
		return res;
		}	
		else {
			var test = true;
			try {
				if (typeof(xml.selectNodes)=="undefined")
					test = false;
			} catch(e){ /*IE7 and below can't operate with xml object*/ }
			//IE
			if (test)
				return xml.selectNodes(path);
			else {
				//Google hate us, there is no interface to do XPath
				//use naive approach
				var name = path.split("/").pop();
				return xml.getElementsByTagName(name);
			}
		}
	},
	//convert xml tag to js object, all subtags and attributes are mapped to the properties of result object
	tagToObject:function(tag,z){
		z=z||{};
		var flag=false;
				
		//map attributes
		var a=tag.attributes;
		if(a && a.length){
			for (var i=0; i<a.length; i++)
		 		z[a[i].name]=a[i].value;
		 	flag = true;
	 	}
		//map subtags
		
		var b=tag.childNodes;
		var state = {};
		for (var i=0; i<b.length; i++){
			if (b[i].nodeType==1){
				var name = b[i].tagName;
				if (typeof z[name] != "undefined"){
					if (!dhx.isArray(z[name]))
						z[name]=[z[name]];
					z[name].push(this.tagToObject(b[i],{}));
				}
				else
					z[b[i].tagName]=this.tagToObject(b[i],{});	//sub-object for complex subtags
				flag=true;
			}
		}
		
		if (!flag)
			return this.nodeValue(tag);
		//each object will have its text content as "value" property
		z.value = z.value||this.nodeValue(tag);
		return z;
	},
	//get value of xml node 
	nodeValue:function(node){
		if (node.firstChild)
		 return node.firstChild.data;	//FIXME - long text nodes in FF not supported for now
		return "";
	},
	//convert XML string to XML object
	fromString:function(xmlString){
		if (window.DOMParser)		// FF, KHTML, Opera
		 return (new DOMParser()).parseFromString(xmlString,"text/xml");
		if (window.ActiveXObject){	// IE, utf-8 only 
		 var temp=new ActiveXObject("Microsoft.xmlDOM");
		 temp.loadXML(xmlString);
		 return temp;
		}
		dhx.error("Load from xml string is not supported");
	},
	//check is XML correct and try to reparse it if its invalid
	checkResponse:function(text,xml){ 
		if (xml && ( xml.firstChild && xml.firstChild.tagName != "parsererror") )
			return xml;
		//parsing as string resolves incorrect content type
		//regexp removes whitespaces before xml declaration, which is vital for FF
		var a=this.fromString(text.replace(/^[\s]+/,""));
		if (a) return a;
		
		dhx.error("xml can't be parsed",text);
	}
};

/*DHX:Depend core/storage.js*/
if(!window.dhx)
	dhx = {};

if(!dhx.storage)
	dhx.storage = {};

dhx.storage.local = {
	put:function(name, data){
		if(name && window.JSON && window.localStorage){
			window.localStorage.setItem(name, window.JSON.stringify(data));
		}
	},
	get:function(name){
		if(name && window.JSON && window.localStorage){
			var json = window.localStorage.getItem(name);
			if(!json)
				return null;
			return dhx.DataDriver.json.toObject(json);
		}else
			return null;
	},
	remove:function(name){
		if(name && window.JSON && window.localStorage){
			window.localStorage.remove(name);
		}
	}
};

dhx.storage.session = {
	put:function(name, data){
		if(name && window.JSON && window.sessionStorage){
			window.sessionStorage.setItem(name, window.JSON.stringify(data));
		}
	},
	get:function(name){
		if(name && window.JSON && window.sessionStorage){
			var json = window.sessionStorage.getItem(name);
			if(!json)
				return null;
			return dhx.DataDriver.json.toObject(json);
		}else
			return null;
	},
	remove:function(name){
		if(name && window.JSON && window.sessionStorage){
			window.sessionStorage.remove(name);
		}
	}
};

dhx.storage.cookie = {
	put:function(name, data, domain, expires ){
		if(name && window.JSON){
			document.cookie = name + "=" + window.JSON.stringify(data) +
			(( expires && (expires instanceof Date)) ? ";expires=" + expires.toUTCString() : "" ) +
			(( domain ) ? ";domain=" + domain : "" );
		}
	},
	delete_cookie:function( name, domain ){
		if(this._get_cookie(name)) document.cookie = name + "=" +
		(( domain ) ? ";domain=" + domain : "") +
		";expires=Thu, 01-Jan-1970 00:00:01 GMT";
	},
	_get_cookie:function(check_name){
		// first we'll split this cookie up into name/value pairs
		// note: document.cookie only returns name=value, not the other components
		var a_all_cookies = document.cookie.split( ';' );
		var a_temp_cookie = '';
		var cookie_name = '';
		var cookie_value = '';
		var b_cookie_found = false; // set boolean t/f default f

		for (var i = 0; i < a_all_cookies.length; i++ ){
			// now we'll split apart each name=value pair
			a_temp_cookie = a_all_cookies[i].split( '=' );

			// and trim left/right whitespace while we're at it
			cookie_name = a_temp_cookie[0].replace(/^\s+|\s+$/g, '');

			// if the extracted name matches passed check_name
			if (cookie_name == check_name ){
				b_cookie_found = true;
				// we need to handle case where cookie has no value but exists (no = sign, that is):
				if ( a_temp_cookie.length > 1 ){
					cookie_value = unescape( a_temp_cookie[1].replace(/^\s+|\s+$/g, '') );
				}
				// note that in cases where cookie is initialized but no value, null is returned
				return cookie_value;
			}
			a_temp_cookie = null;
			cookie_name = '';
		}
		if ( !b_cookie_found ){
			return null;
		}
		return null;
	},
	get:function(name){
		if(name && window.JSON){
			var json = this._get_cookie(name);
			if(!json)
				return null;
			return dhx.DataDriver.json.toObject(json);
		}else
			return null;
	},
	remove:function(name, domain){
		if(name){
			this.delete_cookie(name, domain);
		}
	}
};

dhx.proxy = dhx.proto({
	/*! constructor
	 **/
	$init: function(config) {
		if (typeof(config) === 'string')
			config = {
				url: config,
				storage: dhx.storage.local
			};
		this._settings = config;
		this.name = "DataProxy";
		this.$proxy = true;
		this.$progress = false;
		this.$ready.push(this._after_init_call);
		this._dests = [];
	},
	// is a proxy object
	_after_init_call: function() {
		this._repeat();
		return true;
	},


	/*! loads data by url and sets it into component
	 *	adds loaded data in storage if success
	 *	loads data from storage if url is not loaded
	 *	@param dest
	 *		component where to load data
	 *	@param driver
	 *		driver type: json, xml, etc
	 **/
	load: function(dest, driver) {
		var _dest = dest;
		if (_dest) dest = _dest;

		if (this._repeat()) {
			this._dests.push({ dest: dest, driver: driver });
		} else {
			var self = this;
			dhx.ajax(this._settings.url,  {
				error:function(text, xml, XmlHttpRequest){
					self.restore(dest, driver, text, xml, XmlHttpRequest);
				},
				success:function(text, xml, XmlHttpRequest){
					self.callEvent('onProxySuccess', [text, xml, XmlHttpRequest]);
					self._to_storage({
						'load;data': text,
						'load;driver': driver
					});
					dest.parse(text, driver);
				}
			});
		}
	},


	restore: function(dest, driver, text, xml, XmlHttpRequest) {
		text = text || "";
		xml = xml || null;
		XmlHttpRequest = XmlHttpRequest || null;
		var restored = this._from_storage('load;data');
		this.callEvent('onProxyError', [text, xml, XmlHttpRequest, restored]);
		if (restored !== null) dest.parse(restored, driver);
	},


	/*! saves data to storage by proxy url:
	 *	@param to_store
	 *		hash of data to save
	 *		{
	 *			path: data,
	 *			path: data
	 *		}
	 *		path is a string like some1.some2
	 *		it will be processed to object:
	 *		{ some1: { some2: data } }
	 **/
	_to_storage: function(to_store) {
		var item = this._settings.storage.get(this._settings.url);
		if (item === null) item = {};

		for (var i in to_store) {
			var path = i;
			var data = to_store[i];
			var steps = path.split(";");
			var subitem = item;
			for (var j = 0; j < steps.length - 1; j++) {
				if (typeof(subitem[steps[j]]) === 'undefined')
					subitem[steps[j]] = {};
				subitem = subitem[steps[j]];
			}
			subitem[steps[steps.length - 1]] = data;
		}
		this._settings.storage.put(this._settings.url, item);
	},

	/*! takes data from storage by path
	 *	@param path
	 *		data path, optional
	 *	@return
	 *		data saved relating to given path,
	 *		if path isn't specified returns all data saved relative to proxy url
	 *		if data not found returns null
	**/
	_from_storage: function(path) {
		var item = this._settings.storage.get(this._settings.url);
		if (item === null) return null;
		if (typeof(path) === 'undefined') return item;
		var steps = path.split(";");
		for (var i = 0; i < steps.length - 1; i++) {
			if (typeof(item[steps[i]]))
				item = item[steps[i]];
			else
				return null;
		}
		return item[steps[steps.length - 1]];
	},


	/*! method to integrate proxy with dataprocessor
	 *	sends list of dp object and save them in storage if url isn't accessable
	 *	@param to_send
	 *		list of objects to save
	 *	@param mode
	 *		get/post
	 *	@param dp
	 *		dataprocessor object
	 **/
	_send: function(to_send, mode, dp) {
        // if there are any unsaved changes then them must be saved first
        var data = this._from_storage();
        data = (data && data.dp) ? data.dp : {};
        var get = (data && data.get) ? data.get : [];
        var post = (data && data.post) ? data.post : [];

        if (!get.length && !post.length) {
            var post = dp._updatesToParams(to_send);
            var url = this._settings.url;

            dhx.assert(url, "url was not set for DataProcessor");
            if (typeof url == "function")
                return url(post);

            url += (url.indexOf("?") == -1) ? "?" : "&";
            url += "editing=true";

            var self = this;
            dhx.ajax()[mode](url, post, {
                success: function(text,data,loader) {
                    dp._processResult(text,data,loader);
                    self._repeat();
                },
                error: function(text,data,loader) {
                    self._items_to_storage(to_send, dp, mode);
                    dp._processResult(text,data,loader);
                    dp._updates = [];
                }
            });
            this._process_local_json(to_send);
        } else {
            this._items_to_storage(to_send, dp, mode);
            dp._updates = [];
            for (var i = 0; i < to_send.length; i++) {
                var id = to_send[i].id;
                dp.setUpdated(id, false);
                if (dp._in_progress[id])
                    delete dp._in_progress[id];
            }
            this._repeat();
        }
	},


	/*! saves items into storage to send them later
	 *	@param to_send
	 *		list of items to save
	 *	@param dp
	 *		dataprocessor object
	 **/
	_items_to_storage: function(to_send, dp, method) {
		var data = this._from_storage('dp');
		if (!data) data = {post: [], get: []};
		if (!data.post) data.post = [];
		if (!data.get) data.get = [];
		data[method] = this._items_concat(data[method], to_send);
//		data[method] = data[method].concat(to_send);
		this._to_storage({'dp': data});
		for (var i = 0; i < to_send.length; i++) {
			var item = to_send[i];
			dp.callEvent("onAddToProxy", [item.id, item]);
		}
	},


	_items_concat: function(to, from) {
		var master = {};
		var master_index = -1;
		for (var i = 0; i < to.length; i++) {
			if (typeof(to[i]) == 'object') {
				master = to[i];
				master_index = i;
				break;
			}
		}

		var ids = (master.ids || "");
		for (var i = 0; i < from.length; i++) {
			var item = from[i];
			item = this._updatesToParams([ item ]);
			var id = item.ids;
			if (ids.indexOf(item.ids) == -1) {
				ids += ((ids === "") ? "" : ",") + id;
			} else {
				// some changes where made for this item earlier.
				// we have to combain their statuses
				var old_status = master[id + '_!nativeeditor_status'];
				var new_status = item[id + '_!nativeeditor_status'];
				new_status = this._unit_statuses(old_status, new_status);
				item[id + '_!nativeeditor_status'] = new_status;
			}

			for (var j in item) master[j] = item[j];

			if (item[id + '_!nativeeditor_status'] == 'unset') {
				ids = ids.split(",");
				var j = 0;
				while (j < ids.length) {
					if (ids[j] == id)
						ids.splice(j, 1);
					else
						j++;
				}
				ids = ids.join(",");
				master = this._remove_item(master, id);
			}
		}
		master.ids = ids;

		if (this._is_empty(master)) {
			if (master_index >= 0)
				to.splice(master_index, 1);
		} else {
			if (master_index >= 0)
				to[master_index] = master;
			else
				to.push(master);
		}
		return to;
	},

	/*! combains two statuses of one item changes
	 *	old_status - status of first action
	 *	new _status - status of second action
	 **/
	_unit_statuses: function(old_status, new_status) {
		switch (old_status) {
			case 'insert':
				if (new_status == 'update') return 'insert';
				if (new_status == 'insert') return 'insert';
				if (new_status == 'delete') return 'unset';
            case 'inserted':
                if (new_status == 'updated') return 'inserted';
                if (new_status == 'inserted') return 'inserted';
                if (new_status == 'deleted') return 'unset';
			case 'update':
            case 'updated':
				return new_status;
			case 'delete':
				if (new_status == 'update') return 'update';
				if (new_status == 'insert') return 'update';
				if (new_status == 'delete') return 'delete';
            case 'deleted':
                if (new_status == 'updated') return 'updated';
                if (new_status == 'inserted') return 'updated';
                if (new_status == 'deleted') return 'deleted';
			default:
				break;
		}
		return new_status;
	},


	_remove_item: function(obj, rem_id) {
		var len = 0;
		for (var i in obj) {
			if (i.indexOf(rem_id + '_') === 0)
				delete obj[i];
			else
				len++;
		}
		return obj;
	},


	/*! checks if obj is empty
	 **/
	_is_empty: function(obj) {
		for (var i in obj)
			if (i !== 'ids')
				return false;
		return true;
	},


	/*! process changes in saved local json data
	 *	for example if item was deleted we may delete it in saved in storage json
	 *	@param to_send
	 *		list of data to send
	 **/
	_process_local_json: function(to_send) {
		var data = this._from_storage('load;data');
		var driver = this._from_storage('load;driver');
		if (driver !== 'json') return;
		if (data === null) return;

		try {
			data = 'dhx.temp = ' + data;
			eval(data);
			data = dhx.temp;
		} catch(e) {}
		for (var i = 0; i < to_send.length; i++) {
			var index = this._index(to_send[i].id, data);
			var item = to_send[i];
			for (var j in item.data) {
				if (j.substr(0, 1) == '$')
					delete item.data[j];
			}
			if (to_send[i].operation == 'insert' && index !== null) to_send[i].operation = 'update';
			switch (to_send[i].operation) {
				case 'delete':
					if (index !== null) data.splice(index, 1);
					break;
				case 'insert':
					data.push(to_send[i].data);
					break;
				case 'update':
				default:
					if (index !== null) data[index] = to_send[i].data;
					break;
			}
		}
		data = window.JSON.stringify(data);
		this._to_storage({'load;data': data});
	},


	/*! gets item index by id
	 *	@param id
	 *		id to find
	 *	@param data
	 *		list of data
	 *	@return
	 *		index or null
	 **/
	_index: function(id, data) {
		for (var i = 0; i < data.length; i++) {
			if (data[i].id == id)
				return i;
		}
		return null;
	},


	/*! append saved in storage items to repeat saving for them
	 *	@param to_send
	 *		list of items from dataprocessor
	 **/
	_items_from_storage: function(to_send) {
		var items = this._from_storage('dp');
		if (!items) return;
		var hash = {};
		for (var i = 0; i < to_send.length; i++)
			hash[to_send[i].id] = true;
		for (var i = 0; i < items.length; i++) {
			if (typeof(hash[items[i].id]) === 'undefined') {
				to_send.push(items[i]);
				hash[items[i].id] = true;
			}
		}
//		this._to_storage({'dp': {}});
	},


	/*! method to load url like dhx.ajax.get
	 *	but also saves request parameters if it fails to repeat later
	 **/
	get: function(params, callback) {
		this._send_request(params, callback, 'get');
	},

	/*! method to load url like dhx.ajax.post
	 *	but also saves request parameters if it fails to repeat later
	 **/
	post: function(params, callback) {
		this._send_request(params, callback, 'post');
	},

	_send_request: function(params, callback, method) {
		if (callback && typeof(callback) == 'function')
			callback = {	
				success: callback,
				error: callback
			};
		var self = this;
		dhx.ajax()[method](this._settings.url, params, {
			error: function(text, xml, XmlHttpRequest) {
				// we should save request here.
				var obj = self._from_storage();
				if (obj === null) obj = {};
				if (typeof(obj[method]) == 'undefined') obj[method] = [];

				obj[method].push(params);
				self._to_storage(obj);

				if (callback)
					callback.error(text, xml, XmlHttpRequest);
			},
			success: function(text, xml, XmlHttpRequest) {
				if (callback)
					callback.success(text, xml, XmlHttpRequest);
				self._repeat();
			}
		});
	},


	/*! returns true if exists saved item and it was sent
	 *	false otherwise
	 **/
	_repeat: function() {
		if (this.$progress) return true;
		var data = this._from_storage();
		data = (data && data.dp) ? data.dp : {};
		var get = (data && data.get) ? data.get : [];
		var self = this;

		var method = (get.length > 0) ? 'get' : 'post';
		data = (typeof(data[method]) !== 'undefined') ? data[method] : [];

		if (typeof(data[0]) !== 'undefined') {
			this.$progress = true;
			var item = data = data[0];
			this[method](data, {
				success: function(text, xml, httpXmlRequest) {
					var data = self._from_storage().dp[method];
					data.splice(0, 1);
					var params = {dp: {}};
					params.dp[method] = data;
					self._to_storage(params);

					self.callEvent('onRequestEnd', [text, xml, httpXmlRequest]);
					self.$progress = false;
					if (data.length === 0)
						self._load_dests(true);
					else
						self._repeat();
				},
				error: function() {
					self.$progress = false;
					self._load_dests(false);
				}
			});
			return true;
		}
		return false;
	},

	_load_dests: function(mode) {
		for (var i = 0; i < this._dests.length; i++)
			if (mode)
				this.load(this._dests[i].dest, this._dests[i].driver);
			else
				this.restore(this._dests[i].dest, this._dests[i].driver);
	},

	/*! process updates list to POST and GET params according dataprocessor protocol
	 *	@param updates
	 *		list of objects { id: "item id", data: "data hash", operation: "type of operation"}
	 *	@return
	 *		object { post: { hash of post params as name: value }, get: { hash of get params as name: value } }
	 **/

	_updatesToParams: function(updates) {
		var post_params = {};

		if (!this._settings.single){
			var ids = [];
			for (var i = 0; i < updates.length; i++) {
				var action = updates[i];
				ids.push(action.id);
				this._updatesData(action.data, post_params, action.id+"_", action.operation);
			}
			post_params.ids = ids.join(",");
		} else
			this._updatesData(updates[0].data, post_params, "", updates[0].operation);

		return post_params;
	},

	_updatesData:function(source, target, prefix, operation){
		for (var j in source){
			if (j.indexOf("$")!==0)
				target[prefix + j] = source[j];
		}
		target[prefix + '!nativeeditor_status'] = operation;
	}
}, dhx.EventSystem);