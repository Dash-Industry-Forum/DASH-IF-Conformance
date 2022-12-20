const ValidationResult = (() => {
  function ValidationResult(result) {
    let instance;

    let _rawResult = result;
    let _source = result.source;
    let _verdict = result.verdict;
    let _isParseSegments = result.parse_segments;
    let _modules = parseModules(result);
    let _healthChecks = parseHealthChecks(result);

    function getSource() {
      return _source;
    }

    function getVerdict() {
      return _verdict;
    }

    function isParseSegments() {
      return _isParseSegments;
    }

    function getRawResult() {
      return _rawResult;
    }

    function getModules() {
      return _modules;
    }

    function getHealthChecks() {
      return _healthChecks;
    }

    function getTestResult(testId) {
      let moduleName = testId.module;
      let partName = testId.part;
      let test = testId.test;
      let section = testId.section;
      let rawTest = _rawResult.entries[moduleName][partName].test.find(
        (element) => element.test === test && element.section === section
      );

      return new TestResult(rawTest);
    }

    function parseModules(result) {
      let moduleNames = Object.keys(result.entries).filter(
        (key) => key !== "Stats" && key !== "verdict" && key !== "HEALTH"
      );
      let modules = moduleNames.map((name) => {
        let module = result.entries[name];
        module.name = name;
        module = new Module(module);
        return module;
      });

      return modules;
    }

    function parseHealthChecks(result) {
      let name = "HEALTH";
      if (!result.entries[name]) return null;
      let healthChecks = result.entries[name];
      healthChecks.name = name;
      healthChecks = new Module(healthChecks);
      return healthChecks;
    }

    instance = {
      getSource,
      getVerdict,
      isParseSegments,
      getRawResult,
      getModules,
      getTestResult,
      getHealthChecks,
    };

    return instance;
  }

  function Module(module) {
    let instance;

    let _name = module.name || "";
    let _verdict = module.verdict || "";

    function getName() {
      return _name;
    }

    function getVerdict() {
      return _verdict;
    }

    function getParts() {
      return _parts;
    }

    function parseParts(module) {
      let partNames = Object.keys(module).filter(
        (key) => typeof module[key] === "object" && "test" in module[key]
      );
      let parts = partNames.map((partName) => {
        let part = module[partName];
        part.name = partName;
        part.module = instance;
        part = new Part(part);
        return part;
      });
      return parts;
    }

    instance = {
      getName,
      getVerdict,
      getParts,
    };
    let _parts = parseParts(module);

    return instance;
  }

  function Part(part) {
    let instance;

    let _module = part.module;
    let _name = part.name || "";
    let _verdict = part.verdict || "";

    function getName() {
      return _name;
    }

    function getVerdict() {
      return _verdict;
    }

    function getModule() {
      return _module;
    }

    function getTestResults() {
      return _testResults;
    }

    function parseTestResults(part) {
      return part.test.map((test) => {
        test.part = instance;
        return new TestResult(test);
      });
    }

    instance = {
      getName,
      getVerdict,
      getModule,
      getTestResults,
    };

    let _testResults = parseTestResults(part);

    return instance;
  }

  function TestResult(testResult) {
    let instance;

    let _section = testResult.section || "";
    let _test = testResult.test || "";
    let _state = testResult.state || "";
    let _part = testResult.part;
    let _messages = testResult.messages || [];
    let _testId = {
      module: _part.getModule().getName(),
      part: _part.getName(),
      section: _section,
      test: _test,
    };

    function getSection() {
      return _section;
    }

    function getTest() {
      return _test;
    }

    function getState() {
      return _state;
    }

    function getPart() {
      return _part;
    }

    function getTestId() {
      return _testId;
    }

    function getMessages() {
      return _messages;
    }

    instance = {
      getSection,
      getTest,
      getState,
      getPart,
      getTestId,
      getMessages,
    };

    return instance;
  }

  return ValidationResult;
})();
