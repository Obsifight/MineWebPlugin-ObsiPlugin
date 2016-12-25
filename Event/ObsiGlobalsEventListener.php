<?php
App::uses('CakeEventListener', 'Event');

class ObsiGlobalsEventListener implements CakeEventListener {

  private $controller;

 public function __construct($request, $response, $controller) {
   $this->controller = $controller;
 }

  public function implementedEvents() {
      return array(
          'onLoadPage' => 'setVars'
      );
  }

  public function setVars() {

    if ($this->controller->params['controller'] == "pages" && $this->controller->params['action'] == "display") {
      // registered_count
      $cache = Cache::read('registered_count', 'data');
      if (!$cache) {
        $registered_count = $this->controller->User->find('count');
        Cache::write('registered_count', $registered_count, 'data');
      } else {
        $registered_count = $cache;
      }
      $this->controller->set('registered_count', $registered_count);

      // max players
      $cache = Cache::read('maxPlayers', 'data');
      if (!$cache) {
        $this->CountPlayersLog = ClassRegistry::init('Obsi.CountPlayersLog');
        $findMaxplayers = $this->CountPlayersLog->find('first', array('order' => 'players_online DESC'));
        $maxPlayers = (isset($findMaxplayers['CountPlayersLog']['players_online'])) ? $findMaxplayers['CountPlayersLog']['players_online'] : 0;
        Cache::write('maxPlayers', $maxPlayers, 'data');
      } else {
        $maxPlayers = $cache;
      }
      $this->controller->set('maxPlayers', $maxPlayers);
    }

    if ($this->controller->params['controller'] == "shop" && $this->controller->params['plugin'] == "shop") {
      $this->controller->set('header', false);
    }

    // did you know
    $cache = Cache::read('didYouKnow', 'data');
    if (!$cache) {
      $this->didYouKnowModel = ClassRegistry::init('Obsi.DidYouKnow');
      $didYouKnow = array_map(function ($el) {

        return $el['DidYouKnow']['text']; // return text element
      }, $this->didYouKnowModel->find('all'));
      Cache::write('didYouKnow', $didYouKnow, 'data');
    } else {
      $didYouKnow = $cache;
    }
    $this->controller->set(compact('didYouKnow'));
  }

}
