<?php
class GetYoutubeVideosShell extends AppShell {

  public $uses = array('User', 'Obsi.YoutubeChannel', 'Obsi.YoutubeVideo'); //Models
  private $developer_key = 'AIzaSyCZCZOTBbcY-W183Yk2sC6DAgvHP5doA08';
  private $versionOpenDate = '2017-05-20 15:00:00';

  public function main() {
    $this->out('Start get youtubes videos...');
    // init
    require_once ROOT.DS.'app'.DS.'Plugin'.DS.'Obsi'.DS.'Vendor'.DS.'google-api-php-client'.DS.'src'.DS.'Google'.DS.'autoload.php';
    $client = new Google_Client();
    $client->setDeveloperKey($this->developer_key);
    $youtube = new Google_Service_YouTube($client);
    // get all channels
    $channels = $this->YoutubeChannel->find('all');
    // each
    foreach ($channels as $channel) {
      $channel = $channel['YoutubeChannel'];
      $channel_id = $channel['youtube_channel_id'];
      $this->out('- '.$channel_id);
      // Retrieve the list of uploaded videos
      $findRelatedPlaylists = $youtube->channels->listChannels('contentDetails', array(
        'id' => $channel_id
      ));
      $items = $findRelatedPlaylists->getItems();
      if (!empty($items) && isset($items[0]) && method_exists($items[0], 'getContentDetails')) {
        $uploadsPlaylistId = $items[0]->getContentDetails()->getRelatedPlaylists()->uploads;
        $this->out('  Uploads playlist id finded: '.$uploadsPlaylistId);
        // get uploads list
        $uploads = $this->getUploadsFromUploadsPlaylist($uploadsPlaylistId, $channel_id, $youtube);
        // set into database
        if (!empty($uploads))
          $this->YoutubeVideo->saveAll($uploads);
      }
    }
  }

  private function getUploadsFromUploadsPlaylist($uploadsPlaylistId, $channel_id, $youtube) {
    $this->out('  Get uploads from '.$channel_id);
    $uploads = array();
    $continue = true;
    $pageToken = null;
    // if we don't have all uploads
    $i = 0;
    while ($continue) {
      // init
      $params = array(
        'playlistId' => $uploadsPlaylistId,
        'maxResults' => 50
      );
      if ($pageToken)
        $params['pageToken'] = $pageToken;
      // request
      $findUploadsList = $youtube->playlistItems->listPlaylistItems('contentDetails', $params);
      $pageToken = $findUploadsList->nextPageToken;
      $this->out('  Finded '.$findUploadsList->getPageInfo()->totalResults.' uploaded videos');
      // add uploads
      foreach ($findUploadsList->getItems() as $item) {
        $i++;
        $this->out('    Check video #'.$i.' - ID: '.$item->contentDetails->videoId);
        // check date
        if (strtotime($item->contentDetails->videoPublishedAt) < strtotime($this->versionOpenDate)) {// before open
          $continue = false; // stop, it's olds videos
          break;
        }
        // get more data
        $video = $youtube->videos->listVideos('statistics,status,snippet', array(
          'id' => $item->contentDetails->videoId
        ));
        if ($video->getItems()[0]->status->privacyStatus !== 'public')
          continue;
        $video = $video->getItems()[0];
        $publicData = $video->getSnippet();
        // find in db
        $findVideo = $this->YoutubeVideo->find('first', array('conditions' => array('video_id' => $item->contentDetails->videoId)));
        // formatting
        $data = array(
          'id' => ($findVideo && !empty($findVideo)) ? $findVideo['YoutubeVideo']['id'] : null,
          'channel_id' => $channel_id,
          'video_id' => $item->contentDetails->videoId,
          'title' => $publicData->localized->title,
          'description' => $publicData->localized->description,
          'views_count' => $video->statistics->viewCount,
          'likes_count' => $video->statistics->likeCount,
          'thumbnail_link' => $publicData->thumbnails->medium->url,
          'publication_date' => date('Y-m-d H:i:s', strtotime($item->contentDetails->videoPublishedAt)),
          'eligible' => false
        );
        // check
        if (preg_match('/obsifight/im', $data['title'])) // need contains obsifight
          if (preg_match('/obsifight/im', $data['description'])) // need contains obsifight
            if (preg_match('/obsifight\.(fr|net)/im', $data['description'])) // need contains link to obsifight.net or obsifight.fr
              if ($data['views_count'] >= 100)
                if (strtotime('+7 days', strtotime($data['publication_date'])) > time()) // upload last 7 days
                  $data['eligible'] = true;
        // add to result
        $uploads[] = $data;
      }
      // check results
      if ($pageToken === null)
        $continue = false; // all videos get
    }
    return $uploads;
  }
}
