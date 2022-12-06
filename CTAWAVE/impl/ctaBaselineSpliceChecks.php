<?php

$this->checkSequentialSwitchingSetMediaProfile();
$this->checkDiscontinuousSplicePoints();
$this->checkEncryptionChangeSplicePoint();
$this->checkSampleEntryChangeSplicePoint();
$this->checkDefaultKIDChangeSplicePoint();
$this->checkPictureAspectRatioSplicePoint();
$this->checkFrameRateSplicePoint();
$this->checkAudioChannelSplicePoint();

$this->WAVEProgramChecks();
