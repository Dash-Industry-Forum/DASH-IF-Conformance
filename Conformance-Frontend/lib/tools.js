const Tools = (function () {
  let _markdownConverter = null;

  async function wait(millis) {
    return new Promise(function (resolve) {
      setTimeout(resolve, millis);
    });
  }

  // https://stackoverflow.com/questions/9763441/milliseconds-to-time-in-javascript
  function msToTime(
    s,
    { showMs = false, alwaysShowHrs = false, alwaysShowMins = false } = {}
  ) {
    // Pad to 2 or 3 digits, default is 2
    function pad(n, z) {
      z = z || 2;
      return ("00" + n).slice(-z);
    }

    var ms = s % 1000;
    s = (s - ms) / 1000;
    var secs = s % 60;
    s = (s - secs) / 60;
    var mins = s % 60;
    var hrs = (s - mins) / 60;

    let timeString = "";

    if (alwaysShowHrs || hrs > 0) timeString += pad(hrs) + ":";
    if (alwaysShowMins || hrs > 0 || mins > 0) timeString += pad(mins) + ":";
    timeString += pad(secs);
    if (showMs) timeString += "." + pad(ms, 3);

    return timeString;
  }

  function downloadFileFromData({ fileName, data, type }) {
    let link = document.createElement("a");
    link.download = fileName;

    let blob = new Blob([data], { type });
    link.href = window.URL.createObjectURL(blob);
    link.click();
  }

  function kebabize(str) {
    return str
      .split("")
      .map((letter, idx) => {
        return letter.toUpperCase() === letter
          ? `${idx !== 0 ? "-" : ""}${letter.toLowerCase()}`
          : letter;
      })
      .join("");
  }

  function getMarkdownConverter() {
    if (_markdownConverter) return _markdownConverter;

    const classMap = {
//      body: "container",
//      h1: "ui large header",
//      h2: "ui medium header",
//      ul: "ui list",
//      li: "ui item",
    };

    const bindings = Object.keys(classMap).map((key) => ({
      type: "output",
      regex: new RegExp(`<${key}(.*)>`, "g"),
      replace: `<${key} class="${classMap[key]}" $1>`,
    }));

    _markdownConverter = new showdown.Converter({
      extensions: bindings,
    });

    return _markdownConverter;
  }

  function markdownToHtml(markdownText) {
    let converter = getMarkdownConverter();
    let content = converter.makeHtml(markdownText);
    let parser = new DOMParser();
    let doc = parser.parseFromString(content, "text/html");
    let html = UI.createElement({
      className: "container",
    });
    let elements = Array.from(doc.body.children);
    elements.forEach(element => html.appendChild(element));
    return html;
  }

  let instance = {
    wait,
    msToTime,
    downloadFileFromData,
    kebabize,
    markdownToHtml,
  };
  return instance;
})();
