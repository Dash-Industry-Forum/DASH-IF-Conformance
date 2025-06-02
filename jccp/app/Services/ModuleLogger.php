<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class ModuleLogger
{
    public string $logfile = '';

    private mixed $entries = [];

    private string $currentModule = '';
    private string $currentHook = '';

    private string $streamSource = '';
    private bool $parseSegments = false;

    private string $verdict = 'PASS';

    private mixed $features = [];

    /**
     * @var array<string> $validatorMessages;
     **/
    private array $validatorMessages = array();

    private string $id = '';

    public function __construct(string $id = '', string $module = '', string $hook = '')
    {
        $this->id = $id;
        Log::info("Construct!");
        if ($module) {
            $this->setModule($module);
        }
        if ($hook) {
            $this->setHook($hook);
        }
    }

    public function getID(): string
    {
        return $this->id;
    }

    public function reset(string $id = ''): void
    {
        /*
        global $session;

        if ($id !== '') {
            if ($session) {
                $session->reset($id);
                $this->logfile = $session->getDir() . '/logger.txt';
            }
        }
         */
        $this->entries = array();
        $this->features = array();
        $this->currentModule = '';
        $this->currentHook = '';
        $this->verdict = "PASS";
        $this->streamSource = '';
        $this->parseSegments = false;
    }

    /**
     * @param array<string> $verdictList
     **/
    public function selectVerdict(array $verdictList): string
    {
        $result = "PASS";
        foreach ($verdictList as $i => $verdict) {
            if ($verdict == "FAIL") {
                return $verdict;
            }
            if ($verdict == "WARN") {
                $result = "WARN";
            }
        }
        return $result;
    }

    public function merge(mixed $l): void
    {
        $this->entries = array_merge_recursive($this->entries, $l->entries);

        $this->verdict = $this->selectVerdict([$l->verdict]);
        $this->entries['verdict'] = $this->selectVerdict($this->entries['verdict']);
        foreach ($this->entries as $module => &$moduleValues) {
            if ($module == 'verdict') {
                continue;
            }
            $moduleValues['verdict'] = $this->selectVerdict($moduleValues['verdict']);
            foreach ($moduleValues as $hook => &$hookValues) {
                if ($hook == 'verdict') {
                    continue;
                }
                if (gettype($hookValues) == "array") {
                    $hookValues['verdict'] = $this->selectVerdict($hookValues['verdict']);
                }
            }
        }
    }

    public function setParseSegments(bool $parseSegments): void
    {
        $this->parseSegments = $parseSegments;
    }

    public function testCountCurrentHook(): int
    {
        if (!array_key_exists($this->currentModule, $this->entries)) {
            return 0;
        }
        if (!array_key_exists($this->currentHook, $this->entries[$this->currentModule])) {
            return 0;
        }
        if (!array_key_exists('test', $this->entries[$this->currentModule][$this->currentHook])) {
            return 0;
        }
        return sizeof($this->entries[$this->currentModule][$this->currentHook]['test']);
    }

    public function setSource(string $sourceName): void
    {
        $this->streamSource = $sourceName;
    }

    public function setModule(string $moduleName): void
    {
        $this->currentModule = $moduleName;
        $this->currentHook = '';
    }

    public function getCurrentModule(): string
    {
        return $this->currentModule;
    }

    public function getCurrentHook(): string
    {
        return $this->currentHook;
    }

    public function getModuleVerdict(string $moduleName): string
    {
        if (!array_key_exists($moduleName, $this->entries)) {
            return "PASS";
        }
        return $this->entries[$moduleName]['verdict'];
    }

    public function setHook(string $hookName): void
    {
        $this->currentHook = $hookName;
    }
    public function getHook(): string
    {
        return $this->currentHook;
    }

    public function test(
        string $spec,
        string $section,
        string $test,
        bool $check,
        string $fail_type,
        string $msg_succ,
        string $msg_fail
    ): bool {
        if ($check) {
            $this->addTestResult(
                $spec,
                $section,
                $test,
                "✓ " . $msg_succ,
                "PASS"
            );
            return true;
        }
            $this->addTestResult(
                $spec,
                $section,
                $test,
                ($fail_type == "WARN" ? "! " : "✗ ") . $msg_fail,
                $fail_type
            );
            return false;
    }

    public function addTestResult(
        string $spec,
        string $section,
        string $test,
        string $result,
        string $severity
    ): void {
        if (!array_key_exists($this->currentModule, $this->entries)) {
            $this->entries[$this->currentModule] = array();
        }
        if (!array_key_exists($this->currentHook, $this->entries[$this->currentModule])) {
            $this->entries[$this->currentModule][$this->currentHook] = array();
        }
        if (!array_key_exists('test', $this->entries[$this->currentModule][$this->currentHook])) {
            $this->entries[$this->currentModule][$this->currentHook]['test'] = array();
        }
        foreach ($this->entries[$this->currentModule][$this->currentHook]['test'] as &$entry) {
            if ($entry['spec'] != $spec) {
                continue;
            }
            if ($entry['section'] != $section) {
                continue;
            }
            if ($entry['test'] != $test) {
                continue;
            }
            $entry['messages'][] = $result;
            if ($severity == "FAIL") {
                $entry['state'] = "FAIL";
            }
            if ($severity == "WARN") {
                if ($entry['state'] != "FAIL") {
                    $entry['state'] = "WARN";
                }
            }
            $this->propagateSeverity($severity);
            return;
        }
        $this->addEntry(
            'test',
            array(
            'spec' => $spec,
            'section' => $section,
            'test' => $test,
            'messages' => [$result],
            'state' => $severity
            )
        );
        $this->propagateSeverity($severity);
    }

    private function propagateSeverity(string $severity): void
    {
        if ($severity == "FAIL") {
            $this->entries[$this->currentModule][$this->currentHook]['verdict'] = "FAIL";
            $this->entries[$this->currentModule]['verdict'] = "FAIL";
            $this->entries['verdict'] = "FAIL";
        }
        if ($severity == "WARN") {
            if (empty($this->entries[$this->currentModule][$this->currentHook]['verdict'])) {
                $this->entries[$this->currentModule][$this->currentHook]['verdict'] = "WARN";
            } elseif ($this->entries[$this->currentModule][$this->currentHook]['verdict'] != "FAIL") {
                $this->entries[$this->currentModule][$this->currentHook]['verdict'] = "WARN";
            }
            if (empty($this->entries[$this->currentModule]['verdict'])) {
                $this->entries[$this->currentModule]['verdict'] = "WARN";
            } elseif ($this->entries[$this->currentModule]['verdict'] != "FAIL") {
                $this->entries[$this->currentModule]['verdict'] = "WARN";
            }
            if (empty($this->entries['verdict'])) {
                $this->entries['verdict'] = "WARN";
            } elseif ($this->entries['verdict'] != "FAIL") {
                $this->entries['verdict'] = "WARN";
            }
        }
        if ($severity == "PASS") {
            if (empty($this->entries[$this->currentModule][$this->currentHook]['verdict'])) {
                $this->entries[$this->currentModule][$this->currentHook]['verdict'] = "PASS";
            }
            if (empty($this->entries[$this->currentModule]['verdict'])) {
                $this->entries[$this->currentModule]['verdict'] = "PASS";
            }
            if (empty($this->entries['verdict'])) {
                $this->entries['verdict'] = "PASS";
            }
        }
    }

    public function error(string $message): void
    {
        $this->addEntry('error', $message);
    }

    public function message(string $message): void
    {
        $this->addEntry('info', $message);
    }

    public function debug(string $message): void
    {
        $this->addEntry('debug', $message);
    }

    public function validatorMessage(string $message): void
    {
        $this->validatorMessages[] = $message;
    }

    private function addEntry(string $type, mixed $entry): void
    {

        if (!array_key_exists($this->currentModule, $this->entries)) {
            $this->entries[$this->currentModule] = array('verdict' => 'PASS');
        }
        if (!array_key_exists($this->currentHook, $this->entries[$this->currentModule])) {
            $this->entries[$this->currentModule][$this->currentHook] = array('verdict' => 'PASS');
        }
        if ($entry !== null) {
            $this->entries[$this->currentModule][$this->currentHook][$type][] = $entry;
        }

        $this->write();
    }

    public function addFeature(string $feature): void
    {
        if (!array_key_exists($this->currentModule, $this->features)) {
            $this->features[$this->currentModule] = array();
        }
        if (!array_key_exists($this->currentHook, $this->features[$this->currentModule])) {
            $this->features[$this->currentModule][$this->currentHook] = array();
        }
        $this->features[$this->currentModule][$this->currentHook][] = $feature;
    }

    public function hasFeature(string $feature): bool
    {
        if (!array_key_exists($this->currentModule, $this->features)) {
            return false;
        }
        if (!array_key_exists($this->currentHook, $this->features[$this->currentModule])) {
            return false;
        }
        foreach ($this->features[$this->currentModule][$this->currentHook] as $ft) {
            if ($ft == $feature) {
                return true;
            }
        }
        return false;
    }

    public function write(): void
    {
        if ($this->logfile) {
            $this->entries['Stats']['LastWritten'] = date("Y-m-d h:i:s");
            file_put_contents($this->logfile, \json_encode($this->asArray()));
        }
    }

    public function asJSON(bool $compact = false): string
    {
        if (!$compact) {
            return \json_encode($this->asArray());
        }

        $entries = $this->entries;

        foreach ($this->entries as $k1 => &$module) {
            foreach ($module as $k2 => &$element) {
                if ($k2 == "verdict") {
                    continue;
                }
                foreach ($element as $key => &$test) {
                    if ($key != "test") {
                        continue;
                    }
                    foreach ($test as $idx => &$t) {
                        if ($t['state'] == "PASS") {
                              unset($t['messages']);
                        }
                    }
                }
            }
        }
        return \json_encode($this->entries);
    }

    /**
     * @return array<string, mixed>
     **/
    public function asArray(): array
    {
        $result = array();
        $result['parse_segments'] = $this->parseSegments;
        $result['source'] = $this->streamSource;
        $result['entries'] = $this->entries;
        $result['verdict'] = "PASS";
        if (array_key_exists("verdict", $this->entries) && !empty($this->entries['verdict'])) {
            $result['verdict'] = $this->entries['verdict'];
        }

        $result['enabled_modules'] = array();


        global $modules;
        if (!$modules) {
            $modules = [];
        }

        foreach ($modules as $module) {
            if ($module->isEnabled()) {
                $result['enabled_modules'][] = $module;
            }
        }

        $result['validator_messages'] = $this->validatorMessages;

        return $result;
    }
}
