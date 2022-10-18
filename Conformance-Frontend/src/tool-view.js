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

  let _result;

  let _rootElementId;
  let _validatorFormElementId;
  let _resultsElementId;
  let _resultSummaryId;

  async function handleValidation() {
    const mpdUrlInput = UI.getElement("mpd-url");
    const mpdUrl = mpdUrlInput.value;
    _result = await ConformanceService.validateContentByUrl(mpdUrl);
    renderResults();
  }

  function render(rootElementId) {
    _rootElementId = rootElementId = rootElementId || _rootElementId;
    let validatorFormElementId = UI.generateElementId();
    let resultsElementId = UI.generateElementId();
    let toolView = UI.createElement({
      id: _rootElementId,
      className: "d-flex flex-column",
      children: [
        { element: "h1", text: "Home" },
        { id: validatorFormElementId },
        { id: resultsElementId },
      ],
    });
    UI.replaceElement(_rootElementId, toolView);
    renderValidatorForm(validatorFormElementId);
    renderResults(resultsElementId);
  }

  function renderValidatorForm(elementId) {
    _validatorFormElementId = elementId = elementId || _validatorFormElementId;
    let validatorForm = UI.createElement({
      id: elementId,
      className: "container border rounded p-3 bg-light",
      style: "max-width: 768px",
      children: {
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
    });
    UI.replaceElement(elementId, validatorForm);
  }

  function renderResults(elementId) {
    _resultsElementId = elementId = elementId || _resultsElementId;
    if (!_result) return;
    let resultSummaryId = UI.generateElementId();
    let resultDetailsId = UI.generateElementId();
    let resultsView = UI.createElement({
      id: elementId,
      children: [
        { element: "h2", text: "Results" },
        {
          className: "border rounded p-3",
          children: [{ id: resultSummaryId, className: "border-end" }, { id: resultDetailsId, style: "width: 300px" }],
        },
      ],
    });

    UI.replaceElement(_resultsElementId, resultsView);
    renderResultSummary(resultSummaryId);
  }

  function renderResultSummary(elementId) {
    _resultSummaryId = elementId = elementId || _resultSummaryId;
    let resultSummary = UI.createElement({
      id: elementId,
      children: Object.keys(_result.entries)
        .filter((key) => key !== "Stats" && key !== "verdict")
        .map((module) => ({
          children: [
            {
              children: [
                {
                  element: "i",
                  className: getVerdictIcon(_result.entries[module].verdict),
                  style: "width: 1.5em",
                },
                { element: "span", text: module },
              ],
            },
            {
              style: "padding-left: 1.5em",
              children: Object.keys(_result.entries[module])
                .filter((key) => key !== "verdict")
                .map((part) => ({
                  children: [
                    {
                      children: [
                        {
                          element: "i",
                          className: getVerdictIcon(
                            _result.entries[module][part].verdict
                          ),
                          style: "width: 1.5em",
                        },
                        { element: "span", text: part },
                      ],
                    },
                    {
                      style: "padding-left: 1.5em",
                      children: _result.entries[module][part].test.map(
                        (test) => ({
                          children: [
                            {
                              element: "i",
                              className: getVerdictIcon(test.state),
                              style: "width: 1.5em",
                            },
                            { element: "span", text: test.section },
                            {
                              element: "i",
                              className: "fa-solid fa-circle mx-2",
                              style: "font-size: 0.3em; vertical-align: 1em",
                            },
                            { element: "span", text: test.test },
                          ],
                        })
                      ),
                    },
                  ],
                })),
            },
          ],
        })),
    });
    UI.replaceElement(_resultSummaryId, resultSummary);
  }

  function getVerdictIcon(verdict) {
    switch (verdict) {
      case "PASS":
        return "fa-solid fa-check";
      case "FAIL":
        return "fa-solid fa-xmark";
      default:
        return "fa-solid fa-question";
    }
  }

  let instance = {
    render,
  };
  return instance;
}
