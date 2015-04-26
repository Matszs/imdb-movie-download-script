# imdb-movie-download-script
For getting the top 250 from IMDB &amp; downloading trailers from YouTube


#gebruik

De applicatie is zo ingesteld dat alles vanuit run.php (in de root-folder) te gebruiken is. In dit bestand is al een test situatie ontwikkeld welke waarschijnlijk aansluit op de wensen van de meeste.

#installatie

- Neem de database structuur uit het run.php bestand
- Zet een MySQL database op en plaats de structuur in je database 
- Plaats je database gegevens in het run.php bestand

Als je nu niet het run.php aanpast en het script draait (php run.php of via je webbrowser er naartoe), worden:
- De top 250 opgehaald van IMDb
- Van deze top 250 worden de 'movies' op de positie 1 t/m 100 gepakt
- Bij deze 'movies' worden de trailers van YouTube gepakt
- Na het ophalen worden de 'movies' in de database opgeslagen
- Ingesteld staat dat de map 'movies' wordt gebruikt voor het opslaan van de trailers (mp4), de map 'thumbs' (jpg) wordt gebruikt voor de film posters.

Het script vereist cURL, Ffmpeg, json_decode en is getest met PHP 5.6.3
