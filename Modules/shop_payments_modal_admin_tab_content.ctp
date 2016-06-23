<div class="tab-pane" id="tab_psc">

  <h3><?= $Lang->get('SHOP__PAYSAFECARD_ADMIN_TITLE') ?></h3>

  <br><br>

  <div id="ajax-psc"></div>

  <table class="table table-bordered dataTable">
    <thead>
      <tr>
        <th><?= $Lang->get('USER__USERNAME') ?></th>
        <th><?= $Lang->get('SHOP__GLOBAL_AMOUNT') ?></th>
        <th><?= $Lang->get('SHOP__VOUCHER_CODE') ?></th>
        <th><?= $Lang->get('GLOBAL__CREATED') ?></th>
        <th class="right"><?= $Lang->get('GLOBAL__ACTIONS') ?></th>
      </tr>
    </thead>
    <style media="screen">
      table tr td:last-child > div.btn-group {
        width: 220px;
      }
    </style>
    <tbody>
      <?php if(isset($paysafecards)) { ?>
        <?php foreach ($paysafecards as $key => $value) { ?>
          <?php if($value['Paysafecard']['user_id'] != "0") { ?>
            <tr>
              <td><?= $usersByID[$value['Paysafecard']['user_id']] ?></td>
              <td><?= $value['Paysafecard']['amount'] ?></td>
              <td class="psc-code" data-psc-id="<?= $value['Paysafecard']['id'] ?>">
                <?php

                if(isset($pscTaked[$value['Paysafecard']['id']])) {
                  if($pscTaked[$value['Paysafecard']['id']]) {
                    echo $value['Paysafecard']['code'];
                  } else {
                    echo '<small><i>Prise en charge</i></small>';
                  }
                } else {
                  echo '<small><i>Vous devez prendre la PSC</i></small>';
                }

                ?>
              </td>
              <td><?= $Lang->date($value['Paysafecard']['created']) ?></td>
              <td>
                <?php
                if(isset($pscTaked[$value['Paysafecard']['id']])) {
                  if($pscTaked[$value['Paysafecard']['id']]) {
                    echo '<div class="btn-group">';
                      echo '<a href="#" class="btn btn-success" onClick="valid('.$value['Paysafecard']['id'].')">Valider</a>';
                      echo '<a href="'.$this->Html->url(array('controller' => 'paysafecard', 'action' => 'invalid', 'plugin' => 'obsi', $value['Paysafecard']['id'])).'" class="btn btn-danger" data-psc-id="'.$value['Paysafecard']['id'].'">Invalider</a>';
                      echo '<a href="'.$this->Html->url(array('controller' => 'paysafecard', 'action' => 'banUser', 'plugin' => 'obsi', 'admin' => true, $value['Paysafecard']['user_id'])).'" class="btn btn-warning">Interdire</a>';
                    echo '</div>';
                  } else {
                    echo '<a href="#" class="btn btn-info disabled" disabled>Déjà prise en charge</a>';
                  }
                } else {
                  echo '<div class="btn-group">';
                    echo '<a href="#" class="btn btn-info take-psc" data-psc-id="'.$value['Paysafecard']['id'].'" data-psc-user-id="'.$value['Paysafecard']['user_id'].'">Je la prends</a>';
                    echo '<a href="'.$this->Html->url(array('controller' => 'paysafecard', 'action' => 'banUser', 'plugin' => 'obsi', 'admin' => true, $value['Paysafecard']['user_id'])).'" class="btn btn-warning">Interdire</a>';
                  echo '</div>';
                }
                ?>
              </td>
            </tr>
          <?php } ?>
        <?php } ?>
      <?php } ?>
    </tbody>
  </table>


<script type="text/javascript">

  $('.take-psc').on('click', function(e) {

    e.preventDefault();

    var el = $(this);
    var id = el.attr('data-psc-id');
    var user_id = el.attr('data-psc-user-id');
    var td = el.parent();

    el.addClass('disabled').attr('disabled', true);

    var inputs = {};
    inputs['id'] = id;
    inputs["data[_Token][key]"] = '<?= $csrfToken ?>';

    $.post('<?= $this->Html->url(array('controller' => 'paysafecard', 'action' => 'take', 'plugin' => 'obsi')) ?>', inputs, function(data) {

      if(data.statut) {
        $('td.psc-code[data-psc-id="'+id+'"]').html(data.code);
        $('#ajax-psc').fadeOut(150, function() {
          $(this).hide();
        })
        td.html('<a href="#" class="btn btn-success" onClick="valid('+id+')">Valider</a>');
        td.append('<a href="<?= $this->Html->url(array('controller' => 'paysafecard', 'action' => 'invalid', 'plugin' => 'obsi')) ?>/'+id+'" class="btn btn-danger" data-psc-id="'+id+'">Invalider</a>');
        td.append('<a href="<?= $this->Html->url(array('controller' => 'paysafecard', 'action' => 'banUser', 'plugin' => 'obsi', 'admin' => true)) ?>/'+user_id+'" class="btn btn-warning">Interdire</a>');
      } else {
        el.removeClass('disabled').attr('disabled', false);
        $('#ajax-psc').fadeIn().html('<div class="alert alert-danger"><b>Erreur : </b> '+data.msg+'</div>');
      }

    }).error(function(data) {
      el.removeClass('disabled').attr('disabled', false);
      alert('Une erreur est survenue !');
      console.log(data);
    });


  });

</script>

  <script>
  function valid(id) {
      var money = prompt("<?= $Lang->get('SHOP__PAYSAFECARD_VALID_CONFIRM') ?>");

      if (money != null) {
          document.location = '<?= $this->Html->url(array('controller' => 'paysafecard', 'action' => 'valid', 'plugin' => 'obsi')) ?>/'+id+'/'+money;
      } else {
        return false;
      }
  }
  </script>


  <hr>

  <h3><?= $Lang->get('SHOP__PAYSAFECARD_HISTORIES') ?></h3>

  <table class="table table-bordered dataTable">
    <thead>
      <tr>
        <th><?= $Lang->get('SHOP__PAYSAFECARD_CODE') ?></th>
        <th><?= $Lang->get('USER__USERNAME') ?></th>
        <th><?= $Lang->get('SHOP__PAYSAFECARD_VALID_USER') ?></th>
        <th><?= $Lang->get('SHOP__GLOBAL_AMOUNT') ?></th>
        <th><?= ucfirst($Configuration->getMoneyName()) ?></th>
        <th><?= $Lang->get('GLOBAL__CREATED') ?></th>
      </tr>
    </thead>
    <tbody>
      <?php if(isset($histories['paysafecard'])) { ?>
        <?php foreach ($histories['paysafecard'] as $key => $value) { ?>
          <tr>
            <td><?= $value['PaysafecardHistory']['code'] ?></td>
            <td><?= (isset($usersByID[$value['PaysafecardHistory']['user_id']])) ? $usersByID[$value['PaysafecardHistory']['user_id']] : $value['PaysafecardHistory']['user_id'] ?></td>
            <td><?= (isset($usersByID[$value['PaysafecardHistory']['author_id']])) ? $usersByID[$value['PaysafecardHistory']['author_id']] : $value['PaysafecardHistory']['author_id'] ?></td>
            <td><?= $value['PaysafecardHistory']['amount'] ?></td>
            <td><?= $value['PaysafecardHistory']['credits_gived'] ?></td>
            <td><?= $Lang->date($value['PaysafecardHistory']['created']) ?></td>
          </tr>
        <?php } ?>
      <?php } ?>
    </tbody>
  </table>

  <hr>

  <h3>Liste des bannis de PaySafeCard</h3>

  <table class="table table-bordered dataTable">
    <thead>
      <tr>
        <th><?= $Lang->get('USER__USERNAME') ?></th>
        <th>Banni par</th>
        <th><?= $Lang->get('GLOBAL__CREATED') ?></th>
        <th class="right"><?= $Lang->get('GLOBAL__ACTIONS') ?></th>
      </tr>
    </thead>
    <style media="screen">
      table tr td:last-child > div.btn-group {
        width: 220px;
      }
    </style>
    <tbody>
      <?php if(isset($findPscBans)) { ?>
        <?php foreach ($findPscBans as $ban) { ?>
          <tr>
            <td><?= $ban['PscBan']['user_pseudo'] ?></td>
            <td><?= $ban['PscBan']['author_pseudo'] ?></td>
            <td><?= $Lang->date($ban['PscBan']['created']) ?></td>
            <td>
              <a class="btn btn-success" href="<?= $this->Html->url(array('admin' => true, 'plugin' => 'obsi', 'controller' => 'paysafecard', 'action' => 'unbanUser', $ban['PscBan']['id'])) ?>">Débannir</a>
            </td>
          </tr>
        <?php } ?>
      <?php } ?>
    </tbody>
  </table>

</div>
