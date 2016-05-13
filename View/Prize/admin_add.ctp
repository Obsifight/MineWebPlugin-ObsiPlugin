<section class="content">
  <div class="row">
    <div class="col-md-12">
      <div class="box">
        <div class="box-header with-border">
          <h3 class="box-title">Ajout d'un lot</h3>
        </div>
        <div class="box-body">
          <form action="<?= $this->Html->url(array('action' => 'add_ajax', 'admin' => true)) ?>" method="post" data-ajax="true" data-redirect-url="<?= $this->Html->url(array('action' => 'index', 'admin' => true)) ?>">

            <div class="ajax-msg"></div>

            <div class="form-group">
              <label>Pseudo du joueur</label>
              <input name="pseudo" class="form-control" type="text">
            </div>

            <div class="form-group">
              <label>Nom du lot</label>
              <input name="name" class="form-control" type="text">
            </div>

            <div class="form-group">
              <label><?= $Lang->get('GLOBAL__SERVER_COMMANDS') ?></label>
              <div class="input-group">
                <input name="commands[0]" class="form-control" type="text">
                <div class="input-group-btn">
                  <button data-i="1" type="button" id="addCommand" class="btn btn-success"><?= $Lang->get('SHOP__ITEM_ADD_COMMAND') ?></button>
                </div>
              </div>
              <div class="addCommand"></div>
              <small><b>{PLAYER}</b> = Pseudo <br><b><?= $Lang->get('GLOBAL__EXAMPLE') ?>:</b> <i>give {PLAYER} 1 1</i></small>
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
<script type="text/javascript">
$('#addCommand').on('click', function(e) {

  e.preventDefault();

  var i = parseInt($(this).attr('data-i'));

  var input = '';
  input += '<div style="margin-top:5px;" class="input-group" id="'+i+'">';
    input += '<input name="commands['+i+']" class="form-control" type="text">';
    input += '<span class="input-group-btn">';
      input += '<button class="btn btn-danger delete-cmd" data-id="'+i+'" type="button"><span class="fa fa-close"></span></button>';
    input += '</span>';
  input + '</div>';

  i++;

  $(this).attr('data-i', i);

  $('.addCommand').append(input);

  $('.delete-cmd').unbind('click');
  $('.delete-cmd').on('click', function(e) {

    var id = $(this).attr('data-id');

    $('#'+id).slideUp(150, function() {
      $('#'+id).remove();
    });
  });

});

$('.delete-cmd').on('click', function(e) {

  var id = $(this).attr('data-id');

  $('#'+id).slideUp(150, function() {
    $('#'+id).remove();
  });
});
</script>
