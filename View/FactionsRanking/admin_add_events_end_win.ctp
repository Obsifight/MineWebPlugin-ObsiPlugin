<section class="content">
  <div class="row">
    <div class="col-md-12">
      <div class="box">
        <div class="box-header with-border">
          <h3 class="box-title">Ajout une victoire lors d'un event end</h3>
        </div>
        <div class="box-body">
          <form action="<?= $this->Html->url(array('action' => 'add_events_end_win_ajax', 'admin' => true)) ?>" method="post" data-ajax="true" data-redirect-url="<?= $this->Html->url(array('controller' => 'obsiadmin', 'action' => 'index', 'admin' => true)) ?>">

            <div class="ajax-msg"></div>

            <div class="form-group">
              <label>Nom de la faction</label>
              <input name="name" class="form-control" type="text">
            </div>

            <div class="pull-right">
              <a href="<?= $this->Html->url(array('controller' => 'shop', 'action' => 'index', 'admin' => true)) ?>" class="btn btn-default"><?= $Lang->get('GLOBAL__CANCEL') ?></a>
              <button class="btn btn-primary" type="submit"><?= $Lang->get('GLOBAL__SUBMIT') ?></button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</section>
