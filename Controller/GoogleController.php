<?php
class GoogleController extends AppController {

  public function beforeFilter() {
    parent::beforeFilter();
    $this->Security->unlockedActions = array('auth');

    // API GOOGLE
    require_once ROOT.DS.'app'.DS.'Plugin'.DS.'Obsi'.DS.'Vendor'.DS.'google-api-php-client'.DS.'src'.DS.'Google'.DS.'autoload.php';
  }

  private $client_id = '908579317485-flhrog8ra4k9qimqdbs296c3hanm21mm.apps.googleusercontent.com';
  private $client_token = 'Vc_kaWejHy_EgM_h063HovZ_';

  public function auth() {
    $this->autoRender = false;

    $client = new Google_Client();
    $client->setClientId($this->client_id);
    $client->setClientSecret($this->client_token);

    $client->setScopes('https://www.googleapis.com/auth/youtube.readonly');
    $redirect = Router::url($this->here, true);
    $client->setRedirectUri($redirect);

    // Define an object that will be used to make all API requests.
    $youtube = new Google_Service_YouTube($client);

    // Check if an auth token exists for the required scopes
    if (isset($_GET['code'])) {
      try {
        $client->authenticate($_GET['code']);
      } catch (Exception $e) {
        $this->redirect($client->createAuthUrl()); // not valid code
      }

      if ($client->getAccessToken()) {
        $youtube = new Google_Service_YouTube($client);
        $channels = $youtube->channels->listChannels('statistics', array(
          'mine' => true
        ));
        // stats
        $subs = intval($channels->getItems()[0]->getStatistics()->getSubscriberCount());
        if ($subs >= 750)
          $this->Session->setFlash("Tu as plus de 750 abonnés, tu as donc obtenu le grade YouTubeur sur notre serveur ! Bon jeu !", 'toastr.success');
        else
          $this->Session->setFlash("Tu n'as pas plus de 750 abonnés, tu ne peux donc pas obtenir le grade YouTubeur sur notre serveur pour le moment.", 'toastr.error');
        return $this->redirect(array('controller' => 'user', 'action' => 'profile', 'plugin' => false));
      }

    }
    $this->redirect($client->createAuthUrl()); // node code
  }

}
