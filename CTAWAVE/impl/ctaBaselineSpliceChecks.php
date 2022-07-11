<?php

global $MediaProfDatabase, $session_dir, $string_info, $progress_xml, $progress_report, $adaptation_set_template,
$reprsentation_template, $CTAspliceConstraitsLog;

///\RefactorTodo Create Separate Logger Instance
///\RefactorTodo If possible, loop once instead of in each file
$this->checkSequentialSwSetMProfile();
$this->checkDiscontinuousSplicePoints();
$this->checkEncryptionChangeSplicePoint();
$this->checkSampleEntryChangeSplicePoint();
$this->checkDefaultKIDChangeSplicePoint();
$this->checkPicAspectRatioSplicePoint();
$this->checkFrameRateSplicePoint();
$this->checkAudioChannelSplicePoint();


$this->WAVEProgramChecks();
