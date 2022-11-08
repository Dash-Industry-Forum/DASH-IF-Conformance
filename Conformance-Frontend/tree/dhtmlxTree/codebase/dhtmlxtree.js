/*
Product Name: dhtmlxTree 
Version: 5.1.0 
Edition: Standard 
License: content of this file is covered by DHTMLX Commercial or enterpri. Usage outside GPL terms is prohibited. To obtain Commercial or Enterprise license contact sales@dhtmlx.com
Copyright UAB Dinamenta http://www.dhtmlx.com
*/

if (typeof (window.dhx) == "undefined") {
    window.dhx = window.dhx4 = {
        version: "5.1.0",
        skin: null,
        skinDetect: function (a) {
            var c = Math.floor(dhx4.readFromCss(a + "_skin_detect") / 10) * 10;
            return {
                10: "dhx_skyblue",
                20: "dhx_web",
                30: "dhx_terrace",
                40: "material"
            } [c] || null
        },
        readFromCss: function (d, e, f) {
            var c = document.createElement("DIV");
            c.className = d;
            if (document.body.firstChild != null) {
                document.body.insertBefore(c, document.body.firstChild)
            } else {
                document.body.appendChild(c)
            }
            if (typeof (f) == "string") {
                c.innerHTML = f
            }
            var a = c[e || "offsetWidth"];
            c.parentNode.removeChild(c);
            c = null;
            return a
        },
        lastId: 1,
        newId: function () {
            return this.lastId++
        },
        zim: {
            data: {},
            step: 5,
            first: function () {
                return 100
            },
            last: function () {
                var d = this.first();
                for (var c in this.data) {
                    d = Math.max(d, this.data[c])
                }
                return d
            },
            reserve: function (a) {
                this.data[a] = this.last() + this.step;
                return this.data[a]
            },
            clear: function (a) {
                if (this.data[a] != null) {
                    this.data[a] = null;
                    delete this.data[a]
                }
            }
        },
        s2b: function (a) {
            if (typeof (a) == "string") {
                a = a.toLowerCase()
            }
            return (a == true || a == 1 || a == "true" || a == "1" || a == "yes" || a == "y" || a == "on")
        },
        s2j: function (s) {
            var obj = null;
            dhx4.temp = null;
            try {
                eval("dhx4.temp=" + s)
            } catch (e) {
                dhx4.temp = null
            }
            obj = dhx4.temp;
            dhx4.temp = null;
            return obj
        },
        absLeft: function (a) {
            if (typeof (a) == "string") {
                a = document.getElementById(a)
            }
            return this.getOffset(a).left
        },
        absTop: function (a) {
            if (typeof (a) == "string") {
                a = document.getElementById(a)
            }
            return this.getOffset(a).top
        },
        _aOfs: function (a) {
            var d = 0,
                c = 0;
            while (a) {
                d = d + parseInt(a.offsetTop);
                c = c + parseInt(a.offsetLeft);
                a = a.offsetParent
            }
            return {
                top: d,
                left: c
            }
        },
        _aOfsRect: function (e) {
            var h = e.getBoundingClientRect();
            var j = document.body;
            var c = document.documentElement;
            var a = window.pageYOffset || c.scrollTop || j.scrollTop;
            var f = window.pageXOffset || c.scrollLeft || j.scrollLeft;
            var g = c.clientTop || j.clientTop || 0;
            var k = c.clientLeft || j.clientLeft || 0;
            var l = h.top + a - g;
            var d = h.left + f - k;
            return {
                top: Math.round(l),
                left: Math.round(d)
            }
        },
        getOffset: function (a) {
            if (a.getBoundingClientRect) {
                return this._aOfsRect(a)
            } else {
                return this._aOfs(a)
            }
        },
        _isObj: function (a) {
            return (a != null && typeof (a) == "object" && typeof (a.length) == "undefined")
        },
        _copyObj: function (e) {
            if (this._isObj(e)) {
                var d = {};
                for (var c in e) {
                    if (typeof (e[c]) == "object" && e[c] != null) {
                        d[c] = this._copyObj(e[c])
                    } else {
                        d[c] = e[c]
                    }
                }
            } else {
                var d = [];
                for (var c = 0; c < e.length; c++) {
                    if (typeof (e[c]) == "object" && e[c] != null) {
                        d[c] = this._copyObj(e[c])
                    } else {
                        d[c] = e[c]
                    }
                }
            }
            return d
        },
        screenDim: function () {
            var a = (navigator.userAgent.indexOf("MSIE") >= 0);
            var c = {};
            c.left = document.body.scrollLeft;
            c.right = c.left + (window.innerWidth || document.body.clientWidth);
            c.top = Math.max((a ? document.documentElement : document.getElementsByTagName("html")[0]).scrollTop, document.body.scrollTop);
            c.bottom = c.top + (a ? Math.max(document.documentElement.clientHeight || 0, document.documentElement.offsetHeight || 0) : window.innerHeight);
            return c
        },
        selectTextRange: function (f, h, c) {
            f = (typeof (f) == "string" ? document.getElementById(f) : f);
            var a = f.value.length;
            h = Math.max(Math.min(h, a), 0);
            c = Math.min(c, a);
            if (f.setSelectionRange) {
                try {
                    f.setSelectionRange(h, c)
                } catch (g) {}
            } else {
                if (f.createTextRange) {
                    var d = f.createTextRange();
                    d.moveStart("character", h);
                    d.moveEnd("character", c - a);
                    try {
                        d.select()
                    } catch (g) {}
                }
            }
        },
        transData: null,
        transDetect: function () {
            if (this.transData == null) {
                this.transData = {
                    transProp: false,
                    transEv: null
                };
                var d = {
                    MozTransition: "transitionend",
                    WebkitTransition: "webkitTransitionEnd",
                    OTransition: "oTransitionEnd",
                    msTransition: "transitionend",
                    transition: "transitionend"
                };
                for (var c in d) {
                    if (this.transData.transProp == false && document.documentElement.style[c] != null) {
                        this.transData.transProp = c;
                        this.transData.transEv = d[c]
                    }
                }
                d = null
            }
            return this.transData
        },
        _xmlNodeValue: function (a) {
            var d = "";
            for (var c = 0; c < a.childNodes.length; c++) {
                d += (a.childNodes[c].nodeValue != null ? a.childNodes[c].nodeValue.toString().replace(/^[\n\r\s]{0,}/, "").replace(/[\n\r\s]{0,}$/, "") : "")
            }
            return d
        }
    };
    window.dhx4.isIE = (navigator.userAgent.indexOf("MSIE") >= 0 || navigator.userAgent.indexOf("Trident") >= 0);
    window.dhx4.isIE6 = (window.XMLHttpRequest == null && navigator.userAgent.indexOf("MSIE") >= 0);
    window.dhx4.isIE7 = (navigator.userAgent.indexOf("MSIE 7.0") >= 0 && navigator.userAgent.indexOf("Trident") < 0);
    window.dhx4.isIE8 = (navigator.userAgent.indexOf("MSIE 8.0") >= 0 && navigator.userAgent.indexOf("Trident") >= 0);
    window.dhx4.isIE9 = (navigator.userAgent.indexOf("MSIE 9.0") >= 0 && navigator.userAgent.indexOf("Trident") >= 0);
    window.dhx4.isIE10 = (navigator.userAgent.indexOf("MSIE 10.0") >= 0 && navigator.userAgent.indexOf("Trident") >= 0 && window.navigator.pointerEnabled != true);
    window.dhx4.isIE11 = (navigator.userAgent.indexOf("Trident") >= 0 && window.navigator.pointerEnabled == true);
    window.dhx4.isEdge = (navigator.userAgent.indexOf("Edge") >= 0);
    window.dhx4.isOpera = (navigator.userAgent.indexOf("Opera") >= 0);
    window.dhx4.isChrome = (navigator.userAgent.indexOf("Chrome") >= 0) && !window.dhx4.isEdge;
    window.dhx4.isKHTML = (navigator.userAgent.indexOf("Safari") >= 0 || navigator.userAgent.indexOf("Konqueror") >= 0) && !window.dhx4.isEdge;
    window.dhx4.isFF = (navigator.userAgent.indexOf("Firefox") >= 0);
    window.dhx4.isIPad = (navigator.userAgent.search(/iPad/gi) >= 0);
    window.dhx4.dnd = {
        evs: {},
        p_en: ((window.dhx4.isIE || window.dhx4.isEdge) && (window.navigator.pointerEnabled || window.navigator.msPointerEnabled)),
        _mTouch: function (a) {
            return (window.dhx4.isIE10 && a.pointerType == a.MSPOINTER_TYPE_MOUSE || window.dhx4.isIE11 && a.pointerType == "mouse" || window.dhx4.isEdge && a.pointerType == "mouse")
        },
        _touchOn: function (a) {
            if (a == null) {
                a = document.body
            }
            a.style.touchAction = a.style.msTouchAction = "";
            a = null
        },
        _touchOff: function (a) {
            if (a == null) {
                a = document.body
            }
            a.style.touchAction = a.style.msTouchAction = "none";
            a = null
        }
    };
    if (window.navigator.pointerEnabled == true) {
        window.dhx4.dnd.evs = {
            start: "pointerdown",
            move: "pointermove",
            end: "pointerup"
        }
    } else {
        if (window.navigator.msPointerEnabled == true) {
            window.dhx4.dnd.evs = {
                start: "MSPointerDown",
                move: "MSPointerMove",
                end: "MSPointerUp"
            }
        } else {
            if (typeof (window.addEventListener) != "undefined") {
                window.dhx4.dnd.evs = {
                    start: "touchstart",
                    move: "touchmove",
                    end: "touchend"
                }
            }
        }
    }
}
if (typeof (window.dhx4.template) == "undefined") {
    window.dhx4.trim = function (a) {
        return String(a).replace(/^\s{1,}/, "").replace(/\s{1,}$/, "")
    };
    window.dhx4.template = function (c, d, a) {
        return c.replace(/#([a-z0-9_-]{1,})(\|([^#]*))?#/gi, function () {
            var h = arguments[1];
            var g = window.dhx4.trim(arguments[3]);
            var j = null;
            var f = [d[h]];
            if (g.length > 0) {
                g = g.split(":");
                var e = [];
                for (var l = 0; l < g.length; l++) {
                    if (l > 0 && e[e.length - 1].match(/\\$/) != null) {
                        e[e.length - 1] = e[e.length - 1].replace(/\\$/, "") + ":" + g[l]
                    } else {
                        e.push(g[l])
                    }
                }
                j = e[0];
                for (var l = 1; l < e.length; l++) {
                    f.push(e[l])
                }
            }
            if (typeof (j) == "string" && typeof (window.dhx4.template[j]) == "function") {
                return window.dhx4.template[j].apply(window.dhx4.template, f)
            }
            if (h.length > 0 && typeof (d[h]) != "undefined") {
                if (a == true) {
                    return window.dhx4.trim(d[h])
                }
                return String(d[h])
            }
            return ""
        })
    };
    window.dhx4.template.date = function (a, c) {
        if (a != null) {
            if (a instanceof Date) {
                return window.dhx4.date2str(a, c)
            } else {
                a = a.toString();
                if (a.match(/^\d*$/) != null) {
                    return window.dhx4.date2str(new Date(parseInt(a)), c)
                }
                return a
            }
        }
        return ""
    };
    window.dhx4.template.maxlength = function (c, a) {
        return String(c).substr(0, a)
    };
    window.dhx4.template.number_format = function (e, f, d, a) {
        var c = window.dhx4.template._parseFmt(f, d, a);
        if (c == false) {
            return e
        }
        return window.dhx4.template._getFmtValue(e, c)
    };
    window.dhx4.template.lowercase = function (a) {
        if (typeof (a) == "undefined" || a == null) {
            a = ""
        }
        return String(a).toLowerCase()
    };
    window.dhx4.template.uppercase = function (a) {
        if (typeof (a) == "undefined" || a == null) {
            a = ""
        }
        return String(a).toUpperCase()
    };
    window.dhx4.template._parseFmt = function (j, d, a) {
        var e = j.match(/^([^\.\,0-9]*)([0\.\,]*)([^\.\,0-9]*)/);
        if (e == null || e.length != 4) {
            return false
        }
        var c = {
            i_len: false,
            i_sep: (typeof (d) == "string" ? d : ","),
            d_len: false,
            d_sep: (typeof (a) == "string" ? a : "."),
            s_bef: (typeof (e[1]) == "string" ? e[1] : ""),
            s_aft: (typeof (e[3]) == "string" ? e[3] : "")
        };
        var h = e[2].split(".");
        if (h[1] != null) {
            c.d_len = h[1].length
        }
        var g = h[0].split(",");
        if (g.length > 1) {
            c.i_len = g[g.length - 1].length
        }
        return c
    };
    window.dhx4.template._getFmtValue = function (value, fmt) {
        var r = String(value).match(/^(-)?([0-9]{1,})(\.([0-9]{1,}))?$/);
        if (r != null && r.length == 5) {
            var v0 = "";
            if (r[1] != null) {
                v0 += r[1]
            }
            v0 += fmt.s_bef;
            if (fmt.i_len !== false) {
                var i = 0;
                var v1 = "";
                for (var q = r[2].length - 1; q >= 0; q--) {
                    v1 = "" + r[2].charAt(q) + v1;
                    if (++i == fmt.i_len && q > 0) {
                        v1 = fmt.i_sep + v1;
                        i = 0
                    }
                }
                v0 += v1
            } else {
                v0 += r[2]
            }
            if (fmt.d_len !== false) {
                if (r[4] == null) {
                    r[4] = ""
                }
                while (r[4].length < fmt.d_len) {
                    r[4] += "0"
                }
                eval("dhx4.temp = new RegExp(/\\d{" + fmt.d_len + "}/);");
                var t1 = (r[4]).match(dhx4.temp);
                if (t1 != null) {
                    v0 += fmt.d_sep + t1
                }
                dhx4.temp = t1 = null
            }
            v0 += fmt.s_aft;
            return v0
        }
        return value
    }
}
if (typeof (window.dhx4.dateLang) == "undefined") {
    window.dhx4.dateLang = "en";
    window.dhx4.dateStrings = {
        en: {
            monthFullName: ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"],
            monthShortName: ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"],
            dayFullName: ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"],
            dayShortName: ["Su", "Mo", "Tu", "We", "Th", "Fr", "Sa"]
        }
    };
    window.dhx4.dateFormat = {
        en: "%Y-%m-%d"
    };
    window.dhx4.date2str = function (g, e, a) {
        if (e == null || typeof (e) == "undefined") {
            e = window.dhx4.dateFormat[window.dhx4.dateLang]
        }
        if (a == null || typeof (a) == "undefined") {
            a = window.dhx4.dateStrings[window.dhx4.dateLang]
        }
        if (g instanceof Date) {
            var f = function (h) {
                return (String(h).length == 1 ? "0" + String(h) : h)
            };
            var c = function (k) {
                switch (k) {
                    case "%d":
                        return f(g.getDate());
                    case "%j":
                        return g.getDate();
                    case "%D":
                        return a.dayShortName[g.getDay()];
                    case "%l":
                        return a.dayFullName[g.getDay()];
                    case "%m":
                        return f(g.getMonth() + 1);
                    case "%n":
                        return g.getMonth() + 1;
                    case "%M":
                        return a.monthShortName[g.getMonth()];
                    case "%F":
                        return a.monthFullName[g.getMonth()];
                    case "%y":
                        return f(g.getYear() % 100);
                    case "%Y":
                        return g.getFullYear();
                    case "%g":
                        return (g.getHours() + 11) % 12 + 1;
                    case "%h":
                        return f((g.getHours() + 11) % 12 + 1);
                    case "%G":
                        return g.getHours();
                    case "%H":
                        return f(g.getHours());
                    case "%i":
                        return f(g.getMinutes());
                    case "%s":
                        return f(g.getSeconds());
                    case "%a":
                        return (g.getHours() > 11 ? "pm" : "am");
                    case "%A":
                        return (g.getHours() > 11 ? "PM" : "AM");
                    case "%%":
                        return "%";
                    case "%u":
                        return g.getMilliseconds();
                    case "%P":
                        if (window.dhx4.temp_calendar != null && window.dhx4.temp_calendar.tz != null) {
                            return window.dhx4.temp_calendar.tz
                        }
                        var n = g.getTimezoneOffset();
                        var l = Math.abs(Math.floor(n / 60));
                        var j = Math.abs(n) - l * 60;
                        return (n > 0 ? "-" : "+") + f(l) + ":" + f(j);
                    default:
                        return k
                }
            };
            var d = String(e || window.dhx4.dateFormat).replace(/%[a-zA-Z]/g, c)
        }
        return (d || String(g))
    };
    window.dhx4.str2date = function (h, x, B) {
        if (x == null || typeof (x) == "undefined") {
            x = window.dhx4.dateFormat[window.dhx4.dateLang]
        }
        if (B == null || typeof (B) == "undefined") {
            B = window.dhx4.dateStrings[window.dhx4.dateLang]
        }
        x = x.replace(/[\-\[\]\/\{\}\(\)\*\+\?\.\\\^\$\\:|]/g, "\\$&");
        var A = [];
        var m = [];
        x = x.replace(/%[a-z]/gi, function (e) {
            switch (e) {
                case "%d":
                case "%m":
                case "%y":
                case "%h":
                case "%H":
                case "%i":
                case "%s":
                    m.push(e);
                    return "(\\d{2})";
                case "%D":
                case "%l":
                case "%M":
                case "%F":
                    m.push(e);
                    return "([a-zéûä\u0430-\u044F\u0451]{1,})";
                case "%j":
                case "%n":
                case "%g":
                case "%G":
                    m.push(e);
                    return "(\\d{1,2})";
                case "%Y":
                    m.push(e);
                    return "(\\d{4})";
                case "%a":
                    m.push(e);
                    return "([a|p]m)";
                case "%A":
                    m.push(e);
                    return "([A|P]M)";
                case "%u":
                    m.push(e);
                    return "(\\d{1,6})";
                case "%P":
                    m.push(e);
                    return "([+-]\\d{1,2}:\\d{1,2})"
            }
            return e
        });
        var C = new RegExp(x, "i");
        var n = h.match(C);
        if (n == null || n.length - 1 != m.length) {
            return "Invalid Date"
        }
        for (var c = 1; c < n.length; c++) {
            A.push(n[c])
        }
        var d = {
            "%y": 1,
            "%Y": 1,
            "%n": 2,
            "%m": 2,
            "%M": 2,
            "%F": 2,
            "%d": 3,
            "%j": 3,
            "%a": 4,
            "%A": 4,
            "%H": 5,
            "%G": 5,
            "%h": 5,
            "%g": 5,
            "%i": 6,
            "%s": 7,
            "%u": 7,
            "%P": 7
        };
        var o = {};
        var l = {};
        for (var c = 0; c < m.length; c++) {
            if (typeof (d[m[c]]) != "undefined") {
                var g = d[m[c]];
                if (!o[g]) {
                    o[g] = [];
                    l[g] = []
                }
                o[g].push(A[c]);
                l[g].push(m[c])
            }
        }
        A = [];
        m = [];
        for (var c = 1; c <= 7; c++) {
            if (o[c] != null) {
                for (var u = 0; u < o[c].length; u++) {
                    A.push(o[c][u]);
                    m.push(l[c][u])
                }
            }
        }
        var a = new Date();
        a.setDate(1);
        a.setHours(0);
        a.setMinutes(0);
        a.setSeconds(0);
        a.setMilliseconds(0);
        var s = function (k, e) {
            for (var f = 0; f < e.length; f++) {
                if (e[f].toLowerCase() == k) {
                    return f
                }
            }
            return -1
        };
        for (var c = 0; c < A.length; c++) {
            switch (m[c]) {
                case "%d":
                case "%j":
                case "%n":
                case "%m":
                case "%Y":
                case "%H":
                case "%G":
                case "%i":
                case "%s":
                case "%u":
                    if (!isNaN(A[c])) {
                        a[{
                            "%d": "setDate",
                            "%j": "setDate",
                            "%n": "setMonth",
                            "%m": "setMonth",
                            "%Y": "setFullYear",
                            "%H": "setHours",
                            "%G": "setHours",
                            "%i": "setMinutes",
                            "%s": "setSeconds",
                            "%u": "setMilliseconds"
                        } [m[c]]](Number(A[c]) + (m[c] == "%m" || m[c] == "%n" ? -1 : 0))
                    }
                    break;
                case "%M":
                case "%F":
                    var j = s(A[c].toLowerCase(), B[{
                        "%M": "monthShortName",
                        "%F": "monthFullName"
                    } [m[c]]]);
                    if (j >= 0) {
                        a.setMonth(j)
                    }
                    break;
                case "%y":
                    if (!isNaN(A[c])) {
                        var y = Number(A[c]);
                        a.setFullYear(y + (y > 50 ? 1900 : 2000))
                    }
                    break;
                case "%g":
                case "%h":
                    if (!isNaN(A[c])) {
                        var y = Number(A[c]);
                        if (y <= 12 && y >= 0) {
                            a.setHours(y + (s("pm", A) >= 0 ? (y == 12 ? 0 : 12) : (y == 12 ? -12 : 0)))
                        }
                    }
                    break;
                case "%P":
                    if (window.dhx4.temp_calendar != null) {
                        window.dhx4.temp_calendar.tz = A[c]
                    }
                    break
            }
        }
        return a
    }
}
if (typeof (window.dhx4.ajax) == "undefined") {
    window.dhx4.ajax = {
        cache: false,
        method: "get",
        parse: function (a) {
            if (typeof a !== "string") {
                return a
            }
            a = a.replace(/^[\s]+/, "");
            if (window.DOMParser && !dhx4.isIE) {
                var c = (new window.DOMParser()).parseFromString(a, "text/xml")
            } else {
                if (window.ActiveXObject !== window.undefined) {
                    var c = new window.ActiveXObject("Microsoft.XMLDOM");
                    c.async = "false";
                    c.loadXML(a)
                }
            }
            return c
        },
        xmltop: function (a, f, d) {
            if (typeof f.status == "undefined" || f.status < 400) {
                xml = (!f.responseXML) ? dhx4.ajax.parse(f.responseText || f) : (f.responseXML || f);
                if (xml && xml.documentElement !== null) {
                    try {
                        if (!xml.getElementsByTagName("parsererror").length) {
                            return xml.getElementsByTagName(a)[0]
                        }
                    } catch (c) {}
                }
            }
            if (d !== -1) {
                dhx4.callEvent("onLoadXMLError", ["Incorrect XML", arguments[1], d])
            }
            return document.createElement("DIV")
        },
        xpath: function (d, a) {
            if (!a.nodeName) {
                a = a.responseXML || a
            }
            if (dhx4.isIE) {
                try {
                    return a.selectNodes(d) || []
                } catch (g) {
                    return []
                }
            } else {
                var f = [];
                var h;
                var c = (a.ownerDocument || a).evaluate(d, a, null, XPathResult.ANY_TYPE, null);
                while (h = c.iterateNext()) {
                    f.push(h)
                }
                return f
            }
        },
        query: function (a) {
            return dhx4.ajax._call((a.method || "GET"), a.url, a.data || "", (a.async || true), a.callback, null, a.headers)
        },
        get: function (a, c) {
            return this._call("GET", a, null, true, c)
        },
        getSync: function (a) {
            return this._call("GET", a, null, false)
        },
        put: function (c, a, d) {
            return this._call("PUT", c, a, true, d)
        },
        del: function (c, a, d) {
            return this._call("DELETE", c, a, true, d)
        },
        post: function (c, a, d) {
            if (arguments.length == 1) {
                a = ""
            } else {
                if (arguments.length == 2 && (typeof (a) == "function" || typeof (window[a]) == "function")) {
                    d = a;
                    a = ""
                } else {
                    a = String(a)
                }
            }
            return this._call("POST", c, a, true, d)
        },
        postSync: function (c, a) {
            a = (a == null ? "" : String(a));
            return this._call("POST", c, a, false)
        },
        getLong: function (a, c) {
            this._call("GET", a, null, true, c, {
                url: a
            })
        },
        postLong: function (c, a, d) {
            if (arguments.length == 2 && (typeof (a) == "function" || typeof (window[a]))) {
                d = a;
                a = ""
            }
            this._call("POST", c, a, true, d, {
                url: c,
                postData: a
            })
        },
        _call: function (c, d, e, j, l, p, g) {
            if (typeof e === "object") {
                var h = [];
                for (var m in e) {
                    h.push(m + "=" + encodeURIComponent(e[m]))
                }
                e = h.join("&")
            }
            var f = dhx.promise.defer();
            var o = (window.XMLHttpRequest && !dhx4.isIE ? new XMLHttpRequest() : new ActiveXObject("Microsoft.XMLHTTP"));
            var k = (navigator.userAgent.match(/AppleWebKit/) != null && navigator.userAgent.match(/Qt/) != null && navigator.userAgent.match(/Safari/) != null);
            if (j == true) {
                o.onreadystatechange = function () {
                    if ((o.readyState == 4) || (k == true && o.readyState == 3)) {
                        if (o.status != 200 || o.responseText == "") {
                            f.reject(o);
                            if (!dhx4.callEvent("onAjaxError", [{
                                    xmlDoc: o,
                                    filePath: d,
                                    async: j
                                }])) {
                                return
                            }
                        }
                        window.setTimeout(function () {
                            if (typeof (l) == "function") {
                                try {
                                    l.apply(window, [{
                                        xmlDoc: o,
                                        filePath: d,
                                        async: j
                                    }])
                                } catch (a) {
                                    f.reject(a)
                                }
                                f.resolve(o.responseText)
                            }
                            if (p != null) {
                                if (typeof (p.postData) != "undefined") {
                                    dhx4.ajax.postLong(p.url, p.postData, l)
                                } else {
                                    dhx4.ajax.getLong(p.url, l)
                                }
                            }
                            l = null;
                            o = null
                        }, 1)
                    }
                }
            }
            if (c == "GET") {
                d += this._dhxr(d)
            }
            o.open(c, d, j);
            if (g != null) {
                for (var n in g) {
                    o.setRequestHeader(n, g[n])
                }
            } else {
                if (c == "POST" || c == "PUT" || c == "DELETE") {
                    o.setRequestHeader("Content-Type", "application/x-www-form-urlencoded")
                } else {
                    if (c == "GET") {
                        e = null
                    }
                }
            }
            o.setRequestHeader("X-Requested-With", "XMLHttpRequest");
            o.send(e);
            if (j != true) {
                if ((o.readyState == 4) || (k == true && o.readyState == 3)) {
                    if (o.status != 200 || o.responseText == "") {
                        dhx4.callEvent("onAjaxError", [{
                            xmlDoc: o,
                            filePath: d,
                            async: j
                        }])
                    }
                }
            }
            f.xmlDoc = o;
            f.filePath = d;
            f.async = j;
            return f
        },
        _dhxr: function (a, c) {
            if (this.cache != true) {
                if (a.match(/^[\?\&]$/) == null) {
                    a = (a.indexOf("?") >= 0 ? "&" : "?")
                }
                if (typeof (c) == "undefined") {
                    c = true
                }
                return a + "dhxr" + new Date().getTime() + (c == true ? "=1" : "")
            }
            return ""
        }
    }
}
if (typeof (window.dhx4._enableDataLoading) == "undefined") {
    window.dhx4._enableDataLoading = function (h, d, g, f, j) {
        if (j == "clear") {
            for (var c in h._dhxdataload) {
                h._dhxdataload[c] = null;
                delete h._dhxdataload[c]
            }
            h._loadData = null;
            h._dhxdataload = null;
            h.load = null;
            h.loadStruct = null;
            h = null;
            return
        }
        h._dhxdataload = {
            initObj: d,
            xmlToJson: g,
            xmlRootTag: f,
            onBeforeXLS: null
        };
        h._loadData = function (p, q, r) {
            if (arguments.length == 2) {
                r = q;
                q = null
            }
            var o = null;
            if (arguments.length == 3) {
                r = arguments[2]
            }
            this.callEvent("onXLS", []);
            if (typeof (p) == "string") {
                var n = p.replace(/^\s{1,}/, "").replace(/\s{1,}$/, "");
                var v = new RegExp("^<" + this._dhxdataload.xmlRootTag);
                if (v.test(n.replace(/^<\?xml[^\?]*\?>\s*/, ""))) {
                    o = dhx4.ajax.parse(p);
                    if (o != null) {
                        o = this[this._dhxdataload.xmlToJson].apply(this, [o])
                    }
                }
                if (o == null && (n.match(/^[\s\S]*{[.\s\S]*}[\s\S]*$/) != null || n.match(/^[\s\S]*\[[.\s\S]*\][\s\S]*$/) != null)) {
                    o = dhx4.s2j(n)
                }
                if (o == null) {
                    var m = [];
                    if (typeof (this._dhxdataload.onBeforeXLS) == "function") {
                        var n = this._dhxdataload.onBeforeXLS.apply(this, [p]);
                        if (n != null && typeof (n) == "object") {
                            if (n.url != null) {
                                p = n.url
                            }
                            if (n.params != null) {
                                for (var s in n.params) {
                                    m.push(s + "=" + encodeURIComponent(n.params[s]))
                                }
                            }
                        }
                    }
                    var u = this;
                    var l = function (a) {
                        var k = null;
                        if ((a.xmlDoc.getResponseHeader("Content-Type") || "").search(/xml/gi) >= 0 || (a.xmlDoc.responseText.replace(/^\s{1,}/, "")).match(/^</) != null) {
                            k = u[u._dhxdataload.xmlToJson].apply(u, [a.xmlDoc.responseXML])
                        } else {
                            k = dhx4.s2j(a.xmlDoc.responseText)
                        }
                        if (k != null) {
                            u[u._dhxdataload.initObj].apply(u, [k, p])
                        }
                        u.callEvent("onXLE", []);
                        if (r != null) {
                            if (typeof (r) == "function") {
                                r.apply(u, [])
                            } else {
                                if (typeof (window[r]) == "function") {
                                    window[r].apply(u, [])
                                }
                            }
                        }
                        l = r = null;
                        k = a = u = null
                    };
                    m = m.join("&") + (typeof (q) == "string" ? "&" + q : "");
                    if (dhx4.ajax.method == "post") {
                        return dhx4.ajax.post(p, m, l)
                    } else {
                        if (dhx4.ajax.method == "get") {
                            return dhx4.ajax.get(p + (m.length > 0 ? (p.indexOf("?") > 0 ? "&" : "?") + m : ""), l)
                        }
                    }
                    return
                }
            } else {
                if (typeof (p.documentElement) == "object" || (typeof (p.tagName) != "undefined" && typeof (p.getElementsByTagName) != "undefined" && p.getElementsByTagName(this._dhxdataload.xmlRootTag).length > 0)) {
                    o = this[this._dhxdataload.xmlToJson].apply(this, [p])
                } else {
                    o = window.dhx4._copyObj(p)
                }
            }
            if (o != null) {
                this[this._dhxdataload.initObj].apply(this, [o])
            }
            this.callEvent("onXLE", []);
            if (r != null) {
                if (typeof (r) == "function") {
                    r.apply(this, [])
                } else {
                    if (typeof (window[r]) == "function") {
                        window[r].apply(this, [])
                    }
                }
                r = null
            }
        };
        if (j != null) {
            var e = {
                struct: "loadStruct",
                data: "load"
            };
            for (var c in j) {
                if (j[c] == true) {
                    h[e[c]] = function () {
                        return this._loadData.apply(this, arguments)
                    }
                }
            }
        }
        h = null
    }
}
if (typeof (window.dhx4._eventable) == "undefined") {
    window.dhx4._eventable = function (a, c) {
        if (c == "clear") {
            a.detachAllEvents();
            a.dhxevs = null;
            a.attachEvent = null;
            a.detachEvent = null;
            a.checkEvent = null;
            a.callEvent = null;
            a.detachAllEvents = null;
            a = null;
            return
        }
        a.dhxevs = {
            data: {}
        };
        a.attachEvent = function (d, f) {
            d = String(d).toLowerCase();
            if (!this.dhxevs.data[d]) {
                this.dhxevs.data[d] = {}
            }
            var e = window.dhx4.newId();
            this.dhxevs.data[d][e] = f;
            return e
        };
        a.detachEvent = function (g) {
            for (var e in this.dhxevs.data) {
                var f = 0;
                for (var d in this.dhxevs.data[e]) {
                    if (d == g) {
                        this.dhxevs.data[e][d] = null;
                        delete this.dhxevs.data[e][d]
                    } else {
                        f++
                    }
                }
                if (f == 0) {
                    this.dhxevs.data[e] = null;
                    delete this.dhxevs.data[e]
                }
            }
        };
        a.checkEvent = function (d) {
            d = String(d).toLowerCase();
            return (this.dhxevs.data[d] != null)
        };
        a.callEvent = function (e, g) {
            e = String(e).toLowerCase();
            if (this.dhxevs.data[e] == null) {
                return true
            }
            var f = true;
            for (var d in this.dhxevs.data[e]) {
                f = this.dhxevs.data[e][d].apply(this, g) && f
            }
            return f
        };
        a.detachAllEvents = function () {
            for (var e in this.dhxevs.data) {
                for (var d in this.dhxevs.data[e]) {
                    this.dhxevs.data[e][d] = null;
                    delete this.dhxevs.data[e][d]
                }
                this.dhxevs.data[e] = null;
                delete this.dhxevs.data[e]
            }
        };
        a = null
    };
    dhx4._eventable(dhx4)
}
if (!window.dhtmlxValidation) {
    dhtmlxValidation = function () {};
    dhtmlxValidation.prototype = {
        isEmpty: function (a) {
            return a == ""
        },
        isNotEmpty: function (a) {
            return (a instanceof Array ? a.length > 0 : !a == "")
        },
        isValidBoolean: function (a) {
            return !!a.toString().match(/^(0|1|true|false)$/)
        },
        isValidEmail: function (a) {
            return !!a.toString().match(/(^[a-z0-9]([0-9a-z\-_\.]*)@([0-9a-z_\-\.]*)([.][a-z]{3})$)|(^[a-z]([0-9a-z_\.\-]*)@([0-9a-z_\-\.]*)(\.[a-z]{2,5})$)/i)
        },
        isValidInteger: function (a) {
            return !!a.toString().match(/(^-?\d+$)/)
        },
        isValidNumeric: function (a) {
            return !!a.toString().match(/(^-?\d\d*[\.|,]\d*$)|(^-?\d\d*$)|(^-?[\.|,]\d\d*$)/)
        },
        isValidAplhaNumeric: function (a) {
            return !!a.toString().match(/^[_\-a-z0-9]+$/gi)
        },
        isValidDatetime: function (c) {
            var a = c.toString().match(/^(\d{4})-(\d{2})-(\d{2})\s(\d{2}):(\d{2}):(\d{2})$/);
            return a && !!(a[1] <= 9999 && a[2] <= 12 && a[3] <= 31 && a[4] <= 59 && a[5] <= 59 && a[6] <= 59) || false
        },
        isValidDate: function (a) {
            var c = a.toString().match(/^(\d{4})-(\d{2})-(\d{2})$/);
            return c && !!(c[1] <= 9999 && c[2] <= 12 && c[3] <= 31) || false
        },
        isValidTime: function (c) {
            var a = c.toString().match(/^(\d{1,2}):(\d{1,2}):(\d{1,2})$/);
            return a && !!(a[1] <= 24 && a[2] <= 59 && a[3] <= 59) || false
        },
        isValidIPv4: function (a) {
            var c = a.toString().match(/^(\d{1,3})\.(\d{1,3})\.(\d{1,3})\.(\d{1,3})$/);
            return c && !!(c[1] <= 255 && c[2] <= 255 && c[3] <= 255 && c[4] <= 255) || false
        },
        isValidCurrency: function (a) {
            return a.toString().match(/^\$?\s?\d+?([\.,\,]?\d+)?\s?\$?$/) && true || false
        },
        isValidSSN: function (a) {
            return a.toString().match(/^\d{3}\-?\d{2}\-?\d{4}$/) && true || false
        },
        isValidSIN: function (a) {
            return a.toString().match(/^\d{9}$/) && true || false
        }
    };
    dhtmlxValidation = new dhtmlxValidation()
}
if (typeof (window.dhtmlx) == "undefined") {
    window.dhtmlx = {
        extend: function (d, c) {
            for (var e in c) {
                if (!d[e]) {
                    d[e] = c[e]
                }
            }
            return d
        },
        extend_api: function (a, e, d) {
            var c = window[a];
            if (!c) {
                return
            }
            window[a] = function (h) {
                if (h && typeof h == "object" && !h.tagName) {
                    var g = c.apply(this, (e._init ? e._init(h) : arguments));
                    for (var f in dhtmlx) {
                        if (e[f]) {
                            this[e[f]](dhtmlx[f])
                        }
                    }
                    for (var f in h) {
                        if (e[f]) {
                            this[e[f]](h[f])
                        } else {
                            if (f.indexOf("on") === 0) {
                                this.attachEvent(f, h[f])
                            }
                        }
                    }
                } else {
                    var g = c.apply(this, arguments)
                }
                if (e._patch) {
                    e._patch(this)
                }
                return g || this
            };
            window[a].prototype = c.prototype;
            if (d) {
                dhtmlx.extend(window[a].prototype, d)
            }
        },
        url: function (a) {
            if (a.indexOf("?") != -1) {
                return "&"
            } else {
                return "?"
            }
        }
    }
}

function dhtmlDragAndDropObject() {
    if (window.dhtmlDragAndDrop) {
        return window.dhtmlDragAndDrop
    }
    this.lastLanding = 0;
    this.dragNode = 0;
    this.dragStartNode = 0;
    this.dragStartObject = 0;
    this.tempDOMU = null;
    this.tempDOMM = null;
    this.waitDrag = 0;
    window.dhtmlDragAndDrop = this;
    return this
}
dhtmlDragAndDropObject.prototype.removeDraggableItem = function (a) {
    a.onmousedown = null;
    a.dragStarter = null;
    a.dragLanding = null
};
dhtmlDragAndDropObject.prototype.addDraggableItem = function (a, c) {
    a.onmousedown = this.preCreateDragCopy;
    a.dragStarter = c;
    this.addDragLanding(a, c)
};
dhtmlDragAndDropObject.prototype.addDragLanding = function (a, c) {
    a.dragLanding = c
};
dhtmlDragAndDropObject.prototype.preCreateDragCopy = function (a) {
    if ((a || window.event) && (a || event).button == 2) {
        return
    }
    if (window.dhtmlDragAndDrop.waitDrag) {
        window.dhtmlDragAndDrop.waitDrag = 0;
        document.body.onmouseup = window.dhtmlDragAndDrop.tempDOMU;
        document.body.onmousemove = window.dhtmlDragAndDrop.tempDOMM;
        return false
    }
    if (window.dhtmlDragAndDrop.dragNode) {
        window.dhtmlDragAndDrop.stopDrag(a)
    }
    window.dhtmlDragAndDrop.waitDrag = 1;
    window.dhtmlDragAndDrop.tempDOMU = document.body.onmouseup;
    window.dhtmlDragAndDrop.tempDOMM = document.body.onmousemove;
    window.dhtmlDragAndDrop.dragStartNode = this;
    window.dhtmlDragAndDrop.dragStartObject = this.dragStarter;
    document.body.onmouseup = window.dhtmlDragAndDrop.preCreateDragCopy;
    document.body.onmousemove = window.dhtmlDragAndDrop.callDrag;
    window.dhtmlDragAndDrop.downtime = new Date().valueOf();
    if ((a) && (a.preventDefault)) {
        a.preventDefault();
        return false
    }
    return false
};
dhtmlDragAndDropObject.prototype.callDrag = function (d) {
    if (!d) {
        d = window.event
    }
    dragger = window.dhtmlDragAndDrop;
    if ((new Date()).valueOf() - dragger.downtime < 100) {
        return
    }
    if (!dragger.dragNode) {
        if (dragger.waitDrag) {
            dragger.dragNode = dragger.dragStartObject._createDragNode(dragger.dragStartNode, d);
            if (!dragger.dragNode) {
                return dragger.stopDrag()
            }
            dragger.dragNode.onselectstart = function () {
                return false
            };
            dragger.gldragNode = dragger.dragNode;
            document.body.appendChild(dragger.dragNode);
            document.body.onmouseup = dragger.stopDrag;
            dragger.waitDrag = 0;
            dragger.dragNode.pWindow = window;
            dragger.initFrameRoute()
        } else {
            return dragger.stopDrag(d, true)
        }
    }
    if (dragger.dragNode.parentNode != window.document.body && dragger.gldragNode) {
        var a = dragger.gldragNode;
        if (dragger.gldragNode.old) {
            a = dragger.gldragNode.old
        }
        a.parentNode.removeChild(a);
        var c = dragger.dragNode.pWindow;
        if (a.pWindow && a.pWindow.dhtmlDragAndDrop.lastLanding) {
            a.pWindow.dhtmlDragAndDrop.lastLanding.dragLanding._dragOut(a.pWindow.dhtmlDragAndDrop.lastLanding)
        }
        if (_isIE) {
            var g = document.createElement("Div");
            g.innerHTML = dragger.dragNode.outerHTML;
            dragger.dragNode = g.childNodes[0]
        } else {
            dragger.dragNode = dragger.dragNode.cloneNode(true)
        }
        dragger.dragNode.pWindow = window;
        dragger.gldragNode.old = dragger.dragNode;
        document.body.appendChild(dragger.dragNode);
        c.dhtmlDragAndDrop.dragNode = dragger.dragNode
    }
    dragger.dragNode.style.left = d.clientX + 15 + (dragger.fx ? dragger.fx * (-1) : 0) + (document.body.scrollLeft || document.documentElement.scrollLeft) + "px";
    dragger.dragNode.style.top = d.clientY + 3 + (dragger.fy ? dragger.fy * (-1) : 0) + (document.body.scrollTop || document.documentElement.scrollTop) + "px";
    if (!d.srcElement) {
        var f = d.target
    } else {
        f = d.srcElement
    }
    dragger.checkLanding(f, d)
};
dhtmlDragAndDropObject.prototype.calculateFramePosition = function (f) {
    if (window.name) {
        var d = parent.frames[window.name].frameElement.offsetParent;
        var e = 0;
        var c = 0;
        while (d) {
            e += d.offsetLeft;
            c += d.offsetTop;
            d = d.offsetParent
        }
        if ((parent.dhtmlDragAndDrop)) {
            var a = parent.dhtmlDragAndDrop.calculateFramePosition(1);
            e += a.split("_")[0] * 1;
            c += a.split("_")[1] * 1
        }
        if (f) {
            return e + "_" + c
        } else {
            this.fx = e
        }
        this.fy = c
    }
    return "0_0"
};
dhtmlDragAndDropObject.prototype.checkLanding = function (c, a) {
    if ((c) && (c.dragLanding)) {
        if (this.lastLanding) {
            this.lastLanding.dragLanding._dragOut(this.lastLanding)
        }
        this.lastLanding = c;
        this.lastLanding = this.lastLanding.dragLanding._dragIn(this.lastLanding, this.dragStartNode, a.clientX, a.clientY, a);
        this.lastLanding_scr = (_isIE ? a.srcElement : a.target)
    } else {
        if ((c) && (c.tagName != "BODY")) {
            this.checkLanding(c.parentNode, a)
        } else {
            if (this.lastLanding) {
                this.lastLanding.dragLanding._dragOut(this.lastLanding, a.clientX, a.clientY, a)
            }
            this.lastLanding = 0;
            if (this._onNotFound) {
                this._onNotFound()
            }
        }
    }
};
dhtmlDragAndDropObject.prototype.stopDrag = function (c, d) {
    dragger = window.dhtmlDragAndDrop;
    if (!d) {
        dragger.stopFrameRoute();
        var a = dragger.lastLanding;
        dragger.lastLanding = null;
        if (a) {
            a.dragLanding._drag(dragger.dragStartNode, dragger.dragStartObject, a, (_isIE ? event.srcElement : c.target))
        }
    }
    dragger.lastLanding = null;
    if ((dragger.dragNode) && (dragger.dragNode.parentNode == document.body)) {
        dragger.dragNode.parentNode.removeChild(dragger.dragNode)
    }
    dragger.dragNode = 0;
    dragger.gldragNode = 0;
    dragger.fx = 0;
    dragger.fy = 0;
    dragger.dragStartNode = 0;
    dragger.dragStartObject = 0;
    document.body.onmouseup = dragger.tempDOMU;
    document.body.onmousemove = dragger.tempDOMM;
    dragger.tempDOMU = null;
    dragger.tempDOMM = null;
    dragger.waitDrag = 0
};
dhtmlDragAndDropObject.prototype.stopFrameRoute = function (d) {
    if (d) {
        window.dhtmlDragAndDrop.stopDrag(1, 1)
    }
    for (var a = 0; a < window.frames.length; a++) {
        try {
            if ((window.frames[a] != d) && (window.frames[a].dhtmlDragAndDrop)) {
                window.frames[a].dhtmlDragAndDrop.stopFrameRoute(window)
            }
        } catch (c) {}
    }
    try {
        if ((parent.dhtmlDragAndDrop) && (parent != window) && (parent != d)) {
            parent.dhtmlDragAndDrop.stopFrameRoute(window)
        }
    } catch (c) {}
};
dhtmlDragAndDropObject.prototype.initFrameRoute = function (d, f) {
    if (d) {
        window.dhtmlDragAndDrop.preCreateDragCopy();
        window.dhtmlDragAndDrop.dragStartNode = d.dhtmlDragAndDrop.dragStartNode;
        window.dhtmlDragAndDrop.dragStartObject = d.dhtmlDragAndDrop.dragStartObject;
        window.dhtmlDragAndDrop.dragNode = d.dhtmlDragAndDrop.dragNode;
        window.dhtmlDragAndDrop.gldragNode = d.dhtmlDragAndDrop.dragNode;
        window.document.body.onmouseup = window.dhtmlDragAndDrop.stopDrag;
        window.waitDrag = 0;
        if (((!_isIE) && (f)) && ((!_isFF) || (_FFrv < 1.8))) {
            window.dhtmlDragAndDrop.calculateFramePosition()
        }
    }
    try {
        if ((parent.dhtmlDragAndDrop) && (parent != window) && (parent != d)) {
            parent.dhtmlDragAndDrop.initFrameRoute(window)
        }
    } catch (c) {}
    for (var a = 0; a < window.frames.length; a++) {
        try {
            if ((window.frames[a] != d) && (window.frames[a].dhtmlDragAndDrop)) {
                window.frames[a].dhtmlDragAndDrop.initFrameRoute(window, ((!d || f) ? 1 : 0))
            }
        } catch (c) {}
    }
};
_isFF = false;
_isIE = false;
_isOpera = false;
_isKHTML = false;
_isMacOS = false;
_isChrome = false;
_FFrv = false;
_KHTMLrv = false;
_OperaRv = false;
if (navigator.userAgent.indexOf("Macintosh") != -1) {
    _isMacOS = true
}
if (navigator.userAgent.toLowerCase().indexOf("chrome") > -1) {
    _isChrome = true
}
if ((navigator.userAgent.indexOf("Safari") != -1) || (navigator.userAgent.indexOf("Konqueror") != -1)) {
    _KHTMLrv = parseFloat(navigator.userAgent.substr(navigator.userAgent.indexOf("Safari") + 7, 5));
    if (_KHTMLrv > 525) {
        _isFF = true;
        _FFrv = 1.9
    } else {
        _isKHTML = true
    }
} else {
    if (navigator.userAgent.indexOf("Opera") != -1) {
        _isOpera = true;
        _OperaRv = parseFloat(navigator.userAgent.substr(navigator.userAgent.indexOf("Opera") + 6, 3))
    } else {
        if (navigator.appName.indexOf("Microsoft") != -1) {
            _isIE = true;
            if ((navigator.appVersion.indexOf("MSIE 8.0") != -1 || navigator.appVersion.indexOf("MSIE 9.0") != -1 || navigator.appVersion.indexOf("MSIE 10.0") != -1 || document.documentMode > 7) && document.compatMode != "BackCompat") {
                _isIE = 8
            }
        } else {
            if (navigator.appName == "Netscape" && navigator.userAgent.indexOf("Trident") != -1) {
                _isIE = 8
            } else {
                _isFF = true;
                _FFrv = parseFloat(navigator.userAgent.split("rv:")[1])
            }
        }
    }
}
if (typeof (window.dhtmlxEvent) == "undefined") {
    function dhtmlxEvent(c, d, a) {
        if (c.addEventListener) {
            c.addEventListener(d, a, false)
        } else {
            if (c.attachEvent) {
                c.attachEvent("on" + d, a)
            }
        }
    }
}
if (dhtmlxEvent.touchDelay == null) {
    dhtmlxEvent.touchDelay = 2000
}
if (typeof (dhtmlxEvent.initTouch) == "undefined") {
    dhtmlxEvent.initTouch = function () {
        var e;
        var f;
        var c, a;
        dhtmlxEvent(document.body, "touchstart", function (g) {
            f = g.touches[0].target;
            c = g.touches[0].clientX;
            a = g.touches[0].clientY;
            e = window.setTimeout(d, dhtmlxEvent.touchDelay)
        });

        function d() {
            if (f) {
                var g = document.createEvent("HTMLEvents");
                g.initEvent("dblclick", true, true);
                f.dispatchEvent(g);
                e = f = null
            }
        }
        dhtmlxEvent(document.body, "touchmove", function (g) {
            if (e) {
                if (Math.abs(g.touches[0].clientX - c) > 50 || Math.abs(g.touches[0].clientY - a) > 50) {
                    window.clearTimeout(e);
                    e = f = false
                }
            }
        });
        dhtmlxEvent(document.body, "touchend", function (g) {
            if (e) {
                window.clearTimeout(e);
                e = f = false
            }
        });
        dhtmlxEvent.initTouch = function () {}
    }
}(function (c) {
    var d = typeof setImmediate !== "undefined" ? setImmediate : function (f) {
        setTimeout(f, 0)
    };

    function e(g, h) {
        var f = this;
        f.promise = f;
        f.state = "pending";
        f.val = null;
        f.fn = g || null;
        f.er = h || null;
        f.next = []
    }
    e.prototype.resolve = function (g) {
        var f = this;
        if (f.state === "pending") {
            f.val = g;
            f.state = "resolving";
            d(function () {
                f.fire()
            })
        }
    };
    e.prototype.reject = function (g) {
        var f = this;
        if (f.state === "pending") {
            f.val = g;
            f.state = "rejecting";
            d(function () {
                f.fire()
            })
        }
    };
    e.prototype.then = function (g, j) {
        var f = this;
        var h = new e(g, j);
        f.next.push(h);
        if (f.state === "resolved") {
            h.resolve(f.val)
        }
        if (f.state === "rejected") {
            h.reject(f.val)
        }
        return h
    };
    e.prototype.fail = function (f) {
        return this.then(null, f)
    };
    e.prototype.finish = function (h) {
        var f = this;
        f.state = h;
        if (f.state === "resolved") {
            for (var g = 0; g < f.next.length; g++) {
                f.next[g].resolve(f.val)
            }
        }
        if (f.state === "rejected") {
            for (var g = 0; g < f.next.length; g++) {
                f.next[g].reject(f.val)
            }
            if (!f.next.length) {
                throw (f.val)
            }
        }
    };
    e.prototype.thennable = function (k, f, h, n, m) {
        var g = this;
        m = m || g.val;
        if (typeof m === "object" && typeof k === "function") {
            try {
                var j = 0;
                k.call(m, function (o) {
                    if (j++ !== 0) {
                        return
                    }
                    f(o)
                }, function (o) {
                    if (j++ !== 0) {
                        return
                    }
                    h(o)
                })
            } catch (l) {
                h(l)
            }
        } else {
            n(m)
        }
    };
    e.prototype.fire = function () {
        var f = this;
        var g;
        try {
            g = f.val && f.val.then
        } catch (h) {
            f.val = h;
            f.state = "rejecting";
            return f.fire()
        }
        f.thennable(g, function (j) {
            f.val = j;
            f.state = "resolving";
            f.fire()
        }, function (j) {
            f.val = j;
            f.state = "rejecting";
            f.fire()
        }, function (j) {
            f.val = j;
            if (f.state === "resolving" && typeof f.fn === "function") {
                try {
                    f.val = f.fn.call(undefined, f.val)
                } catch (k) {
                    f.val = k;
                    return f.finish("rejected")
                }
            }
            if (f.state === "rejecting" && typeof f.er === "function") {
                try {
                    f.val = f.er.call(undefined, f.val);
                    f.state = "resolving"
                } catch (k) {
                    f.val = k;
                    return f.finish("rejected")
                }
            }
            if (f.val === f) {
                f.val = TypeError();
                return f.finish("rejected")
            }
            f.thennable(g, function (l) {
                f.val = l;
                f.finish("resolved")
            }, function (l) {
                f.val = l;
                f.finish("rejected")
            }, function (l) {
                f.val = l;
                f.state === "resolving" ? f.finish("resolved") : f.finish("rejected")
            })
        })
    };
    e.prototype.done = function () {
        if (this.state = "rejected" && !this.next) {
            throw this.val
        }
        return null
    };
    e.prototype.nodeify = function (f) {
        if (typeof f === "function") {
            return this.then(function (h) {
                try {
                    f(null, h)
                } catch (g) {
                    setImmediate(function () {
                        throw g
                    })
                }
                return h
            }, function (h) {
                try {
                    f(h)
                } catch (g) {
                    setImmediate(function () {
                        throw g
                    })
                }
                return h
            })
        }
        return this
    };
    e.prototype.spread = function (f, g) {
        return this.all().then(function (h) {
            return typeof f === "function" && f.apply(null, h)
        }, g)
    };
    e.prototype.all = function () {
        var f = this;
        return this.then(function (r) {
            var g = new e();
            if (!(r instanceof Array)) {
                g.reject(TypeError);
                return g
            }
            var j = 0;
            var q = r.length;

            function m() {
                if (++j === q) {
                    g.resolve(r)
                }
            }
            for (var n = 0, k = r.length; n < k; n++) {
                var s = r[n];
                var h;
                try {
                    h = s && s.then
                } catch (o) {
                    g.reject(o);
                    break
                }(function (l) {
                    f.thennable(h, function (p) {
                        r[l] = p;
                        m()
                    }, function (p) {
                        g.reject(p)
                    }, function () {
                        m()
                    }, s)
                })(n)
            }
            return g
        })
    };
    var a = {
        all: function (f) {
            var g = new e(null, null);
            g.resolve(f);
            return g.all()
        },
        defer: function () {
            return new e(null, null)
        },
        fcall: function () {
            var h = new e();
            var f = Array.apply([], arguments);
            var g = f.shift();
            try {
                var k = g.apply(null, f);
                h.resolve(k)
            } catch (j) {
                h.reject(j)
            }
            return h
        },
        nfcall: function () {
            var h = new e();
            var f = Array.apply([], arguments);
            var g = f.shift();
            try {
                f.push(function (k, l) {
                    if (k) {
                        return h.reject(k)
                    }
                    return h.resolve(l)
                });
                g.apply(null, f)
            } catch (j) {
                h.reject(j)
            }
            return h
        }
    };
    c.promise = a
})(dhx);

function dataProcessor(a) {
    this.serverProcessor = a;
    this.action_param = "!nativeeditor_status";
    this.object = null;
    this.updatedRows = [];
    this.autoUpdate = true;
    this.updateMode = "cell";
    this._tMode = "GET";
    this._headers = null;
    this._payload = null;
    this.post_delim = "_";
    this._waitMode = 0;
    this._in_progress = {};
    this._invalid = {};
    this.mandatoryFields = [];
    this.messages = [];
    this.styles = {
        updated: "font-weight:bold;",
        inserted: "font-weight:bold;",
        deleted: "text-decoration : line-through;",
        invalid: "background-color:FFE0E0;",
        invalid_cell: "border-bottom:2px solid red;",
        error: "color:red;",
        clear: "font-weight:normal;text-decoration:none;"
    };
    this.enableUTFencoding(true);
    dhx4._eventable(this);
    if (this.connector_init) {
        this.setTransactionMode("POST", true);
        this.serverProcessor += (this.serverProcessor.indexOf("?") != -1 ? "&" : "?") + "editing=true"
    }
    return this
}
dataProcessor.prototype = {
    url: function (a) {
        if (a.indexOf("?") != -1) {
            return "&"
        } else {
            return "?"
        }
    },
    setTransactionMode: function (c, a) {
        if (typeof c == "object") {
            this._tMode = c.mode || this._tMode;
            this._headers = this._headers || c.headers;
            this._payload = this._payload || c.payload
        } else {
            this._tMode = c;
            this._tSend = a
        }
        if (this._tMode == "REST") {
            this._tSend = false;
            this._endnm = true
        }
        if (this._tMode == "JSON") {
            this._tSend = false;
            this._endnm = true;
            this._headers = this._headers || {};
            this._headers["Content-type"] = "application/json"
        }
    },
    escape: function (a) {
        if (this._utf) {
            return encodeURIComponent(a)
        } else {
            return escape(a)
        }
    },
    enableUTFencoding: function (a) {
        this._utf = dhx4.s2b(a)
    },
    setDataColumns: function (a) {
        this._columns = (typeof a == "string") ? a.split(",") : a
    },
    getSyncState: function () {
        return !this.updatedRows.length
    },
    enableDataNames: function (a) {
        this._endnm = dhx4.s2b(a)
    },
    enablePartialDataSend: function (a) {
        this._changed = dhx4.s2b(a)
    },
    setUpdateMode: function (c, a) {
        this.autoUpdate = (c == "cell");
        this.updateMode = c;
        this.dnd = a
    },
    ignore: function (c, a) {
        this._silent_mode = true;
        c.call(a || window);
        this._silent_mode = false
    },
    setUpdated: function (e, d, f) {
        this._log("item " + e + " " + (d ? "marked" : "unmarked") + " [" + (f || "updated") + "]");
        if (this._silent_mode) {
            return
        }
        var c = this.findRow(e);
        f = f || "updated";
        var a = this.obj.getUserData(e, this.action_param);
        if (a && f == "updated") {
            f = a
        }
        if (d) {
            this.set_invalid(e, false);
            this.updatedRows[c] = e;
            this.obj.setUserData(e, this.action_param, f);
            if (this._in_progress[e]) {
                this._in_progress[e] = "wait"
            }
        } else {
            if (!this.is_invalid(e)) {
                this.updatedRows.splice(c, 1);
                this.obj.setUserData(e, this.action_param, "")
            }
        }
        if (!d) {
            this._clearUpdateFlag(e)
        }
        this.markRow(e, d, f);
        if (d && this.autoUpdate) {
            this.sendData(e)
        }
    },
    _clearUpdateFlag: function (a) {},
    markRow: function (g, d, f) {
        var e = "";
        var c = this.is_invalid(g);
        if (c) {
            e = this.styles[c];
            d = true
        }
        if (this.callEvent("onRowMark", [g, d, f, c])) {
            e = this.styles[d ? f : "clear"] + e;
            this.obj[this._methods[0]](g, e);
            if (c && c.details) {
                e += this.styles[c + "_cell"];
                for (var a = 0; a < c.details.length; a++) {
                    if (c.details[a]) {
                        this.obj[this._methods[1]](g, a, e)
                    }
                }
            }
        }
    },
    getState: function (a) {
        return this.obj.getUserData(a, this.action_param)
    },
    is_invalid: function (a) {
        return this._invalid[a]
    },
    set_invalid: function (d, c, a) {
        if (a) {
            c = {
                value: c,
                details: a,
                toString: function () {
                    return this.value.toString()
                }
            }
        }
        this._invalid[d] = c
    },
    checkBeforeUpdate: function (a) {
        return true
    },
    sendData: function (a) {
        if (a) {
            this._log("Sending: " + a)
        }
        if (this._waitMode && (this.obj.mytype == "tree" || this.obj._h2)) {
            return
        }
        if (this.obj.editStop) {
            this.obj.editStop()
        }
        if (typeof a == "undefined" || this._tSend) {
            return this.sendAllData()
        }
        if (this._in_progress[a]) {
            return false
        }
        this.messages = [];
        if (this.getState(a) !== "deleted") {
            if (!this.checkBeforeUpdate(a) && this.callEvent("onValidationError", [a, this.messages])) {
                return false
            }
        }
        this._beforeSendData(this._getRowData(a), a)
    },
    _beforeSendData: function (a, c) {
        if (!this.callEvent("onBeforeUpdate", [c, this.getState(c), a])) {
            return false
        }
        this._sendData(a, c)
    },
    serialize: function (e, f) {
        if (typeof e == "string") {
            return e
        }
        if (typeof f != "undefined") {
            return this.serialize_one(e, "")
        } else {
            var a = [];
            var d = [];
            for (var c in e) {
                if (e.hasOwnProperty(c)) {
                    a.push(this.serialize_one(e[c], c + this.post_delim));
                    d.push(c)
                }
            }
            a.push("ids=" + this.escape(d.join(",")));
            if (window.dhtmlx && dhtmlx.security_key) {
                a.push("dhx_security=" + dhtmlx.security_key)
            }
            return a.join("&")
        }
    },
    serialize_one: function (e, c) {
        if (typeof e == "string") {
            return e
        }
        var a = [];
        for (var d in e) {
            if (e.hasOwnProperty(d)) {
                if ((d == "id" || d == this.action_param) && this._tMode == "REST") {
                    continue
                }
                a.push(this.escape((c || "") + d) + "=" + this.escape(e[d]))
            }
        }
        return a.join("&")
    },
    _applyPayload: function (a) {
        if (this._payload) {
            for (var c in this._payload) {
                a = a + (a.indexOf("?") === -1 ? "?" : "&") + this.escape(c) + "=" + this.escape(this._payload[c])
            }
        }
        return a
    },
    _sendData: function (f, g) {
        this._log("url: " + this.serverProcessor);
        this._log(f);
        if (!f) {
            return
        }
        if (!this.callEvent("onBeforeDataSending", g ? [g, this.getState(g), f] : [null, null, f])) {
            return false
        }
        if (g) {
            this._in_progress[g] = (new Date()).valueOf()
        }
        var m = this;
        var l = function (p) {
            var r = [];
            if (g) {
                r.push(g)
            } else {
                if (f) {
                    for (var q in f) {
                        r.push(q)
                    }
                }
            }
            return m.afterUpdate(m, p, r)
        };
        var c = this.serverProcessor + (this._user ? (this.url(this.serverProcessor) + ["dhx_user=" + this._user, "dhx_version=" + this.obj.getUserData(0, "version")].join("&")) : "");
        var o = this._applyPayload(c);
        if (this._tMode == "GET") {
            dhx4.ajax.query({
                url: o + ((o.indexOf("?") != -1) ? "&" : "?") + this.serialize(f, g),
                method: "GET",
                headers: this._headers,
                callback: l
            })
        } else {
            if (this._tMode == "POST") {
                dhx4.ajax.query({
                    url: o,
                    method: "POST",
                    headers: this._headers,
                    callback: l,
                    data: this.serialize(f, g)
                })
            } else {
                if (this._tMode == "JSON") {
                    var h = f[this.action_param];
                    var k = {};
                    for (var n in f) {
                        k[n] = f[n]
                    }
                    delete k[this.action_param];
                    delete k.id;
                    delete k.gr_id;
                    dhx4.ajax.query({
                        url: o,
                        method: "POST",
                        headers: this._headers,
                        callback: l,
                        data: JSON.stringify({
                            id: g,
                            action: h,
                            data: k
                        })
                    })
                } else {
                    if (this._tMode == "REST") {
                        var e = this.getState(g);
                        var d = c.replace(/(\&|\?)editing\=true/, "");
                        var j = d.split("?");
                        if (j[1]) {
                            j[1] = "?" + j[1]
                        }
                        var k = "";
                        var a = "post";
                        if (e == "inserted") {
                            k = this.serialize(f, g)
                        } else {
                            if (e == "deleted") {
                                a = "DELETE";
                                d = j[0] + g + (j[1] || "")
                            } else {
                                a = "PUT";
                                k = this.serialize(f, g);
                                d = j[0] + g + (j[1] || "")
                            }
                        }
                        this._applyPayload(d);
                        dhx4.ajax.query({
                            url: d,
                            method: a,
                            headers: this._headers,
                            data: k,
                            callback: l
                        })
                    }
                }
            }
        }
        this._waitMode++
    },
    sendAllData: function () {
        this._log("Sending all updated items");
        if (!this.updatedRows.length) {
            return
        }
        this.messages = [];
        var c = true;
        for (var a = 0; a < this.updatedRows.length; a++) {
            if (this.getState(this.updatedRows[a]) !== "deleted") {
                c &= this.checkBeforeUpdate(this.updatedRows[a])
            }
        }
        if (!c && !this.callEvent("onValidationError", ["", this.messages])) {
            return false
        }
        if (this._tSend) {
            this._sendData(this._getAllData())
        } else {
            for (var a = 0; a < this.updatedRows.length; a++) {
                if (!this._in_progress[this.updatedRows[a]]) {
                    if (this.is_invalid(this.updatedRows[a])) {
                        continue
                    }
                    this._beforeSendData(this._getRowData(this.updatedRows[a]), this.updatedRows[a]);
                    if (this._waitMode && (this.obj.mytype == "tree" || this.obj._h2)) {
                        return
                    }
                }
            }
        }
    },
    _getAllData: function (e) {
        var c = {};
        var a = false;
        for (var d = 0; d < this.updatedRows.length; d++) {
            var f = this.updatedRows[d];
            if (this._in_progress[f] || this.is_invalid(f)) {
                continue
            }
            if (!this.callEvent("onBeforeUpdate", [f, this.getState(f), this._getRowData(f)])) {
                continue
            }
            c[f] = this._getRowData(f, f + this.post_delim);
            a = true;
            this._in_progress[f] = (new Date()).valueOf()
        }
        return a ? c : null
    },
    setVerificator: function (c, a) {
        this.mandatoryFields[c] = a || (function (d) {
            return (d !== "")
        })
    },
    clearVerificator: function (a) {
        this.mandatoryFields[a] = false
    },
    findRow: function (c) {
        var a = 0;
        for (a = 0; a < this.updatedRows.length; a++) {
            if (c == this.updatedRows[a]) {
                break
            }
        }
        return a
    },
    defineAction: function (a, c) {
        if (!this._uActions) {
            this._uActions = []
        }
        this._uActions[a] = c
    },
    afterUpdateCallback: function (c, h, g, f) {
        this._log("Action: " + g + " SID:" + c + " TID:" + h, f);
        var a = c;
        var e = (g != "error" && g != "invalid");
        if (!e) {
            this.set_invalid(c, g)
        }
        if ((this._uActions) && (this._uActions[g]) && (!this._uActions[g](f))) {
            return (delete this._in_progress[a])
        }
        if (this._in_progress[a] != "wait") {
            this.setUpdated(c, false)
        }
        var d = c;
        switch (g) {
            case "inserted":
            case "insert":
                if (h != c) {
                    this.obj[this._methods[2]](c, h);
                    c = h
                }
                break;
            case "delete":
            case "deleted":
                this.obj.setUserData(c, this.action_param, "true_deleted");
                this.obj[this._methods[3]](c);
                delete this._in_progress[a];
                return this.callEvent("onAfterUpdate", [c, g, h, f]);
                break
        }
        if (this._in_progress[a] != "wait") {
            if (e) {
                this.obj.setUserData(c, this.action_param, "")
            }
            delete this._in_progress[a]
        } else {
            delete this._in_progress[a];
            this.setUpdated(h, true, this.obj.getUserData(c, this.action_param))
        }
        this.callEvent("onAfterUpdate", [d, g, h, f])
    },
    enableDebug: function () {
        this._debug = true
    },
    _log: function () {
        if (this._debug && window.console && window.console.info) {
            window.console.info.apply(window.console, arguments)
        }
    },
    afterUpdate: function (k, j, a) {
        this._log("Server response received");
        if (window.JSON) {
            try {
                var o = JSON.parse(j.xmlDoc.responseText);
                var f = o.action || this.getState(a) || "updated";
                var c = o.sid || a[0];
                var d = o.tid || a[0];
                k.afterUpdateCallback(c, d, f, o);
                k.finalizeUpdate();
                return
            } catch (l) {}
        }
        var n = dhx4.ajax.xmltop("data", j.xmlDoc);
        if (!n || n.tagName == "DIV") {
            return this.cleanUpdate(a)
        }
        var m = dhx4.ajax.xpath("//data/action", n);
        if (!m.length) {
            return this.cleanUpdate(a)
        }
        for (var h = 0; h < m.length; h++) {
            var g = m[h];
            var f = g.getAttribute("type");
            var c = g.getAttribute("sid");
            var d = g.getAttribute("tid");
            k.afterUpdateCallback(c, d, f, g)
        }
        k.finalizeUpdate()
    },
    cleanUpdate: function (c) {
        if (c) {
            for (var a = 0; a < c.length; a++) {
                delete this._in_progress[c[a]]
            }
        }
    },
    finalizeUpdate: function () {
        if (this._waitMode) {
            this._waitMode--
        }
        if ((this.obj.mytype == "tree" || this.obj._h2) && this.updatedRows.length) {
            this.sendData()
        }
        this.callEvent("onAfterUpdateFinish", []);
        if (!this.updatedRows.length) {
            this.callEvent("onFullSync", [])
        }
    },
    init: function (a) {
        this.obj = a;
        if (a._dp_init) {
            a._dp_init(this)
        }
        if (this.connector_init) {
            a._dataprocessor = this
        }
    },
    setOnAfterUpdate: function (a) {
        this.attachEvent("onAfterUpdate", a)
    },
    setOnBeforeUpdateHandler: function (a) {
        this.attachEvent("onBeforeDataSending", a)
    },
    setAutoUpdate: function (d, c) {
        d = d || 2000;
        this._user = c || (new Date()).valueOf();
        this._need_update = false;
        this._update_busy = false;
        this.attachEvent("onAfterUpdate", function (e, g, h, f) {
            this.afterAutoUpdate(e, g, h, f)
        });
        this.attachEvent("onFullSync", function () {
            this.fullSync()
        });
        var a = this;
        window.setInterval(function () {
            a.loadUpdate()
        }, d)
    },
    afterAutoUpdate: function (a, d, e, c) {
        if (d == "collision") {
            this._need_update = true;
            return false
        } else {
            return true
        }
    },
    fullSync: function () {
        if (this._need_update == true) {
            this._need_update = false;
            this.loadUpdate()
        }
        return true
    },
    getUpdates: function (a, c) {
        if (this._update_busy) {
            return false
        } else {
            this._update_busy = true
        }
        dhx4.ajax.get(a, c)
    },
    _v: function (a) {
        if (a.firstChild) {
            return a.firstChild.nodeValue
        }
        return ""
    },
    _a: function (a) {
        var d = [];
        for (var c = 0; c < a.length; c++) {
            d[c] = this._v(a[c])
        }
        return d
    },
    loadUpdate: function () {
        var c = this;
        var a = this.obj.getUserData(0, "version");
        var d = this.serverProcessor + this.url(this.serverProcessor) + ["dhx_user=" + this._user, "dhx_version=" + a].join("&");
        d = d.replace("editing=true&", "");
        this.getUpdates(d, function (k) {
            var l = dhx4.ajax.xmltop("updates", k.xmlDoc);
            var g = dhx4.ajax.xpath("//userdata", l);
            c.obj.setUserData(0, "version", c._v(g[0]));
            var e = dhx4.ajax.xpath("//update", l);
            if (e.length) {
                c._silent_mode = true;
                for (var h = 0; h < e.length; h++) {
                    var f = e[h].getAttribute("status");
                    var m = e[h].getAttribute("id");
                    var j = e[h].getAttribute("parent");
                    switch (f) {
                        case "inserted":
                            c.callEvent("insertCallback", [e[h], m, j]);
                            break;
                        case "updated":
                            c.callEvent("updateCallback", [e[h], m, j]);
                            break;
                        case "deleted":
                            c.callEvent("deleteCallback", [e[h], m, j]);
                            break
                    }
                }
                c._silent_mode = false
            }
            c._update_busy = false;
            c = null
        })
    }
};
if (window.dataProcessor && !dataProcessor.prototype.init_original) {
    dataProcessor.prototype.connector_init = true
}

function dhtmlXMenuObject(f, g) {
    var e = this;
    this.conf = {
        skin: (g || window.dhx4.skin || (typeof (dhtmlx) != "undefined" ? dhtmlx.skin : null) || window.dhx4.skinDetect("dhxmenu") || "material"),
        mode: "web",
        align: "left",
        is_touched: false,
        selected: -1,
        last_click: -1,
        fixed_pos: false,
        rtl: false,
        icons_path: "",
        icons_css: false,
        arrow_ff_fix: (navigator.userAgent.indexOf("MSIE") >= 0 && document.compatMode == "BackCompat"),
        live_id: window.dhx4.newId(),
        tags: {
            root: "menu",
            item: "item",
            text_ext: "itemtext",
            userdata: "userdata",
            tooltip: "tooltip",
            hotkey: "hotkey",
            href: "href"
        },
        autoload: {},
        hide_tm: {},
        top_mode: true,
        top_tmtime: 200,
        v_enabled: false,
        v: {
            x1: null,
            x2: null,
            y1: null,
            y2: null
        },
        dir_toplv: "bottom",
        dir_sublv: "right",
        auto_overflow: false,
        overflow_limit: 0,
        of_utm: null,
        of_utime: 20,
        of_ustep: 3,
        of_dtm: null,
        of_dtime: 20,
        of_dstep: 3,
        of_ah: {
            dhx_skyblue: 24,
            dhx_web: 25,
            dhx_terrace: 27,
            material: 25
        },
        of_ih: {
            dhx_skyblue: 24,
            dhx_web: 24,
            dhx_terrace: 24,
            material: 30
        },
        tm_sec: 400,
        tm_handler: null,
        dload: false,
        dload_url: "",
        dload_icon: false,
        dload_params: {
            action: "loadMenu"
        },
        dload_pid: "parentId",
        tl_botmarg: 1,
        tl_rmarg: 0,
        tl_ofsleft: 1,
        context: false,
        ctx_zoneid: false,
        ctx_autoshow: true,
        ctx_autohide: true,
        ctx_hideall: true,
        ctx_zones: {},
        ctx_baseid: null,
        selected_sub: [],
        opened_poly: []
    };
    if (typeof (f) == "object" && f != null && typeof (f.tagName) == "undefined") {
        if (f.icons_path != null || f.icon_path != null) {
            this.conf.icons_path = (f.icons_path || f.icon_path)
        }
        if (f.skin != null) {
            this.conf.skin = f.skin
        }
        if (f.visible_area) {
            this.conf.v_enabled = true;
            this.conf.v = {
                x1: f.visible_area.x1,
                x2: f.visible_area.x2,
                y1: f.visible_area.y1,
                y2: f.visible_area.y2
            }
        }
        for (var d in {
                json: 1,
                xml: 1,
                items: 1,
                top_text: 1,
                align: 1,
                open_mode: 1,
                overflow: 1,
                dynamic: 1,
                dynamic_icon: 1,
                context: 1,
                onload: 1,
                onclick: 1,
                oncheckboxclick: 1,
                onradioclick: 1,
                iconset: 1
            }) {
            if (f[d] != null) {
                this.conf.autoload[d] = f[d]
            }
        }
        f = f.parent
    }
    if (f == null) {
        this.base = document.body
    } else {
        var c = (typeof (f) == "string" ? document.getElementById(f) : f);
        if (c != null) {
            this.base = c;
            if (!this.base.id) {
                this.base.id = "menuBaseId_" + new Date().getTime()
            }
            this.base.className += " dhtmlxMenu_" + this.conf.skin + "_Middle dir_left";
            this.base._autoSkinUpdate = true;
            if (this.base.oncontextmenu) {
                this.base._oldContextMenuHandler = this.base.oncontextmenu
            }
            this.conf.ctx_baseid = this.base;
            this.base.onselectstart = function (a) {
                a = a || event;
                if (a.preventDefault) {
                    a.preventDefault()
                } else {
                    a.returnValue = false
                }
                return false
            };
            this.base.oncontextmenu = function (a) {
                a = a || event;
                if (a.preventDefault) {
                    a.preventDefault()
                } else {
                    a.returnValue = false
                }
                return false
            }
        } else {
            this.base = document.body
        }
    }
    this.idPrefix = "";
    this.topId = "dhxWebMenuTopId";
    this.idPull = {};
    this.itemPull = {};
    this.userData = {};
    this.radio = {};
    this.setSkin = function (j) {
        var k = this.conf.skin;
        this.conf.skin = j;
        switch (this.conf.skin) {
            case "dhx_skyblue":
            case "dhx_web":
                this.conf.tl_botmarg = 2;
                this.conf.tl_rmarg = 1;
                this.conf.tl_ofsleft = 1;
                break;
            case "dhx_terrace":
            case "material":
                this.conf.tl_botmarg = 0;
                this.conf.tl_rmarg = 0;
                this.conf.tl_ofsleft = 0;
                break
        }
        if (this.base._autoSkinUpdate) {
            this.base.className = this.base.className.replace("dhtmlxMenu_" + k + "_Middle", "") + " dhtmlxMenu_" + this.conf.skin + "_Middle"
        }
        for (var h in this.idPull) {
            this.idPull[h].className = String(this.idPull[h].className).replace(k, this.conf.skin)
        }
    };
    this.setSkin(this.conf.skin);
    this._addSubItemToSelected = function (j, h) {
        var a = true;
        for (var k = 0; k < this.conf.selected_sub.length; k++) {
            if ((this.conf.selected_sub[k][0] == j) && (this.conf.selected_sub[k][1] == h)) {
                a = false
            }
        }
        if (a == true) {
            this.conf.selected_sub.push(new Array(j, h))
        }
        return a
    };
    this._removeSubItemFromSelected = function (k, j) {
        var a = new Array();
        var h = false;
        for (var l = 0; l < this.conf.selected_sub.length; l++) {
            if ((this.conf.selected_sub[l][0] == k) && (this.conf.selected_sub[l][1] == j)) {
                h = true
            } else {
                a[a.length] = this.conf.selected_sub[l]
            }
        }
        if (h == true) {
            this.conf.selected_sub = a
        }
        return h
    };
    this._getSubItemToDeselectByPolygon = function (k) {
        var a = new Array();
        for (var l = 0; l < this.conf.selected_sub.length; l++) {
            if (this.conf.selected_sub[l][1] == k) {
                a[a.length] = this.conf.selected_sub[l][0];
                a = a.concat(this._getSubItemToDeselectByPolygon(this.conf.selected_sub[l][0]));
                var j = true;
                for (var h = 0; h < this.conf.opened_poly.length; h++) {
                    if (this.conf.opened_poly[h] == this.conf.selected_sub[l][0]) {
                        j = false
                    }
                }
                if (j == true) {
                    this.conf.opened_poly[this.conf.opened_poly.length] = this.conf.selected_sub[l][0]
                }
                this.conf.selected_sub[l][0] = -1;
                this.conf.selected_sub[l][1] = -1
            }
        }
        return a
    };
    this._hidePolygon = function (a) {
        if (this.idPull["polygon_" + a] != null) {
            if (this.idPull["polygon_" + a]._zId != null) {
                window.dhx4.zim.clear(this.idPull["polygon_" + a]._zId)
            }
            if (typeof (this._menuEffect) != "undefined" && this._menuEffect !== false) {
                this._hidePolygonEffect("polygon_" + a)
            } else {
                if (this.idPull["polygon_" + a].style.display == "none") {
                    return
                }
                this.idPull["polygon_" + a].style.display = "none";
                if (this.idPull["arrowup_" + a] != null) {
                    this.idPull["arrowup_" + a].style.display = "none"
                }
                if (this.idPull["arrowdown_" + a] != null) {
                    this.idPull["arrowdown_" + a].style.display = "none"
                }
                this._updateItemComplexState(a, true, false);
                if (window.dhx4.isIE6 && this.idPull["polygon_" + a + "_ie6cover"] != null) {
                    this.idPull["polygon_" + a + "_ie6cover"].style.display = "none"
                }
            }
            a = String(a).replace(this.idPrefix, "");
            if (a == this.topId) {
                a = null
            }
            this.callEvent("onHide", [a]);
            if (a != null && this.conf.skin == "dhx_terrace" && this.itemPull[this.idPrefix + a].parent == this.idPrefix + this.topId) {
                this._improveTerraceButton(this.idPrefix + a, true)
            }
        }
    };
    this._showPolygon = function (B, k) {
        var G = this._countVisiblePolygonItems(B);
        if (G == 0) {
            return
        }
        var C = "polygon_" + B;
        if ((this.idPull[C] != null) && (this.idPull[B] != null)) {
            if (this.conf.top_mode && this.conf.mode == "web" && !this.conf.context) {
                if (!this.idPull[B]._mouseOver && k == this.conf.dir_toplv) {
                    return
                }
            }
            if (!this.conf.fixed_pos) {
                this._autoDetectVisibleArea()
            }
            var D = 0;
            var F = 0;
            var I = null;
            var u = null;
            if (this.idPull[C]._zId == null) {
                this.idPull[C]._zId = window.dhx4.newId()
            }
            this.idPull[C]._zInd = window.dhx4.zim.reserve(this.idPull[C]._zId);
            this.idPull[C].style.visibility = "hidden";
            this.idPull[C].style.left = "0px";
            this.idPull[C].style.top = "0px";
            this.idPull[C].style.display = "";
            this.idPull[C].style.zIndex = this.idPull[C]._zInd;
            if (this.conf.auto_overflow) {
                if (this.idPull[C].childNodes[1].childNodes[0].offsetHeight > this.conf.v.y2 - this.conf.v.y1) {
                    var s = Math.max(Math.floor((this.conf.v.y2 - this.conf.v.y1 - this.conf.of_ah[this.conf.skin] * 2) / this.conf.of_ih[this.conf.skin]), 1);
                    this.conf.overflow_limit = s
                } else {
                    this.conf.overflow_limit = 0;
                    if (this.idPull["arrowup_" + B] != null) {
                        this._removeUpArrow(String(B).replace(this.idPrefix, ""))
                    }
                    if (this.idPull["arrowdown_" + B] != null) {
                        this._removeDownArrow(String(B).replace(this.idPrefix, ""))
                    }
                }
            }
            if (this.conf.overflow_limit > 0 && this.conf.overflow_limit < G) {
                if (this.idPull["arrowup_" + B] == null) {
                    this._addUpArrow(String(B).replace(this.idPrefix, ""))
                }
                if (this.idPull["arrowdown_" + B] == null) {
                    this._addDownArrow(String(B).replace(this.idPrefix, ""))
                }
                I = this.idPull["arrowup_" + B];
                I.style.display = "none";
                u = this.idPull["arrowdown_" + B];
                u.style.display = "none"
            }
            if (this.conf.overflow_limit > 0 && this.conf.overflow_limit < G) {
                this.idPull[C].childNodes[1].style.height = this.conf.of_ih[this.conf.skin] * this.conf.overflow_limit + "px";
                I.style.width = u.style.width = this.idPull[C].childNodes[1].style.width = this.idPull[C].childNodes[1].childNodes[0].offsetWidth + "px";
                this.idPull[C].childNodes[1].scrollTop = 0;
                I.style.display = "";
                D = I.offsetHeight;
                u.style.display = "";
                F = u.offsetHeight
            } else {
                this.idPull[C].childNodes[1].style.height = "";
                this.idPull[C].childNodes[1].style.width = ""
            }
            if (this.itemPull[B] != null) {
                var q = "polygon_" + this.itemPull[B]["parent"]
            } else {
                if (this.conf.context) {
                    var q = this.idPull[this.idPrefix + this.topId]
                }
            }
            var a = (this.idPull[B].tagName != null ? window.dhx4.absLeft(this.idPull[B]) : this.idPull[B][0]);
            var H = (this.idPull[B].tagName != null ? window.dhx4.absTop(this.idPull[B]) : this.idPull[B][1]);
            var j = (this.idPull[B].tagName != null ? this.idPull[B].offsetWidth : 0);
            var l = (this.idPull[B].tagName != null ? this.idPull[B].offsetHeight : 0);
            var p = 0;
            var o = 0;
            var r = this.idPull[C].offsetWidth;
            var E = this.idPull[C].offsetHeight;
            if (k == "bottom") {
                if (this.conf.rtl) {
                    p = a + (j != null ? j : 0) - r
                } else {
                    if (this.conf.align == "right") {
                        p = a + j - r
                    } else {
                        p = a - 1 + (k == this.conf.dir_toplv ? this.conf.tl_rmarg : 0)
                    }
                }
                o = H - 1 + l + this.conf.tl_botmarg
            }
            if (k == "right") {
                p = a + j - 1;
                o = H + 2
            }
            if (k == "left") {
                p = a - this.idPull[C].offsetWidth + 2;
                o = H + 2
            }
            if (k == "top") {
                p = a - 1;
                o = H - E + 2
            }
            if (this.conf.fixed_pos) {
                var A = 65536;
                var v = 65536
            } else {
                var A = (this.conf.v.x2 != null ? this.conf.v.x2 : 0);
                var v = (this.conf.v.y2 != null ? this.conf.v.y2 : 0);
                if (A == 0) {
                    if (window.innerWidth) {
                        A = window.innerWidth;
                        v = window.innerHeight
                    } else {
                        A = document.body.offsetWidth;
                        v = document.body.scrollHeight
                    }
                }
            }
            if (p + r > A && !this.conf.rtl) {
                p = a - r + 2
            }
            if (p < this.conf.v.x1 && this.conf.rtl) {
                p = a + j - 2
            }
            if (p < 0) {
                p = 0
            }
            if (o + E > v && this.conf.v.y2 != null) {
                o = Math.max(H + l - E + 2, (this.conf.v_enabled ? this.conf.v.y1 + 2 : 2));
                if (this.conf.context && this.idPrefix + this.topId == B && u != null) {
                    o = o - 2
                }
                if (this.itemPull[B] != null && !this.conf.context) {
                    if (this.itemPull[B]["parent"] == this.idPrefix + this.topId) {
                        o = o - this.base.offsetHeight
                    }
                }
            }
            this.idPull[C].style.left = p + "px";
            this.idPull[C].style.top = o + "px";
            if (typeof (this._menuEffect) != "undefined" && this._menuEffect !== false) {
                this._showPolygonEffect(C)
            } else {
                this.idPull[C].style.visibility = "";
                if (this.conf.overflow_limit > 0 && this.conf.overflow_limit < G) {
                    this.idPull[C].childNodes[1].scrollTop = 0;
                    this._checkArrowsState(B)
                }
                if (window.dhx4.isIE6) {
                    var n = C + "_ie6cover";
                    if (this.idPull[n] == null) {
                        var m = document.createElement("IFRAME");
                        m.className = "dhtmlxMenu_IE6CoverFix_" + this.conf.skin;
                        m.frameBorder = 0;
                        m.setAttribute("src", "javascript:false;");
                        document.body.insertBefore(m, document.body.firstChild);
                        this.idPull[n] = m
                    }
                    this.idPull[n].style.left = p + "px";
                    this.idPull[n].style.top = o + "px";
                    this.idPull[n].style.width = this.idPull[C].offsetWidth + "px";
                    this.idPull[n].style.height = this.idPull[C].offsetHeight + "px";
                    this.idPull[n].style.zIndex = this.idPull[C].style.zIndex - 1;
                    this.idPull[n].style.display = ""
                }
            }
            B = String(B).replace(this.idPrefix, "");
            if (B == this.topId) {
                B = null
            }
            this.callEvent("onShow", [B]);
            if (B != null && this.conf.skin == "dhx_terrace" && this.itemPull[this.idPrefix + B].parent == this.idPrefix + this.topId) {
                this._improveTerraceButton(this.idPrefix + B, false)
            }
        }
    };
    this._redistribSubLevelSelection = function (l, k) {
        while (this.conf.opened_poly.length > 0) {
            this.conf.opened_poly.pop()
        }
        var a = this._getSubItemToDeselectByPolygon(k);
        this._removeSubItemFromSelected(-1, -1);
        for (var h = 0; h < a.length; h++) {
            if ((this.idPull[a[h]] != null) && (a[h] != l)) {
                if (this.itemPull[a[h]]["state"] == "enabled") {
                    this.idPull[a[h]].className = "sub_item"
                }
            }
        }
        for (var h = 0; h < this.conf.opened_poly.length; h++) {
            if (this.conf.opened_poly[h] != k) {
                this._hidePolygon(this.conf.opened_poly[h])
            }
        }
        if (this.itemPull[l]["state"] == "enabled") {
            this.idPull[l].className = "sub_item_selected";
            if (this.itemPull[l]["complex"] && this.conf.dload && (this.itemPull[l]["loaded"] == "no")) {
                if (this.conf.dload_icon == true) {
                    this._updateLoaderIcon(l, true)
                }
                this.itemPull[l].loaded = "get";
                var j = l.replace(this.idPrefix, "");
                this._dhxdataload.onBeforeXLS = function () {
                    var n = {
                        params: {}
                    };
                    n.params[this.conf.dload_pid] = j;
                    for (var m in this.conf.dload_params) {
                        n.params[m] = this.conf.dload_params[m]
                    }
                    return n
                };
                this.loadStruct(this.conf.dload_url)
            }
            if (this.itemPull[l]["complex"] || (this.conf.dload && (this.itemPull[l]["loaded"] == "yes"))) {
                if ((this.itemPull[l]["complex"]) && (this.idPull["polygon_" + l] != null)) {
                    this._updateItemComplexState(l, true, true);
                    this._showPolygon(l, this.conf.dir_sublv)
                }
            }
            this._addSubItemToSelected(l, k);
            this.conf.selected = l
        }
    };
    this._doOnClick = function (h, v, n) {
        this.conf.last_click = h;
        if (this.itemPull[this.idPrefix + h]["href_link"] != null && this.itemPull[this.idPrefix + h].state == "enabled") {
            var o = document.createElement("FORM");
            var s = String(this.itemPull[this.idPrefix + h]["href_link"]).split("?");
            o.action = s[0];
            if (s[1] != null) {
                var l = String(s[1]).split("&");
                for (var a = 0; a < l.length; a++) {
                    var u = String(l[a]).split("=");
                    var r = document.createElement("INPUT");
                    r.type = "hidden";
                    r.name = (u[0] || "");
                    r.value = (u[1] || "");
                    o.appendChild(r)
                }
            }
            if (this.itemPull[this.idPrefix + h]["href_target"] != null) {
                o.target = this.itemPull[this.idPrefix + h]["href_target"]
            }
            o.style.display = "none";
            document.body.appendChild(o);
            o.submit();
            if (o != null) {
                document.body.removeChild(o);
                o = null
            }
            return
        }
        if (v.charAt(0) == "c") {
            return
        }
        if (v.charAt(1) == "d") {
            return
        }
        if (v.charAt(2) == "s") {
            return
        }
        if (this.checkEvent("onClick")) {
            this.callEvent("onClick", [h, this.conf.ctx_zoneid, n])
        } else {
            if ((v.charAt(1) == "d") || (this.conf.mode == "win" && v.charAt(2) == "t")) {
                return
            }
        }
        if (this.conf.context && this._isContextMenuVisible() && this.conf.ctx_autohide) {
            this._hideContextMenu()
        } else {
            if (this._clearAndHide) {
                this._clearAndHide()
            }
        }
    };
    this._doOnTouchMenu = function (a) {
        if (this.conf.is_touched == false) {
            this.conf.is_touched = true;
            if (this.checkEvent("onTouch")) {
                this.callEvent("onTouch", [a])
            }
        }
    };
    this._searchMenuNode = function (k, n) {
        var a = new Array();
        for (var l = 0; l < n.length; l++) {
            if (typeof (n[l]) == "object") {
                if (n[l].length == 5) {
                    if (typeof (n[l][0]) != "object") {
                        if ((n[l][0].replace(this.idPrefix, "") == k) && (l == 0)) {
                            a = n
                        }
                    }
                }
                var h = this._searchMenuNode(k, n[l]);
                if (h.length > 0) {
                    a = h
                }
            }
        }
        return a
    };
    this._getMenuNodes = function (k) {
        var h = new Array;
        for (var j in this.itemPull) {
            if (this.itemPull[j]["parent"] == k) {
                h[h.length] = j
            }
        }
        return h
    };
    this._genStr = function (a) {
        var h = "dhxId_";
        var k = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        for (var j = 0; j < a; j++) {
            h += k.charAt(Math.round(Math.random() * (k.length - 1)))
        }
        return h
    };
    this.getItemType = function (a) {
        a = this.idPrefix + a;
        if (this.itemPull[a] == null) {
            return null
        }
        return this.itemPull[a]["type"]
    };
    this.forEachItem = function (j) {
        for (var h in this.itemPull) {
            j(String(h).replace(this.idPrefix, ""))
        }
    };
    this._clearAndHide = function () {
        e.conf.selected = -1;
        e.conf.last_click = -1;
        while (e.conf.opened_poly.length > 0) {
            e.conf.opened_poly.pop()
        }
        for (var a = 0; a < e.conf.selected_sub.length; a++) {
            var h = e.conf.selected_sub[a][0];
            if (e.idPull[h] != null) {
                if (e.itemPull[h]["state"] == "enabled") {
                    if (e.idPull[h].className == "sub_item_selected") {
                        e.idPull[h].className = "sub_item"
                    }
                    if (e.idPull[h].className == "dhtmlxMenu_" + e.conf.skin + "_TopLevel_Item_Selected") {
                        if (e.itemPull[h]["cssNormal"] != null) {
                            e.idPull[h].className = e.itemPull[h]["cssNormal"]
                        } else {
                            e.idPull[h].className = "dhtmlxMenu_" + e.conf.skin + "_TopLevel_Item_Normal"
                        }
                    }
                }
            }
            e._hidePolygon(h)
        }
        e.conf.is_touched = false;
        if (e.conf.context && e.conf.ctx_hideall) {
            e._hidePolygon(e.idPrefix + e.topId)
        }
    };
    this._showSubLevelItem = function (h, a) {
        if (document.getElementById("arrow_" + this.idPrefix + h) != null) {
            document.getElementById("arrow_" + this.idPrefix + h).style.display = (a ? "none" : "")
        }
        if (document.getElementById("image_" + this.idPrefix + h) != null) {
            document.getElementById("image_" + this.idPrefix + h).style.display = (a ? "none" : "")
        }
        if (document.getElementById(this.idPrefix + h) != null) {
            document.getElementById(this.idPrefix + h).style.display = (a ? "" : "none")
        }
    };
    this._hideSubLevelItem = function (a) {
        this._showSubLevelItem(a, true)
    };
    this.idPrefix = this._genStr(12) + "_";
    this._bodyClick = function (a) {
        a = a || event;
        if (a.button == 2 || (window.dhx4.isOpera && a.ctrlKey == true)) {
            return
        }
        if (e.conf.context) {
            if (e.conf.ctx_autohide && (!window.dhx4.isOpera || (e._isContextMenuVisible() && window.dhx4.isOpera))) {
                e._hideContextMenu()
            }
        } else {
            if (e._clearAndHide) {
                e._clearAndHide()
            }
        }
    };
    this._bodyContext = function (j) {
        j = j || event;
        var h = String((j.srcElement || j.target).className);
        if (h.search("dhtmlxMenu") != -1 && h.search("SubLevelArea") != -1) {
            return
        }
        var a = true;
        var k = j.target || j.srcElement;
        while (k != null) {
            if (k.id != null) {
                if (e.isContextZone(k.id)) {
                    a = false
                }
            }
            if (k == document.body) {
                a = false
            }
            k = k.parentNode
        }
        if (a) {
            e.hideContextMenu()
        }
    };
    if (typeof (window.addEventListener) != "undefined") {
        window.addEventListener("click", this._bodyClick, false);
        window.addEventListener("contextmenu", this._bodyContext, false)
    } else {
        document.body.attachEvent("onclick", this._bodyClick);
        document.body.attachEvent("oncontextmenu", this._bodyContext)
    }
    dhx4.attachEvent("_onGridClick", this._bodyClick);
    this.unload = function () {
        window.dhx4._eventable(this, "clear");
        dhtmlXMenuObject.prototype.liveInst[this.conf.live_id] = null;
        try {
            delete dhtmlXMenuObject.prototype.liveInst[this.conf.live_id]
        } catch (j) {}
        this.conf.live_id = null;
        if (typeof (window.addEventListener) == "function") {
            window.removeEventListener("click", this._bodyClick, false);
            window.removeEventListener("contextmenu", this._bodyContext, false)
        } else {
            document.body.detachEvent("onclick", this._bodyClick);
            document.body.detachEvent("oncontextmenu", this._bodyContext)
        }
        this._bodyClick = null;
        this._bodyContext = null;
        this.removeItem(this.idPrefix + this.topId, true);
        this.itemPull = null;
        this.idPull = null;
        if (this.conf.context) {
            for (var h in this.conf.ctx_zones) {
                this.removeContextZone(h)
            }
        }
        if (this.cont != null) {
            this.cont.className = "";
            this.cont.parentNode.removeChild(this.cont);
            this.cont = null
        }
        if (this.base != null) {
            if (!this.conf.context) {
                this.base.className = ""
            }
            if (!this.conf.context) {
                this.base.oncontextmenu = (this.base._oldContextMenuHandler || null)
            }
            this.base.onselectstart = null;
            this.base = null
        }
        for (var h in this) {
            this[h] = null
        }
        e = null
    };
    dhtmlXMenuObject.prototype.liveInst[this.conf.live_id] = this;
    window.dhx4._enableDataLoading(this, "_initObj", "_xmlToJson", this.conf.tags.root, {
        struct: true
    });
    window.dhx4._eventable(this);
    if (window.dhx4.s2b(this.conf.autoload.context) == true) {
        this.renderAsContextMenu()
    }
    if (this.conf.autoload.iconset == "awesome") {
        this.conf.icons_css = true
    }
    if (this.conf.autoload.dynamic != null) {
        this.enableDynamicLoading(this.conf.autoload.dynamic, window.dhx4.s2b(this.conf.autoload.dynamic_icon))
    } else {
        if (this.conf.autoload.items != null) {
            this.loadStruct(this.conf.autoload.items, this.conf.autoload.onload)
        } else {
            if (this.conf.autoload.json != null) {
                this.loadStruct(this.conf.autoload.json, this.conf.autoload.onload)
            } else {
                if (this.conf.autoload.xml != null) {
                    this.loadStruct(this.conf.autoload.xml, this.conf.autoload.onload)
                }
            }
        }
    }
    for (var d in {
            onclick: 1,
            oncheckboxclick: 1,
            onradioclick: 1
        }) {
        if (this.conf.autoload[d] != null) {
            if (typeof (this.conf.autoload[d]) == "function") {
                this.attachEvent(d, this.conf.autoload[d])
            } else {
                if (typeof (window[this.conf.autoload[d]]) == "function") {
                    this.attachEvent(d, window[this.conf.autoload[d]])
                }
            }
        }
    }
    if (this.conf.autoload.top_text != null) {
        this.setTopText(this.conf.autoload.top_text)
    }
    if (this.conf.autoload.align != null) {
        this.setAlign(this.conf.autoload.align)
    }
    if (this.conf.autoload.open_mode != null) {
        this.setOpenMode(this.conf.autoload.open_mode)
    }
    if (this.conf.autoload.overflow != null) {
        this.setOverflowHeight(this.conf.autoload.overflow)
    }
    for (var d in this.conf.autoload) {
        this.conf.autoload[d] = null;
        delete this.conf.autoload[d]
    }
    this.conf.autoload = null;
    return this
}
dhtmlXMenuObject.prototype._init = function () {
    if (this._isInited == true) {
        return
    }
    if (this.conf.dload) {
        this._dhxdataload.onBeforeXLS = function () {
            var d = {
                params: {}
            };
            for (var c in this.conf.dload_params) {
                d.params[c] = this.conf.dload_params[c]
            }
            return d
        };
        this.loadStruct(this.conf.dload_url)
    } else {
        this._initTopLevelMenu();
        this._isInited = true
    }
};
dhtmlXMenuObject.prototype._countVisiblePolygonItems = function (g) {
    var e = 0;
    for (var c in this.itemPull) {
        var d = this.itemPull[c]["parent"];
        var f = this.itemPull[c]["type"];
        if (this.idPull[c] != null) {
            if (d == g && (f == "item" || f == "radio" || f == "checkbox") && this.idPull[c].style.display != "none") {
                e++
            }
        }
    }
    return e
};
dhtmlXMenuObject.prototype._redefineComplexState = function (c) {
    if (this.idPrefix + this.topId == c) {
        return
    }
    if ((this.idPull["polygon_" + c] != null) && (this.idPull[c] != null)) {
        var a = this._countVisiblePolygonItems(c);
        if ((a > 0) && (!this.itemPull[c]["complex"])) {
            this._updateItemComplexState(c, true, false)
        }
        if ((a == 0) && (this.itemPull[c]["complex"])) {
            this._updateItemComplexState(c, false, false)
        }
    }
};
dhtmlXMenuObject.prototype._updateItemComplexState = function (f, d, e) {
    if ((!this.conf.context) && (this._getItemLevelType(f.replace(this.idPrefix, "")) == "TopLevel")) {
        this.itemPull[f]["complex"] = d;
        return
    }
    if ((this.idPull[f] == null) || (this.itemPull[f] == null)) {
        return
    }
    this.itemPull[f]["complex"] = d;
    if (f == this.idPrefix + this.topId) {
        return
    }
    var a = null;
    var c = this.idPull[f].childNodes[this.conf.rtl ? 0 : 2];
    if (c.childNodes[0]) {
        if (String(c.childNodes[0].className).search("complex_arrow") === 0) {
            a = c.childNodes[0]
        }
    }
    if (this.itemPull[f]["complex"]) {
        if (a == null) {
            a = document.createElement("DIV");
            a.className = "complex_arrow";
            a.id = "arrow_" + f;
            while (c.childNodes.length > 0) {
                c.removeChild(c.childNodes[0])
            }
            c.appendChild(a)
        }
        if (this.conf.dload && (this.itemPull[f].loaded == "get") && this.conf.dload_icon) {
            if (a.className != "complex_arrow_loading") {
                a.className = "complex_arrow_loading"
            }
        } else {
            a.className = "complex_arrow"
        }
        return
    }
    if ((!this.itemPull[f]["complex"]) && (a != null)) {
        c.removeChild(a);
        if (this.itemPull[f]["hotkey_backup"] != null && this.setHotKey) {
            this.setHotKey(f.replace(this.idPrefix, ""), this.itemPull[f]["hotkey_backup"])
        }
    }
};
dhtmlXMenuObject.prototype._getItemLevelType = function (a) {
    return (this.itemPull[this.idPrefix + a]["parent"] == this.idPrefix + this.topId ? "TopLevel" : "SubLevelArea")
};
dhtmlXMenuObject.prototype.setIconsPath = function (a) {
    this.conf.icons_path = a
};
dhtmlXMenuObject.prototype._updateItemImage = function (d, f) {
    d = this.idPrefix + d;
    var k = this.itemPull[d]["type"];
    if (k == "checkbox" || k == "radio") {
        return
    }
    var g = (this.itemPull[d]["parent"] == this.idPrefix + this.topId && !this.conf.context);
    var h = null;
    if (g) {
        for (var a = 0; a < this.idPull[d].childNodes.length; a++) {
            if (h == null && (this.idPull[d].childNodes[a].className || "") == "dhtmlxMenu_TopLevel_Item_Icon" || (this.idPull[d].childNodes[a].tagName || "").toLowerCase() == "i") {
                h = this.idPull[d].childNodes[a]
            }
        }
    } else {
        try {
            var h = this.idPull[d].childNodes[this.conf.rtl ? 2 : 0].childNodes[0]
        } catch (j) {}
        if (!(h != null && typeof (h.className) != "undefined" && (h.className == "sub_icon" || h.tagName.toLowerCase() == "i"))) {
            h = null
        }
    }
    var m = this.itemPull[d][(this.itemPull[d]["state"] == "enabled" ? "imgen" : "imgdis")];
    if (m.length > 0) {
        if (h != null) {
            if (this.conf.icons_css == true) {
                h.className = this.conf.icons_path + m
            } else {
                h.src = this.conf.icons_path + m
            }
        } else {
            if (g) {
                if (this.conf.icons_css == true) {
                    var h = document.createElement("i");
                    h.className = this.conf.icons_path + m
                } else {
                    var h = document.createElement("IMG");
                    h.className = "dhtmlxMenu_TopLevel_Item_Icon";
                    h.src = this.conf.icons_path + m;
                    h.border = "0";
                    h.id = "image_" + d
                }
                if (!this.conf.rtl && this.idPull[d].childNodes.length > 0) {
                    this.idPull[d].insertBefore(h, this.idPull[d].childNodes[0])
                } else {
                    this.idPull[d].appendChild(h)
                }
            } else {
                if (this.conf.icons_css == true) {
                    var l = this.idPull[d].childNodes[this.conf.rtl ? 2 : 0];
                    l.innerHTML = "<i class='" + this.conf.icons_path + m + "'></i>"
                } else {
                    var h = document.createElement("IMG");
                    h.className = "sub_icon";
                    h.src = this.conf.icons_path + m;
                    h.border = "0";
                    h.id = "image_" + d;
                    var l = this.idPull[d].childNodes[this.conf.rtl ? 2 : 0];
                    while (l.childNodes.length > 0) {
                        l.removeChild(l.childNodes[0])
                    }
                    l.appendChild(h)
                }
            }
        }
    } else {
        if (h != null) {
            if (g) {
                h.parentNode.removeChild(h);
                h = null
            } else {
                var c = h.parentNode;
                c.removeChild(h);
                c.innerHTML = "&nbsp;";
                c = h = null
            }
        }
    }
};
dhtmlXMenuObject.prototype._getAllParents = function (g) {
    var d = new Array();
    for (var c in this.itemPull) {
        if (this.itemPull[c]["parent"] == g) {
            d[d.length] = this.itemPull[c]["id"];
            if (this.itemPull[c]["complex"]) {
                var e = this._getAllParents(this.itemPull[c]["id"]);
                for (var f = 0; f < e.length; f++) {
                    d[d.length] = e[f]
                }
            }
        }
    }
    return d
};
dhtmlXMenuObject.prototype._autoDetectVisibleArea = function () {
    if (this.conf.v_enabled) {
        return
    }
    var a = window.dhx4.screenDim();
    this.conf.v.x1 = a.left;
    this.conf.v.x2 = a.right;
    this.conf.v.y1 = a.top;
    this.conf.v.y2 = a.bottom
};
dhtmlXMenuObject.prototype.getItemPosition = function (f) {
    f = this.idPrefix + f;
    var e = -1;
    if (this.itemPull[f] == null) {
        return e
    }
    var a = this.itemPull[f]["parent"];
    var d = (this.idPull["polygon_" + a] != null ? this.idPull["polygon_" + a].tbd : this.cont);
    for (var c = 0; c < d.childNodes.length; c++) {
        if (d.childNodes[c] == this.idPull["separator_" + f] || d.childNodes[c] == this.idPull[f]) {
            e = c
        }
    }
    return e
};
dhtmlXMenuObject.prototype.setItemPosition = function (h, g) {
    h = this.idPrefix + h;
    if (this.idPull[h] == null) {
        return
    }
    var c = (this.itemPull[h]["parent"] == this.idPrefix + this.topId);
    var a = this.idPull[h];
    var e = this.getItemPosition(h.replace(this.idPrefix, ""));
    var d = this.itemPull[h]["parent"];
    var f = (this.idPull["polygon_" + d] != null ? this.idPull["polygon_" + d].tbd : this.cont);
    f.removeChild(f.childNodes[e]);
    if (g < 0) {
        g = 0
    }
    if (c && g < 1) {
        g = 1
    }
    if (g < f.childNodes.length) {
        f.insertBefore(a, f.childNodes[g])
    } else {
        f.appendChild(a)
    }
};
dhtmlXMenuObject.prototype.getParentId = function (a) {
    a = this.idPrefix + a;
    if (this.itemPull[a] == null) {
        return null
    }
    return ((this.itemPull[a]["parent"] != null ? this.itemPull[a]["parent"] : this.topId).replace(this.idPrefix, ""))
};
dhtmlXMenuObject.prototype.hide = function () {
    this._clearAndHide()
};
dhtmlXMenuObject.prototype.clearAll = function () {
    this.removeItem(this.idPrefix + this.topId, true);
    this._isInited = false;
    this.idPrefix = this._genStr(12) + "_";
    this.itemPull = {}
};
if (typeof (dhtmlXMenuObject.prototype.liveInst) == "undefined") {
    dhtmlXMenuObject.prototype.liveInst = {}
}
dhtmlXMenuObject.prototype.setIconset = function (a) {
    this.conf.icons_css = (a == "awesome")
};
dhtmlXMenuObject.prototype._redistribTopLevelSelection = function (e, c) {
    var a = this._getSubItemToDeselectByPolygon("parent");
    this._removeSubItemFromSelected(-1, -1);
    for (var d = 0; d < a.length; d++) {
        if (a[d] != e) {
            this._hidePolygon(a[d])
        }
        if ((this.idPull[a[d]] != null) && (a[d] != e)) {
            this.idPull[a[d]].className = this.idPull[a[d]].className.replace(/Selected/g, "Normal")
        }
    }
    if (this.itemPull[this.idPrefix + e]["state"] == "enabled") {
        this.idPull[this.idPrefix + e].className = "dhtmlxMenu_" + this.conf.skin + "_TopLevel_Item_Selected";
        this._addSubItemToSelected(this.idPrefix + e, "parent");
        this.conf.selected = (this.conf.mode == "win" ? (this.conf.selected != -1 ? e : this.conf.selected) : e);
        if ((this.itemPull[this.idPrefix + e]["complex"]) && (this.conf.selected != -1)) {
            this._showPolygon(this.idPrefix + e, this.conf.dir_toplv)
        }
    }
};
dhtmlXMenuObject.prototype._initTopLevelMenu = function () {
    this.conf.dir_toplv = "bottom";
    this.conf.dir_sublv = (this.conf.rtl ? "left" : "right");
    if (this.conf.context) {
        this.idPull[this.idPrefix + this.topId] = new Array(0, 0);
        this._addSubMenuPolygon(this.idPrefix + this.topId, this.idPrefix + this.topId)
    } else {
        var a = this._getMenuNodes(this.idPrefix + this.topId);
        for (var c = 0; c < a.length; c++) {
            if (this.itemPull[a[c]]["type"] == "item") {
                this._renderToplevelItem(a[c], null)
            }
            if (this.itemPull[a[c]]["type"] == "separator") {
                this._renderSeparator(a[c], null)
            }
        }
    }
};
dhtmlXMenuObject.prototype._renderToplevelItem = function (j, h) {
    var g = this;
    var a = document.createElement("DIV");
    a.id = j;
    if (this.itemPull[j]["state"] == "enabled" && this.itemPull[j]["cssNormal"] != null) {
        a.className = this.itemPull[j]["cssNormal"]
    } else {
        a.className = "dhtmlxMenu_" + this.conf.skin + "_TopLevel_Item_" + (this.itemPull[j]["state"] == "enabled" ? "Normal" : "Disabled")
    }
    if (this.itemPull[j]["title"] != "") {
        var f = document.createElement("DIV");
        f.className = "top_level_text";
        f.innerHTML = this.itemPull[j]["title"];
        a.appendChild(f)
    }
    if (this.itemPull[j]["tip"].length > 0) {
        a.title = this.itemPull[j]["tip"]
    }
    if ((this.itemPull[j]["imgen"] != "") || (this.itemPull[j]["imgdis"] != "")) {
        var e = this.itemPull[j][(this.itemPull[j]["state"] == "enabled") ? "imgen" : "imgdis"];
        if (e) {
            if (this.conf.icons_css == true) {
                var d = document.createElement("i");
                d.className = this.conf.icons_path + e;
                if (a.childNodes.length > 0 && !this.conf.rtl) {
                    a.insertBefore(d, a.childNodes[0])
                } else {
                    a.appendChild(d)
                }
            } else {
                var c = document.createElement("IMG");
                c.border = "0";
                c.id = "image_" + j;
                c.src = this.conf.icons_path + e;
                c.className = "dhtmlxMenu_TopLevel_Item_Icon";
                if (a.childNodes.length > 0 && !this.conf.rtl) {
                    a.insertBefore(c, a.childNodes[0])
                } else {
                    a.appendChild(c)
                }
            }
        }
    }
    a.onselectstart = function (k) {
        k = k || event;
        if (k.preventDefault) {
            k.preventDefault()
        } else {
            k.returnValue = false
        }
        return false
    };
    a.oncontextmenu = function (k) {
        k = k || event;
        if (k.preventDefault) {
            k.preventDefault()
        } else {
            k.returnValue = false
        }
        return false
    };
    if (!this.cont) {
        this.cont = document.createElement("DIV");
        this.cont.dir = "ltr";
        this.cont.className = (this.conf.align == "right" ? "align_right" : "align_left");
        this.base.appendChild(this.cont)
    }
    if (h != null) {
        h++;
        if (h < 0) {
            h = 0
        }
        if (h > this.cont.childNodes.length - 1) {
            h = null
        }
    }
    if (h != null) {
        this.cont.insertBefore(a, this.cont.childNodes[h])
    } else {
        this.cont.appendChild(a)
    }
    this.idPull[a.id] = a;
    if (this.itemPull[j]["complex"] && (!this.conf.dload)) {
        this._addSubMenuPolygon(this.itemPull[j]["id"], this.itemPull[j]["id"])
    }
    a.onmouseover = function () {
        if (g.conf.mode == "web") {
            window.clearTimeout(g.conf.tm_handler)
        }
        var k = g._getSubItemToDeselectByPolygon("parent");
        g._removeSubItemFromSelected(-1, -1);
        for (var m = 0; m < k.length; m++) {
            if (k[m] != this.id) {
                g._hidePolygon(k[m])
            }
            if ((g.idPull[k[m]] != null) && (k[m] != this.id)) {
                if (g.itemPull[k[m]]["cssNormal"] != null) {
                    g.idPull[k[m]].className = g.itemPull[k[m]]["cssNormal"]
                } else {
                    if (g.idPull[k[m]].className == "sub_item_selected") {
                        g.idPull[k[m]].className = "sub_item"
                    }
                    g.idPull[k[m]].className = g.idPull[k[m]].className.replace(/Selected/g, "Normal")
                }
            }
        }
        if (g.itemPull[this.id]["state"] == "enabled") {
            this.className = "dhtmlxMenu_" + g.conf.skin + "_TopLevel_Item_Selected";
            g._addSubItemToSelected(this.id, "parent");
            g.conf.selected = (g.conf.mode == "win" ? (g.conf.selected != -1 ? this.id : g.conf.selected) : this.id);
            if (g.conf.dload) {
                if (g.itemPull[this.id].loaded == "no") {
                    this._dynLoadTM = new Date().getTime();
                    g.itemPull[this.id].loaded = "get";
                    var n = this.id.replace(g.idPrefix, "");
                    g._dhxdataload.onBeforeXLS = function () {
                        var q = {
                            params: {}
                        };
                        q.params[this.conf.dload_pid] = n;
                        for (var o in this.conf.dload_params) {
                            q.params[o] = this.conf.dload_params[o]
                        }
                        return q
                    };
                    g.loadStruct(g.conf.dload_url)
                }
                if (g.conf.top_mode && g.conf.mode == "web" && !g.conf.context) {
                    this._mouseOver = true
                }
            }
            if ((!g.conf.dload) || (g.conf.dload && (!g.itemPull[this.id]["loaded"] || g.itemPull[this.id]["loaded"] == "yes"))) {
                if ((g.itemPull[this.id]["complex"]) && (g.conf.selected != -1)) {
                    if (g.conf.top_mode && g.conf.mode == "web" && !g.conf.context) {
                        this._mouseOver = true;
                        var l = this.id;
                        this._menuOpenTM = window.setTimeout(function () {
                            g._showPolygon(l, g.conf.dir_toplv)
                        }, g.conf.top_tmtime)
                    } else {
                        g._showPolygon(this.id, g.conf.dir_toplv)
                    }
                }
            }
        }
        g._doOnTouchMenu(this.id.replace(g.idPrefix, ""))
    };
    a.onmouseout = function () {
        if (!((g.itemPull[this.id]["complex"]) && (g.conf.selected != -1)) && (g.itemPull[this.id]["state"] == "enabled")) {
            if (g.itemPull[this.id]["cssNormal"] != null) {
                a.className = g.itemPull[this.id]["cssNormal"]
            } else {
                a.className = "dhtmlxMenu_" + g.conf.skin + "_TopLevel_Item_Normal"
            }
        }
        if (g.conf.mode == "web") {
            window.clearTimeout(g.conf.tm_handler);
            g.conf.tm_handler = window.setTimeout(function () {
                g._clearAndHide()
            }, g.conf.tm_sec, "JavaScript")
        }
        if (g.conf.top_mode && g.conf.mode == "web" && !g.conf.context) {
            this._mouseOver = false;
            window.clearTimeout(this._menuOpenTM)
        }
    };
    a.onclick = function (n) {
        if (g.conf.mode == "web") {
            window.clearTimeout(g.conf.tm_handler)
        }
        if (g.conf.mode != "web" && g.itemPull[this.id]["state"] == "disabled") {
            return
        }
        n = n || event;
        n.cancelBubble = true;
        if (n.preventDefault) {
            n.preventDefault()
        } else {
            n.returnValue = false
        }
        if (g.conf.mode == "win") {
            if (g.itemPull[this.id]["complex"]) {
                if (g.conf.selected == this.id) {
                    g.conf.selected = -1;
                    var m = false
                } else {
                    g.conf.selected = this.id;
                    var m = true
                }
                if (m) {
                    g._showPolygon(this.id, g.conf.dir_toplv)
                } else {
                    g._hidePolygon(this.id)
                }
            }
        }
        var k = (g.itemPull[this.id]["complex"] ? "c" : "-");
        var o = (g.itemPull[this.id]["state"] != "enabled" ? "d" : "-");
        var l = {
            ctrl: n.ctrlKey,
            alt: n.altKey,
            shift: n.shiftKey
        };
        g._doOnClick(this.id.replace(g.idPrefix, ""), k + o + "t", l);
        return false
    };
    if (this.conf.skin == "dhx_terrace") {
        this._improveTerraceSkin()
    }
};
dhtmlXMenuObject.prototype._addSubMenuPolygon = function (g, f) {
    var c = this._renderSublevelPolygon(g, f);
    var a = this._getMenuNodes(f);
    for (d = 0; d < a.length; d++) {
        if (this.itemPull[a[d]]["type"] == "separator") {
            this._renderSeparator(a[d], null)
        } else {
            this._renderSublevelItem(a[d], null)
        }
    }
    if (g == f) {
        var e = "topLevel"
    } else {
        var e = "subLevel"
    }
    for (var d = 0; d < a.length; d++) {
        if (this.itemPull[a[d]]["complex"]) {
            this._addSubMenuPolygon(g, this.itemPull[a[d]]["id"])
        }
    }
};
dhtmlXMenuObject.prototype._renderSublevelPolygon = function (f, e) {
    var c = document.createElement("DIV");
    c.className = "dhtmlxMenu_" + this.conf.skin + "_SubLevelArea_Polygon " + (this.conf.rtl ? "dir_right" : "");
    c.dir = "ltr";
    c.oncontextmenu = function (g) {
        g = g || event;
        if (g.preventDefault) {
            g.preventDefault()
        } else {
            g.returnValue = false
        }
        g.cancelBubble = true;
        return false
    };
    c.id = "polygon_" + e;
    c.onclick = function (g) {
        g = g || event;
        g.cancelBubble = true
    };
    c.style.display = "none";
    document.body.insertBefore(c, document.body.firstChild);
    c.innerHTML = '<div style="position:relative;"></div><div style="position: relative; overflow:hidden;"></div><div style="position:relative;"></div>';
    var d = document.createElement("TABLE");
    d.className = "dhtmlxMebu_SubLevelArea_Tbl";
    d.cellSpacing = 0;
    d.cellPadding = 0;
    d.border = 0;
    var a = document.createElement("TBODY");
    d.appendChild(a);
    c.childNodes[1].appendChild(d);
    c.tbl = d;
    c.tbd = a;
    this.idPull[c.id] = c;
    if (this.sxDacProc != null) {
        this.idPull["sxDac_" + e] = new this.sxDacProc(c, c.className);
        if (window.dhx4.isIE) {
            this.idPull["sxDac_" + e]._setSpeed(this.dacSpeedIE);
            this.idPull["sxDac_" + e]._setCustomCycle(this.dacCyclesIE)
        } else {
            this.idPull["sxDac_" + e]._setSpeed(this.dacSpeed);
            this.idPull["sxDac_" + e]._setCustomCycle(this.dacCycles)
        }
    }
    return c
};
dhtmlXMenuObject.prototype._renderSublevelItem = function (a, l) {
    var j = this;
    var k = document.createElement("TR");
    k.className = (this.itemPull[a]["state"] == "enabled" ? "sub_item" : "sub_item_dis");
    var h = document.createElement("TD");
    h.className = "sub_item_icon";
    var p = this.itemPull[a]["type"];
    var m = this.itemPull[a][(this.itemPull[a]["state"] == "enabled" ? "imgen" : "imgdis")];
    if (m != "") {
        if (p == "checkbox" || p == "radio") {
            var f = document.createElement("DIV");
            f.id = "image_" + this.itemPull[a]["id"];
            f.className = "sub_icon " + m;
            h.appendChild(f)
        }
        if (!(p == "checkbox" || p == "radio")) {
            if (this.conf.icons_css == true) {
                h.innerHTML = "<i class='" + this.conf.icons_path + m + "'></i>"
            } else {
                var f = document.createElement("IMG");
                f.id = "image_" + this.itemPull[a]["id"];
                f.className = "sub_icon";
                f.src = this.conf.icons_path + m;
                h.appendChild(f)
            }
        }
    } else {
        h.innerHTML = "&nbsp;"
    }
    var g = document.createElement("TD");
    g.className = "sub_item_text";
    if (this.itemPull[a]["title"] != "") {
        var o = document.createElement("DIV");
        o.className = "sub_item_text";
        o.innerHTML = this.itemPull[a]["title"];
        g.appendChild(o)
    } else {
        g.innerHTML = "&nbsp;"
    }
    var e = document.createElement("TD");
    e.className = "sub_item_hk";
    if (this.itemPull[a]["complex"]) {
        var c = document.createElement("DIV");
        c.className = "complex_arrow";
        c.id = "arrow_" + this.itemPull[a]["id"];
        e.appendChild(c)
    } else {
        if (this.itemPull[a]["hotkey"].length > 0 && !this.itemPull[a]["complex"]) {
            var d = document.createElement("DIV");
            d.className = "sub_item_hk";
            d.innerHTML = this.itemPull[a]["hotkey"];
            e.appendChild(d)
        } else {
            e.innerHTML = "&nbsp;"
        }
    }
    k.appendChild(this.conf.rtl ? e : h);
    k.appendChild(g);
    k.appendChild(this.conf.rtl ? h : e);
    k.id = this.itemPull[a]["id"];
    k.parent = this.itemPull[a]["parent"];
    if (this.itemPull[a]["tip"].length > 0) {
        k.title = this.itemPull[a]["tip"]
    }
    k.onselectstart = function (q) {
        q = q || event;
        if (q.preventDefault) {
            q.preventDefault()
        } else {
            q.returnValue = false
        }
        return false
    };
    k.onmouseover = function (q) {
        if (j.conf.hide_tm[this.id]) {
            window.clearTimeout(j.conf.hide_tm[this.id])
        }
        if (j.conf.mode == "web") {
            window.clearTimeout(j.conf.tm_handler)
        }
        if (!this._visible) {
            j._redistribSubLevelSelection(this.id, this.parent)
        }
        this._visible = true
    };
    k.onmouseout = function () {
        if (j.conf.mode == "web") {
            if (j.conf.tm_handler) {
                window.clearTimeout(j.conf.tm_handler)
            }
            j.conf.tm_handler = window.setTimeout(function () {
                if (j && j._clearAndHide) {
                    j._clearAndHide()
                }
            }, j.conf.tm_sec, "JavaScript")
        }
        var q = this;
        if (j.conf.hide_tm[this.id]) {
            window.clearTimeout(j.conf.hide_tm[this.id])
        }
        j.conf.hide_tm[this.id] = window.setTimeout(function () {
            q._visible = false
        }, 50)
    };
    k.onclick = function (r) {
        if (!j.checkEvent("onClick") && j.itemPull[this.id]["complex"]) {
            return
        }
        r = r || event;
        r.cancelBubble = true;
        if (r.preventDefault) {
            r.preventDefault()
        } else {
            r.returnValue = false
        }
        tc = (j.itemPull[this.id]["complex"] ? "c" : "-");
        td = (j.itemPull[this.id]["state"] == "enabled" ? "-" : "d");
        var q = {
            ctrl: r.ctrlKey,
            alt: r.altKey,
            shift: r.shiftKey
        };
        switch (j.itemPull[this.id]["type"]) {
            case "checkbox":
                j._checkboxOnClickHandler(this.id.replace(j.idPrefix, ""), tc + td + "n", q);
                break;
            case "radio":
                j._radioOnClickHandler(this.id.replace(j.idPrefix, ""), tc + td + "n", q);
                break;
            case "item":
                j._doOnClick(this.id.replace(j.idPrefix, ""), tc + td + "n", q);
                break
        }
        return false
    };
    var n = this.idPull["polygon_" + this.itemPull[a]["parent"]];
    if (l != null) {
        l++;
        if (l < 0) {
            l = 0
        }
        if (l > n.tbd.childNodes.length - 1) {
            l = null
        }
    }
    if (l != null && n.tbd.childNodes[l] != null) {
        n.tbd.insertBefore(k, n.tbd.childNodes[l])
    } else {
        n.tbd.appendChild(k)
    }
    this.idPull[k.id] = k
};
dhtmlXMenuObject.prototype._renderSeparator = function (c, h) {
    var a = (this.conf.context ? "SubLevelArea" : (this.itemPull[c]["parent"] == this.idPrefix + this.topId ? "TopLevel" : "SubLevelArea"));
    if (a == "TopLevel" && this.conf.context) {
        return
    }
    var f = this;
    if (a != "TopLevel") {
        var g = document.createElement("TR");
        g.className = "sub_sep";
        var d = document.createElement("TD");
        d.colSpan = "3";
        g.appendChild(d)
    }
    var e = document.createElement("DIV");
    e.id = "separator_" + c;
    e.className = (a == "TopLevel" ? "top_sep" : "sub_sep");
    e.onselectstart = function (k) {
        k = k || event;
        if (k.preventDefault) {
            k.preventDefault()
        } else {
            k.returnValue = false
        }
    };
    e.onclick = function (m) {
        m = m || event;
        m.cancelBubble = true;
        var k = {
            ctrl: m.ctrlKey,
            alt: m.altKey,
            shift: m.shiftKey
        };
        f._doOnClick(this.id.replace("separator_" + f.idPrefix, ""), "--s", k)
    };
    if (a == "TopLevel") {
        if (h != null) {
            h++;
            if (h < 0) {
                h = 0
            }
            if (this.cont.childNodes[h] != null) {
                this.cont.insertBefore(e, this.cont.childNodes[h])
            } else {
                this.cont.appendChild(e)
            }
        } else {
            var l = this.cont.childNodes[this.cont.childNodes.length - 1];
            if (String(l).search("TopLevel_Text") == -1) {
                this.cont.appendChild(e)
            } else {
                this.cont.insertBefore(e, l)
            }
        }
        this.idPull[e.id] = e
    } else {
        var j = this.idPull["polygon_" + this.itemPull[c]["parent"]];
        if (h != null) {
            h++;
            if (h < 0) {
                h = 0
            }
            if (h > j.tbd.childNodes.length - 1) {
                h = null
            }
        }
        if (h != null && j.tbd.childNodes[h] != null) {
            j.tbd.insertBefore(g, j.tbd.childNodes[h])
        } else {
            j.tbd.appendChild(g)
        }
        d.appendChild(e);
        this.idPull[e.id] = g
    }
};
dhtmlXMenuObject.prototype.addNewSeparator = function (a, c) {
    c = this.idPrefix + (c != null ? c : this._genStr(24));
    var d = this.idPrefix + this.getParentId(a);
    this._addItemIntoGlobalStrorage(c, d, "", "separator", false, "", "");
    this._renderSeparator(c, this.getItemPosition(a))
};
dhtmlXMenuObject.prototype._initObj = function (n, o, h) {
    if (!(n instanceof Array)) {
        h = n.parentId;
        if (h != null && String(h).indexOf(this.idPrefix) !== 0) {
            h = this.idPrefix + String(h)
        }
        n = n.items
    }
    for (var d = 0; d < n.length; d++) {
        if (typeof (n[d].id) == "undefined" || n[d].id == null) {
            n[d].id = this._genStr(24)
        }
        if (n[d].text == null) {
            n[d].text = ""
        }
        if (String(n[d].id).indexOf(this.idPrefix) !== 0) {
            n[d].id = this.idPrefix + String(n[d].id)
        }
        var e = {
            type: "item",
            tip: "",
            hotkey: "",
            state: "enabled",
            imgen: "",
            imgdis: ""
        };
        for (var p in e) {
            if (typeof (n[d][p]) == "undefined") {
                n[d][p] = e[p]
            }
        }
        if (n[d].imgen == "" && n[d].img != null) {
            n[d].imgen = n[d].img
        }
        if (n[d].imgdis == "" && n[d].img_disabled != null) {
            n[d].imgdis = n[d].img_disabled
        }
        if (n[d].title == null && n[d].text != null) {
            n[d].title = n[d].text
        }
        if (n[d].href != null) {
            if (n[d].href.link != null) {
                n[d].href_link = n[d].href.link
            }
            if (n[d].href.target != null) {
                n[d].href_target = n[d].href.target
            }
        }
        if (n[d].userdata != null) {
            for (var p in n[d].userdata) {
                this.userData[n[d].id + "_" + p] = n[d].userdata[p]
            }
        }
        if (typeof (n[d].enabled) != "undefined" && window.dhx4.s2b(n[d].enabled) == false) {
            n[d].state = "disabled"
        } else {
            if (typeof (n[d].disabled) != "undefined" && window.dhx4.s2b(n[d].disabled) == true) {
                n[d].state = "disabled"
            }
        }
        if (typeof (n[d].parent) == "undefined") {
            n[d].parent = (h != null ? h : this.idPrefix + this.topId)
        }
        if (n[d].type == "checkbox") {
            n[d].checked = window.dhx4.s2b(n[d].checked);
            n[d].imgen = n[d].imgdis = "chbx_" + (n[d].checked ? "1" : "0")
        }
        if (n[d].type == "radio") {
            n[d].checked = window.dhx4.s2b(n[d].checked);
            n[d].imgen = n[d].imgdis = "rdbt_" + (n[d].checked ? "1" : "0");
            if (typeof (n[d].group) == "undefined" || n[d].group == null) {
                n[d].group = this._genStr(24)
            }
            if (this.radio[n[d].group] == null) {
                this.radio[n[d].group] = []
            }
            this.radio[n[d].group].push(n[d].id)
        }
        this.itemPull[n[d].id] = n[d];
        if (n[d].items != null && n[d].items.length > 0) {
            this.itemPull[n[d].id].complex = true;
            this._initObj(n[d].items, true, n[d].id)
        } else {
            if (this.conf.dload && n[d].complex == true) {
                this.itemPull[n[d].id].loaded = "no"
            }
        }
        this.itemPull[n[d].id].items = null
    }
    if (o !== true) {
        if (this.conf.dload == true) {
            if (h == null) {
                this._initTopLevelMenu()
            } else {
                this._addSubMenuPolygon(h, h);
                if (this.conf.selected == h) {
                    var m = (this.itemPull[h].parent == this.idPrefix + this.topId);
                    var c = (m && !this.conf.context ? this.conf.dir_toplv : this.conf.dir_sublv);
                    var f = false;
                    if (m && this.conf.top_mode && this.conf.mode == "web" && !this.conf.context) {
                        var r = this.idPull[h];
                        if (r._mouseOver == true) {
                            var g = this.conf.top_tmtime - (new Date().getTime() - r._dynLoadTM);
                            if (g > 1) {
                                var l = h;
                                var j = this;
                                r._menuOpenTM = window.setTimeout(function () {
                                    j._showPolygon(l, c);
                                    j = l = null
                                }, g);
                                f = true
                            }
                        }
                    }
                    if (!f) {
                        this._showPolygon(h, c)
                    }
                }
                this.itemPull[h].loaded = "yes";
                if (this.conf.dload_icon == true) {
                    this._updateLoaderIcon(h, false)
                }
            }
        } else {
            this._init()
        }
    }
};
dhtmlXMenuObject.prototype._xmlToJson = function (g, f) {
    var j = [];
    if (f == null) {
        var k = g.getElementsByTagName(this.conf.tags.root);
        if (k == null || (k != null && k.length == 0)) {
            return {
                items: []
            }
        }
        k = k[0]
    } else {
        k = g
    }
    if (k.getAttribute("parentId") != null) {
        f = this.idPrefix + k.getAttribute("parentId")
    }
    for (var c = 0; c < k.childNodes.length; c++) {
        if (typeof (k.childNodes[c].tagName) != "undefined" && String(k.childNodes[c].tagName).toLowerCase() == this.conf.tags.item) {
            var a = k.childNodes[c];
            var n = {
                id: this.idPrefix + (a.getAttribute("id") || this._genStr(24)),
                title: a.getAttribute("text") || "",
                imgen: a.getAttribute("img") || "",
                imgdis: a.getAttribute("imgdis") || "",
                tip: "",
                hotkey: "",
                type: a.getAttribute("type") || "item"
            };
            if (a.getAttribute("cssNormal") != null) {
                n.cssNormal = a.getAttribute("cssNormal")
            }
            if (n.type == "checkbox") {
                n.checked = a.getAttribute("checked")
            }
            if (n.type == "radio") {
                n.checked = a.getAttribute("checked");
                n.group = a.getAttribute("group")
            }
            n.state = "enabled";
            if (a.getAttribute("enabled") != null && window.dhx4.s2b(a.getAttribute("enabled")) == false) {
                n.state = "disabled"
            } else {
                if (a.getAttribute("disabled") != null && window.dhx4.s2b(a.getAttribute("disabled")) == true) {
                    n.state = "disabled"
                }
            }
            n.parent = (f != null ? f : this.idPrefix + this.topId);
            if (this.conf.dload) {
                n.complex = (a.getAttribute("complex") != null);
                if (n.complex) {
                    n.loaded = "no"
                }
            } else {
                var e = this._xmlToJson(a, n.id);
                n.items = e.items;
                n.complex = (n.items.length > 0)
            }
            for (var l = 0; l < a.childNodes.length; l++) {
                if (typeof (a.childNodes[l].tagName) != "undefined") {
                    var m = String(a.childNodes[l].tagName || "").toLowerCase();
                    if (m == this.conf.tags.userdata) {
                        var h = a.childNodes[l];
                        if (h.getAttribute("name") != null) {
                            this.userData[n.id + "_" + h.getAttribute("name")] = (h.firstChild != null && h.firstChild.nodeValue != null ? h.firstChild.nodeValue : "")
                        }
                    }
                    if (m == this.conf.tags.text_ext) {
                        n.title = a.childNodes[l].firstChild.nodeValue
                    }
                    if (m == this.conf.tags.tooltip) {
                        n.tip = a.childNodes[l].firstChild.nodeValue
                    }
                    if (m == this.conf.tags.hotkey) {
                        n.hotkey = a.childNodes[l].firstChild.nodeValue
                    }
                    if (m == this.conf.tags.href && n.type == "item") {
                        n.href_link = a.childNodes[l].firstChild.nodeValue;
                        if (a.childNodes[l].getAttribute("target") != null) {
                            n.href_target = a.childNodes[l].getAttribute("target")
                        }
                    }
                }
            }
            j.push(n)
        }
    }
    var a = {
        parentId: f,
        items: j
    };
    return a
};
dhtmlXMenuObject.prototype.enableDynamicLoading = function (a, c) {
    this.conf.dload = true;
    this.conf.dload_url = a;
    this.conf.dload_sign = (String(this.conf.dload_url).search(/\?/) == -1 ? "?" : "&");
    this.conf.dload_icon = c;
    this._init()
};
dhtmlXMenuObject.prototype._updateLoaderIcon = function (e, d) {
    if (this.idPull[e] == null) {
        return
    }
    if (String(this.idPull[e].className).search("TopLevel_Item") >= 0) {
        return
    }
    var c = (this.conf.rtl ? 0 : 2);
    if (!this.idPull[e].childNodes[c]) {
        return
    }
    if (!this.idPull[e].childNodes[c].childNodes[0]) {
        return
    }
    var a = this.idPull[e].childNodes[c].childNodes[0];
    if (String(a.className).search("complex_arrow") === 0) {
        a.className = "complex_arrow" + (d ? "_loading" : "")
    }
};
dhtmlXMenuObject.prototype.addNewSibling = function (e, f, a, c, d, j) {
    var h = this.idPrefix + (f != null ? f : this._genStr(24));
    var g = this.idPrefix + (e != null ? this.getParentId(e) : this.topId);
    this._addItemIntoGlobalStrorage(h, g, a, "item", c, d, j);
    if ((g == this.idPrefix + this.topId) && (!this.conf.context)) {
        this._renderToplevelItem(h, this.getItemPosition(e))
    } else {
        this._renderSublevelItem(h, this.getItemPosition(e))
    }
};
dhtmlXMenuObject.prototype.addNewChild = function (h, g, e, a, c, d, f) {
    if (h == null) {
        if (this.conf.context) {
            h = this.topId
        } else {
            this.addNewSibling(h, e, a, c, d, f);
            if (g != null) {
                this.setItemPosition(e, g)
            }
            return
        }
    }
    e = this.idPrefix + (e != null ? e : this._genStr(24));
    if (this.setHotKey) {
        this.setHotKey(h, "")
    }
    h = this.idPrefix + h;
    this._addItemIntoGlobalStrorage(e, h, a, "item", c, d, f);
    if (this.idPull["polygon_" + h] == null) {
        this._renderSublevelPolygon(h, h)
    }
    this._renderSublevelItem(e, g - 1);
    this._redefineComplexState(h)
};
dhtmlXMenuObject.prototype.removeItem = function (e, g, f) {
    if (!g) {
        e = this.idPrefix + e
    }
    var h = null;
    if (e != this.idPrefix + this.topId) {
        if (this.itemPull[e] == null) {
            return
        }
        if (this.idPull["polygon_" + e] && this.idPull["polygon_" + e]._tmShow) {
            window.clearTimeout(this.idPull["polygon_" + e]._tmShow)
        }
        var n = this.itemPull[e]["type"];
        if (n == "separator") {
            var m = this.idPull["separator_" + e];
            if (this.itemPull[e]["parent"] == this.idPrefix + this.topId) {
                m.onclick = null;
                m.onselectstart = null;
                m.id = null;
                m.parentNode.removeChild(m)
            } else {
                m.childNodes[0].childNodes[0].onclick = null;
                m.childNodes[0].childNodes[0].onselectstart = null;
                m.childNodes[0].childNodes[0].id = null;
                m.childNodes[0].removeChild(m.childNodes[0].childNodes[0]);
                m.removeChild(m.childNodes[0]);
                m.parentNode.removeChild(m)
            }
            this.idPull["separator_" + e] = null;
            this.itemPull[e] = null;
            delete this.idPull["separator_" + e];
            delete this.itemPull[e];
            m = null
        } else {
            h = this.itemPull[e]["parent"];
            var m = this.idPull[e];
            m.onclick = null;
            m.oncontextmenu = null;
            m.onmouseover = null;
            m.onmouseout = null;
            m.onselectstart = null;
            m.id = null;
            while (m.childNodes.length > 0) {
                m.removeChild(m.childNodes[0])
            }
            m.parentNode.removeChild(m);
            this.idPull[e] = null;
            this.itemPull[e] = null;
            delete this.idPull[e];
            delete this.itemPull[e];
            m = null
        }
        n = null
    }
    for (var k in this.itemPull) {
        if (this.itemPull[k]["parent"] == e) {
            this.removeItem(k, true, true)
        }
    }
    var l = new Array(e);
    if (h != null && !f) {
        if (this.idPull["polygon_" + h] != null) {
            if (this.idPull["polygon_" + h].tbd.childNodes.length == 0) {
                l.push(h);
                this._updateItemComplexState(h, false, false)
            }
        }
    }
    for (var c = 0; c < l.length; c++) {
        if (this.idPull["polygon_" + l[c]]) {
            var d = this.idPull["polygon_" + l[c]];
            d.onclick = null;
            d.oncontextmenu = null;
            d.tbl.removeChild(d.tbd);
            d.tbd = null;
            d.childNodes[1].removeChild(d.tbl);
            d.tbl = null;
            d.id = null;
            d.parentNode.removeChild(d);
            d = null;
            if (window.dhx4.isIE6) {
                var j = "polygon_" + l[c] + "_ie6cover";
                if (this.idPull[j] != null) {
                    document.body.removeChild(this.idPull[j]);
                    delete this.idPull[j]
                }
            }
            if (this.idPull["arrowup_" + e] != null && this._removeArrow) {
                this._removeArrow("arrowup_" + e)
            }
            if (this.idPull["arrowdown_" + e] != null && this._removeArrow) {
                this._removeArrow("arrowdown_" + e)
            }
            this.idPull["polygon_" + l[c]] = null;
            delete this.idPull["polygon_" + l[c]]
        }
    }
    l = null;
    if (this.conf.skin == "dhx_terrace" && arguments.length == 1) {
        this._improveTerraceSkin()
    }
};
dhtmlXMenuObject.prototype._addItemIntoGlobalStrorage = function (j, a, d, h, e, c, g) {
    var f = {
        id: j,
        title: d,
        imgen: (c != null ? c : ""),
        imgdis: (g != null ? g : ""),
        type: h,
        state: (e == true ? "disabled" : "enabled"),
        parent: a,
        complex: false,
        hotkey: "",
        tip: ""
    };
    this.itemPull[f.id] = f
};
dhtmlXMenuObject.prototype.renderAsContextMenu = function () {
    this.conf.context = true;
    if (this.base._autoSkinUpdate == true) {
        this.base.className = this.base.className.replace("dhtmlxMenu_" + this.conf.skin + "_Middle", "");
        this.base._autoSkinUpdate = false
    }
    if (this.conf.ctx_baseid != null) {
        this.addContextZone(this.conf.ctx_baseid)
    }
};
dhtmlXMenuObject.prototype.addContextZone = function (c) {
    if (c == document.body) {
        c = "document.body." + this.idPrefix;
        var e = document.body
    } else {
        if (typeof (c) == "string") {
            var e = document.getElementById(c)
        } else {
            var e = c
        }
    }
    var g = false;
    for (var d in this.conf.ctx_zones) {
        g = g || (d == c) || (this.conf.ctx_zones[d] == e)
    }
    if (g == true) {
        return false
    }
    this.conf.ctx_zones[c] = e;
    var f = this;
    if (window.dhx4.isOpera) {
        this.operaContext = function (a) {
            f._doOnContextMenuOpera(a, f)
        };
        e.addEventListener("mouseup", this.operaContext, false)
    } else {
        if (e.oncontextmenu != null && !e._oldContextMenuHandler) {
            e._oldContextMenuHandler = e.oncontextmenu
        }
        e.oncontextmenu = function (h) {
            for (var a in dhtmlXMenuObject.prototype.liveInst) {
                if (a != f.conf.live_id) {
                    if (dhtmlXMenuObject.prototype.liveInst[a].context) {
                        dhtmlXMenuObject.prototype.liveInst[a]._hideContextMenu()
                    }
                }
            }
            h = h || event;
            h.cancelBubble = true;
            if (h.preventDefault) {
                h.preventDefault()
            } else {
                h.returnValue = false
            }
            f._doOnContextBeforeCall(h, this);
            return false
        }
    }
};
dhtmlXMenuObject.prototype._doOnContextMenuOpera = function (d, a) {
    for (var c in dhtmlXMenuObject.prototype.liveInst) {
        if (c != a.conf.live_id) {
            if (dhtmlXMenuObject.prototype.liveInst[c].context) {
                dhtmlXMenuObject.prototype.liveInst[c]._hideContextMenu()
            }
        }
    }
    d.cancelBubble = true;
    if (d.preventDefault) {
        d.preventDefault()
    } else {
        d.returnValue = false
    }
    if (d.button == 0 && d.ctrlKey == true) {
        a._doOnContextBeforeCall(d, this)
    }
    return false
};
dhtmlXMenuObject.prototype.removeContextZone = function (a) {
    if (!this.isContextZone(a)) {
        return false
    }
    if (a == document.body) {
        a = "document.body." + this.idPrefix
    }
    var c = this.conf.ctx_zones[a];
    if (window.dhx4.isOpera) {
        c.removeEventListener("mouseup", this.operaContext, false)
    } else {
        c.oncontextmenu = (c._oldContextMenuHandler != null ? c._oldContextMenuHandler : null);
        c._oldContextMenuHandler = null
    }
    try {
        this.conf.ctx_zones[a] = null;
        delete this.conf.ctx_zones[a]
    } catch (d) {}
    return true
};
dhtmlXMenuObject.prototype.isContextZone = function (a) {
    if (a == document.body && this.conf.ctx_zones["document.body." + this.idPrefix] != null) {
        return true
    }
    var c = false;
    if (this.conf.ctx_zones[a] != null) {
        if (this.conf.ctx_zones[a] == document.getElementById(a)) {
            c = true
        }
    }
    return c
};
dhtmlXMenuObject.prototype._isContextMenuVisible = function () {
    if (this.idPull["polygon_" + this.idPrefix + this.topId] == null) {
        return false
    }
    return (this.idPull["polygon_" + this.idPrefix + this.topId].style.display == "")
};
dhtmlXMenuObject.prototype._showContextMenu = function (c, d, a) {
    this._clearAndHide();
    if (this.idPull["polygon_" + this.idPrefix + this.topId] == null) {
        return false
    }
    window.clearTimeout(this.conf.tm_handler);
    this.idPull[this.idPrefix + this.topId] = new Array(c, d);
    this._showPolygon(this.idPrefix + this.topId, "bottom");
    this.callEvent("onContextMenu", [a])
};
dhtmlXMenuObject.prototype._hideContextMenu = function () {
    if (this.idPull["polygon_" + this.idPrefix + this.topId] == null) {
        return false
    }
    this._clearAndHide();
    this._hidePolygon(this.idPrefix + this.topId)
};
dhtmlXMenuObject.prototype._doOnContextBeforeCall = function (g, j) {
    this.conf.ctx_zoneid = j.id;
    this._clearAndHide();
    this._hideContextMenu();
    if (window.dhx4.isChrome == true || window.dhx4.isEdge == true || window.dhx4.isOpera == true || window.dhx4.isIE11 == true) {
        var h = window.dhx4.absLeft(g.target) + g.offsetX;
        var f = window.dhx4.absTop(g.target) + g.offsetY
    } else {
        if (window.dhx4.isIE6 == true || window.dhx4.isIE7 == true || window.dhx4.isIE == true) {
            var h = window.dhx4.absLeft(g.srcElement) + g.x || 0;
            var f = window.dhx4.absTop(g.srcElement) + g.y || 0
        } else {
            var d = (g.srcElement || g.target);
            var c = (window.dhx4.isIE || window.dhx4.isKHTML ? g.offsetX : g.layerX);
            var a = (window.dhx4.isIE || window.dhx4.isKHTML ? g.offsetY : g.layerY);
            var h = window.dhx4.absLeft(d) + c;
            var f = window.dhx4.absTop(d) + a
        }
    }
    if (this.checkEvent("onBeforeContextMenu")) {
        if (this.callEvent("onBeforeContextMenu", [j.id, g])) {
            if (this.conf.ctx_autoshow) {
                this._showContextMenu(h, f, j.id);
                this.callEvent("onAfterContextMenu", [j.id, g])
            }
        }
    } else {
        if (this.conf.ctx_autoshow) {
            this._showContextMenu(h, f, j.id);
            this.callEvent("onAfterContextMenu", [j.id])
        }
    }
};
dhtmlXMenuObject.prototype.showContextMenu = function (a, c) {
    this._showContextMenu(a, c, false)
};
dhtmlXMenuObject.prototype.hideContextMenu = function () {
    this._hideContextMenu()
};
dhtmlXMenuObject.prototype.setAutoShowMode = function (a) {
    this.conf.ctx_autoshow = (a == true ? true : false)
};
dhtmlXMenuObject.prototype.setAutoHideMode = function (a) {
    this.conf.ctx_autohide = (a == true ? true : false)
};
dhtmlXMenuObject.prototype.setContextMenuHideAllMode = function (a) {
    this.conf.ctx_hideall = (a == true ? true : false)
};
dhtmlXMenuObject.prototype.getContextMenuHideAllMode = function () {
    return this.conf.ctx_hideall
};
dhtmlXMenuObject.prototype._improveTerraceSkin = function () {
    for (var d in this.itemPull) {
        if (this.itemPull[d].parent == this.idPrefix + this.topId && this.idPull[d] != null) {
            var f = false;
            var e = false;
            if (this.idPull[d].parentNode.firstChild == this.idPull[d]) {
                f = true
            }
            if (this.idPull[d].parentNode.lastChild == this.idPull[d]) {
                e = true
            }
            for (var c in this.itemPull) {
                if (this.itemPull[c].type == "separator" && this.itemPull[c].parent == this.idPrefix + this.topId) {
                    if (this.idPull[d].nextSibling == this.idPull["separator_" + c]) {
                        e = true
                    }
                    if (this.idPull[d].previousSibling == this.idPull["separator_" + c]) {
                        f = true
                    }
                }
            }
            this.idPull[d].style.borderLeftWidth = (f ? "1px" : "0px");
            this.idPull[d].style.borderTopLeftRadius = this.idPull[d].style.borderBottomLeftRadius = (f ? "3px" : "0px");
            this.idPull[d].style.borderTopRightRadius = this.idPull[d].style.borderBottomRightRadius = (e ? "3px" : "0px");
            this.idPull[d]._bl = f;
            this.idPull[d]._br = e
        }
    }
};
dhtmlXMenuObject.prototype._improveTerraceButton = function (c, a) {
    if (a) {
        this.idPull[c].style.borderBottomLeftRadius = (this.idPull[c]._bl ? "3px" : "0px");
        this.idPull[c].style.borderBottomRightRadius = (this.idPull[c]._br ? "3px" : "0px")
    } else {
        this.idPull[c].style.borderBottomLeftRadius = "0px";
        this.idPull[c].style.borderBottomRightRadius = "0px"
    }
};
if (typeof (window.dhtmlXCellObject) != "undefined") {
    dhtmlXCellObject.prototype._createNode_menu = function (f, c, e, a, d) {
        if (typeof (d) != "undefined") {
            f = d
        } else {
            f = document.createElement("DIV");
            f.className = "dhx_cell_menu_" + (this.conf.borders ? "def" : "no_borders");
            f.appendChild(document.createElement("DIV"))
        }
        this.cell.insertBefore(f, this.cell.childNodes[this.conf.idx.toolbar || this.conf.idx.cont]);
        this.conf.ofs_nodes.t.menu = true;
        this._updateIdx();
        return f
    };
    dhtmlXCellObject.prototype.attachMenu = function (a) {
        if (this.dataNodes.menu) {
            return
        }
        this.callEvent("_onBeforeContentAttach", ["menu"]);
        if (typeof (a) == "undefined") {
            a = {}
        }
        if (typeof (a.skin) == "undefined") {
            a.skin = this.conf.skin
        }
        a.parent = this._attachObject("menu").firstChild;
        this.dataNodes.menu = new dhtmlXMenuObject(a);
        this._adjustCont(this._idd);
        a.parent = null;
        a = null;
        this.callEvent("_onContentAttach", []);
        return this.dataNodes.menu
    };
    dhtmlXCellObject.prototype.detachMenu = function () {
        if (this.dataNodes.menu == null) {
            return
        }
        if (typeof (this.dataNodes.menu.unload) == "function") {
            this.dataNodes.menu.unload()
        }
        this.dataNodes.menu = null;
        delete this.dataNodes.menu;
        this._detachObject("menu")
    };
    dhtmlXCellObject.prototype.showMenu = function () {
        this._mtbShowHide("menu", "")
    };
    dhtmlXCellObject.prototype.hideMenu = function () {
        this._mtbShowHide("menu", "none")
    };
    dhtmlXCellObject.prototype.getAttachedMenu = function () {
        return this.dataNodes.menu
    }
}
dhtmlXMenuObject.prototype.setItemEnabled = function (a) {
    this._changeItemState(a, "enabled", this._getItemLevelType(a))
};
dhtmlXMenuObject.prototype.setItemDisabled = function (a) {
    this._changeItemState(a, "disabled", this._getItemLevelType(a))
};
dhtmlXMenuObject.prototype.isItemEnabled = function (a) {
    return (this.itemPull[this.idPrefix + a] != null ? (this.itemPull[this.idPrefix + a]["state"] == "enabled") : false)
};
dhtmlXMenuObject.prototype._changeItemState = function (f, e, c) {
    var d = false;
    var a = this.idPrefix + f;
    if ((this.itemPull[a] != null) && (this.idPull[a] != null)) {
        if (this.itemPull[a]["state"] != e) {
            this.itemPull[a]["state"] = e;
            if (this.itemPull[a]["parent"] == this.idPrefix + this.topId && !this.conf.context) {
                this.idPull[a].className = "dhtmlxMenu_" + this.conf.skin + "_TopLevel_Item_" + (this.itemPull[a]["state"] == "enabled" ? "Normal" : "Disabled")
            } else {
                this.idPull[a].className = "sub_item" + (this.itemPull[a]["state"] == "enabled" ? "" : "_dis")
            }
            this._updateItemComplexState(this.idPrefix + f, this.itemPull[this.idPrefix + f]["complex"], false);
            this._updateItemImage(f, c);
            if ((this.idPrefix + this.conf.last_click == a) && (c != "TopLevel")) {
                this._redistribSubLevelSelection(a, this.itemPull[a]["parent"])
            }
            if (c == "TopLevel" && !this.conf.context) {}
        }
    }
    return d
};
dhtmlXMenuObject.prototype.getItemText = function (a) {
    return (this.itemPull[this.idPrefix + a] != null ? this.itemPull[this.idPrefix + a]["title"] : "")
};
dhtmlXMenuObject.prototype.setItemText = function (g, f) {
    g = this.idPrefix + g;
    if ((this.itemPull[g] != null) && (this.idPull[g] != null)) {
        this._clearAndHide();
        this.itemPull[g]["title"] = f;
        if (this.itemPull[g]["parent"] == this.idPrefix + this.topId && !this.conf.context) {
            var d = null;
            for (var a = 0; a < this.idPull[g].childNodes.length; a++) {
                try {
                    if (this.idPull[g].childNodes[a].className == "top_level_text") {
                        d = this.idPull[g].childNodes[a]
                    }
                } catch (c) {}
            }
            if (String(this.itemPull[g]["title"]).length == "" || this.itemPull[g]["title"] == null) {
                if (d != null) {
                    d.parentNode.removeChild(d)
                }
            } else {
                if (!d) {
                    d = document.createElement("DIV");
                    d.className = "top_level_text";
                    if (this.conf.rtl && this.idPull[g].childNodes.length > 0) {
                        this.idPull[g].insertBefore(d, this.idPull[g].childNodes[0])
                    } else {
                        this.idPull[g].appendChild(d)
                    }
                }
                d.innerHTML = this.itemPull[g]["title"]
            }
        } else {
            var d = null;
            for (var a = 0; a < this.idPull[g].childNodes[1].childNodes.length; a++) {
                if (String(this.idPull[g].childNodes[1].childNodes[a].className || "") == "sub_item_text") {
                    d = this.idPull[g].childNodes[1].childNodes[a]
                }
            }
            if (String(this.itemPull[g]["title"]).length == "" || this.itemPull[g]["title"] == null) {
                if (d) {
                    d.parentNode.removeChild(d);
                    d = null;
                    this.idPull[g].childNodes[1].innerHTML = "&nbsp;"
                }
            } else {
                if (!d) {
                    d = document.createElement("DIV");
                    d.className = "sub_item_text";
                    this.idPull[g].childNodes[1].innerHTML = "";
                    this.idPull[g].childNodes[1].appendChild(d)
                }
                d.innerHTML = this.itemPull[g]["title"]
            }
        }
    }
};
dhtmlXMenuObject.prototype.loadFromHTML = function (d, g, e) {
    var c = this.conf.tags.item;
    this.conf.tags.item = "div";
    var f = (typeof (d) == "string" ? document.getElementById(d) : d);
    var a = this._xmlToJson(f, this.idPrefix + this.topId);
    this._initObj(a);
    this.conf.tags.item = c;
    if (g) {
        f.parentNode.removeChild(f)
    }
    f = objOd = null;
    if (onload != null) {
        if (typeof (e) == "function") {
            e()
        } else {
            if (typeof (window[e]) == "function") {
                window[e]()
            }
        }
    }
};
dhtmlXMenuObject.prototype.hideItem = function (a) {
    this._changeItemVisible(a, false)
};
dhtmlXMenuObject.prototype.showItem = function (a) {
    this._changeItemVisible(a, true)
};
dhtmlXMenuObject.prototype.isItemHidden = function (c) {
    var a = null;
    if (this.idPull[this.idPrefix + c] != null) {
        a = (this.idPull[this.idPrefix + c].style.display == "none")
    }
    return a
};
dhtmlXMenuObject.prototype._changeItemVisible = function (d, c) {
    var a = this.idPrefix + d;
    if (this.itemPull[a] == null) {
        return
    }
    if (this.itemPull[a]["type"] == "separator") {
        a = "separator_" + a
    }
    if (this.idPull[a] == null) {
        return
    }
    this.idPull[a].style.display = (c ? "" : "none");
    this._redefineComplexState(this.itemPull[this.idPrefix + d]["parent"])
};
dhtmlXMenuObject.prototype.setUserData = function (d, a, c) {
    this.userData[this.idPrefix + d + "_" + a] = c
};
dhtmlXMenuObject.prototype.getUserData = function (c, a) {
    return (this.userData[this.idPrefix + c + "_" + a] != null ? this.userData[this.idPrefix + c + "_" + a] : null)
};
dhtmlXMenuObject.prototype.setOpenMode = function (a) {
    this.conf.mode = (a == "win" ? "win" : "web")
};
dhtmlXMenuObject.prototype.setWebModeTimeout = function (a) {
    this.conf.tm_sec = (!isNaN(a) ? a : 400)
};
dhtmlXMenuObject.prototype.getItemImage = function (c) {
    var a = new Array(null, null);
    c = this.idPrefix + c;
    if (this.itemPull[c]["type"] == "item") {
        a[0] = this.itemPull[c]["imgen"];
        a[1] = this.itemPull[c]["imgdis"]
    }
    return a
};
dhtmlXMenuObject.prototype.setItemImage = function (d, a, c) {
    if (this.itemPull[this.idPrefix + d]["type"] != "item") {
        return
    }
    this.itemPull[this.idPrefix + d]["imgen"] = a;
    this.itemPull[this.idPrefix + d]["imgdis"] = c;
    this._updateItemImage(d, this._getItemLevelType(d))
};
dhtmlXMenuObject.prototype.clearItemImage = function (a) {
    this.setItemImage(a, "", "")
};
dhtmlXMenuObject.prototype.setVisibleArea = function (c, a, e, d) {
    this.conf.v_enabled = true;
    this.conf.v.x1 = c;
    this.conf.v.x2 = a;
    this.conf.v.y1 = e;
    this.conf.v.y2 = d
};
dhtmlXMenuObject.prototype.setTooltip = function (c, a) {
    c = this.idPrefix + c;
    if (!(this.itemPull[c] != null && this.idPull[c] != null)) {
        return
    }
    this.idPull[c].title = (a.length > 0 ? a : null);
    this.itemPull[c]["tip"] = a
};
dhtmlXMenuObject.prototype.getTooltip = function (a) {
    if (this.itemPull[this.idPrefix + a] == null) {
        return null
    }
    return this.itemPull[this.idPrefix + a]["tip"]
};
dhtmlXMenuObject.prototype.setTopText = function (a) {
    if (this.conf.context) {
        return
    }
    if (this._topText == null) {
        this._topText = document.createElement("DIV");
        this._topText.className = "dhtmlxMenu_TopLevel_Text_" + (this.conf.rtl ? "left" : (this.conf.align == "left" ? "right" : "left"));
        this.base.appendChild(this._topText)
    }
    this._topText.innerHTML = a
};
dhtmlXMenuObject.prototype.setAlign = function (a) {
    if (this.conf.align == a) {
        return
    }
    if (a == "left" || a == "right") {
        this.conf.align = a;
        if (this.cont) {
            this.cont.className = (this.conf.align == "right" ? "align_right" : "align_left")
        }
        if (this._topText != null) {
            this._topText.className = "dhtmlxMenu_TopLevel_Text_" + (this.conf.align == "left" ? "right" : "left")
        }
    }
};
dhtmlXMenuObject.prototype.setHref = function (d, a, c) {
    if (this.itemPull[this.idPrefix + d] == null) {
        return
    }
    this.itemPull[this.idPrefix + d]["href_link"] = a;
    if (c != null) {
        this.itemPull[this.idPrefix + d]["href_target"] = c
    }
};
dhtmlXMenuObject.prototype.clearHref = function (a) {
    if (this.itemPull[this.idPrefix + a] == null) {
        return
    }
    delete this.itemPull[this.idPrefix + a]["href_link"];
    delete this.itemPull[this.idPrefix + a]["href_target"]
};
dhtmlXMenuObject.prototype.getCircuit = function (c) {
    var a = new Array(c);
    while (this.getParentId(c) != this.topId) {
        c = this.getParentId(c);
        a[a.length] = c
    }
    return a.reverse()
};
dhtmlXMenuObject.prototype._getCheckboxState = function (a) {
    if (this.itemPull[this.idPrefix + a] == null) {
        return null
    }
    return this.itemPull[this.idPrefix + a]["checked"]
};
dhtmlXMenuObject.prototype._setCheckboxState = function (c, a) {
    if (this.itemPull[this.idPrefix + c] == null) {
        return
    }
    this.itemPull[this.idPrefix + c]["checked"] = a
};
dhtmlXMenuObject.prototype._updateCheckboxImage = function (c) {
    if (this.idPull[this.idPrefix + c] == null) {
        return
    }
    this.itemPull[this.idPrefix + c]["imgen"] = "chbx_" + (this._getCheckboxState(c) ? "1" : "0");
    this.itemPull[this.idPrefix + c]["imgdis"] = this.itemPull[this.idPrefix + c]["imgen"];
    try {
        this.idPull[this.idPrefix + c].childNodes[(this.conf.rtl ? 2 : 0)].childNodes[0].className = "sub_icon " + this.itemPull[this.idPrefix + c]["imgen"]
    } catch (a) {}
};
dhtmlXMenuObject.prototype._checkboxOnClickHandler = function (e, a, c) {
    if (a.charAt(1) == "d") {
        return
    }
    if (this.itemPull[this.idPrefix + e] == null) {
        return
    }
    var d = this._getCheckboxState(e);
    if (this.checkEvent("onCheckboxClick")) {
        if (this.callEvent("onCheckboxClick", [e, d, this.conf.ctx_zoneid, c])) {
            this.setCheckboxState(e, !d)
        }
    } else {
        this.setCheckboxState(e, !d)
    }
    if (this.checkEvent("onClick")) {
        this.callEvent("onClick", [e])
    }
};
dhtmlXMenuObject.prototype.setCheckboxState = function (c, a) {
    this._setCheckboxState(c, a);
    this._updateCheckboxImage(c)
};
dhtmlXMenuObject.prototype.getCheckboxState = function (a) {
    return this._getCheckboxState(a)
};
dhtmlXMenuObject.prototype.addCheckbox = function (j, e, k, l, m, a, f) {
    if (this.conf.context && e == this.topId) {} else {
        if (this.itemPull[this.idPrefix + e] == null) {
            return
        }
        if (j == "child" && this.itemPull[this.idPrefix + e]["type"] != "item") {
            return
        }
    }
    var g = "chbx_" + (a ? "1" : "0");
    var d = g;
    if (j == "sibling") {
        var c = this.idPrefix + (l != null ? l : this._genStr(24));
        var h = this.idPrefix + this.getParentId(e);
        this._addItemIntoGlobalStrorage(c, h, m, "checkbox", f, g, d);
        this.itemPull[c]["checked"] = a;
        this._renderSublevelItem(c, this.getItemPosition(e))
    } else {
        var c = this.idPrefix + (l != null ? l : this._genStr(24));
        var h = this.idPrefix + e;
        this._addItemIntoGlobalStrorage(c, h, m, "checkbox", f, g, d);
        this.itemPull[c]["checked"] = a;
        if (this.idPull["polygon_" + h] == null) {
            this._renderSublevelPolygon(h, h)
        }
        this._renderSublevelItem(c, k - 1);
        this._redefineComplexState(h)
    }
};
dhtmlXMenuObject.prototype.setHotKey = function (h, a) {
    h = this.idPrefix + h;
    if (!(this.itemPull[h] != null && this.idPull[h] != null)) {
        return
    }
    if (this.itemPull[h]["parent"] == this.idPrefix + this.topId && !this.conf.context) {
        return
    }
    if (this.itemPull[h]["complex"]) {
        return
    }
    var c = this.itemPull[h]["type"];
    if (!(c == "item" || c == "checkbox" || c == "radio")) {
        return
    }
    var g = null;
    try {
        if (this.idPull[h].childNodes[this.conf.rtl ? 0 : 2].childNodes[0].className == "sub_item_hk") {
            g = this.idPull[h].childNodes[this.conf.rtl ? 0 : 2].childNodes[0]
        }
    } catch (f) {}
    if (a.length == 0) {
        this.itemPull[h]["hotkey_backup"] = this.itemPull[h]["hotkey"];
        this.itemPull[h]["hotkey"] = "";
        if (g != null) {
            g.parentNode.removeChild(g)
        }
    } else {
        this.itemPull[h]["hotkey"] = a;
        this.itemPull[h]["hotkey_backup"] = null;
        if (g == null) {
            g = document.createElement("DIV");
            g.className = "sub_item_hk";
            var d = this.idPull[h].childNodes[this.conf.rtl ? 0 : 2];
            while (d.childNodes.length > 0) {
                d.removeChild(d.childNodes[0])
            }
            d.appendChild(g)
        }
        g.innerHTML = a
    }
};
dhtmlXMenuObject.prototype.getHotKey = function (a) {
    if (this.itemPull[this.idPrefix + a] == null) {
        return null
    }
    return this.itemPull[this.idPrefix + a]["hotkey"]
};
dhtmlXMenuObject.prototype._clearAllSelectedSubItemsInPolygon = function (a) {
    var d = this._getSubItemToDeselectByPolygon(a);
    for (var c = 0; c < this.conf.opened_poly.length; c++) {
        if (this.conf.opened_poly[c] != a) {
            this._hidePolygon(this.conf.opened_poly[c])
        }
    }
    for (var c = 0; c < d.length; c++) {
        if (this.idPull[d[c]] != null && this.itemPull[d[c]]["state"] == "enabled") {
            this.idPull[d[c]].className = "dhtmlxMenu_" + this.conf.skin + "_SubLevelArea_Item_Normal"
        }
    }
};
dhtmlXMenuObject.prototype._checkArrowsState = function (e) {
    var c = this.idPull["polygon_" + e].childNodes[1];
    var d = this.idPull["arrowup_" + e];
    var a = this.idPull["arrowdown_" + e];
    if (c.scrollTop == 0) {
        d.className = "dhtmlxMenu_" + this.conf.skin + "_SubLevelArea_ArrowUp_Disabled"
    } else {
        d.className = "dhtmlxMenu_" + this.conf.skin + "_SubLevelArea_ArrowUp" + (d.over ? "_Over" : "")
    }
    if (c.scrollTop + c.offsetHeight < c.scrollHeight) {
        a.className = "dhtmlxMenu_" + this.conf.skin + "_SubLevelArea_ArrowDown" + (a.over ? "_Over" : "")
    } else {
        a.className = "dhtmlxMenu_" + this.conf.skin + "_SubLevelArea_ArrowDown_Disabled"
    }
    c = d = a = null
};
dhtmlXMenuObject.prototype._addUpArrow = function (e) {
    var c = this;
    var d = document.createElement("DIV");
    d.pId = this.idPrefix + e;
    d.id = "arrowup_" + this.idPrefix + e;
    d.className = "dhtmlxMenu_" + this.conf.skin + "_SubLevelArea_ArrowUp";
    d.over = false;
    d.onselectstart = function (f) {
        f = f || event;
        if (f.preventDefault) {
            f.preventDefault()
        } else {
            f.returnValue = false
        }
        return false
    };
    d.oncontextmenu = function (f) {
        f = f || event;
        if (f.preventDefault) {
            f.preventDefault()
        } else {
            f.returnValue = false
        }
        return false
    };
    d.onmouseover = function () {
        if (c.conf.mode == "web") {
            window.clearTimeout(c.conf.tm_handler)
        }
        c._clearAllSelectedSubItemsInPolygon(this.pId);
        if (this.className == "dhtmlxMenu_" + c.conf.skin + "_SubLevelArea_ArrowUp_Disabled") {
            return
        }
        this.className = "dhtmlxMenu_" + c.conf.skin + "_SubLevelArea_ArrowUp_Over";
        this.over = true;
        c._canScrollUp = true;
        c._doScrollUp(this.pId, true)
    };
    d.onmouseout = function () {
        if (c.conf.mode == "web") {
            window.clearTimeout(c.conf.tm_handler);
            c.conf.tm_handler = window.setTimeout(function () {
                c._clearAndHide()
            }, c.conf.tm_sec, "JavaScript")
        }
        this.over = false;
        c._canScrollUp = false;
        if (this.className == "dhtmlxMenu_" + c.conf.skin + "_SubLevelArea_ArrowUp_Disabled") {
            return
        }
        this.className = "dhtmlxMenu_" + c.conf.skin + "_SubLevelArea_ArrowUp";
        window.clearTimeout(c.conf.of_utm)
    };
    d.onclick = function (f) {
        f = f || event;
        if (f.preventDefault) {
            f.preventDefault()
        } else {
            f.returnValue = false
        }
        f.cancelBubble = true;
        return false
    };
    var a = this.idPull["polygon_" + this.idPrefix + e];
    a.childNodes[0].appendChild(d);
    this.idPull[d.id] = d;
    a = d = null
};
dhtmlXMenuObject.prototype._addDownArrow = function (e) {
    var c = this;
    var d = document.createElement("DIV");
    d.pId = this.idPrefix + e;
    d.id = "arrowdown_" + this.idPrefix + e;
    d.className = "dhtmlxMenu_" + this.conf.skin + "_SubLevelArea_ArrowDown";
    d.over = false;
    d.onselectstart = function (f) {
        f = f || event;
        if (f.preventDefault) {
            f.preventDefault()
        } else {
            f.returnValue = false
        }
        return false
    };
    d.oncontextmenu = function (f) {
        f = f || event;
        if (f.preventDefault) {
            f.preventDefault()
        } else {
            f.returnValue = false
        }
        return false
    };
    d.onmouseover = function () {
        if (c.conf.mode == "web") {
            window.clearTimeout(c.conf.tm_handler)
        }
        c._clearAllSelectedSubItemsInPolygon(this.pId);
        if (this.className == "dhtmlxMenu_" + c.conf.skin + "_SubLevelArea_ArrowDown_Disabled") {
            return
        }
        this.className = "dhtmlxMenu_" + c.conf.skin + "_SubLevelArea_ArrowDown_Over";
        this.over = true;
        c._canScrollDown = true;
        c._doScrollDown(this.pId, true)
    };
    d.onmouseout = function () {
        if (c.conf.mode == "web") {
            window.clearTimeout(c.conf.tm_handler);
            c.conf.tm_handler = window.setTimeout(function () {
                c._clearAndHide()
            }, c.conf.tm_sec, "JavaScript")
        }
        this.over = false;
        c._canScrollDown = false;
        if (this.className == "dhtmlxMenu_" + c.conf.skin + "_SubLevelArea_ArrowDown_Disabled") {
            return
        }
        this.className = "dhtmlxMenu_" + c.conf.skin + "_SubLevelArea_ArrowDown";
        window.clearTimeout(c.conf.of_dtm)
    };
    d.onclick = function (f) {
        f = f || event;
        if (f.preventDefault) {
            f.preventDefault()
        } else {
            f.returnValue = false
        }
        f.cancelBubble = true;
        return false
    };
    var a = this.idPull["polygon_" + this.idPrefix + e];
    a.childNodes[2].appendChild(d);
    this.idPull[d.id] = d;
    a = d = null
};
dhtmlXMenuObject.prototype._removeUpArrow = function (c) {
    var a = "arrowup_" + this.idPrefix + c;
    this._removeArrow(a)
};
dhtmlXMenuObject.prototype._removeDownArrow = function (c) {
    var a = "arrowdown_" + this.idPrefix + c;
    this._removeArrow(a)
};
dhtmlXMenuObject.prototype._removeArrow = function (a) {
    var c = this.idPull[a];
    c.onselectstart = null;
    c.oncontextmenu = null;
    c.onmouseover = null;
    c.onmouseout = null;
    c.onclick = null;
    if (c.parentNode) {
        c.parentNode.removeChild(c)
    }
    c = null;
    this.idPull[a] = null;
    try {
        delete this.idPull[a]
    } catch (d) {}
};
dhtmlXMenuObject.prototype._isArrowExists = function (a) {
    if (this.idPull["arrowup_" + a] != null && this.idPull["arrowdown_" + a] != null) {
        return true
    }
    return false
};
dhtmlXMenuObject.prototype._doScrollUp = function (g, e) {
    var a = this.idPull["polygon_" + g].childNodes[1];
    if (this._canScrollUp && a.scrollTop > 0) {
        var d = false;
        var f = a.scrollTop - this.conf.of_ustep;
        if (f < 0) {
            d = true;
            f = 0
        }
        a.scrollTop = f;
        if (!d) {
            var c = this;
            this.conf.of_utm = window.setTimeout(function () {
                c._doScrollUp(g, false);
                c = null
            }, this.conf.of_utime)
        } else {
            e = true
        }
    } else {
        this._canScrollUp = false;
        this._checkArrowsState(g)
    }
    if (e) {
        this._checkArrowsState(g)
    }
};
dhtmlXMenuObject.prototype._doScrollDown = function (g, e) {
    var a = this.idPull["polygon_" + g].childNodes[1];
    if (this._canScrollDown && a.scrollTop + a.offsetHeight <= a.scrollHeight) {
        var d = false;
        var f = a.scrollTop + this.conf.of_dstep;
        if (f + a.offsetHeight >= a.scrollHeight) {
            d = true;
            f = a.scrollHeight - a.offsetHeight
        }
        a.scrollTop = f;
        if (!d) {
            var c = this;
            this.conf.of_dtm = window.setTimeout(function () {
                c._doScrollDown(g, false);
                c = null
            }, this.conf.of_dtime)
        } else {
            e = true
        }
    } else {
        this._canScrollDown = false;
        this._checkArrowsState(g)
    }
    if (e) {
        this._checkArrowsState(g)
    }
};
dhtmlXMenuObject.prototype._countPolygonItems = function (g) {
    var e = 0;
    for (var c in this.itemPull) {
        var d = this.itemPull[c]["parent"];
        var f = this.itemPull[c]["type"];
        if (d == this.idPrefix + g && (f == "item" || f == "radio" || f == "checkbox")) {
            e++
        }
    }
    return e
};
dhtmlXMenuObject.prototype.setOverflowHeight = function (e) {
    if (e === "auto") {
        this.conf.overflow_limit = 0;
        this.conf.auto_overflow = true;
        return
    }
    if (this.conf.overflow_limit == 0 && e <= 0) {
        return
    }
    this._clearAndHide();
    if (this.conf.overflow_limit >= 0 && e > 0) {
        this.conf.overflow_limit = e;
        return
    }
    if (this.conf.overflow_limit > 0 && e <= 0) {
        for (var d in this.itemPull) {
            if (this._isArrowExists(d)) {
                var c = String(d).replace(this.idPrefix, "");
                this._removeUpArrow(c);
                this._removeDownArrow(c);
                this.idPull["polygon_" + d].childNodes[1].style.height = ""
            }
        }
        this.conf.overflow_limit = 0;
        return
    }
};
dhtmlXMenuObject.prototype._getRadioImgObj = function (d) {
    try {
        var a = this.idPull[this.idPrefix + d].childNodes[(this.conf.rtl ? 2 : 0)].childNodes[0]
    } catch (c) {
        var a = null
    }
    return a
};
dhtmlXMenuObject.prototype._setRadioState = function (e, d) {
    var c = this._getRadioImgObj(e);
    if (c != null) {
        var a = this.itemPull[this.idPrefix + e];
        a.checked = d;
        a.imgen = "rdbt_" + (a.checked ? "1" : "0");
        a.imgdis = a.imgen;
        c.className = "sub_icon " + a.imgen
    }
};
dhtmlXMenuObject.prototype._radioOnClickHandler = function (e, a, c) {
    if (a.charAt(1) == "d" || this.itemPull[this.idPrefix + e]["group"] == null) {
        return
    }
    var d = this.itemPull[this.idPrefix + e]["group"];
    if (this.checkEvent("onRadioClick")) {
        if (this.callEvent("onRadioClick", [d, this.getRadioChecked(d), e, this.conf.ctx_zoneid, c])) {
            this.setRadioChecked(d, e)
        }
    } else {
        this.setRadioChecked(d, e)
    }
    if (this.checkEvent("onClick")) {
        this.callEvent("onClick", [e])
    }
};
dhtmlXMenuObject.prototype.getRadioChecked = function (e) {
    var g = null;
    for (var d = 0; d < this.radio[e].length; d++) {
        var f = this.radio[e][d].replace(this.idPrefix, "");
        var a = this._getRadioImgObj(f);
        if (a != null) {
            var c = (a.className).match(/rdbt_1$/gi);
            if (c != null) {
                g = f
            }
        }
    }
    return g
};
dhtmlXMenuObject.prototype.setRadioChecked = function (c, e) {
    if (this.radio[c] == null) {
        return
    }
    for (var a = 0; a < this.radio[c].length; a++) {
        var d = this.radio[c][a].replace(this.idPrefix, "");
        this._setRadioState(d, (d == e))
    }
};
dhtmlXMenuObject.prototype.addRadioButton = function (k, f, l, m, n, o, a, g) {
    if (this.conf.context && f == this.topId) {} else {
        if (this.itemPull[this.idPrefix + f] == null) {
            return
        }
        if (k == "child" && this.itemPull[this.idPrefix + f]["type"] != "item") {
            return
        }
    }
    var d = this.idPrefix + (m != null ? m : this._genStr(24));
    var h = "rdbt_" + (a ? "1" : "0");
    var c = h;
    if (k == "sibling") {
        var j = this.idPrefix + this.getParentId(f);
        this._addItemIntoGlobalStrorage(d, j, n, "radio", g, h, c);
        this._renderSublevelItem(d, this.getItemPosition(f))
    } else {
        var j = this.idPrefix + f;
        this._addItemIntoGlobalStrorage(d, j, n, "radio", g, h, c);
        if (this.idPull["polygon_" + j] == null) {
            this._renderSublevelPolygon(j, j)
        }
        this._renderSublevelItem(d, l - 1);
        this._redefineComplexState(j)
    }
    var e = (o != null ? o : this._genStr(24));
    this.itemPull[d]["group"] = e;
    if (this.radio[e] == null) {
        this.radio[e] = new Array()
    }
    this.radio[e][this.radio[e].length] = d;
    if (a == true) {
        this.setRadioChecked(e, String(d).replace(this.idPrefix, ""))
    }
};
dhtmlXMenuObject.prototype.serialize = function () {
    var a = "<menu>" + this._readLevel(this.idPrefix + this.topId) + "</menu>";
    return a
};
dhtmlXMenuObject.prototype._readLevel = function (e) {
    var f = "";
    for (var k in this.itemPull) {
        if (this.itemPull[k]["parent"] == e) {
            var c = "";
            var d = "";
            var m = "";
            var j = String(this.itemPull[k]["id"]).replace(this.idPrefix, "");
            var h = "";
            var l = (this.itemPull[k]["title"] != "" ? ' text="' + this.itemPull[k]["title"] + '"' : "");
            var g = "";
            if (this.itemPull[k]["type"] == "item") {
                if (this.itemPull[k]["imgen"] != "") {
                    c = ' img="' + this.itemPull[k]["imgen"] + '"'
                }
                if (this.itemPull[k]["imgdis"] != "") {
                    d = ' imgdis="' + this.itemPull[k]["imgdis"] + '"'
                }
                if (this.itemPull[k]["hotkey"] != "") {
                    m = "<hotkey>" + this.itemPull[k]["hotkey"] + "</hotkey>"
                }
            }
            if (this.itemPull[k]["type"] == "separator") {
                h = ' type="separator"'
            } else {
                if (this.itemPull[k]["state"] == "disabled") {
                    g = ' enabled="false"'
                }
            }
            if (this.itemPull[k]["type"] == "checkbox") {
                h = ' type="checkbox"' + (this.itemPull[k]["checked"] ? ' checked="true"' : "")
            }
            if (this.itemPull[k]["type"] == "radio") {
                h = ' type="radio" group="' + this.itemPull[k]["group"] + '" ' + (this.itemPull[k]["checked"] ? ' checked="true"' : "")
            }
            f += "<item id='" + j + "'" + l + h + c + d + g + ">";
            f += m;
            if (this.itemPull[k]["complex"]) {
                f += this._readLevel(k)
            }
            f += "</item>"
        }
    }
    return f
};
dhtmlXMenuObject.prototype.enableEffect = function (d, f, e) {
    this._menuEffect = (d == "opacity" || d == "slide" || d == "slide+" ? d : false);
    this._pOpStyleIE = (navigator.userAgent.search(/MSIE\s[678]\.0/gi) >= 0);
    for (var c in this.idPull) {
        if (c.search(/polygon/) === 0) {
            this._pOpacityApply(c, (this._pOpStyleIE ? 100 : 1));
            this.idPull[c].style.height = ""
        }
    }
    this._pOpMax = (typeof (f) == "undefined" ? 100 : f) / (this._pOpStyleIE ? 1 : 100);
    this._pOpStyleName = (this._pOpStyleIE ? "filter" : "opacity");
    this._pOpStyleValue = (this._pOpStyleIE ? "progid:DXImageTransform.Microsoft.Alpha(Opacity=#)" : "#");
    this._pSlSteps = (this._pOpStyleIE ? 10 : 20);
    this._pSlTMTimeMax = e || 50
};
dhtmlXMenuObject.prototype._showPolygonEffect = function (a) {
    this._pShowHide(a, true)
};
dhtmlXMenuObject.prototype._hidePolygonEffect = function (a) {
    this._pShowHide(a, false)
};
dhtmlXMenuObject.prototype._pOpacityApply = function (a, c) {
    this.idPull[a].style[this._pOpStyleName] = String(this._pOpStyleValue).replace("#", c || this.idPull[a]._op)
};
dhtmlXMenuObject.prototype._pShowHide = function (a, c) {
    if (!this.idPull) {
        return
    }
    if (this.idPull[a]._tmShow != null) {
        if ((this.idPull[a]._step_h > 0 && c == true) || (this.idPull[a]._step_h < 0 && c == false)) {
            return
        }
        window.clearTimeout(this.idPull[a]._tmShow);
        this.idPull[a]._tmShow = null;
        this.idPull[a]._max_h = null
    }
    if (c == false && (this.idPull[a].style.visibility == "hidden" || this.idPull[a].style.display == "none")) {
        return
    }
    if (c == true && this.idPull[a].style.display == "none") {
        this.idPull[a].style.visibility = "hidden";
        this.idPull[a].style.display = ""
    }
    if (this.idPull[a]._max_h == null) {
        this.idPull[a]._max_h = parseInt(this.idPull[a].offsetHeight);
        this.idPull[a]._h = (c == true ? 0 : this.idPull[a]._max_h);
        this.idPull[a]._step_h = Math.round(this.idPull[a]._max_h / this._pSlSteps) * (c == true ? 1 : -1);
        if (this.idPull[a]._step_h == 0) {
            return
        }
        this.idPull[a]._step_tm = Math.round(this._pSlTMTimeMax / this._pSlSteps);
        if (this._menuEffect == "slide+" || this._menuEffect == "opacity") {
            this.idPull[a].op_tm = this.idPull[a]._step_tm;
            this.idPull[a].op_step = (this._pOpMax / this._pSlSteps) * (c == true ? 1 : -1);
            if (this._pOpStyleIE) {
                this.idPull[a].op_step = Math.round(this.idPull[a].op_step)
            }
            this.idPull[a]._op = (c == true ? 0 : this._pOpMax);
            this._pOpacityApply(a)
        } else {
            this.idPull[a]._op = (this._pOpStyleIE ? 100 : 1);
            this._pOpacityApply(a)
        }
        if (this._menuEffect.search(/slide/) === 0) {
            this.idPull[a].style.height = "0px"
        }
        this.idPull[a].style.visibility = "visible"
    }
    this._pEffectSet(a, this.idPull[a]._h + this.idPull[a]._step_h)
};
dhtmlXMenuObject.prototype._pEffectSet = function (d, c) {
    if (!this.idPull) {
        return
    }
    if (this.idPull[d]._tmShow) {
        window.clearTimeout(this.idPull[d]._tmShow)
    }
    this.idPull[d]._h = Math.max(0, Math.min(c, this.idPull[d]._max_h));
    if (this._menuEffect.search(/slide/) === 0) {
        this.idPull[d].style.height = this.idPull[d]._h + "px"
    }
    c += this.idPull[d]._step_h;
    if (this._menuEffect == "slide+" || this._menuEffect == "opacity") {
        this.idPull[d]._op = Math.max(0, Math.min(this._pOpMax, this.idPull[d]._op + this.idPull[d].op_step));
        this._pOpacityApply(d)
    }
    if ((this.idPull[d]._step_h > 0 && c <= this.idPull[d]._max_h) || (this.idPull[d]._step_h < 0 && c >= 0)) {
        var a = this;
        this.idPull[d]._tmShow = window.setTimeout(function () {
            a._pEffectSet(d, c)
        }, this.idPull[d]._step_tm)
    } else {
        if (this._menuEffect.search(/slide/) === 0) {
            this.idPull[d].style.height = ""
        }
        if (this.idPull[d]._step_h < 0) {
            this.idPull[d].style.visibility = "hidden"
        }
        if (this._menuEffect == "slide+" || this._menuEffect == "opacity") {
            this.idPull[d]._op = (this.idPull[d]._step_h < 0 ? (this._pOpStyleIE ? 100 : 1) : this._pOpMax);
            this._pOpacityApply(d)
        }
        this.idPull[d]._tmShow = null;
        this.idPull[d]._h = null;
        this.idPull[d]._max_h = null;
        this.idPull[d]._step_tm = null
    }
};

function xmlPointer(a) {
    this.d = a
}
xmlPointer.prototype = {
    text: function () {
        if (!_isFF) {
            return this.d.xml
        }
        var a = new XMLSerializer();
        return a.serializeToString(this.d)
    },
    get: function (a) {
        return this.d.getAttribute(a)
    },
    exists: function () {
        return !!this.d
    },
    content: function () {
        return this.d.firstChild ? (this.d.firstChild.wholeText || this.d.firstChild.data) : ""
    },
    each: function (e, j, h, g) {
        var d = this.d.childNodes;
        var k = new xmlPointer();
        if (d.length) {
            for (g = g || 0; g < d.length; g++) {
                if (d[g].tagName == e) {
                    k.d = d[g];
                    if (j.apply(h, [k, g]) == -1) {
                        return
                    }
                }
            }
        }
    },
    get_all: function () {
        var d = {};
        var c = this.d.attributes;
        for (var e = 0; e < c.length; e++) {
            d[c[e].name] = c[e].value
        }
        return d
    },
    sub: function (e) {
        var d = this.d.childNodes;
        var g = new xmlPointer();
        if (d.length) {
            for (var f = 0; f < d.length; f++) {
                if (d[f].tagName == e) {
                    g.d = d[f];
                    return g
                }
            }
        }
    },
    up: function (a) {
        return new xmlPointer(this.d.parentNode)
    },
    set: function (a, c) {
        this.d.setAttribute(a, c)
    },
    clone: function (a) {
        return new xmlPointer(this.d)
    },
    sub_exists: function (d) {
        var c = this.d.childNodes;
        if (c.length) {
            for (var e = 0; e < c.length; e++) {
                if (c[e].tagName == d) {
                    return true
                }
            }
        }
        return false
    },
    through: function (d, j, m, g, n) {
        var k = this.d.childNodes;
        if (k.length) {
            for (var e = 0; e < k.length; e++) {
                if (k[e].tagName == d && k[e].getAttribute(j) != null && k[e].getAttribute(j) != "" && (!m || k[e].getAttribute(j) == m)) {
                    var h = new xmlPointer(k[e]);
                    g.apply(n, [h, e])
                }
                var l = this.d;
                this.d = k[e];
                this.through(d, j, m, g, n);
                this.d = l
            }
        }
    }
};

function dhtmlXTreeObject(k, g, c, a) {
    if (dhtmlxEvent.initTouch) {
        dhtmlxEvent.initTouch()
    }
    if (_isIE) {
        try {
            document.execCommand("BackgroundImageCache", false, true)
        } catch (j) {}
    }
    if (typeof (k) != "object") {
        this.parentObject = document.getElementById(k)
    } else {
        this.parentObject = k
    }
    this.parentObject.style.overflow = "hidden";
    this._itim_dg = true;
    this.dlmtr = ",";
    this.dropLower = false;
    this.enableIEImageFix(true);
    this.xmlstate = 0;
    this.mytype = "tree";
    this.smcheck = true;
    this.width = g;
    this.height = c;
    this.rootId = a;
    this.childCalc = null;
    this.def_img_x = "18px";
    this.def_img_y = "18px";
    this.def_line_img_x = "18px";
    this.def_line_img_y = "24px";
    this._dragged = new Array();
    this._selected = new Array();
    this._aimgs = true;
    this.htmlcA = " [";
    this.htmlcB = "]";
    this.lWin = window;
    this.cMenu = 0;
    this.mlitems = 0;
    this.iconURL = "";
    this.dadmode = 0;
    this.slowParse = false;
    this.autoScroll = true;
    this.hfMode = 0;
    this.nodeCut = new Array();
    this.XMLsource = 0;
    this.XMLloadingWarning = 0;
    this._idpull = {};
    this._pullSize = 0;
    this.treeLinesOn = true;
    this.tscheck = false;
    this.timgen = true;
    this.dpcpy = false;
    this._ld_id = null;
    this._dynDeleteBranches = {};
    this._oie_onXLE = [];
    this.imPath = window.dhx_globalImgPath || "";
    this.checkArray = new Array("iconUncheckAll.gif", "iconCheckAll.gif", "iconCheckGray.gif", "iconUncheckDis.gif", "iconCheckDis.gif", "iconCheckDis.gif");
    this.radioArray = new Array("radio_off.gif", "radio_on.gif", "radio_on.gif", "radio_off.gif", "radio_on.gif", "radio_on.gif");
    this.lineArray = new Array("line2.gif", "line3.gif", "line4.gif", "blank.gif", "blank.gif", "line1.gif");
    this.minusArray = new Array("minus2.gif", "minus3.gif", "minus4.gif", "minus.gif", "minus5.gif");
    this.plusArray = new Array("plus2.gif", "plus3.gif", "plus4.gif", "plus.gif", "plus5.gif");
    this.imageArray = new Array("leaf.gif", "folderOpen.gif", "folderClosed.gif");
    this.cutImg = new Array(0, 0, 0);
    this.cutImage = "but_cut.gif";
    dhx4._eventable(this);
    this.dragger = new dhtmlDragAndDropObject();
    this.htmlNode = new dhtmlXTreeItemObject(this.rootId, "", 0, this);
    this.htmlNode.htmlNode.childNodes[0].childNodes[0].style.display = "none";
    this.htmlNode.htmlNode.childNodes[0].childNodes[0].childNodes[0].className = "hiddenRow";
    this.allTree = this._createSelf();
    this.allTree.appendChild(this.htmlNode.htmlNode);
    if (dhtmlx.$customScroll) {
        dhtmlx.CustomScroll.enable(this)
    }
    if (_isFF) {
        this.allTree.childNodes[0].width = "100%";
        this.allTree.childNodes[0].style.overflow = "hidden"
    }
    var f = this;
    this.allTree.onselectstart = new Function("return false;");
    if (_isMacOS) {
        this.allTree.oncontextmenu = function (l) {
            return f._doContClick(l || window.event, true)
        }
    }
    this.allTree.onmousedown = function (l) {
        return f._doContClick(l || window.event)
    };
    this.XMLLoader = this._parseXMLTree;
    if (_isIE) {
        this.preventIECashing(true)
    }
    this.selectionBar = document.createElement("DIV");
    this.selectionBar.className = "selectionBar";
    this.selectionBar.innerHTML = "&nbsp;";
    this.selectionBar.style.display = "none";
    this.allTree.appendChild(this.selectionBar);
    if (window.addEventListener) {
        window.addEventListener("unload", function () {
            try {
                f.destructor()
            } catch (l) {}
        }, false)
    }
    if (window.attachEvent) {
        window.attachEvent("onunload", function () {
            try {
                f.destructor()
            } catch (l) {}
        })
    }
    this.setImagesPath = this.setImagePath;
    this.setIconsPath = this.setIconPath;
    this.setSkin(window.dhx4.skin || (typeof (dhtmlx) != "undefined" ? dhtmlx.skin : null) || window.dhx4.skinDetect("dhxtree") || "material");
    if (dhtmlx.image_path) {
        var h = dhtmlx.image_path;
        var d = this.parentObject.className.match(/dhxtree_dhx_([a-z_]*)/i);
        if (d != null && d[1] != null) {
            h += "dhxtree_" + d[1] + "/"
        }
        this.setImagePath(h)
    }
    return this
}
dhtmlXTreeObject.prototype.setDataMode = function (a) {
    this._datamode = a
};
dhtmlXTreeObject.prototype._doContClick = function (h, a) {
    if (!a && h.button != 2) {
        if (this._acMenu) {
            if (this._acMenu.hideContextMenu) {
                this._acMenu.hideContextMenu()
            } else {
                this.cMenu._contextEnd()
            }
        }
        return true
    }
    var c = (_isIE ? h.srcElement : h.target);
    while ((c) && (c.tagName != "BODY")) {
        if (c.parentObject) {
            break
        }
        c = c.parentNode
    }
    if ((!c) || (!c.parentObject)) {
        return true
    }
    var f = c.parentObject;
    if (!this.callEvent("onRightClick", [f.id, h])) {
        (h.srcElement || h.target).oncontextmenu = function (l) {
            (l || event).cancelBubble = true;
            return false
        }
    }
    this._acMenu = (f.cMenu || this.cMenu);
    if (this._acMenu) {
        if (!(this.callEvent("onBeforeContextMenu", [f.id]))) {
            return true
        }
        if (!_isMacOS) {
            (h.srcElement || h.target).oncontextmenu = function (l) {
                (l || event).cancelBubble = true;
                return false
            }
        }
        if (this._acMenu.showContextMenu) {
            var e = window.document.documentElement;
            var d = window.document.body;
            var j = new Array((e.scrollLeft || d.scrollLeft), (e.scrollTop || d.scrollTop));
            if (_isIE) {
                var k = h.clientX + j[0];
                var g = h.clientY + j[1]
            } else {
                var k = h.pageX;
                var g = h.pageY
            }
            this._acMenu.showContextMenu(k - 1, g - 1);
            this.contextID = f.id;
            h.cancelBubble = true;
            this._acMenu._skip_hide = true
        } else {
            c.contextMenuId = f.id;
            c.contextMenu = this._acMenu;
            c.a = this._acMenu._contextStart;
            c.a(c, h);
            c.a = null
        }
        return false
    }
    return true
};
dhtmlXTreeObject.prototype.enableIEImageFix = function (a) {
    if (!a) {
        this._getImg = function (c) {
            return document.createElement((c == this.rootId) ? "div" : "img")
        };
        this._setSrc = function (d, c) {
            d.src = c
        };
        this._getSrc = function (c) {
            return c.src
        }
    } else {
        this._getImg = function () {
            var c = document.createElement("DIV");
            c.innerHTML = "&nbsp;";
            c.className = "dhx_bg_img_fix";
            return c
        };
        this._setSrc = function (d, c) {
            d.style.backgroundImage = "url(" + c + ")"
        };
        this._getSrc = function (c) {
            var d = c.style.backgroundImage;
            return d.substr(4, d.length - 5).replace(/(^")|("$)/g, "")
        }
    }
};
dhtmlXTreeObject.prototype.destructor = function () {
    for (var c in this._idpull) {
        var d = this._idpull[c];
        if (!d) {
            continue
        }
        d.parentObject = null;
        d.treeNod = null;
        d.childNodes = null;
        d.span = null;
        d.tr.nodem = null;
        d.tr = null;
        d.htmlNode.objBelong = null;
        d.htmlNode = null;
        this._idpull[c] = null
    }
    this.parentObject.innerHTML = "";
    this.allTree.onselectstart = null;
    this.allTree.oncontextmenu = null;
    this.allTree.onmousedown = null;
    for (var c in this) {
        this[c] = null
    }
};

function cObject() {
    return this
}
cObject.prototype = new Object;
cObject.prototype.clone = function () {
    function a() {}
    a.prototype = this;
    return new a()
};

function dhtmlXTreeItemObject(g, c, d, a, e, f) {
    this.htmlNode = "";
    this.acolor = "";
    this.scolor = "";
    this.tr = 0;
    this.childsCount = 0;
    this.tempDOMM = 0;
    this.tempDOMU = 0;
    this.dragSpan = 0;
    this.dragMove = 0;
    this.span = 0;
    this.closeble = 1;
    this.childNodes = new Array();
    this.userData = new cObject();
    this.checkstate = 0;
    this.treeNod = a;
    this.label = c;
    this.parentObject = d;
    this.actionHandler = e;
    this.images = new Array(a.imageArray[0], a.imageArray[1], a.imageArray[2]);
    this.id = a._globalIdStorageAdd(g, this);
    if (this.treeNod.checkBoxOff) {
        this.htmlNode = this.treeNod._createItem(1, this, f)
    } else {
        this.htmlNode = this.treeNod._createItem(0, this, f)
    }
    this.htmlNode.objBelong = this;
    return this
}
dhtmlXTreeObject.prototype._globalIdStorageAdd = function (c, a) {
    if (this._globalIdStorageFind(c, 1, 1)) {
        c = c + "_" + (new Date()).valueOf();
        return this._globalIdStorageAdd(c, a)
    }
    this._idpull[c] = a;
    this._pullSize++;
    return c
};
dhtmlXTreeObject.prototype._globalIdStorageSub = function (a) {
    if (this._idpull[a]) {
        this._unselectItem(this._idpull[a]);
        this._idpull[a] = null;
        this._pullSize--
    }
    if ((this._locker) && (this._locker[a])) {
        this._locker[a] = false
    }
};
dhtmlXTreeObject.prototype._globalIdStorageFind = function (g, a, d, e) {
    var f = this._idpull[g];
    if (f) {
        if ((f.unParsed) && (!d)) {
            this.reParse(f, 0)
        }
        if (this._srnd && !f.htmlNode) {
            this._buildSRND(f, d)
        }
        if ((e) && (this._edsbpsA)) {
            for (var c = 0; c < this._edsbpsA.length; c++) {
                if (this._edsbpsA[c][2] == g) {
                    dhx4.callEvent("ongetItemError", ["Requested item still in parsing process.", g]);
                    return null
                }
            }
        }
        return f
    }
    if ((this.slowParse) && (g != 0) && (!a)) {
        return this.preParse(g)
    } else {
        return null
    }
};
dhtmlXTreeObject.prototype._getSubItemsXML = function (a) {
    var c = [];
    a.each("item", function (d) {
        c.push(d.get("id"))
    }, this);
    return c.join(this.dlmtr)
};
dhtmlXTreeObject.prototype.enableSmartXMLParsing = function (a) {
    this.slowParse = dhx4.s2b(a)
};
dhtmlXTreeObject.prototype.findXML = function (c, a, d) {};
dhtmlXTreeObject.prototype._getAllCheckedXML = function (c, a, e) {
    var d = [];
    if (e == 2) {
        c.through("item", "checked", -1, function (f) {
            d.push(f.get("id"))
        }, this)
    }
    if (e == 1) {
        c.through("item", "id", null, function (f) {
            if (f.get("checked") && (f.get("checked") != -1)) {
                d.push(f.get("id"))
            }
        }, this)
    }
    if (e == 0) {
        c.through("item", "id", null, function (f) {
            if (!f.get("checked") || f.get("checked") == 0) {
                d.push(f.get("id"))
            }
        }, this)
    }
    if (d.length) {
        return a + (a ? this.dlmtr : "") + d.join(this.dlmtr)
    }
    if (a) {
        return a
    } else {
        return ""
    }
};
dhtmlXTreeObject.prototype._setSubCheckedXML = function (a, c) {
    var d = a ? "1" : "";
    c.through("item", "id", null, function (e) {
        if (!e.get("disabled") || e.get("disabled") == 0) {
            e.set("checked", d)
        }
    }, this)
};
dhtmlXTreeObject.prototype._getAllScraggyItemsXML = function (d, a) {
    var e = [];
    var c = function (f) {
        if (!f.sub_exists("item")) {
            e.push(f.get("id"))
        } else {
            f.each("item", c, this)
        }
    };
    c(d);
    return e.join(",")
};
dhtmlXTreeObject.prototype._getAllFatItemsXML = function (d, a) {
    var e = [];
    var c = function (f) {
        if (!f.sub_exists("item")) {
            return
        }
        e.push(f.get("id"));
        f.each("item", c, this)
    };
    c(d);
    return e.join(",")
};
dhtmlXTreeObject.prototype._getAllSubItemsXML = function (d, c, a) {
    var c = [];
    a.through("item", "id", null, function (e) {
        c.push(e.get("id"))
    }, this);
    return c.join(",")
};
dhtmlXTreeObject.prototype.reParse = function (d) {
    var g = this;
    if (!this.parsCount) {
        g.callEvent("onXLS", [g, d.id])
    }
    this.xmlstate = 1;
    var e = d.unParsed;
    d.unParsed = 0;
    this.XMLloadingWarning = 1;
    var a = this.parsingOn;
    var h = this.waitUpdateXML;
    var j = this.parsedArray;
    this.parsedArray = new Array();
    this.waitUpdateXML = false;
    this.parsingOn = d.id;
    this.parsedArray = new Array();
    this.setCheckList = "";
    this._parse(e, d.id, 2);
    var k = this.setCheckList.split(this.dlmtr);
    for (var f = 0; f < this.parsedArray.length; f++) {
        d.htmlNode.childNodes[0].appendChild(this.parsedArray[f])
    }
    if (e.get("order") && e.get("order") != "none") {
        this._reorderBranch(d, e.get("order"), true)
    }
    this.oldsmcheck = this.smcheck;
    this.smcheck = false;
    for (var c = 0; c < k.length; c++) {
        if (k[c]) {
            this.setCheck(k[c], 1)
        }
    }
    this.smcheck = this.oldsmcheck;
    this.parsingOn = a;
    this.waitUpdateXML = h;
    this.parsedArray = j;
    this.XMLloadingWarning = 0;
    this._redrawFrom(this, d);
    if (this._srnd && !d._sready) {
        this.prepareSR(d.id)
    }
    this.xmlstate = 0;
    return true
};
dhtmlXTreeObject.prototype.preParse = function (c) {
    if (!c || !this._p) {
        return null
    }
    var a = false;
    this._p.clone().through("item", "id", c, function (e) {
        this._globalIdStorageFind(e.up().get("id"));
        return a = true
    }, this);
    if (a) {
        var d = this._globalIdStorageFind(c, true, false);
        if (!d) {
            dhx4.callEvent("ongetItemError", ["The item " + c + " not operable. Seems you have non-unique|incorrect IDs in tree's XML.", c])
        }
    }
    return d
};
dhtmlXTreeObject.prototype._escape = function (a) {
    switch (this.utfesc) {
        case "none":
            return a;
            break;
        case "utf8":
            return encodeURIComponent(a);
            break;
        default:
            return escape(a);
            break
    }
};
dhtmlXTreeObject.prototype._drawNewTr = function (f, d) {
    var e = document.createElement("tr");
    var c = document.createElement("td");
    var a = document.createElement("td");
    c.appendChild(document.createTextNode(" "));
    a.colSpan = 3;
    a.appendChild(f);
    e.appendChild(c);
    e.appendChild(a);
    return e
};
dhtmlXTreeObject.prototype.parse = function (e, d, a) {
    if (typeof d == "string") {
        a = d;
        d = null
    }
    if (a === "json") {
        return this._loadJSONObject(e, d)
    } else {
        if (a === "csv") {
            return this._loadCSVString(e, d)
        } else {
            if (a === "jsarray") {
                return this._loadJSArray(e, d)
            }
        }
    }
    var c = this;
    if (!this.parsCount) {
        this.callEvent("onXLS", [c, null])
    }
    this.xmlstate = 1;
    this.XMLLoader({
        responseXML: dhx4.ajax.parse(e)
    }, d)
};
dhtmlXTreeObject.prototype.loadXMLString = function () {
    if (window.console && window.console.info) {
        window.console.info("loadXMLString was deprecated", "http://docs.dhtmlx.com/migration__index.html#migrationfrom43to44")
    }
    return this.parse.apply(this, arguments)
};
dhtmlXTreeObject.prototype.load = function (c, f, d) {
    if (typeof f == "string") {
        d = f;
        f = null
    }
    d = d || this._datamode;
    if (d === "json") {
        return this._loadJSON(c, f)
    } else {
        if (d === "csv") {
            return this._loadCSV(c, f)
        } else {
            if (d === "jsarray") {
                return this._loadJSArrayFile(xmlString, f)
            }
        }
    }
    var e = this;
    if (!this.parsCount) {
        this.callEvent("onXLS", [e, this._ld_id])
    }
    this._ld_id = null;
    this.xmlstate = 1;
    this.XMLLoader = this._parseXMLTree;
    var a = this;
    return dhx4.ajax.get(c, function (g) {
        a.XMLLoader(g.xmlDoc, f);
        a = null
    })
};
dhtmlXTreeObject.prototype.loadXML = function () {
    if (window.console && window.console.info) {
        window.console.info("loadXML was deprecated", "http://docs.dhtmlx.com/migration__index.html#migrationfrom43to44")
    }
    return this.load.apply(this, arguments)
};
dhtmlXTreeObject.prototype._attachChildNode = function (h, g, e, j, w, v, u, k, d, o, p) {
    if (o && o.parentObject) {
        h = o.parentObject
    }
    if (((h.XMLload == 0) && (this.XMLsource)) && (!this.XMLloadingWarning)) {
        h.XMLload = 1;
        this._loadDynXML(h.id)
    }
    var l = h.childsCount;
    var x = h.childNodes;
    if (p && p.tr.previousSibling) {
        if (p.tr.previousSibling.previousSibling) {
            o = p.tr.previousSibling.nodem
        } else {
            k = k.replace("TOP", "") + ",TOP"
        }
    }
    if (o) {
        var f, s;
        for (f = 0; f < l; f++) {
            if (x[f] == o) {
                for (s = l; s != f; s--) {
                    x[1 + s] = x[s]
                }
                break
            }
        }
        f++;
        l = f
    }
    if (k) {
        var q = k.split(",");
        for (var r = 0; r < q.length; r++) {
            switch (q[r]) {
                case "TOP":
                    if (h.childsCount > 0) {
                        o = new Object;
                        o.tr = h.childNodes[0].tr.previousSibling
                    }
                    h._has_top = true;
                    for (f = l; f > 0; f--) {
                        x[f] = x[f - 1]
                    }
                    l = 0;
                    break
            }
        }
    }
    var m;
    if (!(m = this._idpull[g]) || m.span != -1) {
        m = x[l] = new dhtmlXTreeItemObject(g, e, h, this, j, 1);
        g = x[l].id;
        h.childsCount++
    }
    if (!m.htmlNode) {
        m.label = e;
        m.htmlNode = this._createItem((this.checkBoxOff ? 1 : 0), m);
        m.htmlNode.objBelong = m
    }
    if (w) {
        m.images[0] = w
    }
    if (v) {
        m.images[1] = v
    }
    if (u) {
        m.images[2] = u
    }
    var c = this._drawNewTr(m.htmlNode);
    if ((this.XMLloadingWarning) || (this._hAdI)) {
        m.htmlNode.parentNode.parentNode.style.display = "none"
    }
    if ((o) && o.tr && (o.tr.nextSibling)) {
        h.htmlNode.childNodes[0].insertBefore(c, o.tr.nextSibling)
    } else {
        if (this.parsingOn == h.id) {
            this.parsedArray[this.parsedArray.length] = c
        } else {
            h.htmlNode.childNodes[0].appendChild(c)
        }
    }
    if ((o) && (!o.span)) {
        o = null
    }
    if (this.XMLsource) {
        if ((d) && (d != 0)) {
            m.XMLload = 0
        } else {
            m.XMLload = 1
        }
    }
    m.tr = c;
    c.nodem = m;
    if (h.itemId == 0) {
        c.childNodes[0].className = "hiddenRow"
    }
    if ((h._r_logic) || (this._frbtr)) {
        this._setSrc(m.htmlNode.childNodes[0].childNodes[0].childNodes[1].childNodes[0], this.imPath + this.radioArray[0])
    }
    if (k) {
        var q = k.split(",");
        for (var r = 0; r < q.length; r++) {
            switch (q[r]) {
                case "SELECT":
                    this.selectItem(g, false);
                    break;
                case "CALL":
                    this.selectItem(g, true);
                    break;
                case "CHILD":
                    m.XMLload = 0;
                    break;
                case "CHECKED":
                    if (this.XMLloadingWarning) {
                        this.setCheckList += this.dlmtr + g
                    } else {
                        this.setCheck(g, 1)
                    }
                    break;
                case "HCHECKED":
                    this._setCheck(m, "unsure");
                    break;
                case "OPEN":
                    m.openMe = 1;
                    break
            }
        }
    }
    if (!this.XMLloadingWarning) {
        if ((this._getOpenState(h) < 0) && (!this._hAdI)) {
            this.openItem(h.id)
        }
        if (o) {
            this._correctPlus(o);
            this._correctLine(o)
        }
        this._correctPlus(h);
        this._correctLine(h);
        this._correctPlus(m);
        if (h.childsCount >= 2) {
            this._correctPlus(x[h.childsCount - 2]);
            this._correctLine(x[h.childsCount - 2])
        }
        if (h.childsCount != 2) {
            this._correctPlus(x[0])
        }
        if (this.tscheck) {
            this._correctCheckStates(h)
        }
        if (this._onradh) {
            if (this.xmlstate == 1) {
                var a = this.onXLE;
                this.onXLE = function (n) {
                    this._onradh(g);
                    if (a) {
                        a(n)
                    }
                }
            } else {
                this._onradh(g)
            }
        }
    }
    return m
};
dhtmlXTreeObject.prototype.enableContextMenu = function (a) {
    if (a) {
        this.cMenu = a
    }
};
dhtmlXTreeObject.prototype.setItemContextMenu = function (f, e) {
    var a = f.toString().split(this.dlmtr);
    for (var d = 0; d < a.length; d++) {
        var c = this._globalIdStorageFind(a[d]);
        if (!c) {
            continue
        }
        c.cMenu = e
    }
};
dhtmlXTreeObject.prototype.insertNewItem = function (e, j, l, d, h, g, f, c, a) {
    var m = this._globalIdStorageFind(e);
    if (!m) {
        return (-1)
    }
    var k = this._attachChildNode(m, j, l, d, h, g, f, c, a);
    if (!this._idpull[this.rootId].XMLload) {
        this._idpull[this.rootId].XMLload = 1
    }
    if ((!this.XMLloadingWarning) && (this.childCalc)) {
        this._fixChildCountLabel(m)
    }
    return k
};
dhtmlXTreeObject.prototype.insertNewChild = function (e, j, k, d, h, g, f, c, a) {
    return this.insertNewItem(e, j, k, d, h, g, f, c, a)
};
dhtmlXTreeObject.prototype._parseXMLTree = function (a, d) {
    var c = new xmlPointer(dhx4.ajax.xmltop("tree", a));
    this._parse(c);
    this._p = c;
    if (d) {
        d.call(this, a)
    }
};
dhtmlXTreeObject.prototype._parseItem = function (g, l, f, j) {
    var d;
    if (this._srnd && (!this._idpull[d = g.get("id")] || !this._idpull[d].span)) {
        this._addItemSRND(l.id, d, g);
        return
    }
    var h = g.get_all();
    if ((typeof (this.waitUpdateXML) == "object") && (!this.waitUpdateXML[h.id])) {
        this._parse(g, h.id, 1);
        return
    }
    if ((h.text === null) || (typeof (h.text) == "undefined")) {
        h.text = g.sub("itemtext");
        if (h.text) {
            h.text = h.text.content()
        }
    }
    var n = [];
    if (h.select) {
        n.push("SELECT")
    }
    if (h.top) {
        n.push("TOP")
    }
    if (h.call) {
        this.nodeAskingCall = h.id
    }
    if (h.checked == -1) {
        n.push("HCHECKED")
    } else {
        if (h.checked) {
            n.push("CHECKED")
        }
    }
    if (h.open) {
        n.push("OPEN")
    }
    if (this.waitUpdateXML) {
        if (this._globalIdStorageFind(h.id)) {
            var k = this.updateItem(h.id, h.text, h.im0, h.im1, h.im2, h.checked, h.child)
        } else {
            if (this.npl == 0) {
                n.push("TOP")
            } else {
                f = l.childNodes[this.npl]
            }
            var k = this._attachChildNode(l, h.id, h.text, 0, h.im0, h.im1, h.im2, n.join(","), h.child, 0, f);
            h.id = k.id;
            f = null
        }
    } else {
        var k = this._attachChildNode(l, h.id, h.text, 0, h.im0, h.im1, h.im2, n.join(","), h.child, (j || 0), f)
    }
    if (h.tooltip) {
        k.span.parentNode.parentNode.title = h.tooltip
    }
    if (h.style) {
        if (k.span.style.cssText) {
            k.span.style.cssText += (";" + h.style)
        } else {
            k.span.setAttribute("style", k.span.getAttribute("style") + "; " + h.style)
        }
    }
    if (h.radio) {
        k._r_logic = true
    }
    if (h.nocheckbox) {
        var m = k.span.parentNode.previousSibling.previousSibling;
        m.style.display = "none";
        k.nocheckbox = true
    }
    if (h.disabled) {
        if (h.checked != null) {
            this._setCheck(k, h.checked)
        }
        this.disableCheckbox(k, 1)
    }
    k._acc = h.child || 0;
    if (this.parserExtension) {
        this.parserExtension._parseExtension.call(this, g, h, (l ? l.id : 0))
    }
    this.setItemColor(k, h.aCol, h.sCol);
    if (h.locked == "1") {
        this.lockItem(k.id, true, true)
    }
    if ((h.imwidth) || (h.imheight)) {
        this.setIconSize(h.imwidth, h.imheight, k)
    }
    if ((h.closeable == "0") || (h.closeable == "1")) {
        this.setItemCloseable(k, h.closeable)
    }
    var e = "";
    if (h.topoffset) {
        this.setItemTopOffset(k, h.topoffset)
    }
    if ((!this.slowParse) || (typeof (this.waitUpdateXML) == "object")) {
        if (g.sub_exists("item")) {
            e = this._parse(g, h.id, 1)
        }
    } else {
        if ((!k.childsCount) && g.sub_exists("item")) {
            k.unParsed = g.clone()
        }
        g.each("userdata", function (a) {
            this.setUserData(h.id, a.get("name"), a.content())
        }, this)
    }
    if (e != "") {
        this.nodeAskingCall = e
    }
    g.each("userdata", function (a) {
        this.setUserData(g.get("id"), a.get("name"), a.content())
    }, this)
};
dhtmlXTreeObject.prototype._parse = function (d, g, a, c) {
    if (this._srnd && !this.parentObject.offsetHeight) {
        var q = this;
        return window.setTimeout(function () {
            q._parse(d, g, a, c)
        }, 100)
    }
    if (!d.exists()) {
        return
    }
    this.skipLock = true;
    if (!g) {
        g = d.get("id");
        if (this._dynDeleteBranches[g]) {
            this.deleteChildItems(g);
            this._dynDeleteBranches[g]--;
            if (!this._dynDeleteBranches[g]) {
                delete this._dynDeleteBranches[g]
            }
        }
        var m = d.get("dhx_security");
        if (m) {
            dhtmlx.security_key = m
        }
        if (d.get("radio")) {
            this.htmlNode._r_logic = true
        }
        this.parsingOn = g;
        this.parsedArray = new Array();
        this.setCheckList = "";
        this.nodeAskingCall = ""
    }
    var o = this._globalIdStorageFind(g);
    if (!o) {
        return dhx4.callEvent("onDataStructureError", ["XML refers to not existing parent"])
    }
    this.parsCount = this.parsCount ? (this.parsCount + 1) : 1;
    this.XMLloadingWarning = 1;
    if ((o.childsCount) && (!c) && (!this._edsbps) && (!o._has_top)) {
        var h = 0
    } else {
        var h = 0
    }
    this.npl = 0;
    d.each("item", function (p, n) {
        o.XMLload = 1;
        this._parseItem(p, o, 0, h);
        if ((this._edsbps) && (this.npl == this._edsbpsC)) {
            this._distributedStart(d, n + 1, g, a, o.childsCount);
            return -1
        }
        this.npl++
    }, this, c);
    if (!a) {
        d.each("userdata", function (n) {
            this.setUserData(d.get("id"), n.get("name"), n.content())
        }, this);
        o.XMLload = 1;
        if (this.waitUpdateXML) {
            this.waitUpdateXML = false;
            for (var f = o.childsCount - 1; f >= 0; f--) {
                if (o.childNodes[f]._dmark) {
                    this.deleteItem(o.childNodes[f].id)
                }
            }
        }
        var k = this._globalIdStorageFind(this.parsingOn);
        for (var f = 0; f < this.parsedArray.length; f++) {
            o.htmlNode.childNodes[0].appendChild(this.parsedArray[f])
        }
        this.parsedArray = [];
        this.lastLoadedXMLId = g;
        this.XMLloadingWarning = 0;
        var l = this.setCheckList.split(this.dlmtr);
        for (var e = 0; e < l.length; e++) {
            if (l[e]) {
                this.setCheck(l[e], 1)
            }
        }
        if ((this.XMLsource) && (this.tscheck) && (this.smcheck) && (o.id != this.rootId)) {
            if (o.checkstate === 0) {
                this._setSubChecked(0, o)
            } else {
                if (o.checkstate === 1) {
                    this._setSubChecked(1, o)
                }
            }
        }
        this._redrawFrom(this, null, c);
        if (d.get("order") && d.get("order") != "none") {
            this._reorderBranch(o, d.get("order"), true)
        }
        if (this.nodeAskingCall != "") {
            this.callEvent("onClick", [this.nodeAskingCall, this.getSelectedItemId()])
        }
        if (this._branchUpdate) {
            this._branchUpdateNext(d)
        }
    }
    if (this.parsCount == 1) {
        this.parsingOn = null;
        if (this._srnd && o.id != this.rootId) {
            this.prepareSR(o.id);
            if (this.XMLsource) {
                this.openItem(o.id)
            }
        }
        d.through("item", "open", null, function (n) {
            this.openItem(n.get("id"))
        }, this);
        if ((!this._edsbps) || (!this._edsbpsA.length)) {
            var j = this;
            window.setTimeout(function () {
                j.callEvent("onXLE", [j, g])
            }, 1);
            this.xmlstate = 0
        }
        this.skipLock = false
    }
    this.parsCount--;
    var j = this;
    if (this._edsbps) {
        window.setTimeout(function () {
            j._distributedStep(g)
        }, this._edsbpsD)
    }
    if (!a && this.onXLE) {
        this.onXLE(this, g)
    }
    return this.nodeAskingCall
};
dhtmlXTreeObject.prototype._branchUpdateNext = function (a) {
    a.each("item", function (e) {
        var d = e.get("id");
        if (this._idpull[d] && (!this._idpull[d].XMLload)) {
            return
        }
        this._branchUpdate++;
        this.smartRefreshItem(e.get("id"), e)
    }, this);
    this._branchUpdate--
};
dhtmlXTreeObject.prototype.checkUserData = function (c, d) {
    if ((c.nodeType == 1) && (c.tagName == "userdata")) {
        var a = c.getAttribute("name");
        if ((a) && (c.childNodes[0])) {
            this.setUserData(d, a, c.childNodes[0].data)
        }
    }
};
dhtmlXTreeObject.prototype._redrawFrom = function (j, c, h, d) {
    if (!c) {
        var f = j._globalIdStorageFind(j.lastLoadedXMLId);
        j.lastLoadedXMLId = -1;
        if (!f) {
            return 0
        }
    } else {
        f = c
    }
    var g = 0;
    for (var e = (h ? h - 1 : 0); e < f.childsCount; e++) {
        if ((!this._branchUpdate) || (this._getOpenState(f) == 1)) {
            if ((!c) || (d == 1)) {
                f.childNodes[e].htmlNode.parentNode.parentNode.style.display = ""
            }
        }
        if (f.childNodes[e].openMe == 1) {
            this._openItem(f.childNodes[e]);
            f.childNodes[e].openMe = 0
        }
        j._redrawFrom(j, f.childNodes[e]);
        if (this.childCalc != null) {
            if ((f.childNodes[e].unParsed) || ((!f.childNodes[e].XMLload) && (this.XMLsource))) {
                if (f.childNodes[e]._acc) {
                    f.childNodes[e].span.innerHTML = f.childNodes[e].label + this.htmlcA + f.childNodes[e]._acc + this.htmlcB
                } else {
                    f.childNodes[e].span.innerHTML = f.childNodes[e].label
                }
            }
            if ((f.childNodes[e].childNodes.length) && (this.childCalc)) {
                if (this.childCalc == 1) {
                    f.childNodes[e].span.innerHTML = f.childNodes[e].label + this.htmlcA + f.childNodes[e].childsCount + this.htmlcB
                }
                if (this.childCalc == 2) {
                    var a = f.childNodes[e].childsCount - (f.childNodes[e].pureChilds || 0);
                    if (a) {
                        f.childNodes[e].span.innerHTML = f.childNodes[e].label + this.htmlcA + a + this.htmlcB
                    }
                    if (f.pureChilds) {
                        f.pureChilds++
                    } else {
                        f.pureChilds = 1
                    }
                }
                if (this.childCalc == 3) {
                    f.childNodes[e].span.innerHTML = f.childNodes[e].label + this.htmlcA + f.childNodes[e]._acc + this.htmlcB
                }
                if (this.childCalc == 4) {
                    var a = f.childNodes[e]._acc;
                    if (a) {
                        f.childNodes[e].span.innerHTML = f.childNodes[e].label + this.htmlcA + a + this.htmlcB
                    }
                }
            } else {
                if (this.childCalc == 4) {
                    g++
                }
            }
            g += f.childNodes[e]._acc;
            if (this.childCalc == 3) {
                g++
            }
        }
    }
    if ((!f.unParsed) && ((f.XMLload) || (!this.XMLsource))) {
        f._acc = g
    }
    j._correctLine(f);
    j._correctPlus(f);
    if ((this.childCalc) && (!c)) {
        j._fixChildCountLabel(f)
    }
};
dhtmlXTreeObject.prototype._createSelf = function () {
    var a = document.createElement("div");
    a.className = "containerTableStyle";
    a.style.width = this.width;
    a.style.height = this.height;
    this.parentObject.appendChild(a);
    return a
};
dhtmlXTreeObject.prototype._xcloseAll = function (c) {
    if (c.unParsed) {
        return
    }
    if (this.rootId != c.id) {
        if (!c.htmlNode) {
            return
        }
        var e = c.htmlNode.childNodes[0].childNodes;
        var a = e.length;
        for (var d = 1; d < a; d++) {
            e[d].style.display = "none"
        }
        this._correctPlus(c)
    }
    for (var d = 0; d < c.childsCount; d++) {
        if (c.childNodes[d].childsCount) {
            this._xcloseAll(c.childNodes[d])
        }
    }
};
dhtmlXTreeObject.prototype._xopenAll = function (a) {
    this._HideShow(a, 2);
    for (var c = 0; c < a.childsCount; c++) {
        this._xopenAll(a.childNodes[c])
    }
};
dhtmlXTreeObject.prototype._correctPlus = function (c) {
    if (!c.htmlNode) {
        return
    }
    var d = c.htmlNode.childNodes[0].childNodes[0].childNodes[0].lastChild;
    var f = c.htmlNode.childNodes[0].childNodes[0].childNodes[2].childNodes[0];
    var a = this.lineArray;
    if ((this.XMLsource) && (!c.XMLload)) {
        var a = this.plusArray;
        this._setSrc(f, this.iconURL + c.images[2]);
        if (this._txtimg) {
            return (d.innerHTML = "[+]")
        }
    } else {
        if ((c.childsCount) || (c.unParsed)) {
            if ((c.htmlNode.childNodes[0].childNodes[1]) && (c.htmlNode.childNodes[0].childNodes[1].style.display != "none")) {
                if (!c.wsign) {
                    var a = this.minusArray
                }
                this._setSrc(f, this.iconURL + c.images[1]);
                if (this._txtimg) {
                    return (d.innerHTML = "[-]")
                }
            } else {
                if (!c.wsign) {
                    var a = this.plusArray
                }
                this._setSrc(f, this.iconURL + c.images[2]);
                if (this._txtimg) {
                    return (d.innerHTML = "[+]")
                }
            }
        } else {
            this._setSrc(f, this.iconURL + c.images[0])
        }
    }
    var e = 2;
    if (!c.treeNod.treeLinesOn) {
        this._setSrc(d, this.imPath + a[3])
    } else {
        if (c.parentObject) {
            e = this._getCountStatus(c.id, c.parentObject)
        }
        this._setSrc(d, this.imPath + a[e])
    }
};
dhtmlXTreeObject.prototype._correctLine = function (c) {
    if (!c.htmlNode) {
        return
    }
    var a = c.parentObject;
    if (a) {
        if ((this._getLineStatus(c.id, a) == 0) || (!this.treeLinesOn)) {
            for (var d = 1; d <= c.childsCount; d++) {
                if (!c.htmlNode.childNodes[0].childNodes[d]) {
                    break
                }
                c.htmlNode.childNodes[0].childNodes[d].childNodes[0].style.backgroundImage = "";
                c.htmlNode.childNodes[0].childNodes[d].childNodes[0].style.backgroundRepeat = ""
            }
        } else {
            for (var d = 1; d <= c.childsCount; d++) {
                if (!c.htmlNode.childNodes[0].childNodes[d]) {
                    break
                }
                c.htmlNode.childNodes[0].childNodes[d].childNodes[0].style.backgroundImage = "url(" + this.imPath + this.lineArray[5] + ")";
                c.htmlNode.childNodes[0].childNodes[d].childNodes[0].style.backgroundRepeat = "repeat-y"
            }
        }
    }
};
dhtmlXTreeObject.prototype._getCountStatus = function (c, a) {
    if (a.childsCount <= 1) {
        if (a.id == this.rootId) {
            return 4
        } else {
            return 0
        }
    }
    if (a.childNodes[0].id == c) {
        if (a.id == this.rootId) {
            return 2
        } else {
            return 1
        }
    }
    if (a.childNodes[a.childsCount - 1].id == c) {
        return 0
    }
    return 1
};
dhtmlXTreeObject.prototype._getLineStatus = function (c, a) {
    if (a.childNodes[a.childsCount - 1].id == c) {
        return 0
    }
    return 1
};
dhtmlXTreeObject.prototype._HideShow = function (c, f) {
    if (this._locker && !this.skipLock && this._locker[c.id]) {
        return
    }
    if ((this.XMLsource) && (!c.XMLload)) {
        if (f == 1) {
            return
        }
        c.XMLload = 1;
        this._loadDynXML(c.id);
        return
    }
    if (c.unParsed) {
        this.reParse(c)
    }
    var e = c.htmlNode.childNodes[0].childNodes;
    var a = e.length;
    if (a > 1) {
        if (((e[1].style.display != "none") || (f == 1)) && (f != 2)) {
            this.allTree.childNodes[0].border = "1";
            this.allTree.childNodes[0].border = "0";
            nodestyle = "none"
        } else {
            nodestyle = ""
        }
        for (var d = 1; d < a; d++) {
            e[d].style.display = nodestyle
        }
    }
    this._correctPlus(c)
};
dhtmlXTreeObject.prototype._getOpenState = function (a) {
    if (!a.htmlNode) {
        return 0
    }
    var c = a.htmlNode.childNodes[0].childNodes;
    if (c.length <= 1) {
        return 0
    }
    if (c[1].style.display != "none") {
        return 1
    } else {
        return -1
    }
};
dhtmlXTreeObject.prototype.onRowClick2 = function () {
    var a = this.parentObject.treeNod;
    if (!a.callEvent("onDblClick", [this.parentObject.id, a])) {
        return false
    }
    if ((this.parentObject.closeble) && (this.parentObject.closeble != "0")) {
        a._HideShow(this.parentObject)
    } else {
        a._HideShow(this.parentObject, 2)
    }
    if (a.checkEvent("onOpenEnd")) {
        if (!a.xmlstate) {
            a.callEvent("onOpenEnd", [this.parentObject.id, a._getOpenState(this.parentObject)])
        } else {
            a._oie_onXLE.push(a.onXLE);
            a.onXLE = a._epnFHe
        }
    }
    return false
};
dhtmlXTreeObject.prototype.onRowClick = function () {
    var a = this.parentObject.treeNod;
    if (!a.callEvent("onOpenStart", [this.parentObject.id, a._getOpenState(this.parentObject)])) {
        return 0
    }
    if ((this.parentObject.closeble) && (this.parentObject.closeble != "0")) {
        a._HideShow(this.parentObject)
    } else {
        a._HideShow(this.parentObject, 2)
    }
    if (a.checkEvent("onOpenEnd")) {
        if (!a.xmlstate) {
            a.callEvent("onOpenEnd", [this.parentObject.id, a._getOpenState(this.parentObject)])
        } else {
            a._oie_onXLE.push(a.onXLE);
            a.onXLE = a._epnFHe
        }
    }
};
dhtmlXTreeObject.prototype._epnFHe = function (c, d, a) {
    if (d != this.rootId) {
        this.callEvent("onOpenEnd", [d, c.getOpenState(d)])
    }
    c.onXLE = c._oie_onXLE.pop();
    if (!a && !c._oie_onXLE.length) {
        if (c.onXLE) {
            c.onXLE(c, d)
        }
    }
};
dhtmlXTreeObject.prototype.onRowClickDown = function (c) {
    c = c || window.event;
    var a = this.parentObject.treeNod;
    a._selectItem(this.parentObject, c)
};
dhtmlXTreeObject.prototype.getSelectedItemId = function () {
    var c = new Array();
    for (var a = 0; a < this._selected.length; a++) {
        c[a] = this._selected[a].id
    }
    return (c.join(this.dlmtr))
};
dhtmlXTreeObject.prototype._selectItem = function (h, j) {
    if (this.checkEvent("onSelect")) {
        this._onSSCFold = this.getSelectedItemId()
    }
    if ((!this._amsel) || (!j) || ((!j.ctrlKey) && (!j.metaKey) && (!j.shiftKey))) {
        this._unselectItems()
    }
    if ((h.i_sel) && (this._amsel) && (j) && (j.ctrlKey || j.metaKey)) {
        this._unselectItem(h)
    } else {
        if ((!h.i_sel) && ((!this._amselS) || (this._selected.length == 0) || (this._selected[0].parentObject == h.parentObject))) {
            if ((this._amsel) && (j) && (j.shiftKey) && (this._selected.length != 0) && (this._selected[this._selected.length - 1].parentObject == h.parentObject)) {
                var f = this._getIndex(this._selected[this._selected.length - 1]);
                var d = this._getIndex(h);
                if (d < f) {
                    var l = f;
                    f = d;
                    d = l
                }
                for (var g = f; g <= d; g++) {
                    if (!h.parentObject.childNodes[g].i_sel) {
                        this._markItem(h.parentObject.childNodes[g])
                    }
                }
            } else {
                this._markItem(h)
            }
        }
    }
    if (this.checkEvent("onSelect")) {
        var k = this.getSelectedItemId();
        if (k != this._onSSCFold) {
            this.callEvent("onSelect", [k])
        }
    }
};
dhtmlXTreeObject.prototype._markItem = function (a) {
    if (a.scolor) {
        a.span.style.color = a.scolor
    }
    a.span.className = "selectedTreeRow";
    a.span.parentNode.parentNode.className = "selectedTreeRowFull";
    a.i_sel = true;
    this._selected[this._selected.length] = a
};
dhtmlXTreeObject.prototype.getIndexById = function (c) {
    var a = this._globalIdStorageFind(c);
    if (!a) {
        return null
    }
    return this._getIndex(a)
};
dhtmlXTreeObject.prototype._getIndex = function (a) {
    var d = a.parentObject;
    for (var c = 0; c < d.childsCount; c++) {
        if (d.childNodes[c] == a) {
            return c
        }
    }
};
dhtmlXTreeObject.prototype._unselectItem = function (c) {
    if ((c) && (c.i_sel)) {
        c.span.className = "standartTreeRow";
        c.span.parentNode.parentNode.className = "";
        if (c.acolor) {
            c.span.style.color = c.acolor
        }
        c.i_sel = false;
        for (var a = 0; a < this._selected.length; a++) {
            if (!this._selected[a].i_sel) {
                this._selected.splice(a, 1);
                break
            }
        }
    }
};
dhtmlXTreeObject.prototype._unselectItems = function () {
    for (var a = 0; a < this._selected.length; a++) {
        var c = this._selected[a];
        c.span.className = "standartTreeRow";
        c.span.parentNode.parentNode.className = "";
        if (c.acolor) {
            c.span.style.color = c.acolor
        }
        c.i_sel = false
    }
    this._selected = new Array()
};
dhtmlXTreeObject.prototype.onRowSelect = function (f, d, h) {
    f = f || window.event;
    var c = this.parentObject;
    if (d) {
        c = d.parentObject
    }
    var a = c.treeNod;
    var g = a.getSelectedItemId();
    if ((!f) || (!f.skipUnSel)) {
        a._selectItem(c, f)
    }
    if (!h) {
        if (c.actionHandler) {
            c.actionHandler(c.id, g)
        } else {
            a.callEvent("onClick", [c.id, g])
        }
    }
};
dhtmlXTreeObject.prototype._correctCheckStates = function (f) {
    if (!this.tscheck) {
        return
    }
    if (!f) {
        return
    }
    if (f.id == this.rootId) {
        return
    }
    var d = f.childNodes;
    var c = 0;
    var a = 0;
    if (f.childsCount == 0) {
        return
    }
    for (var e = 0; e < f.childsCount; e++) {
        if (d[e].dscheck) {
            continue
        }
        if (d[e].checkstate == 0) {
            c = 1
        } else {
            if (d[e].checkstate == 1) {
                a = 1
            } else {
                c = 1;
                a = 1;
                break
            }
        }
    }
    if ((c) && (a)) {
        this._setCheck(f, "unsure")
    } else {
        if (c) {
            this._setCheck(f, false)
        } else {
            this._setCheck(f, true)
        }
    }
    this._correctCheckStates(f.parentObject)
};
dhtmlXTreeObject.prototype.onCheckBoxClick = function (a) {
    if (!this.treeNod.callEvent("onBeforeCheck", [this.parentObject.id, this.parentObject.checkstate])) {
        return
    }
    if (this.parentObject.dscheck) {
        return true
    }
    if (this.treeNod.tscheck) {
        if (this.parentObject.checkstate == 1) {
            this.treeNod._setSubChecked(false, this.parentObject)
        } else {
            this.treeNod._setSubChecked(true, this.parentObject)
        }
    } else {
        if (this.parentObject.checkstate == 1) {
            this.treeNod._setCheck(this.parentObject, false)
        } else {
            this.treeNod._setCheck(this.parentObject, true)
        }
    }
    this.treeNod._correctCheckStates(this.parentObject.parentObject);
    return this.treeNod.callEvent("onCheck", [this.parentObject.id, this.parentObject.checkstate])
};
dhtmlXTreeObject.prototype._createItem = function (n, m, j) {
    var o = document.createElement("table");
    o.cellSpacing = 0;
    o.cellPadding = 0;
    o.border = 0;
    if (this.hfMode) {
        o.style.tableLayout = "fixed"
    }
    o.style.margin = 0;
    o.style.padding = 0;
    var h = document.createElement("tbody");
    var l = document.createElement("tr");
    var e = document.createElement("td");
    e.className = "standartTreeImage";
    if (this._txtimg) {
        var f = document.createElement("div");
        e.appendChild(f);
        f.className = "dhx_tree_textSign"
    } else {
        var f = this._getImg(m.id);
        f.border = "0";
        if (f.tagName == "IMG") {
            f.align = "absmiddle"
        }
        e.appendChild(f);
        f.style.padding = 0;
        f.style.margin = 0;
        f.style.width = this.def_line_img_x
    }
    var d = document.createElement("td");
    var k = this._getImg(this.cBROf ? this.rootId : m.id);
    k.checked = 0;
    this._setSrc(k, this.imPath + this.checkArray[0]);
    k.style.width = "18px";
    k.style.height = "18px";
    if (!n) {
        d.style.display = "none"
    }
    d.appendChild(k);
    if ((!this.cBROf) && (k.tagName == "IMG")) {
        k.align = "absmiddle"
    }
    k.onclick = this.onCheckBoxClick;
    k.treeNod = this;
    k.parentObject = m;
    if (!window._KHTMLrv) {
        d.width = "20px"
    } else {
        d.width = "16px"
    }
    var c = document.createElement("td");
    c.className = "standartTreeImage";
    var g = this._getImg(this.timgen ? m.id : this.rootId);
    g.onmousedown = this._preventNsDrag;
    g.ondragstart = this._preventNsDrag;
    g.border = "0";
    if (this._aimgs) {
        g.parentObject = m;
        if (g.tagName == "IMG") {
            g.align = "absmiddle"
        }
        g.onclick = this.onRowSelect
    }
    if (!j) {
        this._setSrc(g, this.iconURL + this.imageArray[0])
    }
    c.appendChild(g);
    g.style.padding = 0;
    g.style.margin = 0;
    if (this.timgen) {
        c.style.width = g.style.width = this.def_img_x;
        g.style.height = this.def_img_y
    } else {
        g.style.width = "0px";
        g.style.height = "0px";
        if (_isOpera || window._KHTMLrv) {
            c.style.display = "none"
        }
    }
    var a = document.createElement("td");
    a.className = "dhxTextCell standartTreeRow";
    m.span = document.createElement("span");
    m.span.className = "standartTreeRow";
    if (this.mlitems) {
        m.span.style.width = this.mlitems;
        m.span.style.display = "block"
    } else {
        a.noWrap = true
    }
    if (dhx4.isIE8) {
        a.style.width = "99999px"
    } else {
        if (!window._KHTMLrv) {
            a.style.width = "100%"
        }
    }
    m.span.innerHTML = m.label;
    //--Rafay edit
    m.span.id = m.label.replace(/\s+/g, '');
    //--
    a.appendChild(m.span);
    a.parentObject = m;
    e.parentObject = m;
    a.onclick = this.onRowSelect;
    e.onclick = this.onRowClick;
    a.ondblclick = this.onRowClick2;
    if (this.ettip) {
        l.title = m.label
    }
    if (this.dragAndDropOff) {
        if (this._aimgs) {
            this.dragger.addDraggableItem(c, this);
            c.parentObject = m
        }
        this.dragger.addDraggableItem(a, this)
    }
    m.span.style.paddingLeft = "5px";
    m.span.style.paddingRight = "5px";
    a.style.verticalAlign = "";
    a.style.fontSize = "10pt";
    l.appendChild(e);
    l.appendChild(d);
    l.appendChild(c);
    l.appendChild(a);
    h.appendChild(l);
    o.appendChild(h);
    if (this.ehlt || this.checkEvent("onMouseIn") || this.checkEvent("onMouseOut")) {
        l.onmousemove = this._itemMouseIn;
        l[(_isIE) ? "onmouseleave" : "onmouseout"] = this._itemMouseOut
    }
    return o
};
dhtmlXTreeObject.prototype.setImagePath = function (a) {
    this.imPath = a;
    this.iconURL = a
};
dhtmlXTreeObject.prototype.setIconPath = function (a) {
    this.iconURL = a
};
dhtmlXTreeObject.prototype._getLeafCount = function (e) {
    var d = 0;
    for (var c = 0; c < e.childsCount; c++) {
        if (e.childNodes[c].childsCount == 0) {
            d++
        }
    }
    return d
};
dhtmlXTreeObject.prototype._getChildCounterValue = function (c) {
    var a = this._globalIdStorageFind(c);
    if (!a) {
        return 0
    }
    if ((a.unParsed) || ((!a.XMLload) && (this.XMLsource))) {
        return a._acc
    }
    switch (this.childCalc) {
        case 1:
            return a.childsCount;
            break;
        case 2:
            return this._getLeafCount(a);
            break;
        case 3:
            return a._acc;
            break;
        case 4:
            return a._acc;
            break
    }
};
dhtmlXTreeObject.prototype._fixChildCountLabel = function (g, e) {
    if (this.childCalc == null) {
        return
    }
    if ((g.unParsed) || ((!g.XMLload) && (this.XMLsource))) {
        if (g._acc) {
            g.span.innerHTML = g.label + this.htmlcA + g._acc + this.htmlcB
        } else {
            g.span.innerHTML = g.label
        }
        return
    }
    switch (this.childCalc) {
        case 1:
            if (g.childsCount != 0) {
                g.span.innerHTML = g.label + this.htmlcA + g.childsCount + this.htmlcB
            } else {
                g.span.innerHTML = g.label
            }
            break;
        case 2:
            var f = this._getLeafCount(g);
            if (f != 0) {
                g.span.innerHTML = g.label + this.htmlcA + f + this.htmlcB
            } else {
                g.span.innerHTML = g.label
            }
            break;
        case 3:
            if (g.childsCount != 0) {
                var d = 0;
                for (var c = 0; c < g.childsCount; c++) {
                    if (!g.childNodes[c]._acc) {
                        g.childNodes[c]._acc = 0
                    }
                    d += g.childNodes[c]._acc * 1
                }
                d += g.childsCount * 1;
                g.span.innerHTML = g.label + this.htmlcA + d + this.htmlcB;
                g._acc = d
            } else {
                g.span.innerHTML = g.label;
                g._acc = 0
            }
            if ((g.parentObject) && (g.parentObject != this.htmlNode)) {
                this._fixChildCountLabel(g.parentObject)
            }
            break;
        case 4:
            if (g.childsCount != 0) {
                var d = 0;
                for (var c = 0; c < g.childsCount; c++) {
                    if (!g.childNodes[c]._acc) {
                        g.childNodes[c]._acc = 1
                    }
                    d += g.childNodes[c]._acc * 1
                }
                g.span.innerHTML = g.label + this.htmlcA + d + this.htmlcB;
                g._acc = d
            } else {
                g.span.innerHTML = g.label;
                g._acc = 1
            }
            if ((g.parentObject) && (g.parentObject != this.htmlNode)) {
                this._fixChildCountLabel(g.parentObject)
            }
            break
    }
};
dhtmlXTreeObject.prototype.setChildCalcMode = function (a) {
    switch (a) {
        case "child":
            this.childCalc = 1;
            break;
        case "leafs":
            this.childCalc = 2;
            break;
        case "childrec":
            this.childCalc = 3;
            break;
        case "leafsrec":
            this.childCalc = 4;
            break;
        case "disabled":
            this.childCalc = null;
            break;
        default:
            this.childCalc = 4
    }
};
dhtmlXTreeObject.prototype.setChildCalcHTML = function (c, a) {
    this.htmlcA = c;
    this.htmlcB = a
};
dhtmlXTreeObject.prototype.setOnRightClickHandler = function (a) {
    this.attachEvent("onRightClick", a)
};
dhtmlXTreeObject.prototype.setOnClickHandler = function (a) {
    this.attachEvent("onClick", a)
};
dhtmlXTreeObject.prototype.setOnSelectStateChange = function (a) {
    this.attachEvent("onSelect", a)
};
dhtmlXTreeObject.prototype.setXMLAutoLoading = function (a) {
    this.XMLsource = a
};
dhtmlXTreeObject.prototype.setOnCheckHandler = function (a) {
    this.attachEvent("onCheck", a)
};
dhtmlXTreeObject.prototype.setOnOpenHandler = function (a) {
    this.attachEvent("onOpenStart", a)
};
dhtmlXTreeObject.prototype.setOnOpenStartHandler = function (a) {
    this.attachEvent("onOpenStart", a)
};
dhtmlXTreeObject.prototype.setOnOpenEndHandler = function (a) {
    this.attachEvent("onOpenEnd", a)
};
dhtmlXTreeObject.prototype.setOnDblClickHandler = function (a) {
    this.attachEvent("onDblClick", a)
};
dhtmlXTreeObject.prototype.openAllItems = function (c) {
    var a = this._globalIdStorageFind(c);
    if (!a) {
        return 0
    }
    this._xopenAll(a)
};
dhtmlXTreeObject.prototype.getOpenState = function (c) {
    var a = this._globalIdStorageFind(c);
    if (!a) {
        return ""
    }
    return this._getOpenState(a)
};
dhtmlXTreeObject.prototype.closeAllItems = function (c) {
    if (c === window.undefined) {
        c = this.rootId
    }
    var a = this._globalIdStorageFind(c);
    if (!a) {
        return 0
    }
    this._xcloseAll(a);
    this.allTree.childNodes[0].border = "1";
    this.allTree.childNodes[0].border = "0"
};
dhtmlXTreeObject.prototype.setUserData = function (e, c, d) {
    var a = this._globalIdStorageFind(e, 0, true);
    if (!a) {
        return
    }
    if (c == "hint") {
        a.htmlNode.childNodes[0].childNodes[0].title = d
    }
    if (typeof (a.userData["t_" + c]) == "undefined") {
        if (!a._userdatalist) {
            a._userdatalist = c
        } else {
            a._userdatalist += "," + c
        }
    }
    a.userData["t_" + c] = d
};
dhtmlXTreeObject.prototype.getUserData = function (d, c) {
    var a = this._globalIdStorageFind(d, 0, true);
    if (!a) {
        return
    }
    return a.userData["t_" + c]
};
dhtmlXTreeObject.prototype.getItemColor = function (d) {
    var a = this._globalIdStorageFind(d);
    if (!a) {
        return 0
    }
    var c = new Object();
    if (a.acolor) {
        c.acolor = a.acolor
    }
    if (a.scolor) {
        c.scolor = a.scolor
    }
    return c
};
dhtmlXTreeObject.prototype.setItemColor = function (d, c, e) {
    if ((d) && (d.span)) {
        var a = d
    } else {
        var a = this._globalIdStorageFind(d)
    }
    if (!a) {
        return 0
    } else {
        if (a.i_sel) {
            if (e || c) {
                a.span.style.color = e || c
            }
        } else {
            if (c) {
                a.span.style.color = c
            }
        }
        if (e) {
            a.scolor = e
        }
        if (c) {
            a.acolor = c
        }
    }
};
dhtmlXTreeObject.prototype.getItemText = function (c) {
    var a = this._globalIdStorageFind(c);
    if (!a) {
        return 0
    }
    return (a.htmlNode.childNodes[0].childNodes[0].childNodes[3].childNodes[0].innerHTML)
};
dhtmlXTreeObject.prototype.getParentId = function (c) {
    var a = this._globalIdStorageFind(c);
    if ((!a) || (!a.parentObject)) {
        return ""
    }
    return a.parentObject.id
};
dhtmlXTreeObject.prototype.changeItemId = function (c, d) {
    if (c == d) {
        return
    }
    var a = this._globalIdStorageFind(c);
    if (!a) {
        return 0
    }
    a.id = d;
    a.span.contextMenuId = d;
    this._idpull[d] = this._idpull[c];
    delete this._idpull[c]
};
dhtmlXTreeObject.prototype.doCut = function () {
    if (this.nodeCut) {
        this.clearCut()
    }
    this.nodeCut = (new Array()).concat(this._selected);
    for (var a = 0; a < this.nodeCut.length; a++) {
        var c = this.nodeCut[a];
        c._cimgs = new Array();
        c._cimgs[0] = c.images[0];
        c._cimgs[1] = c.images[1];
        c._cimgs[2] = c.images[2];
        c.images[0] = c.images[1] = c.images[2] = this.cutImage;
        this._correctPlus(c)
    }
};
dhtmlXTreeObject.prototype.doPaste = function (d) {
    var a = this._globalIdStorageFind(d);
    if (!a) {
        return 0
    }
    for (var c = 0; c < this.nodeCut.length; c++) {
        if (this._checkPNodes(a, this.nodeCut[c])) {
            continue
        }
        this._moveNode(this.nodeCut[c], a)
    }
    this.clearCut()
};
dhtmlXTreeObject.prototype.clearCut = function () {
    for (var a = 0; a < this.nodeCut.length; a++) {
        var c = this.nodeCut[a];
        c.images[0] = c._cimgs[0];
        c.images[1] = c._cimgs[1];
        c.images[2] = c._cimgs[2];
        this._correctPlus(c)
    }
    this.nodeCut = new Array()
};
dhtmlXTreeObject.prototype._moveNode = function (a, c) {
    var g = this.dadmodec;
    if (g == 1) {
        var f = c;
        if (this.dadmodefix < 0) {
            while (true) {
                f = this._getPrevNode(f);
                if ((f == -1)) {
                    f = this.htmlNode;
                    break
                }
                if ((f.tr == 0) || (f.tr.style.display == "") || (!f.parentObject)) {
                    break
                }
            }
            var e = f;
            var d = c
        } else {
            if ((f.tr) && (f.tr.nextSibling) && (f.tr.nextSibling.nodem) && (this._getOpenState(f) < 1)) {
                f = f.tr.nextSibling.nodem
            } else {
                if (this._getOpenState(f) < 1) {
                    f = this.htmlNode
                } else {
                    f = this._getNextNode(f);
                    if ((f == -1)) {
                        f = this.htmlNode
                    }
                }
            }
            var d = f;
            var e = c
        }
        if (this._getNodeLevel(e, 0) > this._getNodeLevel(d, 0)) {
            if (!this.dropLower) {
                return this._moveNodeTo(a, e.parentObject)
            } else {
                if (d.id != this.rootId) {
                    return this._moveNodeTo(a, d.parentObject, d)
                } else {
                    return this._moveNodeTo(a, this.htmlNode, null)
                }
            }
        } else {
            return this._moveNodeTo(a, d.parentObject, d)
        }
    } else {
        return this._moveNodeTo(a, c)
    }
};
dhtmlXTreeObject.prototype._fixNodesCollection = function (j, g) {
    var c = 0;
    var e = 0;
    var h = j.childNodes;
    var a = j.childsCount - 1;
    if (g == h[a]) {
        return
    }
    for (var f = 0; f < a; f++) {
        if (h[f] == h[a]) {
            h[f] = h[f + 1];
            h[f + 1] = h[a]
        }
    }
    for (var f = 0; f < a + 1; f++) {
        if (c) {
            var d = h[f];
            h[f] = c;
            c = d
        } else {
            if (h[f] == g) {
                c = h[f];
                h[f] = h[a]
            }
        }
    }
};
dhtmlXTreeObject.prototype._recreateBranch = function (g, j, f, a) {
    var c;
    var k = "";
    if (f) {
        for (c = 0; c < j.childsCount; c++) {
            if (j.childNodes[c] == f) {
                break
            }
        }
        if (c != 0) {
            f = j.childNodes[c - 1]
        } else {
            k = "TOP";
            f = ""
        }
    }
    var d = this._onradh;
    this._onradh = null;
    var h = this._attachChildNode(j, g.id, g.label, 0, g.images[0], g.images[1], g.images[2], k, 0, f);
    h._userdatalist = g._userdatalist;
    h.userData = g.userData.clone();
    if (g._attrs) {
        h._attrs = {};
        for (var e in g._attrs) {
            h._attrs[e] = g._attrs[e]
        }
    }
    h.XMLload = g.XMLload;
    if (d) {
        this._onradh = d;
        this._onradh(h.id)
    }
    if (g.treeNod.dpcpy) {
        g.treeNod._globalIdStorageFind(g.id)
    } else {
        h.unParsed = g.unParsed
    }
    this._correctPlus(h);
    for (var c = 0; c < g.childsCount; c++) {
        this._recreateBranch(g.childNodes[c], h, 0, 1)
    }
    if ((!a) && (this.childCalc)) {
        this._redrawFrom(this, j)
    }
    return h
};
dhtmlXTreeObject.prototype._moveNodeTo = function (n, p, m) {
    if (n.treeNod._nonTrivialNode) {
        return n.treeNod._nonTrivialNode(this, p, m, n)
    }
    if (this._checkPNodes(p, n)) {
        return false
    }
    if (p.mytype) {
        var h = (n.treeNod.lWin != p.lWin)
    } else {
        var h = (n.treeNod.lWin != p.treeNod.lWin)
    }
    if (!this.callEvent("onDrag", [n.id, p.id, (m ? m.id : null), n.treeNod, p.treeNod])) {
        return false
    }
    if ((p.XMLload == 0) && (this.XMLsource)) {
        p.XMLload = 1;
        this._loadDynXML(p.id)
    }
    this.openItem(p.id);
    var d = n.treeNod;
    var k = n.parentObject.childsCount;
    var l = n.parentObject;
    if ((h) || (d.dpcpy)) {
        var e = n.id;
        n = this._recreateBranch(n, p, m);
        if (!d.dpcpy) {
            d.deleteItem(e)
        }
    } else {
        var f = p.childsCount;
        var o = p.childNodes;
        if (f == 0) {
            p._open = true
        }
        d._unselectItem(n);
        o[f] = n;
        n.treeNod = p.treeNod;
        p.childsCount++;
        var j = this._drawNewTr(o[f].htmlNode);
        if (!m) {
            p.htmlNode.childNodes[0].appendChild(j);
            if (this.dadmode == 1) {
                this._fixNodesCollection(p, m)
            }
        } else {
            p.htmlNode.childNodes[0].insertBefore(j, m.tr);
            this._fixNodesCollection(p, m);
            o = p.childNodes
        }
    }
    if ((!d.dpcpy) && (!h)) {
        var a = n.tr;
        if ((document.all) && (navigator.appVersion.search(/MSIE\ 5\.0/gi) != -1)) {
            window.setTimeout(function () {
                a.parentNode.removeChild(a)
            }, 250)
        } else {
            n.parentObject.htmlNode.childNodes[0].removeChild(n.tr)
        }
        if ((!m) || (p != n.parentObject)) {
            for (var g = 0; g < l.childsCount; g++) {
                if (l.childNodes[g].id == n.id) {
                    l.childNodes[g] = 0;
                    break
                }
            }
        } else {
            l.childNodes[l.childsCount - 1] = 0
        }
        d._compressChildList(l.childsCount, l.childNodes);
        l.childsCount--
    }
    if ((!h) && (!d.dpcpy)) {
        n.tr = j;
        j.nodem = n;
        n.parentObject = p;
        if (d != p.treeNod) {
            if (n.treeNod._registerBranch(n, d)) {
                return
            }
            this._clearStyles(n);
            this._redrawFrom(this, n.parentObject);
            if (this._onradh) {
                this._onradh(n.id)
            }
        }
        this._correctPlus(p);
        this._correctLine(p);
        this._correctLine(n);
        this._correctPlus(n);
        if (m) {
            this._correctPlus(m)
        } else {
            if (p.childsCount >= 2) {
                this._correctPlus(o[p.childsCount - 2]);
                this._correctLine(o[p.childsCount - 2])
            }
        }
        this._correctPlus(o[p.childsCount - 1]);
        if (this.tscheck) {
            this._correctCheckStates(p)
        }
        if (d.tscheck) {
            d._correctCheckStates(l)
        }
    }
    if (k > 1) {
        d._correctPlus(l.childNodes[k - 2]);
        d._correctLine(l.childNodes[k - 2])
    }
    d._correctPlus(l);
    d._correctLine(l);
    this._fixChildCountLabel(p);
    d._fixChildCountLabel(l);
    this.callEvent("onDrop", [n.id, p.id, (m ? m.id : null), d, p.treeNod]);
    return n.id
};
dhtmlXTreeObject.prototype._clearStyles = function (a) {
    if (!a.htmlNode) {
        return
    }
    var e = a.htmlNode.childNodes[0].childNodes[0].childNodes[1];
    var c = e.nextSibling.nextSibling;
    a.span.innerHTML = a.label;
    a.i_sel = false;
    if (a._aimgs) {
        this.dragger.removeDraggableItem(e.nextSibling)
    }
    if (this.checkBoxOff) {
        e.childNodes[0].style.display = "";
        e.childNodes[0].onclick = this.onCheckBoxClick;
        this._setSrc(e.childNodes[0], this.imPath + this.checkArray[a.checkstate])
    } else {
        e.style.display = "none"
    }
    e.childNodes[0].treeNod = this;
    this.dragger.removeDraggableItem(c);
    if (this.dragAndDropOff) {
        this.dragger.addDraggableItem(c, this)
    }
    if (this._aimgs) {
        this.dragger.addDraggableItem(e.nextSibling, this)
    }
    c.childNodes[0].className = "standartTreeRow";
    c.parentNode.className = "";
    c.onclick = this.onRowSelect;
    c.ondblclick = this.onRowClick2;
    e.previousSibling.onclick = this.onRowClick;
    this._correctLine(a);
    this._correctPlus(a);
    for (var d = 0; d < a.childsCount; d++) {
        this._clearStyles(a.childNodes[d])
    }
};
dhtmlXTreeObject.prototype._registerBranch = function (c, a) {
    if (a) {
        a._globalIdStorageSub(c.id)
    }
    c.id = this._globalIdStorageAdd(c.id, c);
    c.treeNod = this;
    for (var d = 0; d < c.childsCount; d++) {
        this._registerBranch(c.childNodes[d], a)
    }
    return 0
};
dhtmlXTreeObject.prototype.enableThreeStateCheckboxes = function (a) {
    this.tscheck = dhx4.s2b(a)
};
dhtmlXTreeObject.prototype.setOnMouseInHandler = function (a) {
    this.ehlt = true;
    this.attachEvent("onMouseIn", a)
};
dhtmlXTreeObject.prototype.setOnMouseOutHandler = function (a) {
    this.ehlt = true;
    this.attachEvent("onMouseOut", a)
};
dhtmlXTreeObject.prototype.enableMercyDrag = function (a) {
    this.dpcpy = dhx4.s2b(a)
};
dhtmlXTreeObject.prototype.enableTreeImages = function (a) {
    this.timgen = dhx4.s2b(a)
};
dhtmlXTreeObject.prototype.enableFixedMode = function (a) {
    this.hfMode = dhx4.s2b(a)
};
dhtmlXTreeObject.prototype.enableCheckBoxes = function (c, a) {
    this.checkBoxOff = dhx4.s2b(c);
    this.cBROf = (!(this.checkBoxOff || dhx4.s2b(a)))
};
dhtmlXTreeObject.prototype.setStdImages = function (a, d, c) {
    this.imageArray[0] = a;
    this.imageArray[1] = d;
    this.imageArray[2] = c
};
dhtmlXTreeObject.prototype.enableTreeLines = function (a) {
    this.treeLinesOn = dhx4.s2b(a)
};
dhtmlXTreeObject.prototype.setImageArrays = function (e, a, g, f, d, c) {
    switch (e) {
        case "plus":
            this.plusArray[0] = a;
            this.plusArray[1] = g;
            this.plusArray[2] = f;
            this.plusArray[3] = d;
            this.plusArray[4] = c;
            break;
        case "minus":
            this.minusArray[0] = a;
            this.minusArray[1] = g;
            this.minusArray[2] = f;
            this.minusArray[3] = d;
            this.minusArray[4] = c;
            break
    }
};
dhtmlXTreeObject.prototype.openItem = function (c) {
    this.skipLock = true;
    var a = this._globalIdStorageFind(c);
    if (!a) {
        return 0
    } else {
        return this._openItem(a)
    }
    this.skipLock = false
};
dhtmlXTreeObject.prototype._openItem = function (a) {
    var c = this._getOpenState(a);
    if ((c < 0) || (((this.XMLsource) && (!a.XMLload)))) {
        if (!this.callEvent("onOpenStart", [a.id, c])) {
            return 0
        }
        this._HideShow(a, 2);
        if (this.checkEvent("onOpenEnd")) {
            if (this.onXLE == this._epnFHe) {
                this._epnFHe(this, a.id, true)
            }
            if (!this.xmlstate || !this.XMLsource) {
                this.callEvent("onOpenEnd", [a.id, this._getOpenState(a)])
            } else {
                this._oie_onXLE.push(this.onXLE);
                this.onXLE = this._epnFHe
            }
        }
    } else {
        if (this._srnd) {
            this._HideShow(a, 2)
        }
    }
    if (a.parentObject && !this._skip_open_parent) {
        this._openItem(a.parentObject)
    }
};
dhtmlXTreeObject.prototype.closeItem = function (c) {
    if (this.rootId == c) {
        return 0
    }
    this.skipLock = true;
    var a = this._globalIdStorageFind(c);
    if (!a) {
        return 0
    }
    if (a.closeble) {
        this._HideShow(a, 1)
    }
    this.skipLock = false
};
dhtmlXTreeObject.prototype.getLevel = function (c) {
    var a = this._globalIdStorageFind(c);
    if (!a) {
        return 0
    }
    return this._getNodeLevel(a, 0)
};
dhtmlXTreeObject.prototype.setItemCloseable = function (d, a) {
    a = dhx4.s2b(a);
    if ((d) && (d.span)) {
        var c = d
    } else {
        var c = this._globalIdStorageFind(d)
    }
    if (!c) {
        return 0
    }
    c.closeble = a
};
dhtmlXTreeObject.prototype._getNodeLevel = function (a, c) {
    if (a.parentObject) {
        return this._getNodeLevel(a.parentObject, c + 1)
    }
    return (c)
};
dhtmlXTreeObject.prototype.hasChildren = function (c) {
    var a = this._globalIdStorageFind(c);
    if (!a) {
        return 0
    } else {
        if ((this.XMLsource) && (!a.XMLload)) {
            return true
        } else {
            return a.childsCount
        }
    }
};
dhtmlXTreeObject.prototype._getLeafCount = function (e) {
    var d = 0;
    for (var c = 0; c < e.childsCount; c++) {
        if (e.childNodes[c].childsCount == 0) {
            d++
        }
    }
    return d
};
dhtmlXTreeObject.prototype.setItemText = function (e, d, c) {
    var a = this._globalIdStorageFind(e);
    if (!a) {
        return 0
    }
    a.label = d;
    a.span.innerHTML = d;
    if (this.childCalc) {
        this._fixChildCountLabel(a)
    }
    a.span.parentNode.parentNode.title = c || ""
};
dhtmlXTreeObject.prototype.getItemTooltip = function (c) {
    var a = this._globalIdStorageFind(c);
    if (!a) {
        return ""
    }
    return (a.span.parentNode.parentNode._dhx_title || a.span.parentNode.parentNode.title || "")
};
dhtmlXTreeObject.prototype.refreshItem = function (c) {
    if (!c) {
        c = this.rootId
    }
    var a = this._globalIdStorageFind(c);
    this._dynDeleteBranches[c] = (this._dynDeleteBranches[c] || 0) + 1;
    this._loadDynXML(c)
};
dhtmlXTreeObject.prototype.setItemImage2 = function (e, a, f, d) {
    var c = this._globalIdStorageFind(e);
    if (!c) {
        return 0
    }
    c.images[1] = f;
    c.images[2] = d;
    c.images[0] = a;
    this._correctPlus(c)
};
dhtmlXTreeObject.prototype.setItemImage = function (d, a, e) {
    var c = this._globalIdStorageFind(d);
    if (!c) {
        return 0
    }
    if (e) {
        c.images[1] = a;
        c.images[2] = e
    } else {
        c.images[0] = a
    }
    this._correctPlus(c)
};
dhtmlXTreeObject.prototype.getSubItems = function (d) {
    var a = this._globalIdStorageFind(d, 0, 1);
    if (!a) {
        return 0
    }
    if (a.unParsed) {
        return (this._getSubItemsXML(a.unParsed))
    }
    var c = "";
    for (i = 0; i < a.childsCount; i++) {
        if (!c) {
            c = "" + a.childNodes[i].id
        } else {
            c += this.dlmtr + a.childNodes[i].id
        }
    }
    return c
};
dhtmlXTreeObject.prototype._getAllScraggyItems = function (d) {
    var e = "";
    for (var c = 0; c < d.childsCount; c++) {
        if ((d.childNodes[c].unParsed) || (d.childNodes[c].childsCount > 0)) {
            if (d.childNodes[c].unParsed) {
                var a = this._getAllScraggyItemsXML(d.childNodes[c].unParsed, 1)
            } else {
                var a = this._getAllScraggyItems(d.childNodes[c])
            }
            if (a) {
                if (e) {
                    e += this.dlmtr + a
                } else {
                    e = a
                }
            }
        } else {
            if (!e) {
                e = "" + d.childNodes[c].id
            } else {
                e += this.dlmtr + d.childNodes[c].id
            }
        }
    }
    return e
};
dhtmlXTreeObject.prototype._getAllFatItems = function (d) {
    var e = "";
    for (var c = 0; c < d.childsCount; c++) {
        if ((d.childNodes[c].unParsed) || (d.childNodes[c].childsCount > 0)) {
            if (!e) {
                e = "" + d.childNodes[c].id
            } else {
                e += this.dlmtr + d.childNodes[c].id
            }
            if (d.childNodes[c].unParsed) {
                var a = this._getAllFatItemsXML(d.childNodes[c].unParsed, 1)
            } else {
                var a = this._getAllFatItems(d.childNodes[c])
            }
            if (a) {
                e += this.dlmtr + a
            }
        }
    }
    return e
};
dhtmlXTreeObject.prototype._getAllSubItems = function (g, f, e) {
    if (e) {
        c = e
    } else {
        var c = this._globalIdStorageFind(g)
    }
    if (!c) {
        return 0
    }
    f = "";
    for (var d = 0; d < c.childsCount; d++) {
        if (!f) {
            f = "" + c.childNodes[d].id
        } else {
            f += this.dlmtr + c.childNodes[d].id
        }
        var a = this._getAllSubItems(0, f, c.childNodes[d]);
        if (a) {
            f += this.dlmtr + a
        }
    }
    if (c.unParsed) {
        f = this._getAllSubItemsXML(g, f, c.unParsed)
    }
    return f
};
dhtmlXTreeObject.prototype.selectItem = function (e, d, c) {
    d = dhx4.s2b(d);
    var a = this._globalIdStorageFind(e);
    if ((!a) || (!a.parentObject)) {
        return 0
    }
    if (this.XMLloadingWarning) {
        a.parentObject.openMe = 1
    } else {
        this._openItem(a.parentObject)
    }
    var f = null;
    if (c) {
        f = new Object;
        f.ctrlKey = true;
        if (a.i_sel) {
            f.skipUnSel = true
        }
    }
    if (d) {
        this.onRowSelect(f, a.htmlNode.childNodes[0].childNodes[0].childNodes[3], false)
    } else {
        this.onRowSelect(f, a.htmlNode.childNodes[0].childNodes[0].childNodes[3], true)
    }
};
dhtmlXTreeObject.prototype.getSelectedItemText = function () {
    var c = new Array();
    for (var a = 0; a < this._selected.length; a++) {
        c[a] = this._selected[a].span.innerHTML
    }
    return (c.join(this.dlmtr))
};
dhtmlXTreeObject.prototype._compressChildList = function (a, d) {
    a--;
    for (var c = 0; c < a; c++) {
        if (d[c] == 0) {
            d[c] = d[c + 1];
            d[c + 1] = 0
        }
    }
};
dhtmlXTreeObject.prototype._deleteNode = function (h, f, k) {
    if ((!f) || (!f.parentObject)) {
        return 0
    }
    var a = 0;
    var c = 0;
    if (f.tr.nextSibling) {
        a = f.tr.nextSibling.nodem
    }
    if (f.tr.previousSibling) {
        c = f.tr.previousSibling.nodem
    }
    var g = f.parentObject;
    var d = g.childsCount;
    var j = g.childNodes;
    for (var e = 0; e < d; e++) {
        if (j[e].id == h) {
            if (!k) {
                g.htmlNode.childNodes[0].removeChild(j[e].tr)
            }
            j[e] = 0;
            break
        }
    }
    this._compressChildList(d, j);
    if (!k) {
        g.childsCount--
    }
    if (a) {
        this._correctPlus(a);
        this._correctLine(a)
    }
    if (c) {
        this._correctPlus(c);
        this._correctLine(c)
    }
    if (this.tscheck) {
        this._correctCheckStates(g)
    }
    if (!k) {
        this._globalIdStorageRecSub(f)
    }
};
dhtmlXTreeObject.prototype.setCheck = function (d, c) {
    var a = this._globalIdStorageFind(d, 0, 1);
    if (!a) {
        return
    }
    if (c === "unsure") {
        this._setCheck(a, c)
    } else {
        c = dhx4.s2b(c);
        if ((this.tscheck) && (this.smcheck)) {
            this._setSubChecked(c, a)
        } else {
            this._setCheck(a, c)
        }
    }
    if (this.smcheck) {
        this._correctCheckStates(a.parentObject)
    }
};
dhtmlXTreeObject.prototype._setCheck = function (a, d) {
    if (!a) {
        return
    }
    if (((a.parentObject._r_logic) || (this._frbtr)) && (d)) {
        if (this._frbtrs) {
            if (this._frbtrL) {
                this.setCheck(this._frbtrL.id, 0)
            }
            this._frbtrL = a
        } else {
            for (var c = 0; c < a.parentObject.childsCount; c++) {
                this._setCheck(a.parentObject.childNodes[c], 0)
            }
        }
    }
    var e = a.htmlNode.childNodes[0].childNodes[0].childNodes[1].childNodes[0];
    if (d == "unsure") {
        a.checkstate = 2
    } else {
        if (d) {
            a.checkstate = 1
        } else {
            a.checkstate = 0
        }
    }
    if (a.dscheck) {
        a.checkstate = a.dscheck
    }
    this._setSrc(e, this.imPath + ((a.parentObject._r_logic || this._frbtr) ? this.radioArray : this.checkArray)[a.checkstate])
};
dhtmlXTreeObject.prototype.setSubChecked = function (d, c) {
    var a = this._globalIdStorageFind(d);
    this._setSubChecked(c, a);
    this._correctCheckStates(a.parentObject)
};
dhtmlXTreeObject.prototype._setSubChecked = function (d, a) {
    d = dhx4.s2b(d);
    if (!a) {
        return
    }
    if (((a.parentObject._r_logic) || (this._frbtr)) && (d)) {
        for (var c = 0; c < a.parentObject.childsCount; c++) {
            this._setSubChecked(0, a.parentObject.childNodes[c])
        }
    }
    if (a.unParsed) {
        this._setSubCheckedXML(d, a.unParsed)
    }
    if (a._r_logic || this._frbtr) {
        this._setSubChecked(d, a.childNodes[0])
    } else {
        for (var c = 0; c < a.childsCount; c++) {
            this._setSubChecked(d, a.childNodes[c])
        }
    }
    var e = a.htmlNode.childNodes[0].childNodes[0].childNodes[1].childNodes[0];
    if (d) {
        a.checkstate = 1
    } else {
        a.checkstate = 0
    }
    if (a.dscheck) {
        a.checkstate = a.dscheck
    }
    this._setSrc(e, this.imPath + ((a.parentObject._r_logic || this._frbtr) ? this.radioArray : this.checkArray)[a.checkstate])
};
dhtmlXTreeObject.prototype.isItemChecked = function (c) {
    var a = this._globalIdStorageFind(c);
    if (!a) {
        return
    }
    return a.checkstate
};
dhtmlXTreeObject.prototype.deleteChildItems = function (e) {
    var a = this._globalIdStorageFind(e);
    if (!a) {
        return
    }
    var c = a.childsCount;
    for (var d = 0; d < c; d++) {
        this._deleteNode(a.childNodes[0].id, a.childNodes[0])
    }
};
dhtmlXTreeObject.prototype.deleteItem = function (d, a) {
    if ((!this._onrdlh) || (this._onrdlh(d))) {
        var c = this._deleteItem(d, a);
        if (c) {
            this._fixChildCountLabel(c)
        }
    }
    this.allTree.childNodes[0].border = "1";
    this.allTree.childNodes[0].border = "0"
};
dhtmlXTreeObject.prototype._deleteItem = function (g, c, f) {
    c = dhx4.s2b(c);
    var a = this._globalIdStorageFind(g);
    if (!a) {
        return
    }
    var d = this.getParentId(g);
    var e = a.parentObject;
    this._deleteNode(g, a, f);
    if (this._editCell && this._editCell.id == g) {
        this._editCell = null
    }
    this._correctPlus(e);
    this._correctLine(e);
    if ((c) && (d != this.rootId)) {
        this.selectItem(d, 1)
    }
    return e
};
dhtmlXTreeObject.prototype._globalIdStorageRecSub = function (a) {
    for (var c = 0; c < a.childsCount; c++) {
        this._globalIdStorageRecSub(a.childNodes[c]);
        this._globalIdStorageSub(a.childNodes[c].id)
    }
    this._globalIdStorageSub(a.id);
    var d = a;
    d.span = null;
    d.tr.nodem = null;
    d.tr = null;
    d.htmlNode = null
};
dhtmlXTreeObject.prototype.insertNewNext = function (j, m, l, d, g, f, e, c, a) {
    var h = this._globalIdStorageFind(j);
    if ((!h) || (!h.parentObject)) {
        return (0)
    }
    var k = this._attachChildNode(0, m, l, d, g, f, e, c, a, h);
    if ((!this.XMLloadingWarning) && (this.childCalc)) {
        this._fixChildCountLabel(h.parentObject)
    }
    return k
};
dhtmlXTreeObject.prototype.getItemIdByIndex = function (d, a) {
    var c = this._globalIdStorageFind(d);
    if ((!c) || (a >= c.childsCount)) {
        return null
    }
    return c.childNodes[a].id
};
dhtmlXTreeObject.prototype.getChildItemIdByIndex = function (d, a) {
    var c = this._globalIdStorageFind(d);
    if ((!c) || (a >= c.childsCount)) {
        return null
    }
    return c.childNodes[a].id
};
dhtmlXTreeObject.prototype.setDragHandler = function (a) {
    this.attachEvent("onDrag", a)
};
dhtmlXTreeObject.prototype._clearMove = function () {
    if (this._lastMark) {
        this._lastMark.className = this._lastMark.className.replace(/dragAndDropRow/g, "");
        this._lastMark = null
    }
    this.selectionBar.style.display = "none";
    this.allTree.className = this.allTree.className.replace(" selectionBox", "")
};
dhtmlXTreeObject.prototype.enableDragAndDrop = function (c, a) {
    if (c == "temporary_disabled") {
        this.dADTempOff = false;
        c = true
    } else {
        this.dADTempOff = true
    }
    this.dragAndDropOff = dhx4.s2b(c);
    if (this.dragAndDropOff) {
        this.dragger.addDragLanding(this.allTree, this)
    }
    if (arguments.length > 1) {
        this._ddronr = (!dhx4.s2b(a))
    }
};
dhtmlXTreeObject.prototype._setMove = function (f, d, h) {
    if (f.parentObject.span) {
        var e = dhx4.absTop(f);
        var c = dhx4.absTop(this.allTree) - this.allTree.scrollTop;
        this.dadmodec = this.dadmode;
        this.dadmodefix = 0;
        if (this.dadmode == 2) {
            var g = h - e + (document.body.scrollTop || document.documentElement.scrollTop) - 2 - f.offsetHeight / 2;
            if ((Math.abs(g) - f.offsetHeight / 6) > 0) {
                this.dadmodec = 1;
                if (g < 0) {
                    this.dadmodefix = 0 - f.offsetHeight
                }
            } else {
                this.dadmodec = 0
            }
        }
        if (this.dadmodec == 0) {
            var a = f.parentObject.span;
            a.className += " dragAndDropRow";
            this._lastMark = a
        } else {
            this._clearMove();
            this.selectionBar.style.top = (e - c + ((parseInt(f.parentObject.span.parentNode.parentNode.offsetHeight) || 18) - 1) + this.dadmodefix) + "px";
            this.selectionBar.style.left = "5px";
            if (this.allTree.offsetWidth > 20) {
                this.selectionBar.style.width = (this.allTree.offsetWidth - (_isFF ? 30 : 25)) + "px"
            }
            this.selectionBar.style.display = ""
        }
        this._autoScroll(null, e, c)
    }
};
dhtmlXTreeObject.prototype._autoScroll = function (d, c, a) {
    if (this.autoScroll) {
        if (d) {
            c = dhx4.absTop(d);
            a = dhx4.absTop(this.allTree) - this.allTree.scrollTop
        }
        if ((c - a - parseInt(this.allTree.scrollTop)) > (parseInt(this.allTree.offsetHeight) - 50)) {
            this.allTree.scrollTop = parseInt(this.allTree.scrollTop) + 20
        }
        if ((c - a) < (parseInt(this.allTree.scrollTop) + 30)) {
            this.allTree.scrollTop = parseInt(this.allTree.scrollTop) - 20
        }
    }
};
dhtmlXTreeObject.prototype._createDragNode = function (g, f) {
    if (!this.dADTempOff) {
        return null
    }
    var d = g.parentObject;
    if (!this.callEvent("onBeforeDrag", [d.id, f])) {
        return null
    }
    if (!d.i_sel) {
        this._selectItem(d, f)
    }
    this._checkMSelectionLogic();
    var c = document.createElement("div");
    var h = new Array();
    if (this._itim_dg) {
        for (var a = 0; a < this._selected.length; a++) {
            h[a] = "<table cellspacing='0' cellpadding='0'><tr><td><img width='18px' height='18px' src='" + this._getSrc(this._selected[a].span.parentNode.previousSibling.childNodes[0]) + "'></td><td>" + this._selected[a].span.innerHTML + "</td></tr></table>"
        }
    } else {
        h = this.getSelectedItemText().split(this.dlmtr)
    }
    c.innerHTML = h.join("");
    c.style.position = "absolute";
    c.className = "dragSpanDiv";
    this._dragged = (new Array()).concat(this._selected);
    return c
};
dhtmlXTreeObject.prototype._focusNode = function (a) {
    var c = dhx4.absTop(a.htmlNode) - dhx4.absTop(this.allTree);
    if ((c > (this.allTree.offsetHeight - 30)) || (c < 0)) {
        this.allTree.scrollTop = c + this.allTree.scrollTop
    }
};
dhtmlXTreeObject.prototype._preventNsDrag = function (a) {
    if ((a) && (a.preventDefault)) {
        a.preventDefault();
        return false
    }
    return false
};
dhtmlXTreeObject.prototype._drag = function (h, j, a) {
    if (this._autoOpenTimer) {
        clearTimeout(this._autoOpenTimer)
    }
    if (!a.parentObject) {
        a = this.htmlNode.htmlNode.childNodes[0].childNodes[0].childNodes[1].childNodes[0];
        this.dadmodec = 0
    }
    this._clearMove();
    var g = h.parentObject.treeNod;
    if ((g) && (g._clearMove)) {
        g._clearMove("")
    }
    if ((!this.dragMove) || (this.dragMove())) {
        if ((!g) || (!g._clearMove) || (!g._dragged)) {
            var e = new Array(h.parentObject)
        } else {
            var e = g._dragged
        }
        var c = a.parentObject;
        for (var f = 0; f < e.length; f++) {
            var d = this._moveNode(e[f], c);
            if ((this.dadmodec) && (d !== false)) {
                c = this._globalIdStorageFind(d, true, true)
            }
            if ((d) && (!this._sADnD)) {
                this.selectItem(d, 0, 1)
            }
        }
    }
    if (g) {
        g._dragged = new Array()
    }
};
dhtmlXTreeObject.prototype._dragIn = function (e, c, g, f) {
    if (!this.dADTempOff) {
        return 0
    }
    var h = c.parentObject;
    var a = e.parentObject;
    if ((!a) && (this._ddronr)) {
        return
    }
    if (!this.callEvent("onDragIn", [h.id, a ? a.id : null, h.treeNod, this])) {
        if (a) {
            this._autoScroll(e)
        }
        return 0
    }
    if (!a) {
        this.allTree.className += " selectionBox"
    } else {
        if (h.childNodes == null) {
            this._setMove(e, g, f);
            return e
        }
        var k = h.treeNod;
        for (var d = 0; d < k._dragged.length; d++) {
            if (this._checkPNodes(a, k._dragged[d])) {
                this._autoScroll(e);
                return 0
            }
        }
        this.selectionBar.parentNode.removeChild(this.selectionBar);
        a.span.parentNode.appendChild(this.selectionBar);
        this._setMove(e, g, f);
        if (this._getOpenState(a) <= 0) {
            var j = this;
            this._autoOpenId = a.id;
            this._autoOpenTimer = window.setTimeout(function () {
                j._autoOpenItem(null, j);
                j = null
            }, 1000)
        }
    }
    return e
};
dhtmlXTreeObject.prototype._autoOpenItem = function (c, a) {
    a.openItem(a._autoOpenId)
};
dhtmlXTreeObject.prototype._dragOut = function (a) {
    this._clearMove();
    if (this._autoOpenTimer) {
        clearTimeout(this._autoOpenTimer)
    }
};
dhtmlXTreeObject.prototype._getNextNode = function (a, c) {
    if ((!c) && (a.childsCount)) {
        return a.childNodes[0]
    }
    if (a == this.htmlNode) {
        return -1
    }
    if ((a.tr) && (a.tr.nextSibling) && (a.tr.nextSibling.nodem)) {
        return a.tr.nextSibling.nodem
    }
    return this._getNextNode(a.parentObject, true)
};
dhtmlXTreeObject.prototype._lastChild = function (a) {
    if (a.childsCount) {
        return this._lastChild(a.childNodes[a.childsCount - 1])
    } else {
        return a
    }
};
dhtmlXTreeObject.prototype._getPrevNode = function (a, c) {
    if ((a.tr) && (a.tr.previousSibling) && (a.tr.previousSibling.nodem)) {
        return this._lastChild(a.tr.previousSibling.nodem)
    }
    if (a.parentObject) {
        return a.parentObject
    } else {
        return -1
    }
};
dhtmlXTreeObject.prototype.findItem = function (a, d, c) {
    var e = this._findNodeByLabel(a, d, (c ? this.htmlNode : null));
    if (e) {
        this.selectItem(e.id, true);
        this._focusNode(e);
        return e.id
    } else {
        return null
    }
};
dhtmlXTreeObject.prototype.findItemIdByLabel = function (a, d, c) {
    var e = this._findNodeByLabel(a, d, (c ? this.htmlNode : null));
    if (e) {
        return e.id
    } else {
        return null
    }
};
dhtmlXTreeObject.prototype.findStrInXML = function (c, d, f) {
    if (!c.childNodes && c.item) {
        return this.findStrInJSON(c, d, f)
    }
    if (!c.childNodes) {
        return false
    }
    for (var a = 0; a < c.childNodes.length; a++) {
        if (c.childNodes[a].nodeType == 1) {
            var e = c.childNodes[a].getAttribute(d);
            if (!e && c.childNodes[a].tagName == "itemtext") {
                e = c.childNodes[a].firstChild.data
            }
            if ((e) && (e.toLowerCase().search(f) != -1)) {
                return true
            }
            if (this.findStrInXML(c.childNodes[a], d, f)) {
                return true
            }
        }
    }
    return false
};
dhtmlXTreeObject.prototype.findStrInJSON = function (c, d, f) {
    for (var a = 0; a < c.item.length; a++) {
        var e = c.item[a].text;
        if ((e) && (e.toLowerCase().search(f) != -1)) {
            return true
        }
        if (c.item[a].item && this.findStrInJSON(c.item[a], d, f)) {
            return true
        }
    }
    return false
};
dhtmlXTreeObject.prototype._findNodeByLabel = function (a, f, e) {
    var a = a.replace(new RegExp("^( )+"), "").replace(new RegExp("( )+$"), "");
    a = new RegExp(a.replace(/([\^\.\?\*\+\\\[\]\(\)]{1})/gi, "\\$1").replace(/ /gi, ".*"), "gi");
    if (!e) {
        e = this._selected[0];
        if (!e) {
            e = this.htmlNode
        }
    }
    var c = e;
    if (!f) {
        if ((e.unParsed) && (this.findStrInXML(e.unParsed.d, "text", a))) {
            this.reParse(e)
        }
        e = this._getNextNode(c);
        if (e == -1) {
            e = this.htmlNode.childNodes[0]
        }
    } else {
        var d = this._getPrevNode(c);
        if (d == -1) {
            d = this._lastChild(this.htmlNode)
        }
        if ((d.unParsed) && (this.findStrInXML(d.unParsed.d, "text", a))) {
            this.reParse(d);
            e = this._getPrevNode(c)
        } else {
            e = d
        }
        if (e == -1) {
            e = this._lastChild(this.htmlNode)
        }
    }
    while ((e) && (e != c)) {
        if ((e.label) && (e.label.search(a) != -1)) {
            return (e)
        }
        if (!f) {
            if (e == -1) {
                if (c == this.htmlNode) {
                    break
                }
                e = this.htmlNode.childNodes[0]
            }
            if ((e.unParsed) && (this.findStrInXML(e.unParsed.d, "text", a))) {
                this.reParse(e)
            }
            e = this._getNextNode(e);
            if (e == -1) {
                e = this.htmlNode
            }
        } else {
            var d = this._getPrevNode(e);
            if (d == -1) {
                d = this._lastChild(this.htmlNode)
            }
            if ((d.unParsed) && (this.findStrInXML(d.unParsed.d, "text", a))) {
                this.reParse(d);
                e = this._getPrevNode(e)
            } else {
                e = d
            }
            if (e == -1) {
                e = this._lastChild(this.htmlNode)
            }
        }
    }
    return null
};
dhtmlXTreeObject.prototype.moveItem = function (j, c, k, a) {
    var f = this._globalIdStorageFind(j);
    if (!f) {
        return (0)
    }
    var g = null;
    switch (c) {
        case "right":
            alert("Not supported yet");
            break;
        case "item_child":
            var d = (a || this)._globalIdStorageFind(k);
            if (!d) {
                return (0)
            }
            g = (a || this)._moveNodeTo(f, d, 0);
            break;
        case "item_sibling":
            var d = (a || this)._globalIdStorageFind(k);
            if (!d) {
                return (0)
            }
            g = (a || this)._moveNodeTo(f, d.parentObject, d);
            break;
        case "item_sibling_next":
            var d = (a || this)._globalIdStorageFind(k);
            if (!d) {
                return (0)
            }
            if ((d.tr) && (d.tr.nextSibling) && (d.tr.nextSibling.nodem)) {
                g = (a || this)._moveNodeTo(f, d.parentObject, d.tr.nextSibling.nodem)
            } else {
                g = (a || this)._moveNodeTo(f, d.parentObject)
            }
            break;
        case "left":
            if (f.parentObject.parentObject) {
                g = this._moveNodeTo(f, f.parentObject.parentObject, f.parentObject)
            }
            break;
        case "up":
            var h = this._getPrevNode(f);
            if ((h == -1) || (!h.parentObject)) {
                return null
            }
            g = this._moveNodeTo(f, h.parentObject, h);
            break;
        case "up_strict":
            var h = this._getIndex(f);
            if (h != 0) {
                g = this._moveNodeTo(f, f.parentObject, f.parentObject.childNodes[h - 1])
            }
            break;
        case "down_strict":
            var h = this._getIndex(f);
            var e = f.parentObject.childsCount - 2;
            if (h == e) {
                g = this._moveNodeTo(f, f.parentObject)
            } else {
                if (h < e) {
                    g = this._moveNodeTo(f, f.parentObject, f.parentObject.childNodes[h + 2])
                }
            }
            break;
        case "down":
            var h = this._getNextNode(this._lastChild(f));
            if ((h == -1) || (!h.parentObject)) {
                return
            }
            if (h.parentObject == f.parentObject) {
                var h = this._getNextNode(h)
            }
            if (h == -1) {
                g = this._moveNodeTo(f, f.parentObject)
            } else {
                if ((h == -1) || (!h.parentObject)) {
                    return
                }
                g = this._moveNodeTo(f, h.parentObject, h)
            }
            break
    }
    if (_isIE && _isIE < 8) {
        this.allTree.childNodes[0].border = "1";
        this.allTree.childNodes[0].border = "0"
    }
    return g
};
dhtmlXTreeObject.prototype.setDragBehavior = function (c, a) {
    this._sADnD = (!dhx4.s2b(a));
    switch (c) {
        case "child":
            this.dadmode = 0;
            break;
        case "sibling":
            this.dadmode = 1;
            break;
        case "complex":
            this.dadmode = 2;
            break
    }
};
dhtmlXTreeObject.prototype._loadDynXML = function (d, c) {
    c = c || this.XMLsource;
    var a = (new Date()).valueOf();
    this._ld_id = d;
    if (this.xmlalb == "function") {
        if (c) {
            c(this._escape(d))
        }
    } else {
        if (this.xmlalb == "name") {
            this.load(c + this._escape(d))
        } else {
            if (this.xmlalb == "xmlname") {
                this.load(c + this._escape(d) + ".xml?uid=" + a)
            } else {
                this.load(c + dhtmlx.url(c) + "uid=" + a + "&id=" + this._escape(d))
            }
        }
    }
};
dhtmlXTreeObject.prototype.enableMultiselection = function (c, a) {
    this._amsel = dhx4.s2b(c);
    this._amselS = dhx4.s2b(a)
};
dhtmlXTreeObject.prototype._checkMSelectionLogic = function () {
    var d = new Array();
    for (var c = 0; c < this._selected.length; c++) {
        for (var a = 0; a < this._selected.length; a++) {
            if ((c != a) && (this._checkPNodes(this._selected[a], this._selected[c]))) {
                d[d.length] = this._selected[a]
            }
        }
    }
    for (var c = 0; c < d.length; c++) {
        this._unselectItem(d[c])
    }
};
dhtmlXTreeObject.prototype._checkPNodes = function (c, a) {
    if (this._dcheckf) {
        return false
    }
    if (a == c) {
        return 1
    }
    if (c.parentObject) {
        return this._checkPNodes(c.parentObject, a)
    } else {
        return 0
    }
};
dhtmlXTreeObject.prototype.disableDropCheck = function (a) {
    this._dcheckf = dhx4.s2b(a)
};
dhtmlXTreeObject.prototype.enableDistributedParsing = function (d, c, a) {
    this._edsbps = dhx4.s2b(d);
    this._edsbpsA = new Array();
    this._edsbpsC = c || 10;
    this._edsbpsD = a || 250
};
dhtmlXTreeObject.prototype.getDistributedParsingState = function () {
    return (!((!this._edsbpsA) || (!this._edsbpsA.length)))
};
dhtmlXTreeObject.prototype.getItemParsingState = function (d) {
    var c = this._globalIdStorageFind(d, true, true);
    if (!c) {
        return 0
    }
    if (this._edsbpsA) {
        for (var a = 0; a < this._edsbpsA.length; a++) {
            if (this._edsbpsA[a][2] == d) {
                return -1
            }
        }
    }
    return 1
};
dhtmlXTreeObject.prototype._distributedStart = function (c, f, e, d, a) {
    if (!this._edsbpsA) {
        this._edsbpsA = new Array()
    }
    this._edsbpsA[this._edsbpsA.length] = [c, f, e, d, a]
};
dhtmlXTreeObject.prototype._distributedStep = function (e) {
    var c = this;
    if ((!this._edsbpsA) || (!this._edsbpsA.length)) {
        c.XMLloadingWarning = 0;
        return
    }
    var f = this._edsbpsA[0];
    this.parsedArray = new Array();
    this._parse(f[0], f[2], f[3], f[1]);
    var a = this._globalIdStorageFind(f[2]);
    this._redrawFrom(this, a, f[4], this._getOpenState(a));
    var d = this.setCheckList.split(this.dlmtr);
    for (var g = 0; g < d.length; g++) {
        if (d[g]) {
            this.setCheck(d[g], 1)
        }
    }
    this._edsbpsA = (new Array()).concat(this._edsbpsA.slice(1));
    if ((!this._edsbpsA.length)) {
        window.setTimeout(function () {
            if (c.onXLE) {
                c.onXLE(c, e)
            }
            c.callEvent("onXLE", [c, e])
        }, 1);
        c.xmlstate = 0
    }
};
dhtmlXTreeObject.prototype.enableTextSigns = function (a) {
    this._txtimg = dhx4.s2b(a)
};
dhtmlXTreeObject.prototype.preventIECaching = function (a) {
    dhx4.ajax.cache = !a
};
dhtmlXTreeObject.prototype.preventIECashing = dhtmlXTreeObject.prototype.preventIECaching;
dhtmlXTreeObject.prototype.disableCheckbox = function (d, c) {
    if (typeof (d) != "object") {
        var a = this._globalIdStorageFind(d, 0, 1)
    } else {
        var a = d
    }
    if (!a) {
        return
    }
    a.dscheck = dhx4.s2b(c) ? (((a.checkstate || 0) % 3) + 3) : ((a.checkstate > 2) ? (a.checkstate - 3) : a.checkstate);
    this._setCheck(a);
    if (a.dscheck < 3) {
        a.dscheck = false
    }
};
dhtmlXTreeObject.prototype.smartRefreshBranch = function (c, a) {
    this._branchUpdate = 1;
    this.smartRefreshItem(c, a)
};
dhtmlXTreeObject.prototype.smartRefreshItem = function (e, d) {
    var a = this._globalIdStorageFind(e);
    for (var c = 0; c < a.childsCount; c++) {
        a.childNodes[c]._dmark = true
    }
    this.waitUpdateXML = true;
    if (d && d.exists) {
        this._parse(d, e)
    } else {
        this._loadDynXML(e, d)
    }
};
dhtmlXTreeObject.prototype.refreshItems = function (c, d) {
    var e = c.toString().split(this.dlmtr);
    this.waitUpdateXML = new Array();
    for (var a = 0; a < e.length; a++) {
        this.waitUpdateXML[e[a]] = true
    }
    this.load((d || this.XMLsource) + dhtmlx.url(d || this.XMLsource) + "ids=" + this._escape(c))
};
dhtmlXTreeObject.prototype.updateItem = function (h, g, e, d, c, f, j) {
    var a = this._globalIdStorageFind(h);
    a.userData = new cObject();
    if (g) {
        a.label = g
    }
    a.images = new Array(e || this.imageArray[0], d || this.imageArray[1], c || this.imageArray[2]);
    this.setItemText(h, g);
    if (f) {
        this._setCheck(a, true)
    }
    if (j == "1" && !this.hasChildren(h)) {
        a.XMLload = 0
    }
    this._correctPlus(a);
    a._dmark = false;
    return a
};
dhtmlXTreeObject.prototype.setDropHandler = function (a) {
    this.attachEvent("onDrop", a)
};
dhtmlXTreeObject.prototype.setOnLoadingStart = function (a) {
    this.attachEvent("onXLS", a)
};
dhtmlXTreeObject.prototype.setOnLoadingEnd = function (a) {
    this.attachEvent("onXLE", a)
};
dhtmlXTreeObject.prototype.setXMLAutoLoadingBehaviour = function (a) {
    this.xmlalb = a
};
dhtmlXTreeObject.prototype.enableSmartCheckboxes = function (a) {
    this.smcheck = dhx4.s2b(a)
};
dhtmlXTreeObject.prototype.getXMLState = function () {
    return (this.xmlstate == 1)
};
dhtmlXTreeObject.prototype.setItemTopOffset = function (g, e) {
    var d;
    if (typeof (g) != "object") {
        d = this._globalIdStorageFind(g)
    } else {
        d = g
    }
    var f = d.span.parentNode.parentNode;
    d.span.style.paddingBottom = "1px";
    for (var c = 0; c < f.childNodes.length; c++) {
        if (c != 0) {
            if (_isIE) {
                f.childNodes[c].style.height = "18px";
                f.childNodes[c].style.paddingTop = parseInt(e) + "px"
            } else {
                f.childNodes[c].style.height = 18 + parseInt(e) + "px"
            }
        } else {
            var a = f.childNodes[c].firstChild;
            if (f.childNodes[c].firstChild.tagName != "DIV") {
                a = document.createElement("DIV");
                f.childNodes[c].insertBefore(a, f.childNodes[c].firstChild)
            }
            if ((d.parentObject.id != this.rootId || d.parentObject.childNodes[0] != d) && this.treeLinesOn) {
                f.childNodes[c].style.backgroundImage = "url(" + this.imPath + this.lineArray[5] + ")"
            }
            a.innerHTML = "&nbsp;";
            a.style.overflow = "hidden"
        }
        a.style.verticalAlign = f.childNodes[c].style.verticalAlign = "bottom";
        if (_isIE) {
            this.allTree.childNodes[0].border = "1";
            this.allTree.childNodes[0].border = "0"
        }
    }
};
dhtmlXTreeObject.prototype.setIconSize = function (e, c, f) {
    if (f) {
        if ((f) && (f.span)) {
            var a = f
        } else {
            var a = this._globalIdStorageFind(f)
        }
        if (!a) {
            return (0)
        }
        var d = a.span.parentNode.previousSibling.childNodes[0];
        if (e) {
            d.style.width = e + "px";
            if (window._KHTMLrv) {
                d.parentNode.style.width = e + "px"
            }
        }
        if (c) {
            d.style.height = c + "px";
            if (window._KHTMLrv) {
                d.parentNode.style.height = c + "px"
            }
        }
    } else {
        this.def_img_x = e + "px";
        this.def_img_y = c + "px"
    }
};
dhtmlXTreeObject.prototype.getItemImage = function (f, e, c) {
    var d = this._globalIdStorageFind(f);
    if (!d) {
        return ""
    }
    var a = d.images[e || 0];
    if (c) {
        a = this.iconURL + a
    }
    return a
};
dhtmlXTreeObject.prototype.enableRadioButtons = function (e, d) {
    if (arguments.length == 1) {
        this._frbtr = dhx4.s2b(e);
        this.checkBoxOff = this.checkBoxOff || this._frbtr;
        return
    }
    var c = this._globalIdStorageFind(e);
    if (!c) {
        return ""
    }
    d = dhx4.s2b(d);
    if ((d) && (!c._r_logic)) {
        c._r_logic = true;
        for (var a = 0; a < c.childsCount; a++) {
            this._setCheck(c.childNodes[a], c.childNodes[a].checkstate)
        }
    }
    if ((!d) && (c._r_logic)) {
        c._r_logic = false;
        for (var a = 0; a < c.childsCount; a++) {
            this._setCheck(c.childNodes[a], c.childNodes[a].checkstate)
        }
    }
};
dhtmlXTreeObject.prototype.enableSingleRadioMode = function (a) {
    this._frbtrs = dhx4.s2b(a)
};
dhtmlXTreeObject.prototype.openOnItemAdded = function (a) {
    this._hAdI = !dhx4.s2b(a)
};
dhtmlXTreeObject.prototype.openOnItemAdding = function (a) {
    this._hAdI = !dhx4.s2b(a)
};
dhtmlXTreeObject.prototype.enableMultiLineItems = function (a) {
    if (a === true) {
        this.mlitems = "100%"
    } else {
        this.mlitems = a
    }
};
dhtmlXTreeObject.prototype.enableAutoTooltips = function (a) {
    this.ettip = dhx4.s2b(a)
};
dhtmlXTreeObject.prototype.clearSelection = function (a) {
    if (a) {
        this._unselectItem(this._globalIdStorageFind(a))
    } else {
        this._unselectItems()
    }
};
dhtmlXTreeObject.prototype.showItemSign = function (e, c) {
    var a = this._globalIdStorageFind(e);
    if (!a) {
        return 0
    }
    var d = a.span.parentNode.previousSibling.previousSibling.previousSibling;
    if (!dhx4.s2b(c)) {
        this._openItem(a);
        a.closeble = false;
        a.wsign = true
    } else {
        a.closeble = true;
        a.wsign = false
    }
    this._correctPlus(a)
};
dhtmlXTreeObject.prototype.showItemCheckbox = function (f, e) {
    if (!f) {
        for (var c in this._idpull) {
            this.showItemCheckbox(this._idpull[c], e)
        }
    }
    if (typeof (f) != "object") {
        f = this._globalIdStorageFind(f, 0, 0)
    }
    if (!f) {
        return 0
    }
    f.nocheckbox = !dhx4.s2b(e);
    var d = f.span.parentNode.previousSibling.previousSibling.childNodes[0];
    d.parentNode.style.display = (!f.nocheckbox) ? "" : "none"
};
dhtmlXTreeObject.prototype.setListDelimeter = function (a) {
    this.dlmtr = a
};
dhtmlXTreeObject.prototype.setEscapingMode = function (a) {
    this.utfesc = a
};
dhtmlXTreeObject.prototype.enableHighlighting = function (a) {
    this.ehlt = true;
    this.ehlta = dhx4.s2b(a)
};
dhtmlXTreeObject.prototype._itemMouseOut = function () {
    var c = this.childNodes[3].parentObject;
    var a = c.treeNod;
    a.callEvent("onMouseOut", [c.id]);
    if (c.id == a._l_onMSI) {
        a._l_onMSI = null
    }
    if (!a.ehlta) {
        return
    }
    c.span.className = c.span.className.replace("_lor", "")
};
dhtmlXTreeObject.prototype._itemMouseIn = function () {
    var c = this.childNodes[3].parentObject;
    var a = c.treeNod;
    if (a._l_onMSI != c.id) {
        a.callEvent("onMouseIn", [c.id])
    }
    a._l_onMSI = c.id;
    if (!a.ehlta) {
        return
    }
    c.span.className = c.span.className.replace("_lor", "");
    c.span.className = c.span.className.replace(/((standart|selected)TreeRow)/, "$1_lor")
};
dhtmlXTreeObject.prototype.enableActiveImages = function (a) {
    this._aimgs = dhx4.s2b(a)
};
dhtmlXTreeObject.prototype.focusItem = function (c) {
    var a = this._globalIdStorageFind(c);
    if (!a) {
        return (0)
    }
    this._focusNode(a)
};
dhtmlXTreeObject.prototype.getAllSubItems = function (a) {
    return this._getAllSubItems(a)
};
dhtmlXTreeObject.prototype.getAllChildless = function () {
    return this._getAllScraggyItems(this.htmlNode)
};
dhtmlXTreeObject.prototype.getAllLeafs = dhtmlXTreeObject.prototype.getAllChildless;
dhtmlXTreeObject.prototype._getAllScraggyItems = function (d) {
    var e = "";
    for (var c = 0; c < d.childsCount; c++) {
        if ((d.childNodes[c].unParsed) || (d.childNodes[c].childsCount > 0)) {
            if (d.childNodes[c].unParsed) {
                var a = this._getAllScraggyItemsXML(d.childNodes[c].unParsed, 1)
            } else {
                var a = this._getAllScraggyItems(d.childNodes[c])
            }
            if (a) {
                if (e) {
                    e += this.dlmtr + a
                } else {
                    e = a
                }
            }
        } else {
            if (!e) {
                e = "" + d.childNodes[c].id
            } else {
                e += this.dlmtr + d.childNodes[c].id
            }
        }
    }
    return e
};
dhtmlXTreeObject.prototype._getAllFatItems = function (d) {
    var e = "";
    for (var c = 0; c < d.childsCount; c++) {
        if ((d.childNodes[c].unParsed) || (d.childNodes[c].childsCount > 0)) {
            if (!e) {
                e = "" + d.childNodes[c].id
            } else {
                e += this.dlmtr + d.childNodes[c].id
            }
            if (d.childNodes[c].unParsed) {
                var a = this._getAllFatItemsXML(d.childNodes[c].unParsed, 1)
            } else {
                var a = this._getAllFatItems(d.childNodes[c])
            }
            if (a) {
                e += this.dlmtr + a
            }
        }
    }
    return e
};
dhtmlXTreeObject.prototype.getAllItemsWithKids = function () {
    return this._getAllFatItems(this.htmlNode)
};
dhtmlXTreeObject.prototype.getAllFatItems = dhtmlXTreeObject.prototype.getAllItemsWithKids;
dhtmlXTreeObject.prototype.getAllChecked = function () {
    return this._getAllChecked("", "", 1)
};
dhtmlXTreeObject.prototype.getAllUnchecked = function (a) {
    if (a) {
        a = this._globalIdStorageFind(a)
    }
    return this._getAllChecked(a, "", 0)
};
dhtmlXTreeObject.prototype.getAllPartiallyChecked = function () {
    return this._getAllChecked("", "", 2)
};
dhtmlXTreeObject.prototype.getAllCheckedBranches = function () {
    var a = [this._getAllChecked("", "", 1)];
    var c = this._getAllChecked("", "", 2);
    if (c) {
        a.push(c)
    }
    return a.join(this.dlmtr)
};
dhtmlXTreeObject.prototype._getAllChecked = function (e, d, f) {
    if (!e) {
        e = this.htmlNode
    }
    if (e.checkstate == f) {
        if (!e.nocheckbox) {
            if (d) {
                d += this.dlmtr + e.id
            } else {
                d = "" + e.id
            }
        }
    }
    var a = e.childsCount;
    for (var c = 0; c < a; c++) {
        d = this._getAllChecked(e.childNodes[c], d, f)
    }
    if (e.unParsed) {
        d = this._getAllCheckedXML(e.unParsed, d, f)
    }
    if (d) {
        return d
    } else {
        return ""
    }
};
dhtmlXTreeObject.prototype.setItemStyle = function (e, d, c) {
    var c = c || false;
    var a = this._globalIdStorageFind(e);
    if (!a) {
        return 0
    }
    if (!a.span.style.cssText) {
        a.span.setAttribute("style", a.span.getAttribute("style") + "; " + d)
    } else {
        a.span.style.cssText = c ? d : a.span.style.cssText + ";" + d
    }
};
dhtmlXTreeObject.prototype.enableImageDrag = function (a) {
    this._itim_dg = dhx4.s2b(a)
};
dhtmlXTreeObject.prototype.setOnDragIn = function (a) {
    this.attachEvent("onDragIn", a)
};
dhtmlXTreeObject.prototype.enableDragAndDropScrolling = function (a) {
    this.autoScroll = dhx4.s2b(a)
};
dhtmlXTreeObject.prototype.setSkin = function (a) {
    var c = this.parentObject.className.replace(/dhxtree_[^ ]*/gi, "");
    this.parentObject.className = c + " dhxtree_" + a;
    if (a == "dhx_terrace" || a == "dhx_web" || a == "material") {
        this.enableTreeLines(false)
    }
    if (a == "material") {
        this.setIconSize("25", "25")
    }
};
(function () {
    dhtmlx.extend_api("dhtmlXTreeObject", {
        _init: function (a) {
            return [a.parent, (a.width || "100%"), (a.height || "100%"), (a.root_id || 0)]
        },
        auto_save_selection: "enableAutoSavingSelected",
        auto_tooltip: "enableAutoTooltips",
        checkbox: "enableCheckBoxes",
        checkbox_3_state: "enableThreeStateCheckboxes",
        checkbox_smart: "enableSmartCheckboxes",
        context_menu: "enableContextMenu",
        distributed_parsing: "enableDistributedParsing",
        drag: "enableDragAndDrop",
        drag_copy: "enableMercyDrag",
        drag_image: "enableImageDrag",
        drag_scroll: "enableDragAndDropScrolling",
        editor: "enableItemEditor",
        hover: "enableHighlighting",
        images: "enableTreeImages",
        image_fix: "enableIEImageFix",
        image_path: "setImagePath",
        lines: "enableTreeLines",
        loading_item: "enableLoadingItem",
        multiline: "enableMultiLineItems",
        multiselect: "enableMultiselection",
        navigation: "enableKeyboardNavigation",
        radio: "enableRadioButtons",
        radio_single: "enableSingleRadioMode",
        rtl: "enableRTL",
        search: "enableKeySearch",
        smart_parsing: "enableSmartXMLParsing",
        smart_rendering: "enableSmartRendering",
        text_icons: "enableTextSigns",
        xml: "loadXML",
        skin: "setSkin"
    }, {})
})();
dhtmlXTreeObject.prototype._dp_init = function (a) {
    a.attachEvent("insertCallback", function (e, g, c) {
        var d = dhx4.ajax.xpath(".//item", e);
        var f = d[0].getAttribute("text");
        this.obj.insertNewItem(c, g, f, 0, 0, 0, 0, "CHILD")
    });
    a.attachEvent("updateCallback", function (e, g, c) {
        var d = dhx4.ajax.xpath(".//item", e);
        var f = d[0].getAttribute("text");
        this.obj.setItemText(g, f);
        if (this.obj.getParentId(g) != c) {
            this.obj.moveItem(g, "item_child", c)
        }
        this.setUpdated(g, true, "updated")
    });
    a.attachEvent("deleteCallback", function (d, e, c) {
        this.obj.setUserData(e, this.action_param, "true_deleted");
        this.obj.deleteItem(e, false)
    });
    a._methods = ["setItemStyle", "", "changeItemId", "deleteItem"];
    this.attachEvent("onEdit", function (c, d) {
        if (c == 3) {
            a.setUpdated(d, true)
        }
        return true
    });
    this.attachEvent("onDrop", function (g, f, e, d, c) {
        if (d == c) {
            a.setUpdated(g, true)
        }
    });
    this._onrdlh = function (c) {
        var d = a.getState(c);
        if (d == "inserted") {
            a.set_invalid(c, false);
            a.setUpdated(c, false);
            return true
        }
        if (d == "true_deleted") {
            a.setUpdated(c, false);
            return true
        }
        a.setUpdated(c, true, "deleted");
        return false
    };
    this._onradh = function (c) {
        a.setUpdated(c, true, "inserted")
    };
    a._getRowData = function (f) {
        var e = {};
        var g = this.obj._globalIdStorageFind(f);
        var d = g.parentObject;
        var c = 0;
        for (c = 0; c < d.childsCount; c++) {
            if (d.childNodes[c] == g) {
                break
            }
        }
        e.tr_id = g.id;
        e.tr_pid = d.id;
        e.tr_order = c;
        e.tr_text = g.span.innerHTML;
        d = (g._userdatalist || "").split(",");
        for (c = 0; c < d.length; c++) {
            e[d[c]] = g.userData["t_" + d[c]]
        }
        return e
    }
};
if (typeof (window.dhtmlXCellObject) != "undefined") {
    dhtmlXCellObject.prototype.attachTree = function (a) {
        this.callEvent("_onBeforeContentAttach", ["tree"]);
        var c = document.createElement("DIV");
        c.style.width = "100%";
        c.style.height = "100%";
        c.style.position = "relative";
        c.style.overflow = "hidden";
        this._attachObject(c);
        this.dataType = "tree";
        this.dataObj = new dhtmlXTreeObject(c, "100%", "100%", (a || 0));
        this.dataObj.setSkin(this.conf.skin);
        this.dataObj.allTree.childNodes[0].style.marginTop = "2px";
        this.dataObj.allTree.childNodes[0].style.marginBottom = "2px";
        c = null;
        this.callEvent("_onContentAttach", []);
        return this.dataObj
    }
}
dhtmlXTreeObject.prototype.makeDraggable = function (c, a) {
    if (typeof (c) != "object") {
        c = document.getElementById(c)
    }
    dragger = new dhtmlDragAndDropObject();
    dropper = new dhx_dragSomethingInTree();
    dragger.addDraggableItem(c, dropper);
    c.dragLanding = null;
    c.ondragstart = dropper._preventNsDrag;
    c.onselectstart = new Function("return false;");
    c.parentObject = new Object;
    c.parentObject.img = c;
    c.parentObject.treeNod = dropper;
    dropper._customDrop = a
};
dhtmlXTreeObject.prototype.makeDragable = dhtmlXTreeObject.prototype.makeDraggable;
dhtmlXTreeObject.prototype.makeAllDraggable = function (c) {
    var d = document.getElementsByTagName("div");
    for (var a = 0; a < d.length; a++) {
        if (d[a].getAttribute("dragInDhtmlXTree")) {
            this.makeDragable(d[a], c)
        }
    }
};

function dhx_dragSomethingInTree() {
    this.lWin = window;
    this._createDragNode = function (c) {
        var a = document.createElement("div");
        a.style.position = "absolute";
        a.innerHTML = (c.innerHTML || c.value);
        a.className = "dragSpanDiv";
        return a
    };
    this._preventNsDrag = function (a) {
        (a || window.event).cancelBubble = true;
        if ((a) && (a.preventDefault)) {
            a.preventDefault();
            return false
        }
        return false
    };
    this._nonTrivialNode = function (c, d, a, e) {
        if (this._customDrop) {
            return this._customDrop(c, e.img.id, d.id, a ? a.id : null)
        }
        var f = (e.img.getAttribute("image") || "");
        var h = e.img.id || "new";
        var g = (e.img.getAttribute("text") || (_isIE ? e.img.innerText : e.img.textContent));
        c[a ? "insertNewNext" : "insertNewItem"](a ? a.id : d.id, h, g, "", f, f, f)
    }
}
dhtmlXTreeObject.prototype.enableItemEditor = function (a) {
    this._eItEd = dhx4.s2b(a);
    if (!this._eItEdFlag) {
        this._edn_click_IE = true;
        this._edn_dblclick = true;
        this._ie_aFunc = this.aFunc;
        this._ie_dblclickFuncHandler = this.dblclickFuncHandler;
        this.setOnDblClickHandler(function (d, c) {
            if (this._edn_dblclick) {
                this._editItem(d, c)
            }
            return true
        });
        this.setOnClickHandler(function (d, c) {
            this._stopEditItem(d, c);
            if ((this.ed_hist_clcik == d) && (this._edn_click_IE)) {
                this._editItem(d, c)
            }
            this.ed_hist_clcik = d;
            return true
        });
        this._eItEdFlag = true
    }
};
dhtmlXTreeObject.prototype.setOnEditHandler = function (a) {
    this.attachEvent("onEdit", a)
};
dhtmlXTreeObject.prototype.setEditStartAction = function (a, c) {
    this._edn_click_IE = dhx4.s2b(a);
    this._edn_dblclick = dhx4.s2b(c)
};
dhtmlXTreeObject.prototype._stopEdit = function (c, g) {
    if (this._editCell) {
        this.dADTempOff = this.dADTempOffEd;
        if (this._editCell.id != c) {
            var e = true;
            if (!g) {
                e = this.callEvent("onEdit", [2, this._editCell.id, this, this._editCell.span.childNodes[0].value])
            } else {
                e = false;
                this.callEvent("onEditCancel", [this._editCell.id, this._editCell._oldValue])
            }
            if (e === true) {
                e = this._editCell.span.childNodes[0].value
            } else {
                if (e === false) {
                    e = this._editCell._oldValue
                }
            }
            var f = (e != this._editCell._oldValue);
            this._editCell.span.innerHTML = e;
            this._editCell.label = this._editCell.span.innerHTML;
            var d = this._editCell.i_sel ? "selectedTreeRow" : "standartTreeRow";
            this._editCell.span.className = d;
            this._editCell.span.parentNode.className = "standartTreeRow";
            this._editCell.span.style.paddingRight = this._editCell.span.style.paddingLeft = "5px";
            this._editCell.span.onclick = this._editCell.span.ondblclick = function () {};
            var h = this._editCell.id;
            if (this.childCalc) {
                this._fixChildCountLabel(this._editCell)
            }
            this._editCell = null;
            if (!g) {
                this.callEvent("onEdit", [3, h, this, f])
            }
            if (this._enblkbrd) {
                this.parentObject.lastChild.focus();
                this.parentObject.lastChild.focus()
            }
        }
    }
};
dhtmlXTreeObject.prototype._stopEditItem = function (c, a) {
    this._stopEdit(c)
};
dhtmlXTreeObject.prototype.stopEdit = function (a) {
    if (this._editCell) {
        this._stopEdit(this._editCell.id + "_non", a)
    }
};
dhtmlXTreeObject.prototype.editItem = function (a) {
    this._editItem(a, this)
};
dhtmlXTreeObject.prototype._editItem = function (f, a) {
    if (this._eItEd) {
        this._stopEdit();
        var d = this._globalIdStorageFind(f);
        if (!d) {
            return
        }
        var e = this.callEvent("onEdit", [0, f, this, d.span.innerHTML]);
        if (e === true) {
            e = (typeof d.span.innerText != "undefined" ? d.span.innerText : d.span.textContent)
        } else {
            if (e === false) {
                return
            }
        }
        this.dADTempOffEd = this.dADTempOff;
        this.dADTempOff = false;
        this._editCell = d;
        d._oldValue = e;
        d.span.innerHTML = "<input type='text' class='intreeeditRow' />";
        d.span.style.paddingRight = d.span.style.paddingLeft = "0px";
        d.span.onclick = d.span.ondblclick = function (g) {
            (g || event).cancelBubble = true
        };
        d.span.childNodes[0].value = e;
        d.span.childNodes[0].onselectstart = function (g) {
            (g || event).cancelBubble = true;
            return true
        };
        d.span.childNodes[0].onmousedown = function (g) {
            (g || event).cancelBubble = true;
            return true
        };
        d.span.childNodes[0].focus();
        d.span.childNodes[0].focus();
        d.span.onclick = function (g) {
            (g || event).cancelBubble = true;
            return false
        };
        d.span.className = "";
        d.span.parentNode.className = "";
        var c = this;
        d.span.childNodes[0].onkeydown = function (g) {
            if (!g) {
                g = window.event
            }
            if (g.keyCode == 13) {
                g.cancelBubble = true;
                c._stopEdit(window.undefined)
            } else {
                if (g.keyCode == 27) {
                    c._stopEdit(window.undefined, true)
                }
            }(g || event).cancelBubble = true
        };
        this.callEvent("onEdit", [1, f, this])
    }
};

function jsonPointer(c, a) {
    this.d = c;
    this.dp = a
}
jsonPointer.prototype = {
    text: function () {
        var a = function (f) {
            var e = [];
            for (var d = 0; d < f.length; d++) {
                e.push("{" + c(f[d]) + "}")
            }
            return e.join(",")
        };
        var c = function (f) {
            var e = [];
            for (var d in f) {
                if (typeof (f[d]) == "object") {
                    if (d.length) {
                        e.push('"' + d + '":[' + a(f[d]) + "]")
                    } else {
                        e.push('"' + d + '":{' + c(f[d]) + "}")
                    }
                } else {
                    e.push('"' + d + '":"' + f[d] + '"')
                }
            }
            return e.join(",")
        };
        return "{" + c(this.d) + "}"
    },
    get: function (a) {
        return this.d[a]
    },
    exists: function () {
        return !!this.d
    },
    content: function () {
        return this.d.content
    },
    each: function (e, j, h) {
        var d = this.d[e];
        var k = new jsonPointer();
        if (d) {
            for (var g = 0; g < d.length; g++) {
                k.d = d[g];
                j.apply(h, [k, g])
            }
        }
    },
    get_all: function () {
        return this.d
    },
    sub: function (a) {
        return new jsonPointer(this.d[a], this.d)
    },
    sub_exists: function (a) {
        return !!this.d[a]
    },
    each_x: function (e, k, j, h, g) {
        var d = this.d[e];
        var l = new jsonPointer(0, this.d);
        if (d) {
            for (g = g || 0; g < d.length; g++) {
                if (d[g][k]) {
                    l.d = d[g];
                    if (j.apply(h, [l, g]) == -1) {
                        return
                    }
                }
            }
        }
    },
    up: function (a) {
        return new jsonPointer(this.dp, this.d)
    },
    set: function (a, c) {
        this.d[a] = c
    },
    clone: function (a) {
        return new jsonPointer(this.d, this.dp)
    },
    through: function (d, j, m, g, n) {
        var k = this.d[d];
        if (k.length) {
            for (var e = 0; e < k.length; e++) {
                if (k[e][j] != null && k[e][j] != "" && (!m || k[e][j] == m)) {
                    var h = new jsonPointer(k[e], this.d);
                    g.apply(n, [h, e])
                }
                var l = this.d;
                this.d = k[e];
                if (this.sub_exists(d)) {
                    this.through(d, j, m, g, n)
                }
                this.d = l
            }
        }
    }
};
dhtmlXTreeObject.prototype.loadJSArrayFile = function (a, c) {
    if (window.console && window.console.info) {
        window.console.info("loadJSArrayFile was deprecated", "http://docs.dhtmlx.com/migration__index.html#migrationfrom43to44")
    }
    return this._loadJSArrayFile(a, c)
};
dhtmlXTreeObject.prototype._loadJSArrayFile = function (file, callback) {
    if (!this.parsCount) {
        this.callEvent("onXLS", [this, this._ld_id])
    }
    this._ld_id = null;
    this.xmlstate = 1;
    var that = this;
    this.XMLLoader = function (xml, callback) {
        eval("var z=" + xml.responseText);
        this._loadJSArray(z);
        if (callback) {
            callback.call(this, xml)
        }
    };
    dhx4.ajax.get(file, function (obj) {
        that.XMLLoader(obj.xmlDoc, callback)
    })
};
dhtmlXTreeObject.prototype.loadCSV = function (a, c) {
    if (window.console && window.console.info) {
        window.console.info("loadCSV was deprecated", "http://docs.dhtmlx.com/migration__index.html#migrationfrom43to44")
    }
    return this._loadCSV(a, c)
};
dhtmlXTreeObject.prototype._loadCSV = function (a, d) {
    if (!this.parsCount) {
        this.callEvent("onXLS", [this, this._ld_id])
    }
    this._ld_id = null;
    this.xmlstate = 1;
    var c = this;
    this.XMLLoader = function (e, f) {
        this._loadCSVString(e.responseText);
        if (f) {
            f.call(this, e)
        }
    };
    dhx4.ajax.get(a, function (e) {
        c.XMLLoader(e.xmlDoc, d)
    })
};
dhtmlXTreeObject.prototype.loadJSArray = function (a, c) {
    if (window.console && window.console.info) {
        window.console.info("loadJSArray was deprecated", "http://docs.dhtmlx.com/migration__index.html#migrationfrom43to44")
    }
    return this._loadJSArray(a, c)
};
dhtmlXTreeObject.prototype._loadJSArray = function (a, e) {
    var h = [];
    for (var c = 0; c < a.length; c++) {
        if (!h[a[c][1]]) {
            h[a[c][1]] = []
        }
        h[a[c][1]].push({
            id: a[c][0],
            text: a[c][2]
        })
    }
    var g = {
        id: this.rootId
    };
    var d = function (m, l) {
        if (h[m.id]) {
            m.item = h[m.id];
            for (var k = 0; k < m.item.length; k++) {
                l(m.item[k], l)
            }
        }
    };
    d(g, d);
    this._loadJSONObject(g, e)
};
dhtmlXTreeObject.prototype.loadCSVString = function (a, c) {
    if (window.console && window.console.info) {
        window.console.info("loadCSVString was deprecated", "http://docs.dhtmlx.com/migration__index.html#migrationfrom43to44")
    }
    return this._loadCSVString(a, c)
};
dhtmlXTreeObject.prototype._loadCSVString = function (a, h) {
    var k = [];
    var c = a.split("\n");
    for (var e = 0; e < c.length; e++) {
        var d = c[e].split(",");
        if (!k[d[1]]) {
            k[d[1]] = []
        }
        k[d[1]].push({
            id: d[0],
            text: d[2]
        })
    }
    var j = {
        id: this.rootId
    };
    var g = function (n, m) {
        if (k[n.id]) {
            n.item = k[n.id];
            for (var l = 0; l < n.item.length; l++) {
                m(n.item[l], m)
            }
        }
    };
    g(j, g);
    this._loadJSONObject(j, h)
};
dhtmlXTreeObject.prototype.loadJSONObject = function (a, c) {
    if (window.console && window.console.info) {
        window.console.info("loadJSONObject was deprecated", "http://docs.dhtmlx.com/migration__index.html#migrationfrom43to44")
    }
    return this._loadJSONObject(a, c)
};
dhtmlXTreeObject.prototype._loadJSONObject = function (a, c) {
    if (!this.parsCount) {
        this.callEvent("onXLS", [this, null])
    }
    this.xmlstate = 1;
    var d = new jsonPointer(a);
    this._parse(d);
    this._p = d;
    if (c) {
        c()
    }
};
dhtmlXTreeObject.prototype.loadJSON = function (a, c) {
    if (window.console && window.console.info) {
        window.console.info("loadJSON was deprecated", "http://docs.dhtmlx.com/migration__index.html#migrationfrom43to44")
    }
    return this._loadJSON(a, c)
};
dhtmlXTreeObject.prototype._loadJSON = function (file, callback) {
    if (!this.parsCount) {
        this.callEvent("onXLS", [this, this._ld_id])
    }
    this._ld_id = null;
    this.xmlstate = 1;
    var that = this;
    this.XMLLoader = function (xml, callback) {
        try {
            eval("var t=" + xml.responseText)
        } catch (e) {
            dhx4.callEvent("onLoadXMLerror", ["Incorrect JSON", (xml), this]);
            return
        }
        var p = new jsonPointer(t);
        this._parse(p);
        this._p = p;
        if (callback) {
            callback.call(this, xml)
        }
    };
    dhx4.ajax.get(file, function (obj) {
        that.XMLLoader(obj.xmlDoc, callback)
    })
};
dhtmlXTreeObject.prototype.serializeTreeToJSON = function () {
    var a = ['{"id":"' + this.rootId + '", "item":['];
    var d = [];
    for (var c = 0; c < this.htmlNode.childsCount; c++) {
        d.push(this._serializeItemJSON(this.htmlNode.childNodes[c]))
    }
    a.push(d.join(","));
    a.push("]}");
    return a.join("")
};
dhtmlXTreeObject.prototype._serializeItemJSON = function (h) {
    var a = [];
    if (h.unParsed) {
        return (h.unParsed.text())
    }
    if (this._selected.length) {
        var d = this._selected[0].id
    } else {
        d = ""
    }
    var g = h.span.innerHTML;
    g = g.replace(/\"/g, '\\"', g);
    if (!this._xfullXML) {
        a.push('{ "id":"' + h.id + '", ' + (this._getOpenState(h) == 1 ? ' "open":"1", ' : "") + (d == h.id ? ' "select":"1",' : "") + ' "text":"' + g + '"' + (((this.XMLsource) && (h.XMLload == 0)) ? ', "child":"1" ' : ""))
    } else {
        a.push('{ "id":"' + h.id + '", ' + (this._getOpenState(h) == 1 ? ' "open":"1", ' : "") + (d == h.id ? ' "select":"1",' : "") + ' "text":"' + g + '", "im0":"' + h.images[0] + '", "im1":"' + h.images[1] + '", "im2":"' + h.images[2] + '" ' + (h.acolor ? (', "aCol":"' + h.acolor + '" ') : "") + (h.scolor ? (', "sCol":"' + h.scolor + '" ') : "") + (h.checkstate == 1 ? ', "checked":"1" ' : (h.checkstate == 2 ? ', "checked":"-1"' : "")) + (h.closeable ? ', "closeable":"1" ' : "") + (((this.XMLsource) && (h.XMLload == 0)) ? ', "child":"1" ' : ""))
    }
    if ((this._xuserData) && (h._userdatalist)) {
        a.push(', "userdata":[');
        var f = h._userdatalist.split(",");
        var e = [];
        for (var c = 0; c < f.length; c++) {
            e.push('{ "name":"' + f[c] + '" , "content":"' + h.userData["t_" + f[c]] + '" }')
        }
        a.push(e.join(","));
        a.push("]")
    }
    if (h.childsCount) {
        a.push(', "item":[');
        var e = [];
        for (var c = 0; c < h.childsCount; c++) {
            e.push(this._serializeItemJSON(h.childNodes[c]))
        }
        a.push(e.join(","));
        a.push("]\n")
    }
    a.push("}\n");
    return a.join("")
};

function dhtmlXTreeFromHTML(obj) {
    if (typeof (obj) != "object") {
        obj = document.getElementById(obj)
    }
    var n = obj;
    var id = n.id;
    var cont = "";
    for (var j = 0; j < obj.childNodes.length; j++) {
        if (obj.childNodes[j].nodeType == "1") {
            if (obj.childNodes[j].tagName == "XMP") {
                var cHead = obj.childNodes[j];
                for (var m = 0; m < cHead.childNodes.length; m++) {
                    cont += cHead.childNodes[m].data
                }
            } else {
                if (obj.childNodes[j].tagName.toLowerCase() == "ul") {
                    cont = dhx_li2trees(obj.childNodes[j], new Array(), 0)
                }
            }
            break
        }
    }
    obj.innerHTML = "";
    var t = new dhtmlXTreeObject(obj, "100%", "100%", 0);
    var z_all = new Array();
    for (b in t) {
        z_all[b.toLowerCase()] = b
    }
    var atr = obj.attributes;
    for (var a = 0; a < atr.length; a++) {
        if ((atr[a].name.indexOf("set") == 0) || (atr[a].name.indexOf("enable") == 0)) {
            var an = atr[a].name;
            if (!t[an]) {
                an = z_all[atr[a].name]
            }
            t[an].apply(t, atr[a].value.split(","))
        }
    }
    if (typeof (cont) == "object") {
        t.XMLloadingWarning = 1;
        for (var i = 0; i < cont.length; i++) {
            var n = t.insertNewItem(cont[i][0], cont[i][3], cont[i][1]);
            if (cont[i][2]) {
                t._setCheck(n, cont[i][2])
            }
        }
        t.XMLloadingWarning = 0;
        t.lastLoadedXMLId = 0;
        t._redrawFrom(t)
    } else {
        t.parse("<tree id='0'>" + cont + "</tree>")
    }
    window[id] = t;
    var oninit = obj.getAttribute("oninit");
    if (oninit) {
        eval(oninit)
    }
    return t
}

function dhx_init_trees() {
    var c = document.getElementsByTagName("div");
    for (var a = 0; a < c.length; a++) {
        if (c[a].className == "dhtmlxTree") {
            dhtmlXTreeFromHTML(c[a])
        }
    }
}

function dhx_li2trees(n, g, d) {
    for (var h = 0; h < n.childNodes.length; h++) {
        var m = n.childNodes[h];
        if ((m.nodeType == 1) && (m.tagName.toLowerCase() == "li")) {
            var l = "";
            var k = null;
            var a = m.getAttribute("checked");
            for (var f = 0; f < m.childNodes.length; f++) {
                var e = m.childNodes[f];
                if (e.nodeType == 3) {
                    l += e.data
                } else {
                    if (e.tagName.toLowerCase() != "ul") {
                        l += dhx_outer_html(e)
                    } else {
                        k = e
                    }
                }
            }
            g[g.length] = [d, l, a, (m.id || (g.length + 1))];
            if (k) {
                g = dhx_li2trees(k, g, (m.id || g.length))
            }
        }
    }
    return g
}

function dhx_outer_html(c) {
    if (c.outerHTML) {
        return c.outerHTML
    }
    var a = document.createElement("DIV");
    a.appendChild(c.cloneNode(true));
    a = a.innerHTML;
    return a
}
if (window.addEventListener) {
    window.addEventListener("load", dhx_init_trees, false)
} else {
    if (window.attachEvent) {
        window.attachEvent("onload", dhx_init_trees)
    }
};
