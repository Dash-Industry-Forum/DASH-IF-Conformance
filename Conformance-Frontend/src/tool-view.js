function ToolView() {
  const URL = "url";
  const FILE = "file";
  const TEXT = "text";

  let modules = ConformanceService.modules;

  let _state = {
    result: Mock.testResults[0],
    detailSelect: { module: null, part: null, section: null, test: null },
  };

  let _rootElementId;
  let _resultsElementId;
  let _resultSummaryId;
  let _resultDetailsId;

  let _validator = new Validator({ modules });

  async function handleProcessingFinished({ result, duration }) {
    _state.result = result;
    _state.duration = duration;
    _state.detailSelect = { module: null, part: null, section: null, test: null },
    renderResults();
  }
  _validator.onProcessingFinished(handleProcessingFinished);

  function render(rootElementId) {
    _rootElementId = rootElementId = rootElementId || _rootElementId;
    let validatorFormElementId = UI.generateElementId();
    let resultsElementId = UI.generateElementId();
    let toolView = UI.createElement({
      id: _rootElementId,
      className: "d-flex flex-column",
      children: [
        { element: "h1", text: "Validator" },
        { id: validatorFormElementId },
        { id: resultsElementId },
      ],
    });
    UI.replaceElement(_rootElementId, toolView);
    _validator.render(validatorFormElementId);
    renderResults(resultsElementId);
  }

  function renderResults(elementId) {
    _resultsElementId = elementId = elementId || _resultsElementId;
    if (!_state.result) return;
    let resultSummaryId = UI.generateElementId();
    let resultDetailsId = UI.generateElementId();
    let resultsView = UI.createElement({
      id: elementId,
      children: [
        {
          className: "d-flex flex-row align-items-baseline pt-3 mb-2",
          children: [
            { text: "Result", className: "fs-2 flex-grow-1" },
            {
              className: "d-flex flex-row align-items-center",
              children: [
                {
                  className: "me-2",
                  text: `Processing Time: ${Tools.msToTime(_state.duration, {
                    alwaysShowMins: true,
                  })}`,
                },
                {
                  className: "btn-group btn-group-sm",
                  role: "group",
                  ariaLabel: "Export",
                  children: [
                    {
                      element: "button",
                      type: "button",
                      className: "btn btn-outline-dark",
                      children: [
                        {
                          element: "i",
                          className: "fa-solid fa-download me-2",
                        },
                        { element: "span", text: "json" },
                      ],
                      onClick: () => {
                        let fileName =
                          "val-result-" + new Date().toISOString() + ".json";
                        let type = "application/json";
                        let data = JSON.stringify(_state.result, null, 2);

                        Tools.downloadFileFromData({ fileName, type, data });
                      },
                    },
                    {
                      element: "button",
                      type: "button",
                      className: "btn btn-outline-dark",
                      children: [
                        {
                          element: "i",
                          className:
                            "fa-solid fa-arrow-up-right-from-square me-2",
                        },
                        { element: "span", text: "html" },
                      ],
                      onClick: () => {
                        let report = HtmlReport.generateReport(_state.result);
                        HtmlReport.openReport(report);
                      },
                    },
                  ],
                },
              ],
            },
          ],
        },
        {
          className: "container-fluid d-flex border rounded p-0",
          style: "max-height: 90vh",
          children: [{ id: resultSummaryId }, { id: resultDetailsId }],
        },
      ],
    });

    UI.replaceElement(_resultsElementId, resultsView);
    renderResultSummary(resultSummaryId);
    renderResultDetails(resultDetailsId);
  }

  function renderResultSummary(elementId) {
    _resultSummaryId = elementId = elementId || _resultSummaryId;
    let resultSummary = UI.createElement({
      id: elementId,
      className: "border-end w-50 d-flex flex-column",
      children: [
        {
          text: "Summary",
          className: "fs-5 fw-semibold bg-light border-bottom py-2 px-3",
        },
        {
          id: elementId + "-scroll",
          className: "flex-grow-1 overflow-auto",
          children: Object.keys(_state.result.entries)
            .filter((key) => key !== "Stats" && key !== "verdict")
            .map((module, index) => ({
              className: "p-3 border-bottom",
              children: [
                {
                  className: "fs-5 mb-2",
                  children: [
                    {
                      element: "i",
                      className: getVerdictIcon(
                        _state.result.entries[module].verdict
                      ),
                    },
                    { element: "span", className: "ms-2", text: module },
                  ],
                },
                {
                  children: Object.keys(_state.result.entries[module])
                    .filter((key) => key !== "verdict")
                    .map((part) => ({
                      children: [
                        {
                          className: "my-3 fw-semibold",
                          children: [
                            {
                              element: "i",
                              className: getVerdictIcon(
                                _state.result.entries[module][part].verdict
                              ),
                              style: "width: 1.5em",
                            },
                            { element: "span", text: part },
                          ],
                        },
                        {
                          className: "list-group",
                          children: _state.result.entries[module][
                            part
                          ].test.map(({ section, test, state }) => ({
                            element: "a",
                            className:
                              "list-group-item list-group-item-action" +
                              (isSelected({ module, part, section, test })
                                ? " fw-semibold bg-light"
                                : ""),
                            href: "#",
                            onClick: isSelected({
                              module,
                              part,
                              section,
                              test,
                            })
                              ? () => {}
                              : () => {
                                  _state.detailSelect = {
                                    module,
                                    part,
                                    section,
                                    test,
                                  };
                                  UI.saveScrollPosition(elementId + "-scroll");
                                  renderResultSummary();
                                  renderResultDetails();
                                },
                            children: [
                              {
                                element: "i",
                                className: getVerdictIcon(state),
                                style: "width: 1.5em",
                              },
                              { element: "span", text: section },
                              {
                                element: "i",
                                className: "fa-solid fa-circle mx-2",
                                style: "font-size: 0.3em; vertical-align: 1em",
                              },
                              {
                                element: "span",
                                text: test,
                              },
                            ],
                          })),
                        },
                      ],
                    })),
                },
              ],
            })),
        },
      ],
    });
    UI.replaceElement(_resultSummaryId, resultSummary);
    UI.loadScrollPosition(elementId + "-scroll");
  }

  function renderResultDetails(elementId) {
    _resultDetailsId = elementId = elementId || _resultDetailsId;
    let { module, part, section, test } = _state.detailSelect;
    if (!module || !part || !section || !test) {
      let instructions = UI.createElement({
        id: elementId,
        className: "bg-light w-50",
        children: {
          className:
            "container-fluid text-center text-secondary h-100 d-flex flex-column justify-content-center",
          style: { maxHeight: "40vh" },
          children: [
            {
              element: "i",
              className: "fa-solid fa-circle-info mb-3",
              style: "font-size: 5em",
            },
            {
              text: "Select a test to see details",
            },
          ],
        },
      });
      UI.replaceElement(elementId, instructions);
      return;
    }
    let { state, messages } = _state.result.entries[module][part].test.find(
      (element) => element.test === test && element.section === section
    );
    let resultDetails = UI.createElement({
      id: elementId,
      className: "w-50",
      children: [
        {
          text: "Details",
          className: "fs-5 fw-semibold bg-light border-bottom py-2 px-3",
        },
        {
          element: "table",
          className: "table",
          children: {
            element: "tbody",
            children: [
              {
                element: "tr",
                children: [
                  { element: "td", text: "Section" },
                  { element: "td", text: section },
                ],
              },
              {
                element: "tr",
                children: [
                  { element: "td", text: "Test" },
                  { element: "td", text: test },
                ],
              },
              {
                element: "tr",
                children: [
                  { element: "td", text: "State" },
                  {
                    element: "td",
                    children: [
                      { element: "i", className: getVerdictIcon(state) },
                      { element: "span", text: state, className: "ms-1" },
                    ],
                  },
                ],
              },
              {
                element: "tr",
                children: [
                  { element: "td", text: "Module" },
                  { element: "td", text: module },
                ],
              },
              {
                element: "tr",
                children: [
                  { element: "td", text: "Messages" },
                  {
                    element: "td",
                    children: {
                      className:
                        "font-monospace overflow-auto border rounded bg-light p-2",
                      style: "max-height: 30em",
                      children: messages.map((message) => ({ text: message })),
                    },
                  },
                ],
              },
            ],
          },
        },
      ],
    });
    UI.replaceElement(elementId, resultDetails);
  }

  function isSelected({ module, part, section, test }) {
    let isSelected = true;
    isSelected = isSelected && module === _state.detailSelect.module;
    isSelected = isSelected && part === _state.detailSelect.part;
    isSelected = isSelected && section === _state.detailSelect.section;
    isSelected = isSelected && test === _state.detailSelect.test;
    return isSelected;
  }

  function getVerdictIcon(verdict) {
    switch (verdict) {
      case "PASS":
        return "fa-solid fa-check text-success";
      case "FAIL":
        return "fa-solid fa-xmark text-danger";
      default:
        return "fa-solid fa-question";
    }
  }

  let instance = {
    render,
  };
  return instance;
}
