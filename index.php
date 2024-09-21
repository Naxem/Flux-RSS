<?php
// URL de la page du site (page d'accueil ou autre)
$siteUrl = "https://www.lebigdata.fr";  // Remplace par l'URL du site que tu veux analyser

// Fonction pour récupérer le contenu d'une page via cURL
function getCurlContent($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // Suivre les redirections
    curl_setopt($ch, CURLOPT_TIMEOUT, 30); // Timeout après 30 secondes
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Désactiver la vérification SSL temporairement
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
    // Utilisation d'un DOMDocument pour parser le HTML
    $dom = new DOMDocument();
    @$dom->loadHTML($html); // Utilisation de @ pour ignorer les erreurs de parsing HTML

    // Rechercher toutes les balises <link>
    $links = $dom->getElementsByTagName('link');

    foreach ($links as $link) {
        // Rechercher les balises <link> avec le type 'application/rss+xml' ou 'application/atom+xml'
        if ($link->getAttribute('type') === 'application/rss+xml' || $link->getAttribute('type') === 'application/atom+xml') {
            $rssFeedUrl = $link->getAttribute('href');
            break; // On arrête la boucle après avoir trouvé la première URL de flux
        }
    }
}

// Si aucune URL de flux n'est trouvée dans le HTML, on essaie les chemins courants
if (!$rssFeedUrl) {
    // Liste des chemins courants pour les flux RSS
    $commonRssPaths = [
        "/rss",
        "/feed",
        "/blog/feed",
        "/category/news/feed"
    ];

    // Essayer de récupérer le flux à partir de chaque chemin possible
    foreach ($commonRssPaths as $path) {
        $tryUrl = $siteUrl . $path;
        echo "Essai de l'URL du flux : $tryUrl<br>";

        $result = getCurlContent($tryUrl);

        if ($result) {
            // Charger le contenu pour vérifier si c'est un flux RSS
            $rss = @simplexml_load_string($result);
            if ($rss !== false && isset($rss->channel)) {
                $rssFeedUrl = $tryUrl;
                break;  // On a trouvé un flux valide
            }
        }
    }
}

// Vérifier si une URL de flux RSS valide a été trouvée
if ($rssFeedUrl) {
    echo "URL du flux RSS trouvée : " . $rssFeedUrl . "<br>";

    // Charger et analyser le flux RSS
    $rss = simplexml_load_file($rssFeedUrl);

    if ($rss === false) {
        die("Erreur lors de la récupération du flux RSS");
    }

    // Boucler à travers les éléments du flux
    foreach ($rss->channel->item as $item) {
        echo "<h2>{$item->title}</h2>";
        echo "<p>{$item->description}</p>";
        echo "<a href='{$item->link}'>Lire l'article</a><br><br>";
    }
} else {
    echo "Aucun flux RSS trouvé.";
}
?>
