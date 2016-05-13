<?php
if(isset($EmailUpdateRequestResponse) && is_array($EmailUpdateRequestResponse) && isset($EmailUpdateRequestResponse['msg']) && isset($EmailUpdateRequestResponse['type'])) {
  echo '<div class="alert alert-'.$EmailUpdateRequestResponse['type'].'">';
  echo $EmailUpdateRequestResponse['msg'];
  echo '</div>';
}

if(isset($canCreateFactionChannel) && $canCreateFactionChannel) {
  echo '<div class="alert alert-info">';
    echo 'Vous êtes chef de la faction <b>'.$userFaction.'</b> ! Vous pouvez donc créer votre channel TeamSpeak en cliquant sur le bouton suivant.';
    echo '<a href="'.$this->Html->url(array('controller' => 'teamspeak', 'action' => 'create', 'plugin' => 'obsi')).'" class="btn btn-info pull-right btn-sm" style="margin-top: -8px;">Créer le channel</a>';
  echo '</div>';
}

if(isset($RefundNotification) && $RefundNotification) {
  echo '<div class="alert alert-info">';
    echo $RefundNotification;
  echo '</div>';
}

if(isset($isInStaff) && $isInStaff && isset($isNotConnected) && $isNotConnected) {
  echo '<div class="alert alert-info">';
    echo 'Vous faites partie du staff et vous n\'êtes pas connecté sur le serveur PvP-Faction ! ';
    echo '<a href="'.$this->Html->url(array('controller' => 'user', 'action' => 'switchServer', 'plugin' => 'obsi')).'" class="btn btn-info pull-right btn-sm" style="margin-top: -8px;">Me switch sur le serveur PvP</a>';
  echo '</div>';
}

if(isset($havePrize) && $havePrize) {
  if($isNotConnected) {
    echo '<div class="alert alert-warning">';
      echo 'Vous avez un lot en attente (<i>'.$prize['name'].'</i>) ! Mais vous n\'êtes pas connecté, connectez-vous en jeu pour pouvoir le recevoir !';
    echo '</div>';
  } else {
    echo '<div class="alert alert-info">';
      echo 'Vous avez un lot en attente (<i>'.$prize['name'].'</i>) ! Récupérez-le dès maintenant !';
      echo '<a href="'.$this->Html->url(array('controller' => 'prize', 'action' => 'get', 'plugin' => 'obsi', $prize['id'])).'" class="btn btn-info pull-right btn-sm" style="margin-top: -8px;">Récupérer mon lot</a>';
    echo '</div>';
  }
}
?>
