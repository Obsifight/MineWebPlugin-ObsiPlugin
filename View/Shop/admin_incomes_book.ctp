<section class="content">
  <div class="row">
    <div class="col-md-12">
      <div class="box">
        <div class="box-header with-border">
          <h3 class="box-title">Livre des recettes</h3>
        </div>
        <div class="box-body">
          <form action="" method="post">
            <input type="hidden" name="data[_Token][key]" value="<?= $csrfToken ?>">

            <div class="form-group">
              <label>Choississez une intervalle de temps</label>
              <div class="input-group">
                <input type="text" name="daterange" class="form-control">
                <span class="input-group-btn">
                  <button class="btn btn-success" type="submit">Obtenir le CSV</button>
                </span>
              </div>
            </div>

          </form>

          <div class="row">

            <div class="col-md-3">
              <a class="btn btn-app" data-range="<?= date('Y-m-01').' - '.date('Y-m-31') ?>">
                <i class="fa fa-calendar"></i> Ce mois-ci
              </a>
            </div>

            <div class="col-md-3">
              <a class="btn btn-app" data-range="<?= date('Y-m-01', strtotime('-1 month')).' - '.date('Y-m-31', strtotime('-1 month')) ?>">
                <i class="fa fa-calendar"></i> Le mois dernier
              </a>
            </div>

            <div class="col-md-3">
              <a class="btn btn-app" data-range="<?= date('Y-m-01', strtotime('-1 month')).' - '.date('Y-m-31') ?>">
                <i class="fa fa-calendar"></i> Les deux derniers mois
              </a>
            </div>

            <div class="col-md-3">
              <a class="btn btn-app" data-range="<?= date('Y-m-01', strtotime('-2 month')).' - '.date('Y-m-31') ?>">
                <i class="fa fa-calendar"></i> Les trois derniers mois
              </a>
            </div>

          </div>
        </div>
      </div>
    </div>
  </div>
</section>
<?= $this->Html->css('Obsi.daterangepicker') ?>
<?= $this->Html->script('Obsi.moment.min') ?>
<?= $this->Html->script('Obsi.daterangepicker') ?>
<script type="text/javascript">
$(function() {
  $('input[name="daterange"]').daterangepicker({
    startDate: '<?= date('Y-m-01') ?>',
    endDate: '<?= date('Y-m-d') ?>',
    locale: {
      direction: 'ltr',
      format: 'YYYY-MM-DD',
      separator: ' - ',
      applyLabel: 'Appliquer',
      cancelLabel: 'Annuler',
      weekLabel: 'W',
      customRangeLabel: 'Intervalle personnalisée',
      daysOfWeek: moment.weekdaysMin(),
      monthNames: moment.monthsShort(),
      firstDay: moment.localeData().firstDayOfWeek()
    }
  })

  $('.btn.btn-app').on('click', function (e) {
    e.preventDefault()
    var range = $(this).attr('data-range')
    $('input[name="daterange"]').val(range)
    $('form').submit()
  })
})
</script>
<style media="screen">
  .btn.btn-app {
    font-size: 20px;
    padding: 20px;
    height: auto;
    width: 100%;
  }
</style>
