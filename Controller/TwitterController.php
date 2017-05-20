<?php
class TwitterController extends ObsiAppController {

  public function link() {
    $this->autoRender = false;
    if (!$this->isConnected)
      throw new ForbiddenException();

    // Generate link
    $password = $this->User->getKey('password');
    $authKey = hash('sha256', $password);
    $userId = $this->User->getKey('id');
    $callback = urlencode(Router::url(array('action' => 'linked'), true));
    $notification = urlencode(Router::url(array('action' => 'notification'), true));

    // Redirect user&notification=$notification
    $this->redirect("http://api.obsifight.net/socials/twitter/authorization/request?userId=$userId&authKey=$authKey&callback=$callback&notification=$notification");
  }

  public function notification() {
    $this->autoRender = false;
    // check request
    if (!$this->request->is('post'))
      throw new NotFoundException();
    if (!$this->request->data || !isset($this->request->data['accessToken']) || !isset($this->request->data['accessSecret']) || !isset($this->request->data['user']) || !isset($this->request->data['userId']) || !isset($this->request->data['authKey']))
      throw new BadRequestException();

    $this->loadModel('User');
    // check authenticity
    $password = $this->User->getKeyFromUser('password', $this->request->data['userId']);
    if (!$password)
      throw new ForbiddenException();
    if (hash('sha256', $password) !== $this->request->data['authKey'])
      throw new ForbiddenException();

    // save data
    $this->loadModel('Obsi.UsersTwitter');
    $findTwitter = $this->UsersTwitter->find('first', array('conditions' => array('user_id' => $this->request->data['userId'])));
    if (!empty($findTwitter))
      $this->UsersTwitter->read(null, $findTwitter['UsersTwitter']['id']);
    else
      $this->UsersTwitter->create();
    $this->UsersTwitter->set(array(
      'user_id' => $this->request->data['userId'],
      'twitter_id' => $this->request->data['user']['id'],
      'screen_name' => $this->request->data['user']['screen_name'],
      'access_token' => $this->request->data['accessToken'],
      'access_secret' => $this->request->data['accessSecret']
    ));
    $this->UsersTwitter->save();
  }

  public function linked() {
    $this->autoRender = false;
    if (!$this->isConnected)
      throw new ForbiddenException();

    $this->Session->setFlash("Tu as bien lié ton compte Twitter '@{$this->request->query['screen_name']}' ! Bon jeu !", 'toastr.success');
    $this->redirect(array('controller' => 'user', 'action' => 'profile', 'plugin' => false));
  }

}
