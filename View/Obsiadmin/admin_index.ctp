<section class="content">
  <div class="row">
    <div class="col-md-12">
      <div class="box">
        <div class="box-header with-border">
          <h3 class="box-title">Différentes fonctionnalités d'ObsiFight</h3>
        </div>
        <div class="box-body">

          <a href="<?= $this->Html->url(array('controller' => 'user', 'action' => 'viewEmailUpdateRequests', 'admin' => true)) ?>" class="btn btn-block btn-lg btn-success">Accèder aux demandes de changements d'email</a>
          <a href="<?= $this->Html->url(array('controller' => 'prize', 'action' => 'index', 'admin' => true)) ?>" class="btn btn-block btn-lg btn-success">Accèder au système de lots</a>
          <a href="<?= $this->Html->url(array('controller' => 'user', 'action' => 'viewPseudoUpdates', 'admin' => true)) ?>" class="btn btn-block btn-lg btn-success">Accèder à l'historique de changements de pseudo</a>

        </div>
      </div>
    </div>
  </div>
</section>
