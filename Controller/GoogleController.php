<?php
class GoogleController extends AppController {

  public function beforeFilter() {
    parent::beforeFilter();
    $this->Security->unlockedActions = array('auth');
  }

  private $client_token = '908579317485-flhrog8ra4k9qimqdbs296c3hanm21mm.apps.googleusercontent.com';

  public function auth() {
    $this->response->type('json');
    $this->autoRender = false;
    if (!$this->request->is('post'))
      throw new NotFoundException('Not post');
    if (!$this->isConnected)
      throw new ForbiddenException('Not logged');
    if (empty($this->request->data['id_token']))
      throw new NotFoundException('Missing id_token');

    // Google API
    require ROOT.DS.'app'.DS.'Plugin'.DS.'Obsi'.DS.'Vendor'.DS.'google-api-php-client'.DS.'src'.DS.'Google'.DS.'autoload.php';

    // Check token
    $client = new Google_Client(/*['client_id' => $this->client_token]*/);
    $client->setAuthConfigFile(ROOT.DS.'app'.DS.'Plugin'.DS.'Obsi'.DS.'Vendor'.DS.'client_secret_908579317485-flhrog8ra4k9qimqdbs296c3hanm21mm.apps.googleusercontent.com.json');
    $payload = $client->verifyIdToken($this->request->data['id_token']);
    if ($payload) {
      // Basic data
      $data = $payload->getAttributes();
      $userid = $data['payload']['sub'];
debug($userid);
debug($data['payload']);
      // Authentification
      $service_account_email = 'websiteyt@obsifight-154211.iam.gserviceaccount.com';
      $key_file_location = ROOT.DS.'app'.DS.'Plugin'.DS.'Obsi'.DS.'Vendor'.DS.'ObsiFight-ca16261e8aff.p12';
      $key = file_get_contents($key_file_location);
      $cred = new Google_Auth_AssertionCredentials(
        $service_account_email,
        array(Google_Service_YouTube::YOUTUBE_READONLY),
        $key
      );
      // Get access token
      $client->setAssertionCredentials($cred);
      if($client->getAuth()->isAccessTokenExpired()) {
        $client->getAuth()->refreshTokenWithAssertion($cred);
      }
      // Get user channel
      $youtube = new Google_Service_YouTube($client);
      $channels = $youtube->channels->listChannels('statistics', array(
        'mine' => true
      ));
      // Get user subs count
      debug($channels->getItems()[0]);
      debug($channels->getItems()[0]->getStatistics()->getSubscriberCount());
      debug($channels->getItems()[0]->getStatistics()->getHiddenSubscriberCount());
      $subsCount = $channels->getItems()[0]->getStatistics()->getSubscriberCount();
      debug($subsCount);
      // Render
      $this->response->body(json_encode(array('status' => true, 'subsCount' => $subsCount)));

    } else {
      // Invalid ID token
      throw new ForbiddenException('Invalid ID token');
    }
  }

}
