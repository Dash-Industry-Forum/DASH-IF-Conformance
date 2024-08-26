<?php

global $session;

$this->downloadTime = new DateTimeImmutable();

$isLocal = false;
$localManifestLocation = '';

if ($session) {
    $localManifestLocation = $session->getDir() . '/Manifest.mpd';
    if (isset($_FILES['mpd']) && move_uploaded_file($_FILES['mpd']['tmp_name'], $localManifestLocation)) {
        $this->url = $localManifestLocation;
        $isLocal = true;
    } else {
        if ($this->url && $this->url != '') {
            //Download with CURL;
            $this->downloadSegment($localManifestLocation, $this->url);
            $isLocal = true;
        }
    }
}

if ($this->url && $this->url != '') {
    if ($isLocal) {
        $this->mpd = file_get_contents($localManifestLocation);
    } else {
        $this->mpd = file_get_contents($this->url);
    }
} elseif (isset($_REQUEST['mpd'])) {
    $this->mpd = $_REQUEST['mpd'];
}


///\Todo: Check if this works with http basic auth
if (!$this->mpd) {
    return;
}
