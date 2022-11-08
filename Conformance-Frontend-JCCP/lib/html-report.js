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
                  { element: "td", text: result.source },
                ],
              },
              {
                element: "tr",
                children: [
                  { element: "td", text: "Parse segments" },
                  {
                    element: "td",
                    text: result.parse_segments ? "True" : "False",
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
                        text: getVerdictIcon(result.verdict),
                      },
                      {
                        element: "span",
                        text: result.verdict,
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
                    children: result.enabled_modules.map((module) => ({
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
    let { entries: modules } = result;
    let moduleNames = Object.keys(modules).filter(
      (name) => name !== "verdict" && name !== "Stats"
    );
    return {
      children: [
        {
          classname: "fs-4 mb-3 mt-5 fw-semibold",
          text: "Summary",
        },
        {
          className: "list-group",
          children: moduleNames.reduce((elements, moduleName) => {
            let { verdict } = modules[moduleName];
            let parts = modules[moduleName];
            let partNames = Object.keys(modules[moduleName]).filter(
              (part) => part !== "verdict" && part !== "name"
            );

            elements.push({
              className: "list-group-item list-group-item-action",
              element: "a",
              href: "#" + toHandle(moduleName),
              children: [
                {
                  element: "span",
                  text: getVerdictIcon(verdict),
                  style: "width: 1.5em; display: inline-block",
                },
                {
                  element: "span",
                  text: moduleName,
                },
              ],
            });

            partNames.forEach((partName) => {
              let { verdict } = parts[partName];
              let tests = parts[partName].test;

              elements.push({
                className: "list-group-item list-group-item-action",
                style: "padding-left: 1em",
                element: "a",
                href: "#" + toHandle(moduleName + "-" + partName),
                children: {
                  style: "padding-left: 1em",
                  children: [
                    {
                      element: "span",
                      text: getVerdictIcon(verdict),
                      style: "width: 1.5em; display: inline-block",
                    },
                    {
                      element: "span",
                      text: partName,
                    },
                  ],
                },
              });

              tests.forEach((test) => {
                elements.push({
                  className: "list-group-item list-group-item-action",
                  element: "a",
                  href: "#" + toHandle(test.section + "-" + test.test),
                  children: {
                    style: "padding-left: 2em",
                    children: [
                      {
                        element: "span",
                        text: getVerdictIcon(test.state),
                        style: "width: 1.5em; display: inline-block",
                      },
                      {
                        element: "span",
                        text: test.section,
                        className: "me-2",
                      },
                      {
                        element: "span",
                        text: test.test,
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
    let moduleNames = Object.keys(results.entries).filter(
      (name) => name !== "verdict" && name !== "Stats"
    );
    let modules = moduleNames.map((name) => {
      let module = results.entries[name];
      module.name = name;
      return module;
    });
    return {
      children: [
        { className: "fs-4 mt-5 mb-3 fw-semibold", text: "Details" },
        {
          children: modules.map(generateModuleDetails),
        },
      ],
    };
  }

  function generateModuleDetails(module) {
    let partNames = Object.keys(module).filter(
      (name) => name !== "verdict" && name !== "name"
    );
    let parts = partNames.map((name) => {
      let part = module[name];
      part.name = name;
      return part;
    });
    return {
      className: "mb-4 card",
      children: [
        {
          id: toHandle(module.name),
          className: "card-header",
          children: [
            {
              element: "span",
              text: getVerdictIcon(module.verdict),
            },
            { element: "span", className: "ms-2", text: module.name },
          ],
        },
        {
          className: "card-body",
          children: parts.map((part) => ({
            id: toHandle(module.name + "-" + part.name),
            children: [
              {
                element: "h5",
                className: "card-title",
                children: [
                  {
                    element: "span",
                    text: getVerdictIcon(part.verdict),
                  },
                  { element: "span", className: "ms-2", text: part.name },
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
                    children: part.test.map((test) => ({
                      id: toHandle(test.section + "-" + test.test),
                      element: "tr",
                      children: [
                        { element: "td", text: test.section },
                        { element: "td", text: test.test },
                        {
                          element: "td",
                          className: "font-monospace",
                          children: test.messages.map((message) => ({
                            text: message,
                          })),
                        },
                        {
                          element: "td",
                          className: "text-nowrap",
                          children: [
                            {
                              element: "span",
                              text: getVerdictIcon(test.state),
                            },
                            {
                              element: "span",
                              text: test.state,
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
      default:
        return "fa-solid fa-question";
    }
  }

  instance = {
    generateReport,
    openReport,
  };

  return instance;
})();
