<?php

namespace DASHIF;

class SessionHandler
{
    private $sessionId;

    public function __construct()
    {
      $this->sessionId = null;
      $this->reset();
    }

    public function reset($id = null, $clearPrevious = true, $keepOutput = true)
    {
      if ($clearPrevious){
        $this->clearDirectory($keepOutput);
      }
      $this->setId($id);
    }

    public function setId($id)
    {
        fwrite(STDERR, "Setting session id to $id \n ");
        $this->sessionId = $id;
    }


    public function getId()
    {
        if ($this->sessionId === null) {
            $this->setId(time()); //Use current timestamp
        }
        return $this->sessionId;
    }

    public function clearDirectory($keepOutput = true){
      $dir = $this->getDir();
      $directoryContents = `ls $dir`;
      if ($directoryContents == '' || !$keepOutput){
        `rm -r $dir`;
        return;
      }
      `mv $dir/logger.txt $dir/.logger.txt`;
      `rm -r $dir/*`;
      `mv $dir/.logger.txt $dir/logger.txt`;
    }

    public function getDir()
    {

        //$sessionDir =  sys_get_temp_dir() . "/sessions/" . $this->getId();
        $sessionDir =  __DIR__ . "/sessions/" . $this->getId();
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
            mkdir($folder, 0777, true);
        }
    }
}
global $session;
$session = new SessionHandler();
