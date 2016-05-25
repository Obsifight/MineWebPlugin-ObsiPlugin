<?php
class FactionsRankingController extends ObsiAppController {

  public function index() {
    $this->set('title_for_layout', 'Classement des factions');

    $this->loadModel('Obsi.FactionsRanking');
    $lastUpdate = $this->FactionsRanking->query("SELECT CREATE_TIME FROM information_schema.tables WHERE  TABLE_SCHEMA = 'web_v5' AND TABLE_NAME = 'obsi__factions_rankings'");
    $lastUpdate = $lastUpdate[0]['tables']['CREATE_TIME'];

    $this->set(compact('lastUpdate'));
  }


  public function get() {
    $this->response->type('json');
    $this->autoRender = false;
    if($this->request->is('ajax')) {

      $result = array();

      $this->loadModel('Obsi.FactionsRanking');
      $factions = $this->FactionsRanking->find('all', array('order' => 'points DESC'));

      $i = 0;
      foreach ($factions as $faction) {

        $result[$i] = $faction['FactionsRanking'];

        unset($result[$i]['id']);
        $result[$i]['position'] = $i+1;

        $result[$i]['name'] = '<b>'.$result[$i]['name'].'</b>';
        $result[$i]['leader'] = '<a style="color: #A94545;" href="'.Router::url('/stats/'.$result[$i]['leader']).'">'.$result[$i]['leader'].'</a>';

        /*

          TEMPORAIRE

        */

          $result[$i]['golds_pieces'] = $result[$i]['end_events'] = $result[$i]['kingzombie_events'] = '<span class="label label-warning">Bientôt disponible</span>';


        $i++;

      }

      echo json_encode(array('data' => $result));

    } else {
      throw new NotFoundException();
    }
  }



}
