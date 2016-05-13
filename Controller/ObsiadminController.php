<?php
class ObsiadminController extends AppController {

  public function admin_index() {
    if($this->isConnected && $this->User->isAdmin()) {
      $this->layout = 'admin';
      $this->set('title_for_layout', 'Fonctionnalités ObsiFight');
    } else {
      throw new ForbiddenException();
    }
  }

}
