<div class="row">
  <div class="col-md-12">
    <div class="box">
      <div class="box-header with-border">
        <h3 class="box-title">ObsiGuard</h3>
      </div>
      <div class="box-body">

        <?php
        if($obsiguardStatus) {

          if(!empty($obsiguardIPs)) {

            echo '<table class="table table-bordered">';
              echo '<thead>';
                echo '<tr>';
                  echo '<th>IP</th>';
                  echo '<th>Action</th>';
                echo '<tr>';
              echo '</thead>';
              echo '<tbody>';
                foreach ($obsiguardIPs as $id => $ip) {
                  echo '<tr>';
                    echo '<td>'.$ip.'</td>';
                    echo '<td><a class="btn btn-danger" href="'.Router::url(array('controller' => 'user', 'action' => 'deleteIPObsiguard', 'plugin' => 'obsi', 'admin' => true, $search_user['id'], $id)).'">Supprimer</a></td>';
                  echo '</tr>';
                }
              echo '</tbody>';
            echo '</table>';

          } else {
            echo '<div class="alert alert-danger">Le joueur n\'a pas autorisé d\'IPs !</div>';
          }

          echo '<br>';

          if($obsiguardDynamicIPStatus) {
            echo '<div class="alert alert-info">';
              echo 'Le joueur a activé le mode IP dynamique.';
              echo '<a href="'.Router::url(array('controller' => 'user', 'action' => 'switchObsiguardDynamic', 'plugin' => 'obsi', 'admin' => true, $search_user['id'])).'" class="btn btn-info pull-right btn-sm" style="margin-top: -8px;">Désactiver</a>';
            echo '</div>';
          } else {
            echo '<div class="alert alert-info">';
              echo 'Le joueur a désactivé le mode IP dynamique.';
              echo '<a href="'.Router::url(array('controller' => 'user', 'action' => 'switchObsiguardDynamic', 'plugin' => 'obsi', 'admin' => true, $search_user['id'])).'" class="btn btn-info pull-right btn-sm" style="margin-top: -8px;">Activer</a>';
            echo '</div>';
          }

          echo '<a class="btn btn-danger btn-block" href="'.Router::url(array('controller' => 'user', 'action' => 'disableObsiguard', 'plugin' => 'obsi', 'admin' => true, $search_user['id'])).'">Désactiver ObsiGuard</a>';

        } else {
          echo '<div class="alert alert-danger">Le joueur n\'a pas activé ObsiGuard !</div>';
        }
        ?>

      </div>
    </div>
  </div>
</div>
