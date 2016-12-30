<?php
class FactionsRankingController extends ObsiAppController {

  public function index() {
    $this->set('title_for_layout', 'Classement des factions');

    $pointsCalcul = array(
      /*array(
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
      )*/
    );

    $this->set(compact('pointsCalcul'));
  }

  public function edit() {
    if (!$this->isConnected)
      throw new ForbiddenException('Not logged');
    $factionId = @json_decode(@file_get_contents('http://factions.api.obsifight.net/player/is-leader/' . $this->User->getKey('pseudo')));
    if (!$factionId || !$factionId->status || !$factionId->isLeader)
      throw new ForbiddenException('Not leader');
    $factionId = $factionId->factionId;

    $this->set(compact('factionId'));
    $this->set('title_for_layout', 'Éditer l\'affichage de votre faction');
  }

  public function uploadLogo() {
    $this->autoRender = false;
    $this->response->type('json');

    // Valid request
    if (!$this->isConnected)
      throw new ForbiddenException('Not logged');
    $factionId = @json_decode(@file_get_contents('http://factions.api.obsifight.net/player/is-leader/' . $this->User->getKey('pseudo')));
    if (!$factionId || !$factionId->status || !$factionId->isLeader)
      throw new ForbiddenException('Not leader');
    $factionId = $factionId->factionId;

    // Config
    $maxSize = 10000000; // octet
    $filename = "faction-logo-$factionId.png";
    $target = WWW_ROOT.DS.'img'.DS.'uploads'.DS.'factions-logo'.DS;
    $widthMax = 320; // pixel
    $heightMax = 320; // pixel

    $isValidImg = $this->Util->isValidImage($this->request, array('png'), $widthMax, $heightMax, $maxSize);

    if(!$isValidImg['status']) {
      $this->response->body(json_encode(array('status' => false, 'msg' => $isValidImg['msg'])));
      return;
    } else {
      $infos = $isValidImg['infos'];
    }

    if(!$this->Util->uploadImage($this->request, $target.$filename)) {
      $this->response->body(json_encode(array('status' => false, 'msg' => $this->Lang->get('FORM__ERROR_WHEN_UPLOAD'))));
      return;
    }

   $this->response->body(json_encode(array('status' => true, 'msg' => 'Le logo de votre faction a bien été enregistré !')));
  }

}
