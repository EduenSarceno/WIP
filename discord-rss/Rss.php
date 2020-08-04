<?php

/*
 * Implementación sencilla de un RSS
 */
class Rss
{
    const ANCHOR_TO_MD = 1;

    public $channel;
    public $items;
    /**
     * Carga y analiza un RSS
     * @param string $url el Rss a cargar
     * @param int $opts opciones
     */
    public function load(string $url, int $opts = 0)
    {
        $rss = Rss::fetch($url);
        if ($rss === false) {
            throw new RssException("can not load $url");
        }

        $doc = @DOMDocument::loadXML($rss);
        if ($doc === false) {
            throw new RssException("can not create DOMDocument");
        }

        $root = $doc->documentElement;
        if ($root->tagName !== 'rss') {
            throw new RssException('root element is not rss');
        }

        $version = (float) $root->getAttribute('version');
        if ($version < 2.0) {
            throw new RssException('rss version is less than 2.0');
        }

        $channel = $root->getElementsByTagName('channel');
        if ($channel->length === 0) {
            throw new RssException('expected one channel');
        } else {
            $channel = $channel[$channel->length - 1];
        }

        $getText = function($item, $e, &$out = null) {
            $out = $item->getElementsByTagName($e)[0] ?? null;
            return $out->textContent ?? null;
        };

        $this->channel = array();
        $this->items = array();
        $this->channel['title'] = $getText($channel, 'title');
        $this->channel['description'] = $getText($channel, 'description');
        $this->channel['link'] = $getText($channel, 'link');

        $items = $channel->getElementsByTagName('item');
        foreach ($items as $elem) {
            $item = new RssItem($this);
            $item->description = $getText($elem, 'description');
            $item->link = $getText($elem, 'link');
            $item->title = $getText($elem, 'title');
            $item->guid = $getText($elem, 'guid', $guid);

            if (is_null($guid)) {
                $item->isPermaLink = false;
            } else {
                $item->isPermaLink = $guid->getAttribute('isPermaLink') === 'true';
            }

            if (($opts & Rss::ANCHOR_TO_MD) > 0) {
                $item->description = Rss::anchorToMD($item->description);
            }

            $this->items[] = $item;
        }

    }

    static private function anchorToMD(string $textContent)
    {
            if (is_null($textContent)) {
                return null;
            }
            $html = '<?xml encoding="utf-8"?><p>' . $textContent . '</p>';
            $doc = @DOMDocument::loadHTML($html, LIBXML_HTML_NOIMPLIED);
            // Debemos iterar de reversa para evitar romper el estado interno
            // del iterador.
            // https://www.php.net/manual/en/domnode.replacechild.php#50500
            $anchors = $doc->documentElement->getElementsByTagName('a');
            for($i = $anchors->length - 1; $i > -1; $i--) {
                $anchor = $anchors->item($i);
                $txt = sprintf('[%s](%s)', trim($anchor->textContent), $anchor->getAttribute('href'));
                $node = $doc->createTextNode($txt);
                $anchor->parentNode->replaceChild($node, $anchor);
            }

            return $doc->textContent;
    }

    static public function fetch(string $url)
    {
        // Debemos fingir ser un navegador normal para evitar (con suerte)
        // que los sitios nos devuelvan 403 Forbidden
        $http = array(
            'method' => 'GET',
            'protocol_version' => 1.1,
            'header' => array(
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Cache-Control: no-cache',
                'Connection: close',
                'Pragma: no-cache',
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:79.0) Gecko/20100101 Firefox/79.0'
            )
            // TODO: permitir el paso de Cookies
        );
        $ctx = stream_context_create(array('http' => $http));
        return file_get_contents($url, false, $ctx);
    }

}
/**
 *  Representa los elementos de un canal RSS.
 *  Actualmente esta clase es utilitaria
 *  @see toEmbed()
 */
class RssItem
{
    const FETCH_RICH_MEDIA = 1;

    public $description;
    public $guid;
    // Indica si guid es un enlace permalink
    public $isPermaLink = false;
    public $link;
    public $rss;
    public $title;

    public function __construct($rss)
    {
        $this->rss = $rss;
    }

    /**
     * Crea un Embed de Discord
     * @param int $opts opciones
     * @return array DiscordEmbed
     * @see https://discord.com/developers/docs/resources/channel#embed-object
     */
    public function toEmbed(int $opts = 0)
    {
        $ret = array(
            'title' => $this->title,
            'description' => $this->description,
            'url' => $this->link
        );
        $author = array(
            'name' => $this->rss->channel['title'],
            'url' => $this->rss->channel['link']
        );

        if (($opts & RssItem::FETCH_RICH_MEDIA) > 0) {
            RssItem::getRichMedia($this, $ret);
        }
        return $ret;
    }

    static private function getRichMedia(RssItem $item, array &$ret) {
        $url = $item->link;
        if ($item->isPermaLink) {
            $url = $item->guid;
        }

        $html = Rss::fetch($url);
        if ($html === false) {
            return;
        }

        $doc = @DOMDocument::loadHTML($html);
        if ($doc === false) {
            return;
        }

        $xpath = new DOMXPath($doc);
        $metaTags = iterator_to_array($xpath->query('//head/meta'));
        $meta = function ($name) use ($metaTags) {
            $ret = array_filter($metaTags, function($e) use ($name) {
                return
                    $e->getAttribute('property') === $name ||
                    $e->getAttribute('name') === $name;
            });
            return array_values($ret);
        };
        $content = function($elem) {
            return $elem->getAttribute('content');
        };

        $img = $meta('og:image')[0] ?? null;
        if (!is_null($img)) {
            $img = $content($img);
            // OpenGraph exige que la rutas sean cualificadas sin embargo
            // muchos sitios no utilizan rutas cualificadas
            if (strpos($img, '/') === 0) {
                $link = $item->rss->channel['link'];
                if (substr($link, -1) !== '/') {
                    $link += '/';
                }
                $img =  $link . substr($img, 1);
            }
            // TODO: manejar rutas relativas

            $image = array('url' => $img);
            $card = $meta('twitter:card')[0] ?? null;
            // Debemos evitar mostrar la imagen como miniatura si el sitio especifica
            // lo contrario
            if (!is_null($card) && $content($card) === 'summary_large_image') {
                $ret['image'] = $image;
            } else {
                $ret['thumbnail'] = $image;
            }
        }

        $color = $meta('theme-color')[0] ?? null;
        if (!is_null($color)) {
            $color = $content($color);
            if (strpos($color, '#') === 0) {
                $ret['color'] =  hexdec(substr($color, 1));
            }
            // TODO mapear otros formatos de colores
        }
    }
}

class RssException extends Exception {}
