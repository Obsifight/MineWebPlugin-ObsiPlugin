<?php
App::uses('CakeEventListener', 'Event');

class ObsiShopEventEventListener implements CakeEventListener {

  private $controller;

 public function __construct($request, $response, $controller) {
   $this->controller = $controller;
 }

  public function implementedEvents() {
      return array(
          'beforeEditUser' => 'editEventMoney'
      );
  }

  public function editEventMoney($event) {
    $data = $this->controller->request->data;
    $eventMoney = $data['event_money'];
    $user_id = $event->data['user_id'];

    $this->controller->User->setToUser('obsi-event_money', $eventMoney, $user_id);

  }

}
