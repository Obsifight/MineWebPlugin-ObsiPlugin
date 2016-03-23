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

    $this->controller->loadModel('News');
    $NewsList = $this->controller->News->find('all', array('conditions' => array('published' => 1), 'order' => 'id desc', 'limit' => 5));
    $this->controller->set('NewsList', $NewsList);

    $registered_count = $this->controller->User->find('count');
    $this->controller->set('registered_count', $registered_count);

    $this->CountPlayersLog = ClassRegistry::init('Obsi.CountPlayersLog');
    $findMaxplayers = $this->CountPlayersLog->find('first', array('order' => 'players_online DESC'));
    $maxPlayers = (isset($findMaxplayers['CountPlayersLog']['players_online'])) ? $findMaxplayers['CountPlayersLog']['players_online'] : 0;
    $this->controller->set('maxPlayers', $maxPlayers);
  }

}
