<?php

namespace DASHIF;

class MPDHandler
{

  private $url;
  private $dom;

  public function __construct($url)
  {
      $this->url = $url;
      $this->dom = null;

      $this->load();
  }

  private function load(){
    if(!$this->url){
      return;
    }
    
    $this->dom = get_DOM($mpd_url, 'MPD');
  }
}
