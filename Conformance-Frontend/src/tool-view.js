function ToolView() {
  let additionalTests = [
    { id: "segment-validation", text: "Segment Validation" },
    { id: "dash-if", text: "Dash-IF" },
    { id: "ll-dash-if", text: "LL Dash-IF" },
    { id: "dvb-19", text: "DVB (2019)" },
    { id: "dvb-18", text: "DVB (2018)" },
    { id: "hbbtv", text: "HbbTV" },
    { id: "cmaf", text: "CMAF" },
    { id: "cta-wave", text: "CTA-WAVE" },
  ];

  function handleValidation() {
    const mpdUrlInput = UI.getElement("mpd-url");
    const mpdUrl = mpdUrlInput.value;
    ConformanceService.validateContentByUrl(mpdUrl);
  }

  function render() {
    return UI.createElement({
      className: "d-flex flex-column",
      children: [
        {
          element: "h1",
          text: "Home",
        },
        {
          element: "form",
          children: [
            {
              className: "mb-3 row",
              children: [
                {
                  element: "label",
                  className: "col-sm-2 col-form-label",
                  for: "mpd-url",
                  text: "MPD",
                },
                {
                  className: "col-sm-10",
                  children: {
                    element: "input",
                    type: "textbox",
                    className: "form-control",
                    id: "mpd-url",
                  },
                },
              ],
            },
            {
              className: "mb-3 row",
              children: [
                {
                  element: "label",
                  className: "col-sm-2 col-form-label",
                  text: "Include additional tests",
                },
                {
                  className: "col-sm-10",
                  children: additionalTests.map((test) => ({
                    className: "form-check",
                    children: [
                      {
                        element: "input",
                        type: "checkbox",
                        className: "form-check-input",
                        id: test.id,
                      },
                      {
                        element: "label",
                        className: "form-check-label",
                        for: test.id,
                        text: test.text,
                      },
                    ],
                  })),
                },
              ],
            },
            {
              className: "d-grid gap-2 d-md-flex justify-content-md-end",
              children: [
                {
                  element: "button",
                  type: "button",
                  className: "btn btn-primary",
                  text: "Validate",
                  onclick: handleValidation,
                },
              ],
            },
          ],
        },
      ],
    });
  }

  let instance = {
    render,
  };
  return instance;
}
