<?php
App::uses('CakeEventListener', 'Event');

class ObsiShopEventListener implements CakeEventListener {

  private $controller;

 public function __construct($request, $response, $controller) {
   $this->controller = $controller;
 }

  public function implementedEvents() {
    return array(
      'onLoadPage' => 'restrictAccount'
    );
  }

  public function restrictAccount($event) {
    if ($this->controller->params['controller'] != 'PaymentPage' || $this->controller->params['action'] != 'addCredit' || $this->controller->params['plugin'] != 'ShopPlus')
      return;
    $this->controller->loadModel('Shop.PaypalHistory');
    $findPayment = $this->controller->PaypalHistory->find('first', array('conditions' => array(
      'user_id' => $this->controller->User->getKey('id'),
      'obsi-status' => array('REVERSED', 'CANCELED_REVERSAL', 'REFUNDED')
    )));
    if (!empty($findPayment))
      $this->controller->set('restrictAccount', true);
    else
      $this->controller->set('restrictAccount', false);
  }

}
