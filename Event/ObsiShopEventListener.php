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
    if ($this->controller->params['controller'] == 'payment' && $this->controller->params['action'] == 'admin_index' && $this->controller->params['plugin'] == 'shop') {
      $this->controller->set('canDisableSendPoints', $this->controller->Permissions->can('ADMIN_DISABLE_SEND_POINTS'));
      $this->controller->loadModel('Shop.PointsTransferHistory');
      $check = $this->controller->PointsTransferHistory->find('first', array('conditions' => array('points' => '-1', 'user_id' => -1, 'author_id' => -1)));
      $this->controller->set('sendPointsState', empty($check));
    }

    if ($this->controller->params['controller'] == 'shop' && $this->controller->params['action'] == 'index' && $this->controller->params['plugin'] == 'shop') {
      // Check if he can see shop (already connected to launcher + logged)
      if (!$this->controller->isConnected)
        return $this->controller->set('canViewShop', false);
      // Check launcher logs
      App::uses('ConnectionManager', 'Model');
      $con = new ConnectionManager;
      try {
        ConnectionManager::create('Util', Configure::read('Obsi.db.Util'));
      } catch (Exception $e) {
        $this->controller->log('Error: '.$e->getMessage());
        return $this->controller->set('canViewShop', true);
      }
      $dbUtil = $con->getDataSource('Util');
      $launcherConnectionLogs = $dbUtil->query("SELECT * FROM loginlogs WHERE username = '{$this->controller->User->getKey('pseudo')}' ORDER BY id DESC");
      if (empty($launcherConnectionLogs))
        return $this->controller->set('canViewShop', false);
      $this->controller->set('canViewShop', true); // he can
    }

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
