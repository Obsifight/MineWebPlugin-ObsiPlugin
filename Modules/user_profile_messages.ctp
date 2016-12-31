<?php
if(isset($isInStaff) && $isInStaff && isset($isNotConnected) && $isNotConnected) {
  echo '<a style="margin-top: -200px;z-index: 99;" href="'.$this->Html->url(array('controller' => 'user', 'action' => 'switchServer', 'plugin' => 'obsi')).'" class="btn btn-info pull-right btn-sm">Me switch sur le serveur PvP</a>';
}

if(isset($EmailUpdateRequestResponse) && is_array($EmailUpdateRequestResponse) && isset($EmailUpdateRequestResponse['msg']) && isset($EmailUpdateRequestResponse['type'])) {
  echo '<div class="alert alert-'.$EmailUpdateRequestResponse['type'].'">';
  echo $EmailUpdateRequestResponse['msg'];
  echo '</div>';
}

if(isset($RefundNotification) && $RefundNotification) {
  echo '<div class="alert alert-info">';
    echo $RefundNotification;
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
