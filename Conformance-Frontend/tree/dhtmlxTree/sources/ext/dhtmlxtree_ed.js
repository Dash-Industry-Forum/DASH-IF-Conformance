//v.3.6 build 130416

/*
Copyright Dinamenta, UAB http://www.dhtmlx.com
You allowed to use this component or parts of it under GPL terms
To use it on other terms or get Professional edition of the component please contact us at sales@dhtmlx.com
*/
/*
Purpose: item edit extension
*/


/**
*     @desc: enable editing of item text
*     @param:  mode - true/false
*     @type: public
*     @topic: 0
*/
dhtmlXTreeObject.prototype.enableItemEditor=function(mode){
        this._eItEd=convertStringToBoolean(mode);
        if (!this._eItEdFlag){

            this._edn_click_IE=true;
            this._edn_dblclick=true;
            this._ie_aFunc=this.aFunc;
            this._ie_dblclickFuncHandler=this.dblclickFuncHandler;

            this.setOnDblClickHandler(function (a,b) {
                if (this._edn_dblclick) this._editItem(a,b);
                return true;
				});

            this.setOnClickHandler(function (a,b) {
                this._stopEditItem(a,b);
                    if ((this.ed_hist_clcik==a)&&(this._edn_click_IE))
                        this._editItem(a,b);
                this.ed_hist_clcik=a;
                return true;
                });

            this._eItEdFlag=true;

            }
        };

/**
*     @desc: set onEdit handler ( multi handler event)
*     @param:  func - function which will be called on edit related events
*     @type: depricated
*     @event:  onEdit
*     @depricated: use grid.attachEvent("onEdit",func); instead
*     @eventdesc: Event occurs on 4 different stages of edit process: before editing started (cancelable), after editing started, before closing (cancelable), after closed
*     @eventparam: state - 0 before editing started , 1 after editing started, 2 before closing, 3 after closed
*     @eventparam: id - id of edited items
*     @eventparam: tree - tree object
*     @eventparam: value - for stage 0 and 2, value of editor
*     @eventreturn: for stages 0 and 2; true - confirm opening/closing, false - deny opening/closing;  text - edit value
*     @topic: 0
*/
dhtmlXTreeObject.prototype.setOnEditHandler=function(func){
		this.attachEvent("onEdit",func);
        };



/**
*     @desc: define which events must start editing
*     @param:  click_IE - click on already selected item - true/false [true by default]
*     @param:  dblclick - on double click
*     @type: public
*     @topic: 0
*/
dhtmlXTreeObject.prototype.setEditStartAction=function(click_IE, dblclick){
        this._edn_click_IE=convertStringToBoolean(click_IE);
        this._edn_dblclick=convertStringToBoolean(dblclick);
        };

dhtmlXTreeObject.prototype._stopEdit=function(a,mode){
    if  (this._editCell){
        this.dADTempOff=this.dADTempOffEd;
        if (this._editCell.id!=a){
			
			var editText=true;
			if(!mode){
	            editText=this.callEvent("onEdit",[2,this._editCell.id,this,this._editCell.span.childNodes[0].value]);
			}
			else{
				editText = false;
				this.callEvent("onEditCancel",[this._editCell.id,this._editCell._oldValue]);
			}
	        if (editText===true)
	           	editText=this._editCell.span.childNodes[0].value;
	        else if (editText===false) editText=this._editCell._oldValue;
	        
			var changed = (editText!=this._editCell._oldValue);
	        this._editCell.span.innerHTML=editText;
	        this._editCell.label=this._editCell.span.innerHTML;
			var cSS=this._editCell.i_sel?"selectedTreeRow":"standartTreeRow";
	        this._editCell.span.className=cSS;
	        this._editCell.span.parentNode.className="standartTreeRow";
	        this._editCell.span.style.paddingRight=this._editCell.span.style.paddingLeft='5px';
	        this._editCell.span.onclick=this._editCell.span.ondblclick=function(){};
	        
	        var id=this._editCell.id; 
	        if (this.childCalc)  this._fixChildCountLabel(this._editCell);
	        this._editCell=null;
	        
			if(!mode)
	        	this.callEvent("onEdit",[3,id,this,changed]);
	        
			if (this._enblkbrd){
				this.parentObject.lastChild.focus();
				this.parentObject.lastChild.focus();
			}
        }
    }
}

dhtmlXTreeObject.prototype._stopEditItem=function(id,tree){
    this._stopEdit(id);
};

/**
*     @desc:  switch currently edited item back to normal view
*     @type: public
*     @topic: 0
*/

dhtmlXTreeObject.prototype.stopEdit=function(mode){
    if (this._editCell)
        this._stopEdit(this._editCell.id+"_non",mode);
}

/**
*     @desc: open editor for specified item
*     @param:  id - item ID
*     @type: public
*     @topic: 0
*/
dhtmlXTreeObject.prototype.editItem=function(id){
    this._editItem(id,this);
}

dhtmlXTreeObject.prototype._editItem=function(id,tree){
    if (this._eItEd){
        this._stopEdit();
        var temp=this._globalIdStorageFind(id);
		if (!temp) return;
				
	    var editText = this.callEvent("onEdit",[0,id,this,temp.span.innerHTML]);
        if (editText===true)
            editText = (typeof temp.span.innerText!="undefined"?temp.span.innerText:temp.span.textContent);
        else if (editText===false) return;
        this.dADTempOffEd=this.dADTempOff;
        this.dADTempOff=false;


        this._editCell=temp;
        temp._oldValue=editText;
        temp.span.innerHTML="<input type='text' class='intreeeditRow' />";
        temp.span.style.paddingRight=temp.span.style.paddingLeft='0px';
        temp.span.onclick = temp.span.ondblclick= function(e){
			(e||event).cancelBubble = true;
		}

        temp.span.childNodes[0].value=editText;

        temp.span.childNodes[0].onselectstart=function(e){
            (e||event).cancelBubble=true;
            return true;
        }
        temp.span.childNodes[0].onmousedown=function(e){
            (e||event).cancelBubble=true;
            return true;
        }

        temp.span.childNodes[0].focus();
        temp.span.childNodes[0].focus();
//		temp.span.childNodes[0].select();
        temp.span.onclick=function (e){ (e||event).cancelBubble=true; return false; };
        temp.span.className="";
        temp.span.parentNode.className="";

        var self=this;

        temp.span.childNodes[0].onkeydown=function(e){
            if (!e) e=window.event;
            if (e.keyCode==13){
				 e.cancelBubble=true;
				 self._stopEdit(window.undefined);	
			}
			else if (e.keyCode==27){
            	self._editCell.span.childNodes[0].value=self._editCell._oldValue;
				self._stopEdit(window.undefined);
			}
			(e||event).cancelBubble=true;
        }
        this.callEvent("onEdit",[1,id,this]);
    }
};
//Dinamenta, UABtd. www.dhtmlx.com
