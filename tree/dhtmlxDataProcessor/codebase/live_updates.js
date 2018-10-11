if (typeof(dataProcessor) !== "undefined") {

	dataProcessor.prototype.live_updates = function(url) {
		if (typeof Faye === "undefined") return;
		var self = this;
		var lu = this._live_upd = {};

		lu.client = new Faye.Client(url);
    	lu.subscription = lu.client.subscribe('/update', function(update){
			var sid = update.sid;
			var tid = update.tid;
			var status = update.status;
			var data = update.data;
			// prevent selection loosing
			if (self.obj.isSelected(sid)) data.$selected = true;
            self.callEvent("onLiveUpdate", [status, sid, tid, data]);
			switch (status) {
				case 'updated':
				case 'update':
				case 'inserted':
				case 'insert':
					if (self.obj.exists(sid)) {
						if (self.obj.isLUEdit(data) === sid) {
							self.obj.stopEditBefore();
						};
						self.ignore(function() {
							self.obj.update(sid, data);
							if (sid !== tid) self.obj.changeId(sid, tid);
						});
					} else {
						data.id = tid;
						self.ignore(function() {
							self.obj.add(data);
						});
					}
					break;
				case 'deleted':
				case 'delete':
					self.ignore(function() {
						if (self.obj.exists(sid)) {
							self.obj.setUserData(sid, '!nativeeditor_status', 'true_deleted');
							self.obj.stopEditBefore();
							self.obj.remove(sid);
							if (self.obj.isLUEdit(data) === sid) {
								// prevent loosing not-saved data
								self.obj.preventLUCollision(data);
								if (self.obj.callEvent("onLiveUpdateCollision", [sid, tid, status, data]) === false) {
									// we have to close editor here without saving
									self.obj.stopEditAfter();
								}
							}
						}
					});
					break;
			}
		});

        this.changed = function(sid, action, tid, tag){
            var item;
            if (self.obj.exists(sid))
                item = self.obj.item(sid);
            else
                item = {};

            if (typeof(item.$selected) !== 'undefined') delete item.$selected;
            if (typeof(item.$template) !== 'undefined') delete item.$template;
            lu.client.publish('/broadcast', { sid: sid, tid: tid, status: action, data: item });
        };
		this.attachEvent("onAfterUpdate", this.changed);

	}

}


if (typeof(dhtmlXGridObject) !== "undefined") {
	dhtmlXGridObject.prototype.item = function(id) {
		var row = this.getRowById(id);
		if (!row) return [];

		var data = { data: this._getRowArray(row) };
		return data;
	};
	dhtmlXGridObject.prototype.update = function(id, data) {
		data = data.data;
		for (var i = 0; i < data.length; i++) {
			var cell = this.cells(id, i);
			cell.setValue(data[i]);
		}
	};
	dhtmlXGridObject.prototype.remove = function(id) {
		if (this.doesRowExist(id))
			this.deleteRow(id);
	};

	dhtmlXGridObject.prototype.exists = function(id) {
		return this.doesRowExist(id);
	};

	dhtmlXGridObject.prototype.add = function(data) {
		var id = data.id;
		data = data.data;
		return this.addRow(id, data);
	};

	dhtmlXGridObject.prototype.changeId = function(old_id, new_id) {
		return this.changeRowId(old_id, new_id);
	};

	dhtmlXGridObject.prototype.stopEditBefore = function() {
		this.editStop();
	};
	dhtmlXGridObject.prototype.stopEditAfter = function() {};

	dhtmlXGridObject.prototype.isLUEdit = function(data) {
		if (this.editor === null) return null;
		return this.row.idd;
	};

	dhtmlXGridObject.prototype.isSelected = function(id) { return false; };
}


if (typeof(dhtmlXTreeObject) !== "undefined") {
	dhtmlXTreeObject.prototype.item = function(id) {
		var text = this.getItemText(id);
		if (!text) text = "";
		var parent = this.getParentId(id);
		if (!parent) parent = "0";
		return {
			text: text,
			parent: parent
		};
	};
	dhtmlXTreeObject.prototype.update = function(id, data) {
		this.setItemText(id, data.text);
		this.moveItem(id, 'item_child', data.parent);
	};
	dhtmlXTreeObject.prototype.remove = function(id) {
		if (this.exists(id))
			this.deleteItem(id);
	};
	
	dhtmlXTreeObject.prototype.exists = function(id) {
		var text = this.getItemText(id);
		return (text !== 0) ? true : false;
	};
	
	dhtmlXTreeObject.prototype.add = function(data) {
		return this.insertNewChild(data.parent, data.id, data.text);
	};

	dhtmlXTreeObject.prototype.changeId = function(old_id, new_id) {
		return this.changeItemId(old_id, new_id);
	};

	dhtmlXTreeObject.prototype.stopEditBefore = function() {
		this.stopEdit();
	};
	dhtmlXTreeObject.prototype.stopEditAfter = function() {};

	dhtmlXTreeObject.prototype.isLUEdit = function(data) {
		return (this._editCell) ? this._editCell.id : null;
	};

	dhtmlXTreeObject.prototype.isSelected = function(id) { return false; };
}



if (typeof(scheduler) !== "undefined") {
	scheduler.item = function(id) {
		var event = this.getEvent(id);
		if (!event) return {};
		var data = {};
		for (var i in event)
			data[i] = event[i];

		data.start_date = scheduler.date.date_to_str(scheduler.config.api_date)(event.start_date);
		data.end_date = scheduler.date.date_to_str(scheduler.config.api_date)(event.end_date);
		return data;
	};
	scheduler.update = function(id, data) {
		var event = this.getEvent(id);
		for (var i in data)
			if (i != 'start_date' && i!='end_date')
				event[i] = data[i];
		var convert = scheduler.date.str_to_date(scheduler.config.api_date);
		scheduler.setEventStartDate(id, convert(data.start_date));
		scheduler.setEventEndDate(id, convert(data.end_date));
		this.updateEvent(id);
	};
	scheduler.remove = function(id) {
		if (this.exists(id))
			this.deleteEvent(id, true);
	};

	scheduler.exists = function(id) {
		var event = this.getEvent(id);
		return event ? true : false;
	};

	scheduler.add = function(data) {
		return this.addEvent(data.start_date, data.end_date, data.text, data.id, data);
	};

	scheduler.changeId = function(old_id, new_id) {
		return this.changeEventId(old_id, new_id);
	};

	scheduler.stopEditBefore = function() {};

	scheduler.stopEditAfter = function() {
		this.endLightbox(false, this._lightbox);
	};

	scheduler.preventLUCollision = function(data) {
		this._new_event=this._lightbox_id;
		data.id = this._lightbox_id;
		this._events[this._lightbox_id] = data;
	};

	scheduler.isLUEdit = function(data) {
		if (this._lightbox_id)
			return this._lightbox_id;
		return null;
	};

	scheduler.isSelected = function(id) { return false; };
}


if (typeof(dhtmlXDataView) !== "undefined") {
	dhtmlXDataView.prototype.stopEditBefore = function() {
		this.stopEdit(true);
	};
	dhtmlXDataView.prototype.stopEditAfter = function() {};
	dhtmlXDataView.prototype.isLUEdit = function(data) { return this.isEdit(); };
}