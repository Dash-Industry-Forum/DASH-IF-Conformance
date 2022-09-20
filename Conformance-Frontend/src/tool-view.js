function ToolView() {
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
              className: "mb-3",
              children: [
                {
                  element: "label",
                  className: "form-label",
                  for: "mpd-url",
                  text: "MPD",
                },
                {
                  element: "input",
                  type: "textbox",
                  className: "form-control",
                  id: "mpd-url",
                },
              ],
            },
            {
              element: "label",
              className: "form-label",
              text: "Include additional tests",
            },
            {
              className: "form-check",
              children: [
                {
                  element: "input",
                  type: "checkbox",
                  className: "form-check-input",
                  id: "segment-validation",
                },
                {
                  element: "label",
                  className: "form-check-label",
                  for: "segment-validation",
                  text: "Segment Validation",
                },
              ],
            },
            {
              className: "form-check",
              children: [
                {
                  element: "input",
                  type: "checkbox",
                  className: "form-check-input",
                  id: "dash-if",
                },
                {
                  element: "label",
                  className: "form-check-label",
                  for: "dash-if",
                  text: "Dash-IF",
                },
              ],
            },
            {
              className: "form-check",
              children: [
                {
                  element: "input",
                  type: "checkbox",
                  className: "form-check-input",
                  id: "ll-dash-if",
                },
                {
                  element: "label",
                  className: "form-check-label",
                  for: "ll-dash-if",
                  text: "LL Dash-IF",
                },
              ],
            },
            {
              className: "form-check",
              children: [
                {
                  element: "input",
                  type: "checkbox",
                  className: "form-check-input",
                  id: "dvb-19",
                },
                {
                  element: "label",
                  className: "form-check-label",
                  for: "dvb-19",
                  text: "DVB (2019)",
                },
              ],
            },
            {
              className: "form-check",
              children: [
                {
                  element: "input",
                  type: "checkbox",
                  className: "form-check-input",
                  id: "dvb-18",
                },
                {
                  element: "label",
                  className: "form-check-label",
                  for: "dvb-18",
                  text: "DVB (2018)",
                },
              ],
            },
            {
              className: "form-check",
              children: [
                {
                  element: "input",
                  type: "checkbox",
                  className: "form-check-input",
                  id: "hbbtv",
                },
                {
                  element: "label",
                  className: "form-check-label",
                  for: "hbbtv",
                  text: "HbbTV",
                },
              ],
            },
            {
              className: "form-check",
              children: [
                {
                  element: "input",
                  type: "checkbox",
                  className: "form-check-input",
                  id: "cmaf",
                },
                {
                  element: "label",
                  className: "form-check-label",
                  for: "cmaf",
                  text: "CMAF",
                },
              ],
            },
            {
              className: "form-check",
              children: [
                {
                  element: "input",
                  type: "checkbox",
                  className: "form-check-input",
                  id: "cta-wave",
                },
                {
                  element: "label",
                  className: "form-check-label",
                  for: "cta-wave",
                  text: "CTA-WAVE",
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
