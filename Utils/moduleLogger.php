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
        global $session_dir;
        $this->logfile = $session_dir . './logger.txt';
        $this->entries = array();
        $this->features = array();
        $this->currentModule = '';
        $this->currentHook = '';
        $this->verdict = "PASS";
        $this->streamSource = '';
        $this->currentTest = null;
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
        } else {
            $this->addTestResult($spec, $section, $test, $msg_fail, $fail_type);
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
                $entry['state'] = $severity;
                return;
            }
            if ($severity == "WARN") {
                if ($entry['state'] != "FAIL") {
                    $entry['state'] = $severity;
                }
            }
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
            $this->entries[$this->currentModule] = array();
        }
        if (!array_key_exists($this->currentHook, $this->entries[$this->currentModule])) {
            $this->entries[$this->currentModule][$this->currentHook] = array();
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
        file_put_contents($this->logfile, json_encode($this->entries));
    }

    public function asJSON()
    {
        $result = array();
        $result['source'] = $this->streamSource;
        $result['entries'] = $this->entries;
        $result['verdict'] = "PASS";
        return json_encode($result);
    }
}

$logger = new ModuleLogger();
