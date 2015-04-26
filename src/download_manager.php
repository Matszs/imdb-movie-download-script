<?

class DownloadManager {
    private $_configuration;
    private static $_events = array();

    public function __construct($details = array()) {
        $defaultDetails = array(
            'movie_start' => 78,
            'movie_end' => 78,
            'movie_location' => 'videos',
            'thumbnail_location' => 'thumbs',
            'save_thumbnail' => true,
            'save_videos' => true
        );

        $this->_configuration = array_merge($defaultDetails, $details);
    }

    public function start() {
        $movies = $this->findMovies();

        if($this->_configuration['save_videos'])
            $this->saveVideosByMovies($movies);

        foreach($movies as $movie)
            $this->triggerVideoData($movie);
    }

    /**
     * @return array
     */
    private function findMovies() {
        $movies = array();

        $page = $this->read('http://www.imdb.com/chart/top');
        $pageData = $this->splitData('<table class="chart"  data-caller-name="other-chart">', '</table>', $page);

        if($pageData) {
            $moviesData = @explode('<tr', $pageData);

            if(isset($moviesData[0]))
                unset($moviesData[0]);
            if(isset($moviesData[1]))
                unset($moviesData[1]);

            foreach($moviesData as $movieData) {
                $movieData = trim(preg_replace('/\s\s+/', ' ', $movieData));
                @preg_match('/<a.*>(.*)<\/a>/s', $movieData, $nameMatches);

                $movieName = (isset($nameMatches[1]) ? $nameMatches[1] : null);

                if($movieName) {

                    $id = $this->splitData('data-tconst="', '"', $movieData);
                    $position = $this->splitData('?ref_=chttp_tt_', '"', $movieData);

                    if($position < $this->_configuration['movie_start'])
                        continue;
                    if($position > $this->_configuration['movie_end'])
                        break;

                    $image = $this->splitData('src="', '"', $movieData);
                    $largeImage = null;
                    if($imageId = $this->splitData('/M/', '.', $image))
                        $largeImage = 'http://ia.media-imdb.com/images/M/' . $imageId . '._V1_SX214_.jpg';

                    $rating = $this->splitData('data-value="', '"', $movieData);
                    $year = $this->splitData('class="secondaryInfo">(', ')', $movieData);
                    $personData = $this->splitData('title="', '"', $movieData);

                    $director = null;
                    $importantActor = null;

                    $persons = explode(',', $personData);
                    foreach($persons as $person) {
                        if(strpos($person, '(dir.)') !== false) {
                            $personFormat = rtrim(ltrim(str_replace('(dir.)', '', $person)));
                            $director = $personFormat;
                        } else if(!$importantActor)
                            $importantActor = rtrim(ltrim($person));
                    }

                    if($largeImage && $this->_configuration['save_thumbnail'] && file_exists($this->_configuration['thumbnail_location'])) {
                        $newLargeImage = $this->_configuration['thumbnail_location'] . DS . preg_replace('~[^\\pL\d_]+~u', '', strtolower(str_replace(' ', '_', $movieName))) . '.jpg';
                        copy($largeImage, $newLargeImage);
                        $largeImage = $newLargeImage;
                    }

                    $movies[] = array(
                        'local_id' => $id,
                        'name' => $movieName,
                        'position' => $position,
                        'movie_poster' => $largeImage,
                        'rating' => $rating,
                        'year' => $year,
                        'director' => $director,
                        'actor' => $importantActor
                    );
                } else {
                    print_r("FOUT: 2");
                }
            }
        } else {
            print_r("FOUT: 1");
        }
        return $movies;
    }


    /**
     *
     *  Loops given array of movies and uses them to search on youtube for the trailer and uses the yt_downloader class for downloading them.
     *
     * @param $movies
     * @throws Exception
     */
    private function saveVideosByMovies(&$movies) {

        yt_downloader::set_downloads_dir($this->_configuration['movie_location']);
        yt_downloader::set_ffmpegLogs_dir('logs');
        yt_downloader::set_download_thumbnail(false);

        foreach($movies as $movieIndex => $movie) {

            $youtubeVideoSearchPage = $this->read('https://www.youtube.com/results?search_query=' . urlencode($movie['name']) . '+' . $movie['year'] . '+trailler');
            $youtubeVideoId = $this->splitData('href="/watch?v=', '"', $youtubeVideoSearchPage);

            $movies[$movieIndex]['youtube_id'] = $youtubeVideoId;

            try {
                $video = new yt_downloader("http://www.youtube.com/watch?v=" . $youtubeVideoId, false);
                $video->set_download_thumbnail(false);
                $video->do_download("video");
                $videoFile = $video->get_video();

                $movies[$movieIndex]['video_url'] = $videoFile;
            }
            catch (Exception $e) {
                print_r('FOUT 4: ' . $e->getMessage());
            }
        }

    }


    private function read($url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.3; WOW64; rv:36.0) Gecko/20100101 Firefox/36.0');
        curl_setopt($ch, CURLOPT_HEADER, array("Accept-Language: en-US,en;q=0.5"));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $return = curl_exec($ch);
        curl_close($ch);
        return $return;
    }

    private function splitData($firstSplit, $secondSplit, $data) {
        if($data = @explode($firstSplit, $data))
            if(isset($data[1]))
                if($data = @explode($secondSplit, $data[1]))
                    if(isset($data[0]))
                        return $data[0];
        return null;
    }

    public function onVideoData($function) {
        self::$_events[] = $function;
    }

    protected function triggerVideoData($movie) {
        foreach(self::$_events as $videoDataEvent) {
            call_user_func($videoDataEvent, $movie);
        }
    }

}