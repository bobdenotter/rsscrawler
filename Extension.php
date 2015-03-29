<?php

namespace Bolt\Extension\Bolt\RSSCrawler;

/**
 * RSS Aggregator Extension for Bolt
 *
 * @author Bob den Otter <bob@twokings.nl>
 */
class Extension extends \Bolt\BaseExtension
{
    const NAME = 'RSSCrawler';

    public function getName()
    {
        return Extension::NAME;
    }

    /**
     * Initialize RSS Crawler
     */
    public function initialize()
    {

        $path = $this->app['config']->get('general/branding/path') . '/rsscrawler';
        $this->app->match($path, array($this, "RSSCrawler"));

    }

    public function RSSCrawler()
    {

        if (empty($this->config['key']) || ($this->config['key'] !== $this->app['request']->get('key'))) {
            return "Key not correct.";
        }

        $cachedir = $this->app['resources']->getPath('cache') . '/rsscrawler/';

        dump($this->config['feeds']);

        foreach ($this->config['feeds'] as $author => $feed) {
            $this->parseFeed($author, $feed);
        }

        return "<br><br><br> Done.";
    }

    private function parseFeed($author, $feed)
    {
        $contenttype = $this->config['contenttype'];

        $fastFeed = \FastFeed\Factory::create();

        $parser = new \FastFeed\Parser\RSSParser();
        $parser->pushAggregator(new \FastFeed\Aggregator\RSSContentAggregator());
        $fastFeed->pushParser($parser);
        $parser = new \FastFeed\Parser\AtomParser();
        $parser->pushAggregator(new \FastFeed\Aggregator\RSSContentAggregator());
        $fastFeed->pushParser($parser);

        $fastFeed->addFeed('default', $feed['feed']);
        $items = $fastFeed->fetch('default');

        // slice, if configured
        if (!empty($this->config['itemAmount'])) {
            $items = array_slice($items, 0, $this->config['itemAmount']);
        }

        foreach ($items as $item) {

            // try to get an existing record for this item
            $record = $this->app['storage']->getContent(
                $contenttype, array(
                    'itemid' => $item->getId(),
                    'returnsingle' => true
                ) );

            if (!$record) {
                // New one.
                $record = $this->app['storage']->getContentObject($contenttype);
                echo "<br> [NEW] ";
                $new = true;
            } else {
                echo "<br> [UPD] ";
                $new = false;
            }

            $date = $item->getDate();

            if ($item->getContent() != false) {
                $raw = $item->getContent();
            } else {
                $raw = $item->getIntro();
            }


            // Sanitize/clean the HTML.
            $maid = new \Maid\Maid(
                array(
                    'output-format' => 'html',
                    'allowed-tags' => array( 'p', 'br', 'hr', 's', 'u', 'strong', 'em', 'i', 'b', 'li', 'ul', 'ol', 'menu', 'blockquote', 'pre', 'code', 'tt', 'h2', 'h3', 'h4', 'h5', 'h6', 'dd', 'dl', 'dh', 'table', 'tbody', 'thead', 'tfoot', 'th', 'td', 'tr', 'a', 'img'),
                    'allowed-attribs' => array('id', 'class', 'name', 'value', 'href', 'src')
                )
            );
            $content = $maid->clean($raw);

            if ($item->getImage() != "") {
                $image = $item->getImage();
            } else {
                $image = $this->findImage($content, $feed['url']);
            }

            $values = array(
                'itemid' => $item->getId(),
                'slug' => "" . $item->getName(),
                'title' => "" . $item->getName(),
                'raw' => "" . $raw,
                'content' => "" . $content,
                'source' => "" . $item->getSource(),
                'author' => $author,
                'image' => "" . $image,
                'status' => 'published',
                'sitetitle' => $feed['title'],
                'sitesource' => $feed['url']
            );

            if ($new || $date instanceof \DateTime) {
                $values['datecreated'] = ($date instanceof \DateTime) ? $date->format('Y-m-d H:i:s') : "";
                $values['datepublish'] = ($date instanceof \DateTime) ? $date->format('Y-m-d H:i:s') : "";
            }

            $tags = $item->getTags();

            if (!empty($tags)) {
                foreach ($tags as $tagkey => $tagvalue) {
                    $tags[$tagkey] = $this->app['slugify']->slugify($tagvalue);
                }
                $record->setTaxonomy('tags', $tags);
            }

            $record->setValues($values);
            $id = $this->app['storage']->saveContent($record);

            echo $values['sitetitle'] . " - " . $values['title'];
        }

    }


    private function findImage($html, $baseurl)
    {
        $doc = new \DOMDocument();
        @$doc->loadHTML($html);

        $tags = $doc->getElementsByTagName('img');

        foreach ($tags as $tag) {
            // Skip feedburner images..
            if (strpos($tag->getAttribute('src'), "feedburner.com") > 0) {
                continue;
            }
            if (strpos($tag->getAttribute('src'), "flattr.com") > 0) {
                continue;
            }


            $image = $tag->getAttribute('src');

            if (strpos($image, "http") === false) {
                $baseurl = parse_url($baseurl);
                $image = $baseurl['scheme'] . "://" . $baseurl['host'] . $image;
            }

            return $image;
            // echo $tag->getAttribute('src') . "<br>\n";
            // printf("<img src='%s' widht='100'>", $tag->getAttribute('src'));
        }

        return "";

    }

}
