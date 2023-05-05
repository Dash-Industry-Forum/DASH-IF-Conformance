const Net = (function () {
  function sendRequest({ method, uri, headers, data }) {
    return new Promise((resolve, reject) => {
      var xhr = new XMLHttpRequest();
      xhr.onload = function () {
        if (xhr.status === 200) {
          resolve(xhr.response);
        } else {
          reject({ status: xhr.status, response: xhr.response });
        }
      };
      xhr.onerror = function () {
        reject();
      };
      xhr.open(method, uri, true);
      for (var header in headers) {
        xhr.setRequestHeader(header, headers[header]);
      }
      xhr.send(data);
    });
  }

  function sendFormRequest({ url, variables }) {
    var form = document.createElement("form");
    form.setAttribute("method", "POST");
    form.setAttribute("action", url);

    for (var name in variables) {
      var value = variables[name];
      var postVariable = document.createElement("input");
      postVariable.setAttribute("type", "hidden");
      postVariable.setAttribute("name", name);
      postVariable.setAttribute("value", value);
      form.appendChild(postVariable);
    }
    document.body.appendChild(form);

    form.submit();
  }

  function isValidUrl(urlString) {
    try {
      const url = new URL(urlString);
      return url.protocol === "http:" || url.protocol === "https:";
    } catch (_) {
      return false;
    }
  }

  let instance = {
    sendRequest,
    sendFormRequest,
    isValidUrl,
  };
  return instance;
})();
