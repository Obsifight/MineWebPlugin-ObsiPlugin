<div id="transfert-points-only" style="display:none;">
  <?php if ($canDisableSendPoints && $sendPointsState): ?>
    <a class="btn btn-danger pull-right" href="<?= $this->Html->url('/user/send-points/disable') ?>">Désactiver le transfert de points</a>
  <?php elseif ($canDisableSendPoints): ?>
    <a class="btn btn-success pull-right" href="<?= $this->Html->url('/user/send-points/enable') ?>">Activer le transfert de points</a>
  <?php endif; ?>
</div>
<script type="text/javascript">
  $('.nav.nav-tabs li a').on('click', function () {
    var a = $(this)
    if (a.attr('href') == '#tab_points_transfer')
      $('#transfert-points-only').show()
    else
      $('#transfert-points-only').hide()
  })
</script>
