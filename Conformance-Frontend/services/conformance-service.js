const { wait } = Tools;

const ConformanceService = (function () {
  async function validateContentByUrl(url, progressCallback) {
    const results = Mock.testResults[0];
    await wait(500);
    return results;
//    for (let entryName in results.entries) {
//      let entry = results.entries[entryName];
//      if (entryName === "verdict" || entryName === "Stats") continue;
//
//      let moduleName = entryName;
//      let module = entry;
//      progressCallback({level: "module", type: "start", payload: moduleName});
//      await wait(200);
//      
//
//      progressCallback({level: "module", type: "end"});
//      await wait(200);
//    }
  }

  let instance = {
    validateContentByUrl,
  };
  return instance;
})();
