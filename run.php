<?

DEFINE('DS', '\\'); // Windows: \\    Other: /

include('src/youtube-dl/youtube-dl.class.php');
include('src/download_manager.php');
include('src/database.php');

/* Initializing Database connection */
Database::init(array(
    'host' => 'akoo.nl',
    'user' => 'seflab',
    'password' => '',
    'database' => 'seflab'
));

/* Initializing download manager */
$downloadManager = new DownloadManager();

/* Event handler for receiving back the details from the download manager */
/*
 * This is an example of the usage. You can build it as you want it.
 *

    DATABASE STRUCTURE:

    CREATE TABLE IF NOT EXISTS `trailer` (
      `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
      `title` varchar(255) DEFAULT NULL,
      `file` varchar(255) DEFAULT NULL,
      `position` int(11) DEFAULT NULL,
      `movie_poster` varchar(255) DEFAULT NULL,
      `rating` double(8,4) DEFAULT NULL,
      `year` varchar(5) DEFAULT NULL,
      `director` varchar(255) DEFAULT NULL,
      `actor` varchar(255) DEFAULT NULL,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

 */
$downloadManager->onVideoData(function($movie) {

    Database::setParam('position', $movie['position']);
    $query = Database::query("SELECT COUNT(*) as amount FROM trailer WHERE trailer.position = {position}");
    $numRows = Database::getArray($query);

    if($numRows[0]['amount'] == 0) { // Only save when the movie is not in the database already.

        Database::setParam(array(
            'title' => $movie['name'],
            'file' => (isset($movie['video_url']) ? $movie['video_url'] : ''),
            'position' => $movie['position'],
            'movie_poster' => $movie['movie_poster'],
            'rating' => $movie['rating'],
            'year' => $movie['year'],
            'director' => $movie['director'],
            'actor' => $movie['actor']
        ));

        Database::query("INSERT INTO trailer (title, file, position, movie_poster, rating, year, director, actor) VALUES (
            '{title}',
            '{file}',
            '{position}',
            '{movie_poster}',
            '{rating}',
            '{year}',
            '{director}',
            '{actor}'
        )");

    }
});

$downloadManager->start();