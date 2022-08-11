<?php

namespace DASHIF;

class SessionHandler
{
    private $sessionId;

    public function __construct()
    {
        $this->reset();
    }

    public function reset($id = null)
    {
        $this->setId($id);
    }

    public function setId($id)
    {
        $this->sessionId = $id;
    }


    public function getId()
    {
        if ($this->sessionId === null) {
            $this->setId(time()); //Use current timestamp
        }
        return $this->sessionId;
    }

    public function getDir()
    {
        $sessionDir =  __DIR__ . "/../sessions/" . $this->getId();
        $this->createFolderIfNotExists($sessionDir, "session");
        return realpath($sessionDir);
    }

    public function getPeriodDir($period)
    {
        $periodDir = $this->getDir() . '/Period' . $period;
        $this->createFolderIfNotExists($periodDir, "period");
        return $periodDir;
    }

    public function getAdaptationDir($period, $adaptation)
    {
        $adaptationDir = $this->getPeriodDir($period) . '/Adaptation' . $adaptation;
        $this->createFolderIfNotExists($adaptationDir, "adaptation");
        return $adaptationDir;
    }

    public function getRepresentationDir($period, $adaptation, $representation)
    {
        $representationDir = $this->getAdaptationDir($period, $adaptation) . '/Representation' . $representation;
        $this->createFolderIfNotExists($representationDir, "representation");
        return $representationDir;
    }

    private function createFolderIfNotExists($folder, $type)
    {
        if (!file_exists($folder)) {
            fwrite(STDERR, "Creating $type folder $folder\n");
            mkdir($folder, 0777, true);
        }
    }
}
global $session;
$session= new SessionHandler();
