<?php
class SanctionsComponent extends Object {

  function shutdown(&$controller) {}
  function beforeRender(&$controller) {}
  function beforeRedirect() {}
  function initialize(&$controller) {}
  function startup(&$controller) {}

  /*
    Savoir si le joueur est banni actuellement
  */
  public function isBanned($username, $api) {
    $result = $api->get("/user/$username/sanctions/banned");
    if (!$result->status || !$result->success) // error
      return false;
    return $result->body['banned'];
  }

  /*
    Savoir si le joueur est mute actuellement
  */
  public function isMuted($username, $api) {
    $result = $api->get("/user/$username/sanctions/muted");
    if (!$result->status || !$result->success) // error
      return false;
    return $result->body['muted'];
  }

}
