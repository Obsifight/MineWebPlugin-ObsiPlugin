<section class="content">
  <div class="row">
    <div class="col-md-12">
      <div class="box">
        <div class="box-header with-border">
          <h3 class="box-title">Historique de changement de pseudo</h3>
        </div>
        <div class="box-body">

          <table class="table table-bordered dataTable">
            <thead>
              <tr>
                <th>Utilisateur</th>
                <th>Ancien pseudo</th>
                <th>Nouveau pseudo</th>
                <th>Date de l'achat/du changement</th>
              </tr>
            </thead>
            <tbody>
              <?php if(isset($updates)) { ?>
                <?php foreach ($updates as $key => $value) { ?>
                  <tr>
                    <td><?= (isset($usersByID[$value['PseudoUpdateHistory']['user_id']])) ? $usersByID[$value['PseudoUpdateHistory']['user_id']] : $value['PseudoUpdateHistory']['user_id'] ?></td>
                    <td><?= $value['PseudoUpdateHistory']['old_pseudo'] ?></td>
                    <td><?= $value['PseudoUpdateHistory']['new_pseudo'] ?></td>
                    <td><?= $Lang->date($value['PseudoUpdateHistory']['created']) ?></td>
                  </tr>
                <?php } ?>
              <?php } ?>
            </tbody>
          </table>

        </div>
      </div>
    </div>
  </div>
</section>
