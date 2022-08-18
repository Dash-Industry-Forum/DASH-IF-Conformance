<?php

///\RefactorTodo Create Separate Logger Instance
///\RefactorTodo If possible, loop once instead of in each file
$this->checkSequentialSwitchingSetMediaProfile();
$this->checkDiscontinuousSplicePoints();
$this->checkEncryptionChangeSplicePoint();
$this->checkSampleEntryChangeSplicePoint();
$this->checkDefaultKIDChangeSplicePoint();
$this->checkPictureAspectRatioSplicePoint();
$this->checkFrameRateSplicePoint();
$this->checkAudioChannelSplicePoint();


$this->WAVEProgramChecks();
