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
$downloadManager->onVideoData(function($movie) {
    // TODO: Save data
    echo "<br />";
    print_r($movie);
});

$downloadManager->start();