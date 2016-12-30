<?php
class DidYouKnowController extends ObsiAppController {

  public function admin_index() {
    $this->layout = 'admin';
    $this->set('title_for_layout', 'Gérer les "le saviez-vous ?"');
    // check request
    if (!$this->isConnected || !$this->Permissions->can('MANAGE_DID_YOU_KNOW'))
      throw new ForbiddenException();

    $this->loadModel('Obsi.DidYouKnow');
    $this->set('list', $this->DidYouKnow->find('all'));
  }

  public function admin_delete() {
    $this->response->type('json');
    $this->autoRender = false;
    // check request
    if (empty($this->request->params['id']))
      throw new NotFoundException('Missing id');
    if (!$this->isConnected || !$this->Permissions->can('MANAGE_DID_YOU_KNOW'))
      throw new ForbiddenException();

    // delete
    $this->loadModel('Obsi.DidYouKnow');
    $this->DidYouKnow->delete($this->request->params['id']);

    // render
    $this->response->body(json_encode(array('statut' => true, 'msg' => 'Vous avez bien supprimé le "Le saviez-vous" !')));
  }

  public function admin_add() {
    $this->response->type('json');
    $this->autoRender = false;
    // check request
    if (!$this->request->is('post'))
      throw new NotFoundException('Not post');
    if (!$this->isConnected || !$this->Permissions->can('MANAGE_DID_YOU_KNOW'))
      throw new ForbiddenException();
    if (empty($this->request->data['text']))
      return $this->response->body(json_encode(array('statut' => false, 'msg' => 'Vous devez remplir tous les champs.')));

    // save
    $this->loadModel('Obsi.DidYouKnow');
    $this->DidYouKnow->create();
    $this->DidYouKnow->set(array(
      'text' => $this->request->data['text']
    ));
    $this->DidYouKnow->save();

    // render
    $this->response->body(json_encode(array('statut' => true, 'msg' => 'Vous avez bien ajouté le "Le saviez-vous" !', 'data' => array('id' => $this->DidYouKnow->getLastInsertId(), 'text' => $this->request->data['text']))));
  }

}
