const Tools = (function () {
  async function wait(millis) {
    return new Promise(function (resolve) {
      setTimeout(resolve, millis);
    });
  }

  let instance = {
    wait,
  };
  return instance;
})();
