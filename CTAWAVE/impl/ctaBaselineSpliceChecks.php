<?php

global $MediaProfDatabase, $session_dir, $string_info, $progress_xml, $progress_report, $adaptation_set_template,
$reprsentation_template, $CTAspliceConstraitsLog;

///\todo Make sure errors get parsed as an option instead of full fail
$this->checkSequentialSwSetMProfile();
$this->checkDiscontinuousSplicePoints();
$this->checkEncryptionChangeSplicePoint();
$this->checkSampleEntryChangeSplicePoint();
$this->checkDefaultKIDChangeSplicePoint();
$this->checkPicAspectRatioSplicePoint();
$this->checkFrameRateSplicePoint();
$this->checkAudioChannelSplicePoint();


$this->WAVEProgramChecks();
