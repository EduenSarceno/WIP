<?php
require 'Rss.php';

/* URL del webhook a publicar */
const WEBHOOK = 'https://discordapp.com/api/webhooks/...';

try {
    $rss = new Rss();
    $rss->load('https://...', Rss::ANCHOR_TO_MD);
} catch (RssException $e) {
    // TODO: manejar errores
    throw $e;
}

$post = function($url, $data, &$response = null) {
    if (gettype($data) !== 'string') {
        $data = json_encode($data, JSON_UNESCAPED_SLASHES);
    }
    $http = array(
        'method' => 'POST',
        'header' => 'Content-Type: application/json',
        'content' => $data
    );
    $ctx = stream_context_create(array('http' => $http));
    $response = file_get_contents($url, false, $ctx);
};

foreach($rss->items as $item) {
    $embed = $item->toEmbed(RssItem::FETCH_RICH_MEDIA);

    $message = array('embeds'=> array($embed));
    $post(WEBHOOK, $message, $response);

    var_dump($embed);
    sleep(10);
}
