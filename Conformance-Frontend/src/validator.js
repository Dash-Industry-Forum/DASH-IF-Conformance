function Validator({ modules }) {
  const READY = "ready";
  const PROCESSING = "processing";

  const PROCESSING_FINISHED = "processing_finished";

  let mpdForms = [
    { type: "url", text: "URL" },
    { type: "file", text: "File Upload" },
    { type: "text", text: "Text Input" },
  ];

  let instance;
  let _state = {
    activeMpdForm: mpdForms[0].type,
    activeModules: {},
    validatorState: READY,
    mpdUrl: null,
  };
  let eventHandler = new EventHandler();

  let _rootElementId;

  async function handleValidation() {
    if (_state.activeMpdForm === "url") {
      _state.validatorState = PROCESSING;
      render();
      let { mpdUrl, activeModules } = _state;
      let result = await ConformanceService.validateContentByUrl({
        mpdUrl,
        activeModules,
      });
      _state.validatorState = READY;
      render();
      eventHandler.dispatchEvent(PROCESSING_FINISHED, { result });
    }
  }

  function onProcessingFinished(callback) {
    eventHandler.on(PROCESSING_FINISHED, callback);
  }

  function render(elementId) {
    _rootElementId = elementId = elementId || _rootElementId;

    let validator = UI.createElement({
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
                                render();
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

    UI.replaceElement(elementId, validator);
  }

  instance = {
    onProcessingFinished,
    render,
  };
  return instance;
}
