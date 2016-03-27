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
?>
