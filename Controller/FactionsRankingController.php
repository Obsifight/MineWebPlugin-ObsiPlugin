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


        /*

          TEMPORAIRE

        */

          $result[$i]['golds_pieces'] = $result[$i]['end_events'] = $result[$i]['kingzombie_events'] = '<span class="label label-warning">Bientôt disponible</span>';


        unset($result[$i]['id']);
        $result[$i]['position'] = $i+1;

        $result[$i]['points_details'] = json_decode($result[$i]['points_details'], true);

        $result[$i]['position'] .= '
        <div class="modal modal-medium fade" id="'.$result[$i]['name'].'_modal" tabindex="-1" role="dialog" aria-labelledby="'.$result[$i]['name'].'_modalLabel" aria-hidden="true">
          <div class="modal-dialog">
            <div class="modal-content">
              <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
                <h4 class="modal-title" id="myModalLabel">'.$result[$i]['name'].'</h4>
              </div>
              <div class="modal-body">
                <p class="text-center"><img title="'.$result[$i]['leader'].'" src="'.Router::url('/getHeadSkin/'.$result[$i]['leader'].'/100').'" class="img-rounded"><br><br><b>Chef : </b>'.$result[$i]['leader'].'</p>
                <br>
                <p class="text-center"><b>Tués : </b>'.$result[$i]['kills'].' &nbsp;&nbsp;<i class="text-success">+ '.number_format($result[$i]['points_details']['kills'], 0, ',', ' ').' points</i></p>
                <p class="text-center"><b>Morts : </b>'.$result[$i]['deaths'].' &nbsp;&nbsp;<i class="text-danger"> '.number_format($result[$i]['points_details']['deaths'], 0, ',', ' ').' points</i></p>
                <p class="text-center"><b>Pièces d\'or : </b>'.$result[$i]['golds_pieces'].' &nbsp;&nbsp;<i class="text-success">+ '.number_format($result[$i]['points_details']['golds_pieces'], 0, ',', ' ').' points</i></p>
                <p class="text-center"><b>Events end gagnés : </b>'.$result[$i]['end_events'].' &nbsp;&nbsp;<i class="text-success">+ '.number_format($result[$i]['points_details']['end_events'], 0, ',', ' ').' points</i></p>
                <p class="text-center"><b>Events KingZombie gagnés : </b>'.$result[$i]['kingzombie_events'].' &nbsp;&nbsp;<i class="text-success">+ '.number_format($result[$i]['points_details']['kingzombie_events'], 0, ',', ' ').' points</i></p>
                <hr>
                <p class="text-center"><b>Total : </b> '.number_format($result[$i]['points'], 0, ',', ' ').' points</p>
              </div>
            </div>
          </div>
        </div>';
        $result[$i]['points'] = number_format($result[$i]['points'], 0, ',', ' ').'&nbsp;&nbsp;<small><u><a data-toggle="modal" data-target="#'.$result[$i]['name'].'_modal" href="#">Détails</a></u></small>';

        $result[$i]['name'] = '<b>'.$result[$i]['name'].'</b>';
        $result[$i]['leader'] = '<a style="color: #A94545;" href="'.Router::url('/stats/'.$result[$i]['leader']).'">'.$result[$i]['leader'].'</a>';

        $i++;

      }

      echo json_encode(array('data' => $result));

    } else {
      throw new NotFoundException();
    }
  }



}
