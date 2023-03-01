const HtmlReport = (() => {
  let instance;

  function openReport(report) {
    let id = UI.generateElementId();
    let child = window.open("about:blank", id);
    child.document.write(
      `<html>
         <head>
           <link href="./css/bootstrap.min.css" rel="stylesheet">
         </head>
         <script>
           location.href = "#";
         </script>
         <body>
         </body>
       </html>`
    );
    child.document.close();
    child.addEventListener("load", () => {
      let root = UI.getRoot(child.document);
      root.appendChild(report);
    });
  }

  function generateReport(result) {
    let report = UI.createElement({
      className: "container",
      children: [
        {
          className: "fs-1 my-3 fw-semibold",
          text: "Validation Report",
        },
        generateGeneralInfo(result),
        generateSummary(result),
        generateDetails(result),
      ],
    });
    return report;
  }

  function generateGeneralInfo(result) {
    return {
      className: "border rounded",
      children: {
        element: "table",
        className: "table",
        children: [
          {
            element: "tbody",
            children: [
              {
                element: "tr",
                children: [
                  { element: "td", text: "Source" },
                  { element: "td", text: result.getSource() },
                ],
              },
              {
                element: "tr",
                children: [
                  { element: "td", text: "Parse segments" },
                  {
                    element: "td",
                    text: result.isParseSegments ? "True" : "False",
                  },
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
                        element: "span",
                        text: getVerdictIcon(result.getVerdict()),
                      },
                      {
                        element: "span",
                        text: result.getVerdict(),
                        className: "ms-1",
                      },
                    ],
                  },
                ],
              },
              {
                element: "tr",
                children: [
                  { element: "td", text: "Enabled modules" },
                  {
                    element: "td",
                    children: result
                      .getRawResult()
                      .enabled_modules.map((module) => ({
                        text: module.name,
                      })),
                  },
                ],
              },
            ],
          },
        ],
      },
    };
  }

  function generateSummary(result) {
    return {
      children: [
        {
          classname: "fs-4 mb-3 mt-5 fw-semibold",
          text: "Summary",
        },
        {
          className: "list-group",
          children: result.getModules().reduce((elements, module) => {
            elements.push({
              className: "list-group-item list-group-item-action",
              element: "a",
              href: "#" + toHandle(module.getName()),
              children: [
                {
                  element: "span",
                  text: getVerdictIcon(module.getVerdict()),
                  style: "width: 1.5em; display: inline-block",
                },
                {
                  element: "span",
                  text: module.getName(),
                },
              ],
            });

            module.getParts().forEach((part) => {
              elements.push({
                className: "list-group-item list-group-item-action",
                style: "padding-left: 1em",
                element: "a",
                href: "#" + toHandle(module.getName() + "-" + part.getName()),
                children: {
                  style: "padding-left: 1em",
                  children: [
                    {
                      element: "span",
                      text: getVerdictIcon(part.getVerdict()),
                      style: "width: 1.5em; display: inline-block",
                    },
                    {
                      element: "span",
                      text: part.getName(),
                    },
                  ],
                },
              });

              part.getTestResults().forEach((testResult) => {
                elements.push({
                  className: "list-group-item list-group-item-action",
                  element: "a",
                  href:
                    "#" +
                    toHandle(
                      testResult.getSection() + "-" + testResult.getTest()
                    ),
                  children: {
                    style: "padding-left: 2em",
                    children: [
                      {
                        element: "span",
                        text: getVerdictIcon(testResult.getState()),
                        style: "width: 1.5em; display: inline-block",
                      },
                      {
                        element: "span",
                        text: testResult.getSection(),
                        className: "me-2",
                      },
                      {
                        element: "span",
                        text: testResult.getTest(),
                      },
                    ],
                  },
                });
              });
            });
            return elements;
          }, []),
        },
      ],
    };
  }

  function generateDetails(results) {
    return {
      children: [
        { className: "fs-4 mt-5 mb-3 fw-semibold", text: "Details" },
        {
          children: results.getModules().map(generateModuleDetails),
        },
      ],
    };
  }

  function generateModuleDetails(module) {
    return {
      className: "mb-4 card",
      children: [
        {
          id: toHandle(module.getName()),
          className: "card-header",
          children: [
            {
              element: "span",
              text: getVerdictIcon(module.getVerdict()),
            },
            { element: "span", className: "ms-2", text: module.getName() },
          ],
        },
        {
          className: "card-body",
          children: module.getParts().map((part) => ({
            id: toHandle(module.getName() + "-" + part.getName()),
            children: [
              {
                element: "h5",
                className: "card-title",
                children: [
                  {
                    element: "span",
                    text: getVerdictIcon(part.getVerdict()),
                  },
                  { element: "span", className: "ms-2", text: part.getName() },
                ],
              },
              {
                element: "table",
                className: "table",
                children: [
                  {
                    element: "thead",
                    children: {
                      element: "tr",
                      children: [
                        { element: "th", text: "Section" },
                        { element: "th", text: "Test" },
                        { element: "th", text: "Messages" },
                        { element: "th", text: "State" },
                      ],
                    },
                  },
                  {
                    element: "tbody",
                    children: part.getTestResults().map((testResult) => ({
                      id: toHandle(testResult.getSection() + "-" + testResult.getTest()),
                      element: "tr",
                      children: [
                        { element: "td", text: testResult.getSection() },
                        { element: "td", text: testResult.getTest() },
                        {
                          element: "td",
                          className: "font-monospace",
                          children: testResult.getMessages().map((message) => ({
                            text: message,
                          })),
                        },
                        {
                          element: "td",
                          className: "text-nowrap",
                          children: [
                            {
                              element: "span",
                              text: getVerdictIcon(testResult.getState()),
                            },
                            {
                              element: "span",
                              text: testResult.getState(),
                              className: "ms-1",
                            },
                          ],
                        },
                      ],
                    })),
                  },
                ],
              },
            ],
          })),
        },
      ],
    };
  }

  function toHandle(str) {
    return str.toLowerCase().replaceAll(" ", "-");
  }

  function getVerdictIcon(verdict) {
    switch (verdict) {
      case "PASS":
        return "✅";
      case "FAIL":
        return "❌";
      case "WARN":
        return "⚠️"
      default:
        return "?";
    }
  }

  instance = {
    generateReport,
    openReport,
  };

  return instance;
})();
