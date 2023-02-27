const { wait } = Tools;

const ConformanceService = (function () {
  const modules = [
    {
      id: "segment-validation",
      text: "Segment Validation",
      queryParam: "segments",
      m3u8Compatible: true,
    },
    {
      id: "dash-if",
      text: "DASH-IF",
      queryParam: "dash",
      m3u8Compatible: false,
    },
    { id: "cmaf", text: "CMAF", queryParam: "cmaf", m3u8Compatible: true },
    {
      id: "cta-wave",
      text: "CTA-WAVE",
      queryParam: "ctawave",
      m3u8Compatible: true,
    },
    { id: "hbbtv", text: "HbbTV", queryParam: "hbbtv", m3u8Compatible: false },
    {
      id: "latest_xsd",
      text: "Latest XSD",
      queryParam: "latest_xsd",
      m3u8Compatible: false,
    },
    {
      id: "dvb",
      text: "DVB (2018 xsd)",
      queryParam: "dvb",
      m3u8Compatible: false,
    },
    {
      id: "dvb2019",
      text: "DVB (2019 xsd)",
      queryParam: "dvb_2019",
      m3u8Compatible: false,
    },
    {
      id: "dash-if-ll",
      text: "DASH-IF IOP Low Latency",
      queryParam: "lowlatency",
      m3u8Compatible: false,
    },
    {
      id: "dash-if-iop",
      text: "DASH-IF Interoperability",
      queryParam: "iop",
      m3u8Compatible: false,
    },
    { id: "dolby", text: "Dolby", queryParam: "dolby", m3u8Compatible: false },
    {
      id: "autodetect",
      text: "Automatically detect profiles",
      queryParam: "autodetect",
      m3u8Compatible: true,
    },
  ];

  const BASE_URI = "/Utils/Process_cli.php?";

  async function validateContentByUrl({ mpdUrl, activeModules }) {
    let uri = BASE_URI + `url=${mpdUrl}&`;
    modules.forEach((module) => {
      if (!module.queryParam) return;
      uri =
        uri + `${module.queryParam}=${activeModules[module.id] ? "1" : "0"}&`;
    });
    let results = await Net.sendRequest({ method: "GET", uri });
    results = JSON.parse(results);
    results = convertInfoData(results);
    return results;
  }

  async function validateContentByText({ mpdText, activeModules }) {
    mpdText = encodeURIComponent(mpdText);
    let data = `mpd=${mpdText}&`;
    modules.forEach((module) => {
      if (!module.queryParam) return;
      data =
        data + `${module.queryParam}=${activeModules[module.id] ? "1" : "0"}&`;
    });
    let results = await Net.sendRequest({
      method: "POST",
      uri: BASE_URI,
      data,
      headers: {
        "Content-type": "application/x-www-form-urlencoded",
      },
    });
    results = JSON.parse(results);
    results = convertInfoData(results);
    return results;
  }

  async function validateContentByFile({ mpdFile, activeModules }) {
    let data = new FormData();
    data.append("mpd", mpdFile);
    modules.forEach((module) => {
      if (!module.queryParam) return;
      data.append(module.queryParam, activeModules[module.id] ? "1" : "0");
    });
    let results = await Net.sendRequest({
      method: "POST",
      uri: BASE_URI,
      data,
    });
    results = JSON.parse(results);
    results = convertInfoData(results);
    return results;
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
              test: `Error #${index + 1} ` + messages[0],
              messages: messages,
              state: "FAIL",
            };
            if (!part.test) part.test = [];
            part.test.push(testResult);
          });
        });
      });
    });
    return new ValidationResult(result);
  }

  let instance = {
    validateContentByUrl,
    validateContentByText,
    validateContentByFile,
    convertInfoData,
    modules,
  };
  return instance;
})();
