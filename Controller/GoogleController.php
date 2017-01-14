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
        $channel = $channels->getItems()[0];
        $subs = intval($channel->getStatistics()->getSubscriberCount());
        $channelId = $channel->getId();
        if ($subs >= 750) {
          $this->loadModel('Obsi.YoutubeChannel');
          // check if not already set for other user
          $find = $this->YoutubeChannel->find('first', array('conditions' => array('youtube_channel_id' => $channelId)));
          if (!empty($find)) {
            $this->Session->setFlash("Cette chaine YouTube est déjà attribuée à un autre utilisateur !", 'toastr.error');
            return $this->redirect(array('controller' => 'user', 'action' => 'profile', 'plugin' => false));
          }
          // save into history
          $this->YoutubeChannel->create();
          $this->YoutubeChannel->set(array(
            'user_id' => $this->User->getKey('id'),
            'youtube_channel_id' => $channelId
          ));
          $this->YoutubeChannel->save();
          // server command
          $this->Server->call(array('performCommand' => "pex user {$this->User->getKey('pseudo')} group add Youtube"), true, Configure::read('ObsiPlugin.server.pvp.id'));
          $this->Server->call(array('performCommand' => "pex user {$this->User->getKey('pseudo')} group remove guerrier"), true, Configure::read('ObsiPlugin.server.pvp.id'));
          // notification
          $this->Session->setFlash("Tu as plus de 750 abonnés, tu as donc obtenu le grade YouTubeur sur notre serveur ! Bon jeu !", 'toastr.success');
        } else {
          $this->Session->setFlash("Tu n'as pas plus de 750 abonnés, tu ne peux donc pas obtenir le grade YouTubeur sur notre serveur pour le moment.", 'toastr.error');
        }
        return $this->redirect(array('controller' => 'user', 'action' => 'profile', 'plugin' => false));
      }

    }
    $this->redirect($client->createAuthUrl()); // node code
  }

  public function manageVideos() {
    if (!$this->isConnected)
      throw new ForbiddenException('Not logged');
    // is youtuber
    $this->loadModel('Obsi.YoutubeChannel');
    $findYoutubeChannel = $this->YoutubeChannel->find('first', array('conditions' => array('user_id' => $this->User->getKey('id'))));
    if (empty($findYoutubeChannel))
      throw new ForbiddenException('Not youtuber');
    $channel = $findYoutubeChannel['YoutubeChannel'];
    $channel_id = $channel['youtube_channel_id'];
    // find videos
    $this->loadModel('Obsi.YoutubeVideo');
    $videos = $this->YoutubeVideo->find('all', array('conditions' => array('channel_id' => $channel_id)));

    // vars
    $this->set(compact('videos', 'channel_id'));
    $this->set('title_for_layout', 'Vos vidéos');
  }

  public function remuneration() {
    $this->autoRender = false;
    if (!$this->isConnected)
      throw new ForbiddenException('Not logged');
    if (!$this->request->params['id'])
      throw new NotFoundException('Missing id');
    // is youtuber
    $this->loadModel('Obsi.YoutubeChannel');
    $findYoutubeChannel = $this->YoutubeChannel->find('first', array('conditions' => array('user_id' => $this->User->getKey('id'))));
    if (empty($findYoutubeChannel))
      throw new ForbiddenException('Not youtuber');
    $channel = $findYoutubeChannel['YoutubeChannel'];
    $channel_id = $channel['youtube_channel_id'];
    // find video
    $this->loadModel('Obsi.YoutubeVideo');
    $findVideo = $this->YoutubeVideo->find('first', array('conditions' => array('id' => $this->request->params['id'], 'channel_id' => $channel_id, 'eligible' => true, 'payed' => false)));
    if (empty($findVideo))
      throw new NotFoundException('Video not found');
    $video = $findVideo['YoutubeVideo'];

    // calcul
    $remuneration = 0.3 * $video['views_count'] + 0.5 * $video['likes_count'];
    // Calculate new sold
    $findUser = $this->User->find('first', array('conditions' => array('id' => $this->User->getKey('id'))));
    $newSold = floatval($findUser['User']['money']) + floatval($remuneration);
    // Set new sold
    $this->User->id = $this->User->getKey('id');
    $this->User->saveField('money', $newSold);

    // set video as non-eligible
    $this->YoutubeVideo->read(null, $video['id']);
    $this->YoutubeVideo->set(array('payed' => true));
    $this->YoutubeVideo->save();
    // set in history video infos + remuneration
    $this->loadModel('Obsi.YoutubeVideosRemunerationHistory');
    $this->YoutubeVideosRemunerationHistory->create();
    $this->YoutubeVideosRemunerationHistory->set(array(
      'video_id' => $video['id'],
      'youtube_video_id' => $video['video_id'],
      'channel_id' => $video['channel_id'],
      'title' => $video['title'],
      'description' => $video['description'],
      'publication_date' => $video['publication_date'],
      'views_count' => $video['views_count'],
      'likes_count' => $video['likes_count'],
      'remuneration' => $remuneration
    ));
    $this->YoutubeVideosRemunerationHistory->save();

    // redirect + success msg
    $this->Session->setFlash("Vous avez été rémunéré de {$remuneration} {$this->Configuration->getMoneyName()} pour votre vidéo \"{$video['title']}\".", 'toastr.success');
    $this->redirect('/user/youtube/videos');
  }

  public function admin_history() {
    if (!$this->isConnected || !$this->Permissions->can('ADMIN_VIEW_VIDEO_REMUNERATION_HISTORY'))
      throw new ForbiddenException();
    $this->layout = 'admin';
    $this->set('title_for_layout', "Voir l'historique des rémunérations");

    $this->loadModel('Obsi.YoutubeVideosRemunerationHistory');
    $this->set('histories', $this->YoutubeVideosRemunerationHistory->find('all', array('order' => 'id DESC')));
  }

}
