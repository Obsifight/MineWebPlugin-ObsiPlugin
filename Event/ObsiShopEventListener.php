<?php
App::uses('CakeEventListener', 'Event');

class ObsiShopEventListener implements CakeEventListener {

  private $controller;

 public function __construct($request, $response, $controller) {
   $this->controller = $controller;
 }

  public function implementedEvents() {
      return array(
          'onLoadPage' => 'paysafecard',
          'requestPage' => 'checkIfPaysafecardBan',
          'paysafecardAdded' => 'notify'
      );
  }

  public function paysafecard($event) {

    $pscTakedModel = ClassRegistry::init('Obsi.PscTaked');
    $findPscTaked = $pscTakedModel->find('all');

    $pscTaked = array();
    if(!empty($findPscTaked)) {
      foreach ($findPscTaked as $key => $value) {
        $pscTaked[$value['PscTaked']['psc_id']] = ($this->controller->isConnected && $this->controller->User->getKey('id') == $value['PscTaked']['user_id']) ? true : false;
      }
    }

    ModuleComponent::$vars['pscTaked'] = $pscTaked;

  }

  public function checkIfPaysafecardBan($event) {
    if($this->controller->params['controller'] == "payment" && $this->controller->params['action'] == "paysafecard") {

      $user_id = $this->controller->User->getKey('id');

      // Ban

      $this->PscBan = ClassRegistry::init('Obsi.PscBan');
      $findBan = $this->PscBan->find('first', array('conditions' => array('user_id' => $user_id)));
      if(!empty($findBan)) {

        echo json_encode(array('statut' => false, 'msg' => 'Vous avez été banni du moyen de paiement PaySafeCard pour abus ! Vous ne pouvez plus utiliser ce moyen de paiement.'));
        exit;

        $event->stopPropagation();
        return false;
      }

    }
  }

  public function notify($event) {

    // Pushbullet

    $msg = $event->data['user']['pseudo'].' a déposé une PaySafeCard de '.$event->data['data']['amount'].' € !';

    App::uses('HttpSocket', 'Network/Http');
    $HttpSocket = new HttpSocket();

    /*$response = $HttpSocket->request([
      'method' => 'POST',
      'uri' => 'https://api.pushbullet.com/v2/pushes',
      'header' => [
        'Authorization' => 'Bearer o.syhxWyjBJ6tir7R4cDGonwV4GausKkzj'
      ],
      'body' => [
        "channel_tag" => "ziziproutcaca",
        "type" => "note",
        "title" => "Nouvelle paysafecard",
        "body" => $msg,
        "url" => "http://obsifight.net/admin/shop/payment"
      ]
    ]);*/
    $response = $HttpSocket->post('https://api.pushbullet.com/v2/pushes',
      [
        "channel_tag" => "ziziproutcaca",
        "type" => "note",
        "title" => "Nouvelle paysafecard",
        "body" => $msg,
        "url" => "http://obsifight.net/admin/shop/payment"
      ],
      [
        'header' => [
          'Authorization' => 'Bearer o.syhxWyjBJ6tir7R4cDGonwV4GausKkzj'
        ]
      ]
    );

    if($response->code != 200) {
      $this->controller->log('PushBullet error ('.$response->code.') : '.$response->body);
    }

  }

}
