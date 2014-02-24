<?php

namespace {

from('Application')
-> import('Controller.Base')
-> import('Model.Post')
-> import('Model.Comment');

from('Hoa')
-> import('Stringbuffer.Read');

}

namespace Application\Controller {

class Feeds extends Base {

  private $post_per_page = 10;

  public function Rss2Action ( ) {

    $config = \Hoa\Registry\Registry::get('config');

    $post = new \Application\Model\Post();
    $list = $post->getList(1, $this->post_per_page);

    $xml = new \SimpleXMLElement('<rss version="2.0" encoding="utf-8"></rss>');
    $xml->addChild('channel');
    $xml->channel->addChild('title', 'Hoa Blog feed');
    $xml->channel->addChild('link', $config['url']);
    $xml->channel->addChild('description', 'Hoa Blog news feed');
    $xml->channel->addChild('language', $config['rss_feed_lang']);
    $xml->channel->addChild('pubDate', date(DATE_RSS));

    foreach ($list as $post) {
      $item = $xml->channel->addChild('item');
      $item->addChild('title', htmlentities($post['title']));
      $item->addChild('link', $config['url'] . $post['id'] . '-' . $post['normalized_title']);
      $item->addChild('description', $post['content']);
      $item->addChild('pubDate', date(DATE_RSS, strtotime($post['posted'])));
    }

    echo $xml->asXML();

    return;
  }
}

}
