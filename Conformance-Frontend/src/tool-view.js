function ToolView() {
  const URL = "url";
  const FILE = "file";
  const TEXT = "text";

  let modules = ConformanceService.modules;

  let _state = {
    result: null,
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
          className: "d-flex flex-row align-items-baseline pt-3",
          children: [
            { text: "Result", className: "fs-2 flex-grow-1" },
            {
              children: [
                {
                  text: `Processing Time: ${Tools.msToTime(_state.duration, {
                    alwaysShowMins: true,
                  })}`,
                },
              ],
            },
          ],
        },
        {
          className: "container-fluid d-flex border rounded p-0",
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
      className: "border-end p-3 w-50",
      children: [
        { element: "h5", text: "Summary", className: "mb-3 fw-semibold" },
        {
          children: Object.keys(_state.result.entries)
            .filter((key) => key !== "Stats" && key !== "verdict")
            .map((module, index) => ({
              children: [
                {
                  className: "fs-5 mb-2",
                  children: [
                    {
                      element: "i",
                      className: getVerdictIcon(
                        _state.result.entries[module].verdict
                      ),
                      style: "width: 1.5em",
                    },
                    { element: "span", text: module },
                  ],
                },
                {
                  style: "padding-left: 1.5em",
                  children: Object.keys(_state.result.entries[module])
                    .filter((key) => key !== "verdict")
                    .map((part) => ({
                      children: [
                        {
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
                          style: "padding-left: 1.5em",
                          children: _state.result.entries[module][
                            part
                          ].test.map(({ section, test, state }) => ({
                            className:
                              "pb-2 " +
                              (isSelected({ module, part, section, test })
                                ? "fw-semibold"
                                : "link-primary text-decoration-underline"),
                            style: { cursor: "pointer" },
                            onclick: isSelected({
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
                Object.keys(_state.result.entries).length - 3 === index
                  ? null
                  : { element: "hr" },
              ],
            })),
        },
      ],
    });
    UI.replaceElement(_resultSummaryId, resultSummary);
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
      className: "p-3 w-50",
      children: [
        { element: "h5", text: "Details" },
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
