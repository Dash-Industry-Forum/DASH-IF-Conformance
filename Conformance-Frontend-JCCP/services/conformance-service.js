const { wait } = Tools;

const ConformanceService = (function () {
  const modules = [
    {
      id: "segment-validation",
      text: "Segment Validation",
      queryParam: "segments",
    },
    { id: "dash-if", text: "DASH-IF", queryParam: "dash" },
    { id: "cmaf", text: "CMAF", queryParam: "cmaf" },
    { id: "cta-wave", text: "CTA-WAVE", queryParam: "ctawave" },
    { id: "hbbtv", text: "HbbTV", queryParam: "hbbtv" },
    { id: "dvb", text: "DVB", queryParam: "dvb" },
    {
      id: "dash-if-ll",
      text: "DASH-IF IOP Low Latency",
      queryParam: "lowlatency",
    },
    { id: "dash-if-iop", text: "DASH-IF Interoperability", queryParam: "iop" },
    { id: "dolby", text: "Dolby", queryParam: "dolby" },
  ];

  async function validateContentByUrl({ mpdUrl, activeModules }) {
    let uri = "/Utils/Process_cli.php?";
    uri = uri + `url=${mpdUrl}&`;
    modules.forEach((module) => {
      if (!module.queryParam) return;
      uri =
        uri + `${module.queryParam}=${activeModules[module.id] ? "1" : "0"}&`;
    });
    let results = await Net.sendRequest({ method: "GET", uri });
    results = JSON.parse(results);
    results = convertInfoData(results);
    return results;

    //return Mock.testResults[0];
  }

  function convertInfoData(result) {
    let moduleNames = Object.keys(result.entries).filter(
      (key) => key !== "Stats" && key !== "verdict"
    );
    let modules = moduleNames.map((name) => {
      let module = result.entries[name];
      module.name = name;
      return module;
    });

    modules.forEach((module) => {
      let partNames = Object.keys(module).filter(
        (key) => typeof module[key] === "object" && "test" in module[key]
      );
      let parts = partNames.map((partName) => {
        let part = module[partName];
        part.name = partName;
        return part;
      });

      parts.forEach((part) => {
        if (!part.info) return;
        let { info } = part;
        info.forEach((info) => {
          let xmlStart = info.split("").findIndex((char) => char === "<");
          if (xmlStart === -1) return;
          let xmlEnd = info
            .split("")
            .reverse()
            .findIndex((char) => char === ">");
          xmlEnd = info.length - xmlEnd;
          let xmlString = info.substring(xmlStart, xmlEnd);
          xmlString = `<results>${xmlString}</results>`;
          let parser = new DOMParser();
          let xml = parser.parseFromString(xmlString, "text/xml");
          let xmlTestResults = Array.from(
            xml.getElementsByTagName("svrl:failed-assert")
          );
          xmlTestResults.forEach((xmlTestResult, index) => {
            let xmlMessages = Array.from(
              xmlTestResult.getElementsByTagName("svrl:text")
            );
            let messages = xmlMessages.map(
              (xmlMessage) => xmlMessage.textContent
            );
            messages.push("");
            messages.push("At location:");
            messages = messages.concat(
              xmlTestResult.getAttribute("location").split("/*:")
            );
            let testResult = {
              spec: module.name,
              section: part.name,
              test: `Error #${index + 1} ` + xmlTestResult.getAttribute("test"),
              messages: messages,
              state: "FAIL",
            };
            if (!part.test) part.test = [];
            part.test.push(testResult);
          });
        });
      });
    });
    return result;
  }

  let instance = {
    validateContentByUrl,
    modules,
  };
  return instance;
})();
