function ToolView() {
  const URL = "url";
  const FILE = "file";
  const TEXT = "text";
  const TYPE_INFO = "info";

  let modules = ConformanceService.modules;

  let _state = {
    //result: ConformanceService.convertInfoData(Mock.testResults[3]),
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
    _state.detailSelect = {
      module: null,
      part: null,
      section: null,
      test: null,
    };
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
      children: [{ id: validatorFormElementId }, { id: resultsElementId }],
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
                        let rawResult = _state.result.getRawResult();
                        let data = JSON.stringify(rawResult, null, 2);

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
          children: [].concat(
            _state.result
              .getModules()
              .map((module) => createModuleElement(module)),
            createHealthChecksElement(_state.result.getHealthChecks())
          ),
        },
      ],
    });
    UI.replaceElement(_resultSummaryId, resultSummary);
    UI.loadScrollPosition(_resultSummaryId + "-scroll");
  }

  function createHealthChecksElement(healthChecks) {
    if (!healthChecks) return;
    let part = healthChecks.getParts()[0];
    let moduleElement = UI.createElement({
      className: "p-3 border-bottom",
      children: [
        {
          className: "fs-5 mb-2",
          children: [{ element: "span", className: "", text: "Health Checks" }],
        },
        {
          children: [
            {
              className: "list-group",
              children: part
                .getTestResults()
                .map((testResult) => createModulePartTestElement(testResult)),
            },
          ],
        },
      ],
    });
    return moduleElement;
  }

  function createModuleElement(module) {
    let moduleElement = UI.createElement({
      className: "p-3 border-bottom",
      children: [
        {
          className: "fs-5 mb-2",
          children: [
            {
              element: "i",
              className: getVerdictIcon(module.getVerdict()),
            },
            { element: "span", className: "ms-2", text: module.getName() },
          ],
        },
        {
          children: module
            .getParts()
            .map((part) => createModulePartElement(part)),
        },
      ],
    });
    return moduleElement;
  }

  function createModulePartElement(part) {
    let modulePartElement = UI.createElement({
      children: [
        {
          className: "my-3 fw-semibold",
          children: [
            {
              element: "i",
              className: getVerdictIcon(part.getVerdict()),
              style: "width: 1.5em",
            },
            { element: "span", text: part.getName() },
          ],
        },
        {
          className: "list-group",
          children: [createModulePartInfoElement(part)].concat(
            part
              .getTestResults()
              .map((testResult) => createModulePartTestElement(testResult))
          ),
        },
      ],
    });

    return modulePartElement;
  }

  function createModulePartInfoElement(part) {
    let info = part.getInfo();
    if (!info || info.length === 0) {
      return null;
    }

    let partName = part.getName();
    let module = part.getModule().getName();
    let type = TYPE_INFO;
    let testId = { module, part: partName, type, section: null };
    let isPartSelected = isSelected(testId);

    let modulePartTestElement = UI.createElement({
      element: "a",
      className:
        "list-group-item list-group-item-action" +
        (isPartSelected ? " fw-semibold bg-light" : ""),
      href: "#",
      onClick: () => {
        if (isPartSelected) return;
        _state.detailSelect = testId;
        UI.saveScrollPosition(_resultSummaryId + "-scroll");
        renderResultSummary();
        renderResultDetails();
      },
      children: [
        {
          element: "i",
          className: "fa-solid fa-info",
          style: "padding-left: 0.3em; width: 1.5em",
        },
        { element: "span", text: "Info" },
      ],
    });
    return modulePartTestElement;
  }

  function createModulePartTestElement(testResult) {
    let section = testResult.getSection();
    let test = testResult.getTest();
    let state = testResult.getState();
    let testId = testResult.getTestId();
    let isPartSelected = isSelected(testId);

    let modulePartTestElement = UI.createElement({
      element: "a",
      className:
        "list-group-item list-group-item-action" +
        (isPartSelected ? " fw-semibold bg-light" : ""),
      href: "#",
      onClick: () => {
        if (isPartSelected) return;
        _state.detailSelect = testId;
        UI.saveScrollPosition(_resultSummaryId + "-scroll");
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
    });
    return modulePartTestElement;
  }

  function renderResultDetails(elementId) {
    _resultDetailsId = elementId = elementId || _resultDetailsId;
    let { module, part, section, test, type } = _state.detailSelect;
    let resultDetails = null;

    if (module !== null && part !== null && section !== null && test !== null) {
      resultDetails = createTestResultDetailsElement(elementId);
    }

    if (module === "HEALTH") {
      resultDetails = createHealthCheckDetailsElement(elementId);
    }

    if (type === TYPE_INFO) {
      resultDetails = createPartInfoDetailsElement(elementId);
    }

    if (!resultDetails) {
      resultDetails = createResultDetailsInstructions(elementId);
    }

    UI.replaceElement(elementId, resultDetails);
  }

  function createResultDetailsInstructions(elementId) {
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
    return instructions;
  }

  function createHealthCheckDetailsElement(elementId) {
    let testId = _state.detailSelect;
    let testResult = _state.result.getTestResult(testId);

    let resultDetails = UI.createElement({
      id: elementId,
      className: "w-50 d-flex flex-column",
      children: [
        {
          text: "Details",
          className: "fs-5 fw-semibold bg-light border-bottom py-2 px-3",
        },
        {
          className: "flex-fill overflow-auto",
          children: {
            element: "table",
            className: "table",
            children: {
              element: "tbody",
              children: [
                {
                  element: "tr",
                  children: [
                    { element: "td", text: "Health Check" },
                    { element: "td", text: testResult.getTest() },
                  ],
                },
                {
                  element: "tr",
                  children: [
                    { element: "td", text: "State" },
                    {
                      element: "td",
                      children: [
                        {
                          element: "i",
                          className: getVerdictIcon(testResult.getState()),
                        },
                        {
                          element: "span",
                          text: testResult.getState(),
                          className: "ms-1",
                        },
                      ],
                    },
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
                          "font-monospace overflow-auto border rounded bg-light p-2 text-break",
                        style: "max-height: 30em",
                        children: testResult.getMessages().map((message) => ({
                          style: { minHeight: "1em", minWidth: "1em" },
                          text: message,
                        })),
                      },
                    },
                  ],
                },
              ],
            },
          },
        },
      ],
    });
    return resultDetails;
  }

  function createPartInfoDetailsElement(elementId) {
    let infoId = _state.detailSelect;
    let info = _state.result.getInfo(infoId);

    let resultDetails = UI.createElement({
      id: elementId,
      className: "w-50 d-flex flex-column",
      children: [
        { element: "span", text: "Messages:", style: "margin: 1em; margin-bottom: 0" },
        {
          element: "div",
          style: "margin: 1em",
          children: {
            className:
              "font-monospace overflow-auto border rounded bg-light p-2 text-break",
            style: "max-height: 30em",
            children: info.map((message) => ({
              style: { minHeight: "1em", minWidth: "1em" },
              text: message,
            })),
          },
        },
      ],
    });
    return resultDetails;
  }

  function createTestResultDetailsElement(elementId) {
    let testId = _state.detailSelect;
    let testResult = _state.result.getTestResult(testId);
    let part = testResult.getPart();
    let module = part.getModule();

    let resultDetails = UI.createElement({
      id: elementId,
      className: "w-50 d-flex flex-column",
      children: [
        {
          text: "Details",
          className: "fs-5 fw-semibold bg-light border-bottom py-2 px-3",
        },
        {
          className: "flex-fill overflow-auto",
          children: {
            element: "table",
            className: "table",
            children: {
              element: "tbody",
              children: [
                {
                  element: "tr",
                  children: [
                    { element: "td", text: "Section" },
                    { element: "td", text: testResult.getSection() },
                  ],
                },
                {
                  element: "tr",
                  children: [
                    { element: "td", text: "Test" },
                    { element: "td", text: testResult.getTest() },
                  ],
                },
                {
                  element: "tr",
                  children: [
                    { element: "td", text: "State" },
                    {
                      element: "td",
                      children: [
                        {
                          element: "i",
                          className: getVerdictIcon(testResult.getState()),
                        },
                        {
                          element: "span",
                          text: testResult.getState(),
                          className: "ms-1",
                        },
                      ],
                    },
                  ],
                },
                {
                  element: "tr",
                  children: [
                    { element: "td", text: "Module" },
                    { element: "td", text: module.getName() },
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
                          "font-monospace overflow-auto border rounded bg-light p-2 text-break",
                        style: "max-height: 30em",
                        children: testResult.getMessages().map((message) => ({
                          style: { minHeight: "1em", minWidth: "1em" },
                          text: message,
                        })),
                      },
                    },
                  ],
                },
              ],
            },
          },
        },
      ],
    });
    return resultDetails;
  }

  function isSelected({ module, part, section, test, type }) {
    let isSelected = true;
    if (_state.detailSelect.section && _state.detailSelect.test) {
      isSelected = isSelected && module === _state.detailSelect.module;
      isSelected = isSelected && part === _state.detailSelect.part;
      isSelected = isSelected && section === _state.detailSelect.section;
      isSelected = isSelected && test === _state.detailSelect.test;
    } else {
      isSelected = isSelected && module === _state.detailSelect.module;
      isSelected = isSelected && part === _state.detailSelect.part;
      isSelected = isSelected && type === _state.detailSelect.type;
    }
    return isSelected;
  }

  function getVerdictIcon(verdict) {
    switch (verdict) {
      case "PASS":
        return "fa-solid fa-check text-success";
      case "FAIL":
        return "fa-solid fa-xmark text-danger";
      case "WARN":
        return "fa-solid fa-triangle-exclamation text-warning";
      default:
        return "fa-solid fa-question";
    }
  }

  let instance = {
    render,
  };
  return instance;
}
