function ToolView() {
  const URL = "url";
  const FILE = "file";
  const TEXT = "text";

  const READY = "ready";
  const PROCESSING = "processing";

  let modules = ConformanceService.modules;

  let mpdForms = [
    { type: "url", text: "URL" },
    { type: "file", text: "File Upload" },
    { type: "text", text: "Text Input" },
  ];

  let _state = {
    result: null,
    detailSelect: { module: null, part: null, section: null, test: null },
    activeMpdForm: mpdForms[0].type,
    mpdUrl: null,
    activeModules: {},
    validatorState: READY,
  };

  let _rootElementId;
  let _validatorFormElementId;
  let _resultsElementId;
  let _resultSummaryId;
  let _resultDetailsId;

  async function handleValidation() {
    if (_state.activeMpdForm === "url") {
      _state.validatorState = PROCESSING;
      renderValidatorForm();
      let { mpdUrl, activeModules } = _state;
      _state.result = await ConformanceService.validateContentByUrl({
        mpdUrl,
        activeModules,
      });
      _state.validatorState = READY;
      renderValidatorForm();
      renderResults();
    }
  }

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
                children: [
                  {
                    element: "ul",
                    className: "nav nav-tabs",
                    children: mpdForms.map((form) => ({
                      element: "li",
                      className: "nav-item",
                      children: {
                        element: "a",
                        className:
                          "nav-link" +
                          (_state.activeMpdForm === form.type ? " active" : ""),
                        onclick:
                          _state.activeMpdForm === form.type
                            ? () => {}
                            : () => {
                                _state.activeMpdForm = form.type;
                                renderValidatorForm();
                              },
                        href: "#",
                        text: form.text,
                      },
                    })),
                  },
                  {
                    className:
                      "p-3 border border-top-0 rounded-bottom bg-white",
                    children: {
                      element: "input",
                      type: "textbox",
                      className: "form-control",
                      id: "mpd-url",
                      value: _state.mpdUrl,
                      onchange: (event) => {
                        _state.mpdUrl = event.target.value;
                      },
                    },
                  },
                ],
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
                children: modules.map((module) => ({
                  className: "form-check",
                  children: [
                    {
                      element: "input",
                      type: "checkbox",
                      className: "form-check-input",
                      id: module.id,
                      onchange: (event) => {
                        _state.activeModules[module.id] = event.target.checked;
                      },
                      checked: _state.activeModules[module.id],
                    },
                    {
                      element: "label",
                      className: "form-check-label",
                      for: module.id,
                      text: module.text,
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
                className:
                  "btn btn-primary" +
                  (_state.validatorState === READY ? "" : " disabled"),
                onclick: handleValidation,
                children: (() => {
                  switch (_state.validatorState) {
                    case READY:
                      return [
                        { element: "i", className: "fa-solid fa-play me-2" },
                        { element: "span", text: "Process" },
                      ];
                    case PROCESSING:
                      return [
                        {
                          element: "i",
                          className: "fa-solid fa-gear fa-spin me-2",
                        },
                        { element: "span", text: "Processing" },
                      ];
                  }
                })(),
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
    if (!_state.result) return;
    let resultSummaryId = UI.generateElementId();
    let resultDetailsId = UI.generateElementId();
    let resultsView = UI.createElement({
      id: elementId,
      children: [
        { element: "h2", text: "Results", className: "pt-3" },
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
                Object.keys(_state.result.entries).length - 3 === index ? null : { element: "hr" },
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
