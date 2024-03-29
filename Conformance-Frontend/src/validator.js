function Validator({ modules }) {
  const READY = "ready";
  const PROCESSING = "processing";
  const NOT_IMPLEMENTED = "not_implemented";

  const PROCESSING_FINISHED = "processing_finished";

  const URL_TYPE = "url";
  const TEXT_TYPE = "text";
  const FILE_TYPE = "file";

  let mpdForms = [
    { type: URL_TYPE, text: "URL" },
    { type: FILE_TYPE, text: "File Upload" },
    { type: TEXT_TYPE, text: "Text Input" },
  ];

  let instance;
  let _state = {
    activeMpdForm: mpdForms[0].type,
    activeModules: {},
    validatorState: READY,
    mpdUrl: null,
    error: null,
    processingStartDate: null,
    processingEndDate: null,
    m3u8Detected: false,
  };
  let eventHandler = new EventHandler();

  let _durationInterval;

  let _rootElementId;
  let _durationElementId;

  async function handleValidation() {
    _state.error = null;

    switch (_state.activeMpdForm) {
      case URL_TYPE: {
        let { mpdUrl } = _state;
        if (!Net.isValidUrl(mpdUrl)) {
          _state.error = "Invalid URL";
          render();
          return;
        }
        break;
      }
      case TEXT_TYPE:
      case FILE_TYPE:
        break;
      default:
        return;
    }

    _state.validatorState = PROCESSING;
    _state.processingStartDate = Date.now();
    updateDuration();
    _durationInterval = setInterval(updateDuration, 1000);
    render();

    let result;
    switch (_state.activeMpdForm) {
      case URL_TYPE: {
        let { mpdUrl, activeModules } = _state;
        try {
          result = await ConformanceService.validateContentByUrl({
            mpdUrl,
            activeModules,
          });
        } catch (error) {
          _state.error = error;
        }
        break;
      }
      case TEXT_TYPE: {
        let { mpdText, activeModules } = _state;
        try {
          result = await ConformanceService.validateContentByText({
            mpdText,
            activeModules,
          });
        } catch (error) {
          _state.error = error;
        }
        break;
      }
      case FILE_TYPE: {
        let { mpdFile, activeModules } = _state;
        try {
          result = await ConformanceService.validateContentByFile({
            mpdFile,
            activeModules,
          });
        } catch (error) {
          _state.error = error;
        }
        break;
      }
    }

    setValidatorState(READY);
    _state.processingEndDate = Date.now();
    render();
    let duration = _state.processingEndDate - _state.processingStartDate;
    eventHandler.dispatchEvent(PROCESSING_FINISHED, { result, duration });
  }

  function setValidatorState(newState) {
    //if (newState === READY) {
    //  if (
    //    _state.activeMpdForm === TEXT_TYPE ||
    //    _state.activeMpdForm === FILE_TYPE
    //  ) {
    //    _state.validatorState = NOT_IMPLEMENTED;
    //  } else {
    //    _state.validatorState = READY;
    //  }
    //  return;
    //}
    _state.validatorState = newState;
  }

  function onProcessingFinished(callback) {
    eventHandler.on(PROCESSING_FINISHED, callback);
  }

  function updateDuration() {
    if (_state.validatorState !== PROCESSING) {
      clearInterval(_durationInterval);
      UI.clearElement(_durationElementId);
      return;
    }
    let durationElement = UI.createElement({
      id: _durationElementId,
      text: Tools.msToTime(Date.now() - _state.processingStartDate, {
        alwaysShowMins: true,
      }),
    });
    UI.replaceElement(_durationElementId, durationElement);
  }

  function render(elementId) {
    _rootElementId = elementId = elementId || _rootElementId;
    _durationElementId = UI.generateElementId();

    let validator = UI.createElement({
      id: elementId,
      className: "container border rounded p-3 bg-light",
      style: "max-width: 960px",
      children: [
        _state.error
          ? {
              className: "alert alert-danger alert-dismissible",
              role: "alert",
              children: [
                { text: _state.error },
                {
                  element: "button",
                  type: "button",
                  className: "btn-close",
                  dataBsDismiss: "alert",
                  ariaLabel: "Close",
                  onClick: () => {
                    _state.error = null;
                    render();
                  },
                },
              ],
            }
          : {},
        {
          element: "form",
          children: [
            {
              className: "mb-3 row",
              children: [
                {
                  element: "h3",
                  className: "col-sm-3 col-form-label",
                  for: "mpd-url",
                  text: "Manifest",
                },
                {
                  className: "col-sm-9",
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
                            (_state.activeMpdForm === form.type
                              ? " active"
                              : ""),
                          onclick:
                            _state.activeMpdForm === form.type
                              ? () => {}
                              : () => {
                                  _state.activeMpdForm = form.type;
                                  if (_state.validatorState !== PROCESSING) {
                                    setValidatorState(READY);
                                  }
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
                      children: (() => {
                        switch (_state.activeMpdForm) {
                          case "url":
                            return {
                              element: "input",
                              type: "textbox",
                              className: "form-control",
                              id: "mpd-url",
                              value: _state.mpdUrl,
                              onchange: (event) => {
                                var mpdUrl = event.target.value;
                                _state.mpdUrl = mpdUrl;
                                var lastFourChars = mpdUrl.substring(
                                  mpdUrl.length - 4
                                );
                                var m3u8Detected = lastFourChars === "m3u8";
                                if (m3u8Detected !== _state.m3u8Detected) {
                                  _state.m3u8Detected = m3u8Detected;
                                  render();
                                }
                              },
                            };
                          case "file":
                            return {
                              element: "input",
                              type: "file",
                              className: "form-control",
                              id: "mpd-file",
                              onchange: (event) => {
                                var mpdFile = event.target.files[0];
                                _state.mpdFile = mpdFile;
                                var lastFourChars = mpdFile.name.substring(
                                  mpdFile.name.length - 4
                                );
                                var m3u8Detected = lastFourChars === "m3u8";
                                if (m3u8Detected !== _state.m3u8Detected) {
                                  _state.m3u8Detected = m3u8Detected;
                                  render();
                                }
                              },
                            };
                          case "text":
                            return {
                              element: "textarea",
                              className: "form-control",
                              id: "mpd-text",
                              onchange: (event) => {
                                _state.mpdText = event.target.value;
                              },
                              text: _state.mpdText || "",
                            };
                        }
                      })(),
                    },
                  ],
                },
              ],
            },
            {
              className: "mb-3 row",
              children: [
                {
                  element: "h3",
                  className: "col-sm-3 col-form-label",
                  text: "Enabled Modules",
                },
                {
                  className: "col-sm-9",
                  children: modules.map((module) => ({
                    className: "form-check",
                    children: [
                      {
                        element: "input",
                        type: "checkbox",
                        className: "form-check-input",
                        id: module.id,
                        onchange: (event) => {
                          _state.activeModules[module.id] =
                            event.target.checked;
                        },
                        checked: _state.m3u8Detected
                          ? _state.activeModules[module.id] &&
                            module.m3u8Compatible
                          : _state.activeModules[module.id],
                        disabled: _state.m3u8Detected
                          ? !module.m3u8Compatible
                          : false,
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
              className:
                "d-grid gap-2 d-md-flex justify-content-md-end align-items-center",
              children: [
                {
                  id: _durationElementId,
                },
                {
                  element: "button",
                  type: "button",
                  className:
                    "btn btn-primary" +
                    (_state.validatorState === READY ? "" : " disabled"),
                  onclick: handleValidation,
                  children: (() => {
                    switch (_state.validatorState) {
                      case NOT_IMPLEMENTED:
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
      ],
    });

    UI.replaceElement(elementId, validator);

    if (_state.validatorState === PROCESSING) updateDuration();
  }

  instance = {
    onProcessingFinished,
    render,
  };
  return instance;
}
