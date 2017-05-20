<?php if(!isset($user_pseudo)) { ?>
  <section class="content">
    <div class="row">
      <div class="col-md-12">
        <div class="box">
          <div class="box-header with-border">
            <h3 class="box-title">Choisir un utilisateur</h3>
          </div>
          <div class="box-body">
            <?php
            if(isset($error)) {
              echo '<div class="alert alert-error">Le joueur n\'a pas été trouvé !</div>';
            }
            ?>

            <form>
              <div class="form-group">
                <label>Pseudo du joueur</label>
                <input name="pseudo" type="text" class="form-control">
              </div>
              <button type="submit" class="btn btn-primary">Rechercher</button>
            </form>
            <script type="text/javascript">
              $('form').on('submit', function(e) {
                e.preventDefault();
                var pseudo = $('input[name="pseudo"]').val();
                window.location = pseudo;
                return false;
              })
            </script>

          </div>
        </div>
      </div>
    </div>
  </section>
<?php } else { ?>
  <section class="content">
    <div class="row">
      <div class="col-md-12">
        <div class="box">
          <div class="box-header with-border">
            <h3 class="box-title">Informations</h3>
          </div>
          <div class="box-body">

            <?php
            if($refunded) {
              echo '<div class="alert alert-success">Le joueur a été remboursé de '.$refundedPB.' PB à la V7 !</div>';
            } else {
              echo '<div class="alert alert-error">Le joueur n\'a pas été remboursé lors de la V7 !</div>';
            }
            ?>

            <hr>

            <p>
              Remboursé à la V5-V6 :
              <?php
              if($refundInV6) {
                echo '<span class="text text-success">Oui</span>';
              } else {
                echo '<span class="text text-danger">Non</span>';
              }
              ?>
            </p>

            <p>
              Remboursé à la V4-V5 :
              <?php
              if($refundInV5) {
                echo '<span class="text text-success">Oui</span>';
              } else {
                echo '<span class="text text-danger">Non</span>';
              }
              ?>
            </p>

            <p>
              Remboursé à la V3-V4 :
              <?php
              if($refundInV4) {
                echo '<span class="text text-success">Oui</span>';
              } else {
                echo '<span class="text text-danger">Non</span>';
              }
              ?>
            </p>

            <p>
              Remboursé à la V2-V3 :
              <?php
              if($refundInV3) {
                echo '<span class="text text-success">Oui</span>';
              } else {
                echo '<span class="text text-danger">Non</span>';
              }
              ?>
            </p>

            <hr>

            <?php if(!$refunded) { ?>
            <a href="#" class="btn btn-info refund-user">Rembourser l'utilisateur</a>
            <?php } ?>

            <script type="text/javascript">
              $('.refund-user').on('click', function(e) {
                e.preventDefault();

                var ask = prompt("Combien voulez-vous rembourser de points à l'utilisateur ?", "");
                if (ask != null) {
                  document.location = '<?= $this->Html->url(array('controller' => 'refund', 'action' => 'refund', 'plugin' => 'obsi', 'admin' => true, $user_pseudo)) ?>/'+ask;
                }
              });
            </script>

          </div>
        </div>
      </div>
      <div class="col-md-12">
        <div class="box">
          <div class="box-header with-border">
            <h3 class="box-title">Liste des achats de <?= $user_pseudo ?> à la V6</h3>
          </div>
          <div class="box-body">

            <?php
            if(!empty($playerItemsV6)) {
            ?>
              <table class="table table-bordered dataTable">
                <thead>
                  <tr>
                    <th>Nom de l'article</th>
                    <th>Prix de l'article</th>
                  </tr>
                </thead>
                <tbody>
                  <?php

                  foreach ($playerItemsV6 as $key => $value) {

                    echo '<tr>';

                      echo '<td>'.$value['name'].'</td>';
                      echo '<td>'.$value['price'].' PB</td>';

                    echo '</tr>';

                  }

                  ?>
                </tbody>
              </table>
            <?php
            } else {
              echo '<div class="alert alert-danger">Le joueur n\'a fais aucun achat à la V5.</div>';
            }
            ?>

          </div>
        </div>
      </div>
      <div class="col-md-12">
        <div class="box">
          <div class="box-header with-border">
            <h3 class="box-title">Liste des achats de <?= $user_pseudo ?> à la V5</h3>
          </div>
          <div class="box-body">

            <?php
            if(!empty($playerItemsV5)) {
            ?>
              <table class="table table-bordered dataTable">
                <thead>
                  <tr>
                    <th>Nom de l'article</th>
                    <th>Prix de l'article</th>
                  </tr>
                </thead>
                <tbody>
                  <?php

                  foreach ($playerItemsV5 as $key => $value) {

                    echo '<tr>';

                      echo '<td>'.$value['name'].'</td>';
                      echo '<td>'.$value['price'].' PB</td>';

                    echo '</tr>';

                  }

                  ?>
                </tbody>
              </table>
            <?php
            } else {
              echo '<div class="alert alert-danger">Le joueur n\'a fais aucun achat à la V5.</div>';
            }
            ?>

          </div>
        </div>
      </div>
      <div class="col-md-12">
        <div class="box">
          <div class="box-header with-border">
            <h3 class="box-title">Liste des achats de <?= $user_pseudo ?> à la V4</h3>
          </div>
          <div class="box-body">

            <?php
            if(!empty($playerItemsV4)) {
            ?>
              <table class="table table-bordered dataTable">
                <thead>
                  <tr>
                    <th>Nom de l'article</th>
                    <th>Prix de l'article</th>
                  </tr>
                </thead>
                <tbody>
                  <?php

                  foreach ($playerItemsV4 as $key => $value) {

                    echo '<tr>';

                      echo '<td>'.$value['name'].'</td>';
                      echo '<td>'.$value['price'].' PB</td>';

                    echo '</tr>';

                  }

                  ?>
                </tbody>
              </table>
            <?php
            } else {
              echo '<div class="alert alert-danger">Le joueur n\'a fais aucun achat à la V4.</div>';
            }
            ?>

          </div>
        </div>
      </div>
      <div class="col-md-12">
        <div class="box">
          <div class="box-header with-border">
            <h3 class="box-title">Liste des achats de <?= $user_pseudo ?> à la V3</h3>
          </div>
          <div class="box-body">


            <?php
            if(!empty($playerItemsV3)) {
            ?>
              <table class="table table-bordered dataTable">
                <thead>
                  <tr>
                    <th>Nom de l'article</th>
                    <th>Prix de l'article</th>
                  </tr>
                </thead>
                <tbody>
                  <?php

                  foreach ($playerItemsV3 as $key => $value) {

                    echo '<tr>';

                      echo '<td>'.$value['nom'].'</td>';
                      echo '<td>'.$value['prix'].' PB</td>';

                    echo '</tr>';

                  }

                  ?>
                </tbody>
              </table>
            <?php
            } else {
              echo '<div class="alert alert-danger">Le joueur n\'a fais aucun achat à la V3.</div>';
            }
            ?>

          </div>
        </div>
      </div>
      <div class="col-md-12">
        <div class="box">
          <div class="box-header with-border">
            <h3 class="box-title">Liste des achats de <?= $user_pseudo ?> à la V2</h3>
          </div>
          <div class="box-body">

            <?php
            if(!empty($playerItemsV2)) {
            ?>
              <table class="table table-bordered dataTable">
                <thead>
                  <tr>
                    <th>Nom de l'article</th>
                    <th>Prix de l'article</th>
                  </tr>
                </thead>
                <tbody>
                  <?php

                  foreach ($playerItemsV2 as $key => $value) {

                    echo '<tr>';

                      echo '<td>'.$value['nom'].'</td>';
                      echo '<td>'.$value['prix'].' PB</td>';

                    echo '</tr>';

                  }

                  ?>
                </tbody>
              </table>
            <?php
            } else {
              echo '<div class="alert alert-danger">Le joueur n\'a fais aucun achat à la V2.</div>';
            }
            ?>

          </div>
        </div>
      </div>
    </div>
  </section>
<?php } ?>
