<section class="content">
  <div class="row">
    <div class="col-md-12">
      <div class="box">
        <div class="box-header with-border">
          <h3 class="box-title">Modification des quotas de PaySafeCard</h3>
        </div>
        <div class="box-body">
          <form action="#" method="post" data-ajax="false">

            <?php

            echo '<div class="form-group">';
              echo '<label>Pseudo</label>';
              echo '<input name="users[0][pseudo]" class="form-control disabled" type="text" value="all">';
            echo '</div>';

            echo '<div class="form-group">';
              echo '<label>Quota</label>';
              echo '<input name="users[0][quota]" class="form-control" type="text" value="'.$quotas[0]['quota'].'">';
            echo '</div>';

            echo '<hr>';

            $i = 0;
            unset($quotas[0]);
            foreach ($quotas as $quota) {
              $i++;

              echo '<div id="'.$i.'">';

                echo '<div class="form-group">';
                  echo '<label>Pseudo</label>';
                  echo '<input name="users['.$i.'][pseudo]" class="form-control" type="text" value="'.$quota['pseudo'].'">';
                echo '</div>';

                echo '<div class="form-group">';
                  echo '<label>Quota</label>';
                  echo '<input name="users['.$i.'][quota]" class="form-control" type="text" value="'.$quota['quota'].'">';
                echo '</div>';

                echo '<button type="button" class="btn btn-danger delete" data-username="'.$quota['pseudo'].'" data-id="'.$i.'">Supprimer ce quota personnel</button>';

                echo '<hr>';

              echo '</div>';

            }
            ?>

            <div id="add">

            </div>

            <button type="button" class="btn btn-success add" data-id="<?= $i ?>">Ajouter un quota personnel</button>

            <input type="hidden" name="data[_Token][key]" value="<?= $csrfToken ?>">

            <div class="pull-right">
              <button class="btn btn-primary" type="submit"><?= $Lang->get('GLOBAL__SUBMIT') ?></button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</section>
<script type="text/javascript">
  function delete_btn() {
    $('.delete').unbind('click');
    $('.delete').on('click', function(e) {
      $.get('<?= $this->Html->url(array('action' => 'remove_quota')) ?>/'+$(this).attr('data-username'));
      $('#'+$(this).attr('data-id')).slideUp(150, function() {
        $(this).remove();
      })
    });
  }
  delete_btn();
  $('.add').on('click', function(e) {

    var i = $(this).attr('data-id');
    i = parseInt(i) + 1;

    var quota = '';
    quota += '<div id="'+i+'">';

      quota += '<div class="form-group">';
        quota += '<label>Pseudo</label>';
        quota += '<input name="users['+i+'][pseudo]" class="form-control" type="text">';
      quota += '</div>';

      quota += '<div class="form-group">';
        quota += '<label>Quota</label>';
        quota += '<input name="users['+i+'][quota]" class="form-control" type="text">';
      quota += '</div>';

      quota += '<button type="button" class="btn btn-danger delete" data-id="'+i+'">Supprimer ce quota personnel</button>';

      quota += '<hr>';

    quota += '</div>';

    $(this).attr('data-id', i);

    $('#add').append(quota);

    delete_btn();

  });
</script>
