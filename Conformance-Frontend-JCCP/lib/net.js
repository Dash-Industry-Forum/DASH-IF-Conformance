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

  // https://www.freecodecamp.org/news/check-if-a-javascript-string-is-a-url/
  function isValidUrl(urlString) {
    let urlPattern = new RegExp(
      "^(https?:\\/\\/)?" + // validate protocol
        "((([a-z\\d]([a-z\\d-]*[a-z\\d])*)\\.)+[a-z]{2,}|" + // validate domain name
        "((\\d{1,3}\\.){3}\\d{1,3}))" + // validate OR ip (v4) address
        "(\\:\\d+)?(\\/[-a-z\\d%_.~+]*)*" + // validate port and path
        "(\\?[;&a-z\\d%_.~+=-]*)?" + // validate query string
        "(\\#[-a-z\\d_]*)?$",
      "i"
    ); // validate fragment locator
    return !!urlPattern.test(urlString);
  }

  let instance = {
    sendRequest,
    isValidUrl,
  };
  return instance;
})();
