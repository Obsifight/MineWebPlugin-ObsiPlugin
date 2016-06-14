<?php
class FactionsRankingController extends ObsiAppController {

  public function index() {
    $this->set('title_for_layout', 'Classement des factions');

    /*$this->loadModel('Obsi.FactionsRanking');
    $lastUpdate = $this->FactionsRanking->query("SELECT CREATE_TIME FROM information_schema.tables WHERE  TABLE_SCHEMA = 'web_v5' AND TABLE_NAME = 'obsi__factions_rankings'");
    $lastUpdate = $lastUpdate[0]['tables']['CREATE_TIME'];*/
    $lastUpdate = date('Y-m-d H:i:s', filemtime(ROOT.DS.'app'.DS.'tmp'.DS.'cache'.DS.'refresh.factions'));

    $factionsData = array(
      array(
        'tableName' => '#',
        'ajaxName' => 'position'
      ),
      array(
        'tableName' => 'Nom',
        'ajaxName' => 'name'
      ),
      /*array(
        'tableName' => 'Chef',
        'ajaxName' => 'leader'
      ),*/
      array(
        'tableName' => 'Tués',
        'ajaxName' => 'kills'
      ),
      array(
        'tableName' => 'Morts',
        'ajaxName' => 'deaths'
      ),
      /*array(
        'tableName' => 'Ratio',
        'ajaxName' => 'ratio'
      ),*/
      array(
        'tableName' => 'Power',
        'ajaxName' => 'power'
      ),
      array(
        'tableName' => 'Pièces d\'or',
        'ajaxName' => 'golds_pieces'
      ),
      array(
        'tableName' => 'Events end',
        'ajaxName' => 'end_events'
      ),
      array(
        'tableName' => 'Events KingZombie',
        'ajaxName' => 'kingzombie_events'
      ),
      array(
        'tableName' => 'Guerre',
        'ajaxName' => 'factions_war'
      ),
      array(
        'tableName' => 'Totems',
        'ajaxName' => 'totems'
      ),
      array(
        'tableName' => 'Points',
        'ajaxName' => 'points'
      )
    );

    $pointsCalcul = array(
      array(
        'title' => 'Tués',
        'td' => array(
          'En dessous de <b>1 000</b>' => '10 points/tués',
          'De <b>1 000</b> à <b>2 000</b>' => '12 points/tués',
          'De <b>2 000</b> à <b>3 000</b>' => '15 points/tués',
          'De <b>3 000</b> à <b>4 000</b>' => '17 points/tués',
          'De <b>4 000</b> à <b>5 000</b>' => '19 points/tués',
          'De <b>5 000</b> à <b>6 000</b>' => '25 points/tués',
          'Au dessus de <b>6 000</b>' => '27 points/tués'
        )
      ),
      array(
        'title' => 'Morts',
        'td' => array(
          'En dessous de <b>1 000</b>' => '-12 points/morts',
          'De <b>1 000</b> à <b>2 000</b>' => '-14 points/morts',
          'De <b>2 000</b> à <b>3 000</b>' => '-17 points/morts',
          'De <b>3 000</b> à <b>4 000</b>' => '-19 points/morts',
          'De <b>4 000</b> à <b>5 000</b>' => '-21 points/morts',
          'De <b>5 000</b> à <b>6 000</b>' => '-27 points/morts',
          'Au dessus de <b>6 000</b>' => '-29 points/morts'
        )
      )
    );

    $this->set(compact('lastUpdate', 'factionsData', 'pointsCalcul'));
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

          $result[$i]['golds_pieces'] = $result[$i]['end_events'] = $result[$i]['kingzombie_events'] = $result[$i]['factions_war'] = '<span class="label label-warning">Bientôt disponible</span>';


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
                <p class="text-center"><b>Score de guerre de factions : </b>'.$result[$i]['factions_war'].' &nbsp;&nbsp;<i class="text-success">+ '.number_format($result[$i]['points_details']['factions_war'], 0, ',', ' ').' points</i></p>
                <p class="text-center"><b>Totem récupérés : </b>'.$result[$i]['totems'].' &nbsp;&nbsp;<i class="text-success">+ '.number_format($result[$i]['points_details']['totems'], 0, ',', ' ').' points</i></p>
                <hr>
                <p class="text-center"><b>Total : </b> '.number_format($result[$i]['points'], 0, ',', ' ').' points</p>
              </div>
            </div>
          </div>
        </div>';
        $result[$i]['points'] = number_format($result[$i]['points'], 0, ',', ' ').'&nbsp;&nbsp;<small><u><a data-toggle="modal" data-target="#'.$result[$i]['name'].'_modal" href="#">Détails</a></u></small>';

        $result[$i]['name'] = '<b>'.$result[$i]['name'].'</b>';
        $result[$i]['leader'] = '<a style="color: #A94545;" href="'.Router::url('/stats/'.$result[$i]['leader']).'">'.$result[$i]['leader'].'</a>';


        /*
          Suppression de données
        */

          unset($result[$i]['leader']);
          unset($result[$i]['ratio']);

        $i++;

      }

      echo json_encode(array('data' => $result));

    } else {
      throw new NotFoundException();
    }
  }



}
