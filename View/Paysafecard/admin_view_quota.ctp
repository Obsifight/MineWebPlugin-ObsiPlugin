<section class="content">
  <div class="row">
    <div class="col-md-12">
      <div class="box">
        <div class="box-header with-border">
          <h3 class="box-title">Informations sur les quotas des utilisateurs</h3>
        </div>
        <div class="box-body">

          <?php

          foreach ($usersQuotas as $user_id => $amount) {

            $user_pseudo = (isset($usersByID[$user_id])) ? $usersByID[$user_id] : $user_id;

            foreach ($quotas as $user => $quota) {
              if($user_pseudo == $user) {
                $userQuota = $quota;
                break;
              }
            }
            if(!isset($userQuota)) {
              $userQuota = $quotas['all'];
            }

            $percentage = $amount * 100 / $userQuota;

            echo '<p>';
              echo '<b>';
                echo $user_pseudo;
              echo ' : </b>';
              echo '<em>'.$amount.'€/'.$userQuota.'€</em>';
              echo '<br>';
              echo '<div class="progress">';
                echo '<div class="progress-bar progress-bar-warning" role="progressbar" style="width: '.$percentage.'%;">';
                echo '</div>';
              echo '</div>';
            echo '</p><hr>';

            unset($percentage);
            unset($userQuota);
            unset($user_pseudo);

          }

          ?>

        </div>
      </div>
    </div>
  </div>
</section>
