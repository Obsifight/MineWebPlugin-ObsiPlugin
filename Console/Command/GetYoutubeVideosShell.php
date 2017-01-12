<?php
class GetYoutubeVideosShell extends AppShell {

  public $uses = array('User', 'Obsi.YoutubeChannel', 'Obsi.YoutubeVideos'); //Models
  private $developer_key = 'AIzaSyCZCZOTBbcY-W183Yk2sC6DAgvHP5doA08';
  private $versionOpenDate = '2017-01-07 17:00:00';

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
      $uploadsPlaylistId = $findRelatedPlaylists->getItems()[0]->getContentDetails()->getRelatedPlaylists()->uploads;
      $this->out('  Uploads playlist id finded: '.$uploadsPlaylistId);
      // get uploads list
      $uploads = $this->getUploadsFromUploadsPlaylist($uploadsPlaylistId, $channel_id, $youtube);
      // set into database
      if (!empty($uploads))
        $this->YoutubeVideos->saveAll($uploads);
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
        // formatting
        $data = array(
          'channel_id' => $channel_id,
          'video_id' => $item->contentDetails->videoId,
          'title' => $publicData->localized->title,
          'description' => $publicData->localized->description,
          'views_count' => $video->statistics->viewCount,
          'likes_count' => $video->statistics->likeCount,
          'thumbnail_link' => $publicData->thumbnails->medium->url,
          'publication_date' => date('Y-m-d H:i:s', strtotime($item->contentDetails->videoPublishedAt))
        );
        // check title
        if (!preg_match('/obsifight/im', trim($data['title']))) // need contains obsifight
          continue;
        // check description
        if (!preg_match('/obsifight/im', $data['description'])) // need contains obsifight
          continue;
        if (!preg_match('/obsifight\.(fr|net)/im', $data['description'])) // need contains link to obsifight.net or obsifight.fr
          continue;
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
