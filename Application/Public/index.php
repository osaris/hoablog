<?php

namespace {

require_once dirname(dirname(__DIR__)) .
             DIRECTORY_SEPARATOR . 'Data' .
             DIRECTORY_SEPARATOR . 'Core.link.php';

from('Hoa')
-> import('Database.Dal')
-> import('Dispatcher.Basic')
-> import('Router.Http')
-> import('Xyl.~')
-> import('File.Read')
-> import('Http.Response.~');

from('Hoathis')
-> import('Xyl.Interpreter.Html.~');

Hoa\Database\Dal::initializeParameters(array(
    'connection.list.default.dal' => Hoa\Database\Dal::PDO,
    'connection.list.default.dsn' => 'sqlite:hoa://Data/Variable/Database/Blog.sqlite',
    'connection.autoload'         => 'default'
));

$dispatcher = new Hoa\Dispatcher\Basic();
$dispatcher->setKitName('Hoathis\Kit\Aggregator');
$router     = new Hoa\Router\Http();
$router->get('posts',       '/',                               'posts', 'index')
       ->get('post',        '/posts/(?<id>\d+)\-(?<normalized_title>.+)\.html', 'posts', 'show')
       ->post('create_comment',    '/posts/(?<post_id>\d+)/comments/create','comments', 'create')
       ->get('admin',       '/admin',                          'admin\log', 'in')
       ->get('log',         '/admin/log',                      'admin\log',   'index')
       ->post('login',      '/admin/log/in',                   'admin\log',   'in')
       ->get('logout',      '/admin/log/out',                  'admin\log',   'out')
       ->get('admin_posts', '/admin/posts',                    'admin\posts', 'index')
       ->get('admin_post',  '/admin/posts/(?<id>\d+)\-(?<normalized_title>.+)\.html', 'admin\posts', 'show')
       ->get('new_post',    '/admin/posts/new',                'admin\posts', 'new')
       ->post('create_post','/admin/posts/create',             'admin\posts', 'create')
       ->get('edit_post',   '/admin/posts/(?<id>\d+)/edit',    'admin\posts', 'edit')
       ->post('update_post','/admin/posts/(?<id>\d+)',         'admin\posts', 'update')
       ->get('delete_post', '/admin/posts/(?<id>\d+)/delete',  'admin\posts', 'delete')
       ->get('api_posts',   '/api/posts',                      'api\posts', 'index');

try {

    $dispatcher->dispatch(
        $router,
        new Hoa\Xyl(
            new Hoa\File\Read('hoa://Application/View/Main.xyl'),
            new Hoa\Http\Response(),
            new Hoathis\Xyl\Interpreter\Html(),
            $router
        )
    );
}
catch ( Hoa\Router\Exception\NotFound $e ) {

    echo 'Your page seems to be not found /o\.', "\n";
}

}
