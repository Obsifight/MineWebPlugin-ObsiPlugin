<?php

/*
  1ère solution:
  -------------

  A chaque envoie d'email
  -> Si l'utilisateur a déjà été confirmé niveau email, on passe
  -> Si l'utilisateur a déjà un message comme quoi son email est invalide
    -> On n'envoie pas l'email
  -> Sinon, on vérifie son email, si il est invalide
    -> On n'envoie pas l'email
    -> On lui met le message l'avertissant que son email est invalide
  -> Sinon, on envoie l'email

  A chaque inscription
  -> On vérifie l'email

  A chaque demande de changement d'email
  -> On vérifie l'email

  A chaque acceptation de changement d'email
  -> On supprime l'eventuel message d'avertissement


  =================================================

  2e solution :
  ------------

  On utilise MailTester sur tous les emails.

  -> Si le check n'est pas valide
    -> On averti

  Inconvénients :
    - Long
    - Pas forcément fiable "Server doesn't allow e-mail address verification"

  Avantages:
   - Gratuit
   - Permet de tout check d'un coup

*/

App::uses('CakeEventListener', 'Event');

class ObsiMailsEventListener implements CakeEventListener {

private $controller;

 public function __construct($request, $response, $controller) {
   $this->controller = $controller;
 }

  public function implementedEvents() {
      return array(
          //'beforeSendMail' => 'checkIfEmailValid'
      );
  }

  public function checkIfEmailValid($event) {
    $email = $event->data['emailConfig']->from;
    $user = $this->controller->User->find('first', array('conditions' => array('email' => $email)));
    if(empty($user)) {
      return;
    }
    $user = $user['User'];

    // Si l'email a déjà été validé
    if($user['obsi-email_valided'] === TRUE) {
      return;
    }



  }

}
