<?php

class ObsiAPIController extends ObsiAppController {

  public $components = array('API');

  public function getHeadSkin($username, $size = 50) {
    $this->response->type('png');
		$this->autoRender = false;

    $filename = 'http://skins.obsifight.fr/skins/'.$username.'.png';

	  echo $this->API->get_head_skin($username, $size, $filename);
  }

}
