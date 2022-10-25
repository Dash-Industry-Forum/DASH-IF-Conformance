const { wait } = Tools;

const ConformanceService = (function () {
  const modules = [
    {
      id: "segment-validation",
      text: "Segment Validation",
      queryParam: "segments",
    },
    { id: "dash-if", text: "Dash-IF" },
    { id: "ll-dash-if", text: "LL Dash-IF" },
    { id: "dvb-19", text: "DVB (2019)" },
    { id: "dvb-18", text: "DVB (2018)" },
    { id: "hbbtv", text: "HbbTV" },
    { id: "cmaf", text: "CMAF", queryParam: "cmaf" },
    { id: "cta-wave", text: "CTA-WAVE" },
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
