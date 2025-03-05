<?php
// URL de la page du site (page d'accueil ou autre)
$siteUrl = "https://www.lebigdata.fr";

// Fonction pour récupérer le contenu d'une page via cURL
function getCurlContent($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode == 200) {
        return $result;
    }
    return false;
}

// Essayer de trouver l'URL du flux RSS dans les balises <link> du HTML
$html = getCurlContent($siteUrl);
$rssFeedUrl = null;

if ($html) {
    $dom = new DOMDocument();
    @$dom->loadHTML($html);

    // Recherche de toutes les balises <link>
    $links = $dom->getElementsByTagName('link');

    foreach ($links as $link) {
        // Rechercher les balises <link> avec le type 'application/rss+xml' ou 'application/atom+xml'
        if ($link->getAttribute('type') === 'application/rss+xml' || $link->getAttribute('type') === 'application/atom+xml') {
            $rssFeedUrl = $link->getAttribute('href');
            break;
        }
    }
}

if (!$rssFeedUrl) {
    // Liste des chemins courants pour les flux RSS
    $commonRssPaths = [
        "/rss",
        "/feed",
        "/blog/feed",
        "/category/news/feed"
    ];

    foreach ($commonRssPaths as $path) {
        $tryUrl = $siteUrl . $path;
        echo "Essai de l'URL du flux : $tryUrl<br>";

        $result = getCurlContent($tryUrl);

        if ($result) {
            $rss = @simplexml_load_string($result);
            if ($rss !== false && isset($rss->channel)) {
                $rssFeedUrl = $tryUrl;
                break;
            }
        }
    }
}

// Vérifier si une URL de flux RSS valide a été trouvée
if ($rssFeedUrl) {
    echo "URL du flux RSS trouvée : " . $rssFeedUrl . "<br>";
    $rss = simplexml_load_file($rssFeedUrl);

    if ($rss === false) {
        die("Erreur lors de la récupération du flux RSS");
    }
    
    foreach ($rss->channel->item as $item) {
        echo "<h2>{$item->title}</h2>";
        echo "<p>{$item->description}</p>";
        echo "<a href='{$item->link}'>Lire l'article</a><br><br>";
    }
} else {
    echo "Aucun flux RSS trouvé.";
}
?>
