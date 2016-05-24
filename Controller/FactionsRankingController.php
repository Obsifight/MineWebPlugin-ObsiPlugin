<?php
class FactionsRankingController extends ObsiAppController {

  public function index() {
    $this->set('title_for_layout', 'Classement des factions');
  }


  public function get() {
    $this->response->type('json');
    if($this->request->is('ajax')) {

      $result = array();

      $this->loadModel('Obsi.FactionsRanking');
      $factions = $this->FactionsRanking->find('all', array('order' => 'points DESC'));

      $i = 0;
      foreach ($factions as $faction) {

        $result[$i] = $faction;
        $result[$i]['position'] = $i+1;

        $i++;

      }

      echo json_encode($result);

    } else {
      throw new NotFoundException();
    }
  }



}
