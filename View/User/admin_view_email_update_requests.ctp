<style media="screen">
table tr td:last-child {
  white-space: nowrap;
  width: 1px;
}
table tr td:last-child > div.btn-group {
  width: 170px;
}
</style>
<section class="content">
  <div class="row">
    <div class="col-md-12">
      <div class="box">
        <div class="box-header with-border">
          <h3 class="box-title">Gérer les demandes de modifications d'email</h3>
        </div>
        <div class="box-body">

          <table class="table table-bordered dataTable">
            <thead>
              <tr>
                <th>Utilisateur</th>
                <th>Nouvel email</th>
                <th>Raison</th>
                <th>IP utilisée</th>
                <th><abbr title="Quand une IP est utilisé plusieurs fois (+25% des 20 dernières connexions)">IP jugée valide</abbr></th>
                <th><?= $Lang->get('GLOBAL__CREATED') ?></th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if(isset($requests)) { ?>
                <?php foreach ($requests as $key => $value) { ?>
                  <tr>
                    <td><?= (isset($usersByID[$value['EmailUpdateRequest']['user_id']])) ? $usersByID[$value['EmailUpdateRequest']['user_id']] : $value['EmailUpdateRequest']['user_id'] ?></td>
                    <td><?= $value['EmailUpdateRequest']['new_email'] ?></td>
                    <td><?= $value['EmailUpdateRequest']['reason'] ?></td>
                    <td><?= $value['EmailUpdateRequest']['ip'] ?></td>
                    <td><?= $value['EmailUpdateRequest']['ip_valid'] ?></td>
                    <td><?= $Lang->date($value['EmailUpdateRequest']['created']) ?></td>
                    <td>
                      <div class="btn-group" role="group">
                        <a href="<?= $this->Html->url(array('plugin' => 'obsi', 'action' => 'invalidEmailUpdateRequest', $value['EmailUpdateRequest']['id'])) ?>" class="btn btn-danger">Refuser</a>
                        <a href="<?= $this->Html->url(array('plugin' => 'obsi', 'action' => 'validEmailUpdateRequest', $value['EmailUpdateRequest']['id'])) ?>" class="btn btn-success">Accepter</a>
                      </div>
                    </td>
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
