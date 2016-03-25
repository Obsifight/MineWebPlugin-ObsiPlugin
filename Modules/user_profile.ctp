<?php if(isset($launcherConnectionLogs) && !empty($launcherConnectionLogs)) { ?>

  <div class="heading-title heading-line-double text-center margin-top-30">
		<h4>Vos connexions au launcher</h4>
	</div>

  <table class="table table-striped table-bordered table-hover" id="connectionLogs">
  	<thead>
  		<tr>
  			<th>IP</th>
  			<th>Date de connexion</th>
  		</tr>
  	</thead>
  	<tbody>
      <?php

      foreach ($launcherConnectionLogs as $key => $value) {
        echo '<tr>';
          echo '<td>'.$value['loginlogs']['ip'].'</td>';
          echo '<td>'.$Lang->date($value['loginlogs']['date']).'</td>';
        echo '</tr>';
      }

      ?>
    </tbody>
  </table>
  <?= $this->Html->css('/theme/Obsifight/css/layout-datatables.css') ?>
  <?= $this->Html->script('/obsi/js/jquery.dataTables.min.js') ?>
  <?= $this->Html->script('/obsi/js/dataTables.tableTools.min.js') ?>
  <?= $this->Html->script('/obsi/js/dataTables.bootstrap.js') ?>
  <script type="text/javascript">
  $('#connectionLogs').DataTable( {
      "ordering": false,
      "lengthChange": false,
      "info": false,
      "language": {
        "emptyTable":     "Aucune données de disponible",
        "info":           "Affichage de _START_ à _END_ sur _TOTAL_ lignes totales",
        "infoEmpty":      "Affichage de 0 à 0 sur 0 lignes totales",
        "infoFiltered":   "(filtré depuis _MAX_ lignes total)",
        "infoPostFix":    "",
        "thousands":      ",",
        "lengthMenu":     "Affichage de _MENU_ lignes",
        "loadingRecords": "Chargement...",
        "processing":     "Chargement...",
        "search":         "Rechercher :",
        "zeroRecords":    "Aucune connexion trouvée",
        "paginate": {
            "first":      "Premier",
            "last":       "Dernier",
            "next":       "Suivant",
            "previous":   "Précédent"
        },
        "aria": {
            "sortAscending":  ": activer le classement croissant",
            "sortDescending": ": activer le classement décroissant"
        }
    }
  } );
  </script>
<?php } ?>
