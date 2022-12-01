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
    { id: "dash-if-ll", text: "DASH-IF IOP Low Latency", queryParam: "lowlatency" },
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
    return results;


    //return Mock.testResults[0];
  }

  let instance = {
    validateContentByUrl,
    modules,
  };
  return instance;
})();
