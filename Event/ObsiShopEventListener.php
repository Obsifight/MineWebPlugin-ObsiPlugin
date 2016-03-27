<?php
App::uses('CakeEventListener', 'Event');

class ObsiShopEventListener implements CakeEventListener {

  private $controller;

 public function __construct($request, $response, $controller) {
   $this->controller = $controller;
 }

  public function implementedEvents() {
      return array(
          'onLoadPage' => 'paysafecard'
      );
  }

  public function paysafecard() {

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

}
