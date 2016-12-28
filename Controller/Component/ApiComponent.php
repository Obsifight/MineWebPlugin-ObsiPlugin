<?php
class ApiComponent extends Object {

  function shutdown(&$controller) {}
  function beforeRender(&$controller) {}
  function beforeRedirect() {}
  function initialize(&$controller) {}
  function startup(&$controller) {}

  private function init() {
    require_once ROOT.DS.'app'.DS.'Plugin'.DS.'Obsi'.DS.'Vendor'.DS.'API'.DS.'ApiObsifight.class.php';
    $this->api = new ApiObsifight(Configure::read('ObsiPlugin.api.username'), Configure::read('ObsiPlugin.api.password'));
  }

  function __call($func, $params) {
    if (!isset($api))
      $this->init();
    return call_user_func_array(array($this->api, $func), $params);
  }

}
