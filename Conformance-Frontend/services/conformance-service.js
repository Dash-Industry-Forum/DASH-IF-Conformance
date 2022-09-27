let ConformanceService = function () {
  async function validateContentByUrl(url) {
    let sessionId = "id" + Math.floor(100000 + Math.random() * 900000);
    let directoryId = "id" + Math.floor(Math.random() * 10000000000 + 1);

    let payload = "";
    payload += 'url="' + url + '"';
    payload = encodeURIComponent(payload);

    try {
      let method = "POST";
      let requestUrl = "../Utils/Process.php";
      let headers = {
        "content-type": "application/x-www-form-urlencoded",
      };
      let response = await sendRequest(method, requestUrl, headers, payload);
      console.log(response);
    } catch (error) {
      console.error(error);
    }
  }

  function sendRequest(method, uri, headers, data) {
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
    validateContentByUrl,
  };
  return instance;
};

ConformanceService = new ConformanceService();
