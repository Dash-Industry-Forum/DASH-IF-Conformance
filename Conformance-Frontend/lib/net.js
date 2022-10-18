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

  let instance = {
    sendRequest,
  };
  return instance;
})();
