<?php

namespace {

from('Hoa')
-> import('Model.~')
-> import('Model.Exception')
-> import('Database.Dal')
-> import('String');

from('Hoathis')
-> import('Model.Exception.*');

}

namespace Application\Model {

class Post extends \Hoa\Model {

    const STATE_ALL       = -1;
    const STATE_DRAFT     = 0;
    const STATE_PUBLISHED = 1;

    protected $_id;
    protected $_title;
    protected $_posted;
    protected $_content;
    protected $_state;

    /**
     * @invariant comments: relation('Application\Model\Comment', boundinteger(0));
     */
    protected $_comments;

    protected function construct ( ) {

        $this->setMappingLayer(\Hoa\Database\Dal::getLastInstance());

        $this->posted = time();
        $this->state = Post::STATE_DRAFT;

        return;
    }

    static public function findByIdAndState ( $id, $state = STATE_PUBLISHED ) {

        $post = new Post();
        $data = $post->getMappingLayer()
                     ->prepare(
                         'SELECT id, posted, title, content, state ' .
                         'FROM   post ' .
                         'WHERE  id = :id ' .
                         'AND (state1 = :state OR :state2 = -1)'
                     )
                     ->bindParam(':id', $id, PDO::PARAM_INT)
                     ->bindParam(':state1', $state, PDO::PARAM_INT)
                     ->bindParam(':state2', $state, PDO::PARAM_INT)
                     ->fetchAll();

        if(!empty($data)) {
            $post->map($data[0]);
            $post->comments->map(
                $post->getMappingLayer()
                     ->prepare(
                         'SELECT id, posted, author, content ' .
                         'FROM   comment ' .
                         'WHERE  post = :post'
                     )
                     ->execute(array('post' => $id))
                     ->fetchAll()
            );
        }
        else
        {
            throw new \Hoathis\Model\Exception\NotFound("Post not found");
        }

        return $post;
    }

    public function update ( Array $attributes = array() ) {

        try {
            $this->title   = trim(strip_tags($attributes['title']));
            $this->content = trim($attributes['content']);
            $this->posted  = strtotime(trim(strip_tags($attributes['posted'])));
            $this->state   = trim(strip_tags($attributes['state']));
        }
        catch (\Hoa\Model\Exception $e) {
            throw new \Hoathis\Model\Exception\ValidationFailed($e->getMessage());
        }

        return $this->getMappingLayer()
                    ->prepare(
                        'UPDATE post SET title = :title, content = :content, ' .
                        'posted = :posted, state = :state ' .
                        'WHERE  id = :id'
                    )
                    ->execute(array(
                        'title'   => $this->title,
                        'content' => $this->content,
                        'posted'  => $this->posted,
                        'state'   => $this->state,
                        'id'      => $this->id
                    ));
    }

    public function create ( Array $attributes = array() ) {

        try {
            $this->title   = trim(strip_tags($attributes["title"]));
            $this->content = trim(strip_tags($attributes["content"]));
            $this->posted  = strtotime(trim(strip_tags($attributes["posted"])));
            $this->state   = trim(strip_tags($attributes['state']));
        }
        catch (\Hoa\Model\Exception $e) {
            throw new \Hoathis\Model\Exception\ValidationFailed($e->getMessage());
        }

        $this->getMappingLayer()
             ->prepare(
                'INSERT INTO post (title, content, posted, state) ' .
                'VALUES (:title, :content, :posted, :state)'
             )
             ->execute(array(
                'title'   => $this->title,
                'content' => $this->content,
                'posted'  => $this->posted,
                'state'   => $this->state
             ));
        $this->id = $this->getMappingLayer()->lastInsertId();
    }

    public function delete ( ) {

      Comment::deleteByPost($this->id);

      return $this->getMappingLayer()
                  ->prepare(
                    'DELETE FROM post WHERE id = :id'
                  )
                  ->execute(array(
                    'id'  => $this->id,
                  ));
    }

    public function getList ( $current_page, $post_per_page, $state = Post::STATE_PUBLISHED ) {

      if( $current_page > ceil($this->count($state)/$post_per_page) ) {
          throw new \Hoathis\Model\Exception\NotFound("Page not found");
      }

      $first_entry = ($current_page - 1) * $post_per_page;

      $list = $this->getMappingLayer()
                   ->prepare(
                    'SELECT id, title, posted, state ' .
                    'FROM post ' .
	                  'WHERE (state = :state1 OR :state2 = -1)' .
                    'ORDER BY posted DESC ' .
                    'LIMIT :first_entry, :post_per_page'
                   )
                   ->bindParam(':state1', $state, PDO::PARAM_INT)
                   ->bindParam(':state2', $state, PDO::PARAM_INT)
                   ->bindParam(':first_entry', $first_entry, PDO::PARAM_INT)
                   ->bindParam(':post_per_page', $post_per_page, PDO::PARAM_INT)
                   ->fetchAll();

      foreach($list as &$post) {

        $post['normalized_title'] = Post::getNormalizedTitle($post['title']);
      }

      return $list;
    }

    public static function getNormalizedTitle( $title ) {

      $normalized_title = new \Hoa\String($title);
      $normalized_title = $normalized_title->toAscii()
                                           ->replace('/\s/', '-')
                                           ->replace('/[^a-zA-Z0-9\-]+/', '')
                                           ->substr(0, 32)
                                           ->toLowerCase();

      // force cast because json_encode (used for API) try to return this as an
      // object without it
      return (string)$normalized_title;
    }

    public function count ( $state = Post::STATE_PUBLISHED ) {

      return $this->getMappingLayer()
                  ->prepare(
                      'SELECT COUNT(*) ' .
                      'FROM post ' .
                      'WHERE (state = :state1 OR :state2 = -1)'
                  )
                  ->bindParam(':state1', $state, PDO::PARAM_INT)
                  ->bindParam(':state2', $state, PDO::PARAM_INT)
                  ->execute()
                  ->fetchColumn();
    }
}

}
