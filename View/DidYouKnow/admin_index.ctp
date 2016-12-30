<section class="content">
  <div class="row">
    <div class="col-md-12">
      <div class="box">
        <div class="box-header with-border">
          <h3 class="box-title">Ajouter un "le saviez-vous ?"</h3>
        </div>
        <div class="box-body">

          <form action="<?= $this->Html->url(array('action' => 'add')) ?>" method="post" data-ajax="true" data-callback-function="afterAdd">
            <div class="form-group">
              <label>Texte</label>
              <div class="input-group">
                <input type="text" class="form-control" name="text" placeholder="Les joueurs ne sont pas très intelligent">
                <div class="input-group-btn">
                  <button type="submit" class="btn btn-success">Enregistrer</button>
                </div>
              </div>
            </div>
          </form>

        </div>
      </div>
      <div class="box">
        <div class="box-header with-border">
          <h3 class="box-title">Liste des "le saviez-vous ?"</h3>
        </div>
        <div class="box-body">

          <table class="table">
            <thead>
              <tr>
                <th>#</th>
                <th>Texte</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php
              foreach ($list as $value) {
                echo '<tr data-id="'.$value['DidYouKnow']['id'].'">';
                  echo '<td>'.$value['DidYouKnow']['id'].'</td>';
                  echo '<td>'.$value['DidYouKnow']['text'].'</td>';
                  echo '<td><button class="btn btn-danger delete" data-id="'.$value['DidYouKnow']['id'].'">Supprimer</button></td>';
                echo '</tr>';
              }
              ?>
            </tbody>
          </table>

        </div>
      </div>
    </div>
  </div>
</section>
<script type="text/javascript">
  function afterAdd(req, res) {
    $('table tbody').append('<tr data-id="' + res.data.id + '"><td>' + res.data.id + '</td><td>' + res.data.text + '</td><td><button class="btn btn-danger delete" data-id="' + res.data.id + '">Supprimer</button></td></tr>');
    deleteEvent()
  }
  function deleteEvent() {
    $('.delete').unbind('click')
    $('.delete').on('click', function (e) {
      var btn = $(this)
      var id = btn.attr('data-id')
      $('table tbody tr[data-id="' + id + '"]').remove()
      $.get('<?= $this->Html->url(array('action' => 'delete', 'id' => '{ID}')) ?>'.replace('{ID}', id))
    })
  }
  deleteEvent()
</script>
