<?php

namespace {

from('Hoa')
-> import('Dispatcher.Kit')
-> import('Session.~');

from('Hoathis')
-> import('Kit.Aggregator');

}

namespace Application\Controller {

class Base extends \Hoathis\Kit\Aggregator {

  protected function LoadPost ( $kit, $id ) {

    try {
      $post = \Application\Model\Post::findByIdAndState($id);
    }
    catch (\Hoathis\Model\Exception\NotFound $e) {
      $kit->getKit('Redirector')
          ->redirect('posts', array('controller' => 'posts',
                                    'action' => 'index'));
    }

    return $post;
  }

}

}