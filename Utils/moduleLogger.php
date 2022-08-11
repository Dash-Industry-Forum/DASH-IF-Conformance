<?php

namespace DASHIF;

class ModuleLogger
{
    public $logfile;

    private $entries;

    private $currentModule;
    private $currentHook;

    private $streamSource;

    private $currentTest;

    private $features;

    public function __construct()
    {
        $this->reset();
    }

    public function reset($id = null)
    {
        global $session;
        $session->reset($id);

        $this->logfile = $session->getDir() . '/logger.txt';
        $this->entries = array();
        $this->features = array();
        $this->currentModule = '';
        $this->currentHook = '';
        $this->verdict = "PASS";
        $this->streamSource = '';
        $this->currentTest = null;
    }

    public function testCountCurrentHook()
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

    public function setSource($sourceName)
    {
        $this->streamSource = $sourceName;
    }

    public function setModule($moduleName)
    {
        $this->currentModule = $moduleName;
        $this->currentHook = '';
    }

    public function setHook($hookName)
    {
        $this->currentHook = $hookName;
    }

    public function test($spec, $section, $test, $check, $fail_type, $msg_succ, $msg_fail)
    {
        if ($check) {
            $this->addTestResult($spec, $section, $test, $msg_succ, "PASS");
            return true;
        } else {
            $this->addTestResult($spec, $section, $test, $msg_fail, $fail_type);
            return false;
        }
    }

    public function addTestResult($spec, $section, $test, $result, $severity)
    {
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

    private function propagateSeverity($severity)
    {
        if ($severity == "FAIL") {
            $this->entries[$this->currentModule][$this->currentHook]['verdict'] = "FAIL";
            $this->entries[$this->currentModule]['verdict'] = "FAIL";
            $this->entries['verdict'] = "FAIL";
        }
        if ($severity == "WARN") {
            if ($this->entries[$this->currentModule][$this->currentHook]['verdict'] != "FAIL") {
                $this->entries[$this->currentModule][$this->currentHook]['verdict'] = "WARN";
            }
            if ($this->entries[$this->currentModule]['verdict'] != "FAIL") {
                $this->entries[$this->currentModule]['verdict'] = "WARN";
            }
            if ($this->entries['verdict'] != "FAIL") {
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

    private function &findTest($spec, $section, $test)
    {
        if (!array_key_exists($this->currentModule, $this->entries)) {
            return null;
        }
        if (!array_key_exists($this->currentHook, $this->entries[$this->currentModule])) {
            return null;
        }
        foreach ($this->entries[$this->currentModule][$this->currentHook]['test'] as $entry) {
            if ($entry['spec'] != $spec) {
                continue;
            }
            if ($entry['section'] != $section) {
                continue;
            }
            if ($entry['test'] != $test) {
                continue;
            }
            return $entry;
        }
        return null;
    }

    public function error($err)
    {
        $this->addEntry('error', $message);
    }

    public function message($message)
    {
        $this->addEntry('info', $message);
    }

    public function debug($message)
    {
        $this->addEntry('debug', $message);
    }

    private function addEntry($type, $entry)
    {

        if (!array_key_exists($this->currentModule, $this->entries)) {
            $this->entries[$this->currentModule] = array('verdict' => 'PASS');
        }
        if (!array_key_exists($this->currentHook, $this->entries[$this->currentModule])) {
            $this->entries[$this->currentModule][$this->currentHook] = array('verdict' => 'PASS');
        }
        $this->entries[$this->currentModule][$this->currentHook][$type][] = $entry;

        $this->write();
    }

    public function addFeature($feature)
    {
        if (!array_key_exists($this->currentModule, $this->featureses)) {
            $this->featuress[$this->currentModule] = array();
        }
        if (!array_key_exists($this->currentHook, $this->featuress[$this->currentModule])) {
            $this->featuress[$this->currentModule][$this->currentHook] = array();
        }
        $this->features[$this->currentModule][$this->currentHook][] = $feature;
    }

    public function hasFeature($feature)
    {
        if (!array_key_exists($this->currentModule, $this->featureses)) {
            return false;
        }
        if (!array_key_exists($this->currentHook, $this->featuress[$this->currentModule])) {
            return false;
        }
        foreach ($this->features[$this->currentModule][$this->currentHook] as $ft) {
            if ($ft == $feature) {
                return true;
            }
        }
        return false;
    }

    private function write()
    {
        $this->entries['Stats']['LastWritten'] = date("Y-m-d h:i:s");
        file_put_contents($this->logfile, json_encode($this->asArray()));
    }

    public function asJSON()
    {
        return json_encode($this->asArray());
    }

    public function asArray()
    {
        $result = array();
        $result['source'] = $this->streamSource;
        $result['entries'] = $this->entries;
        $result['verdict'] = "PASS";
        if (array_key_exists("verdict", $this->entries) && !empty($this->entries['verdict'])) {
            $result['verdict'] = $this->entries['verdict'];
        }

        $result['enabled_modules'] = array();

        global $modules;

        foreach ($modules as $module) {
            if ($module->isEnabled()) {
                $result['enabled_modules'][] = $module;
            }
        }

        return $result;
    }
}
$logger = new ModuleLogger();
