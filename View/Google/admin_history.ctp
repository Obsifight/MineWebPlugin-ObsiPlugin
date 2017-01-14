<section class="content">
  <div class="row">
    <div class="col-md-12">
      <div class="box">
        <div class="box-header with-border">
          <h3 class="box-title">Historique des rémunérations par vidéos youtube</h3>
        </div>
        <div class="box-body">

          <div class="table-responsive">
            <table class="table dataTable">

              <thead>
                <tr>
                  <th>ID de la vidéo</th>
                  <th>ID de la chaîne</th>
                  <th>Titre</th>
                  <th>Date de publication</th>
                  <th>Compte de vues</th>
                  <th>Compte de likes</th>
                  <th>Rémunération</th>
                </tr>
              </thead>
              <tbody>
                <?php
                foreach ($histories as $history) {

                  echo '<tr>';
                    echo '<td><a target="_blank" href="http://youtube.com/watch?v='.$history['YoutubeVideosRemunerationHistory']['youtube_video_id'].'">'.$history['YoutubeVideosRemunerationHistory']['youtube_video_id'].'</a></td>';
                    echo '<td><a target="_blank" href="http://youtube.com/channel/'.$history['YoutubeVideosRemunerationHistory']['channel_id'].'">'.$history['YoutubeVideosRemunerationHistory']['channel_id'].'</a></td>';
                    echo '<td>'.$history['YoutubeVideosRemunerationHistory']['title'].'</td>';
                    echo '<td><i class="fa fa-calendar"></i>&nbsp;&nbsp;'.$history['YoutubeVideosRemunerationHistory']['publication_date'].'</td>';
                    echo '<td><i class="fa fa-eye"></i>&nbsp;&nbsp;'.$history['YoutubeVideosRemunerationHistory']['views_count'].'</td>';
                    echo '<td><i class="fa fa-thumbs-up"></i>&nbsp;&nbsp;'.$history['YoutubeVideosRemunerationHistory']['likes_count'].'</td>';
                    echo '<td>'.$history['YoutubeVideosRemunerationHistory']['remuneration'].' '.$Configuration->getMoneyName().'</td>';
                  echo '</tr>';

                }
                ?>
              </tbody>

            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
