<section class="content">
  <div class="row">
    <div class="col-md-12">
      <div class="box">
        <div class="box-header with-border">
          <h3 class="box-title">Liste des lots en attente</h3>
        </div>
        <div class="box-body">

          <a href="<?= $this->Html->url(array('action' => 'add')) ?>" class="btn btn-success"><i class="fa fa-plus"></i> Ajouter un lot</a>

          <div class="table-responsive">
            <table class="table dataTable">

              <thead>
                <tr>
                  <th>#</th>
                  <th>Joueur</th>
                  <th>Staff</th>
                  <th>Nom</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php
                foreach ($prizes as $prize) {

                  echo '<tr>';
                    echo '<td>'.$prize['Prize']['id'].'</td>';
                    echo '<td>'.$usersByID[$prize['Prize']['user_id']].'</td>';
                    echo '<td>'.$usersByID[$prize['Prize']['staff_id']].'</td>';
                    echo '<td>'.$prize['Prize']['name'].'</td>';
                    echo '<td>';
                      echo '<a href="'.$this->Html->url(array('action' => 'remove', $prize['Prize']['id'])).'" class="btn btn-danger">Supprimer</a>';
                    echo '</td>';
                  echo '</tr>';

                }
                ?>
              </tbody>

            </table>
          </div>

        </div>
      </div>
    </div>
  </div>
</section>
