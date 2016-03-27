<?php
App::uses('CakeEventListener', 'Event');

class ObsiGlobalsEventListener implements CakeEventListener {

  private $controller;

 public function __construct($request, $response, $controller) {
   $this->controller = $controller;
 }

  public function implementedEvents() {
      return array(
          'onLoadPage' => 'setVars'
      );
  }

  public function setVars() {

    $this->controller->loadModel('News');
    $NewsList = $this->controller->News->find('all', array('conditions' => array('published' => 1), 'order' => 'id desc', 'limit' => 5));
    $this->controller->set('NewsList', $NewsList);

    $registered_count = $this->controller->User->find('count');
    $this->controller->set('registered_count', $registered_count);

    $this->CountPlayersLog = ClassRegistry::init('Obsi.CountPlayersLog');
    $findMaxplayers = $this->CountPlayersLog->find('first', array('order' => 'players_online DESC'));
    $maxPlayers = (isset($findMaxplayers['CountPlayersLog']['players_online'])) ? $findMaxplayers['CountPlayersLog']['players_online'] : 0;
    $this->controller->set('maxPlayers', $maxPlayers);

    if($this->controller->params['controller'] == "shop" && $this->controller->params['plugin'] == "shop") {
      $this->controller->set('header', false);
    }

    $this->controller->set('didYouKnow', array(
      'ObsiFight a été fondé par Suertz en 2014.',
      'Sur la boutique, certaines solutions sont plus avantageuses que d\'autres.',
      'La question du Mercredi revient... chaque mercredi.',
      'La v4 d\'ObsiFight était en 1.8.',
      'La limite d\'AP (5 claims) ne porte pas sur les coins.',
      'La TNT au Xénotium a fait crasher le serveur, lors de son premier test.',
      'Le KingZombie est apparu pour la première fois lors de la v4.',
      'Herobrine a été retiré d\'ObsiFight.',
      'L\'Obsidian est deux fois plus durable que le Garnet.',
      'L\'Obsidian est plus résistant que le Titanium, mais fait moins de dégâts.',
      'Les échelles de fer sont apparues pour la première fois lors de la v3.',
      'Le Sadian s\'appelait auparavant Grobsi.',
      'L\'Orbe de réparation peut être enchantée "Incassable III".',
      'Le TP-Kill est autorisé, mais pas les demandes de tp dans le chat.',
      'Ce message a été écrit par Isotheis.',
      'Les joueurs sont capables de réfléchir seuls.',
      'dem0niak joue sur un compte joueur actuellement, le nom reste secret.',
      'Le serveur Obsifight possède un Twitter et un Facebook.',
      'Au départ, il y avait 3 fondateurs: antoinewin, dem0niak et Suertzz.',
      'L\'usebug bateau a toujours été interdit.',
      'Le kit "Obsidien" s\'appelait auparavant kit warrior.',
      'Les KOTH ont fait leur apparition pour la première fois lors du début de la v4.',
      'dem0niak était fondateur à la v1 mais il avait fait le choix d\'être aussi modérateur.',
      'Obsifight était à la v1/v2 parmi les meilleurs pvp/faction francophones ; l\'objectif de la v5 est de revenir sur ces bases.',
      'Un nouveau règlement pour les factions a été mis en place pour v5.',
      'La TNT AU Xénotium a fait son apparition en version 4.',
      'Le manganèse a disparu lors de la v3 mais a fait son retour lors de la v4.',
      'Le xénotium et l\'obsidienne n\'étaient pas présent au début de la v1 mais sont arrivés pendant cette version.',
      'Le bloc d\'obsidienne casse en 1 coup de tnt.',
      'Il y a au total 30 messages pour ce "Le saviez-vous ?".'
    ));
  }

}
