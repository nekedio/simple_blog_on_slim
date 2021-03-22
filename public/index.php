<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use DI\Container;

require __DIR__ . '/../vendor/autoload.php';

$container = new Container();
$container->set('renderer', function () {
    return new \Slim\Views\PhpRenderer(__DIR__ . '/../templates');
});

$app = AppFactory::createFromContainer($container);
$app->addErrorMiddleware(true, true, true);

$repo = new App\PostRepository();
$router = $app->getRouteCollector()->getRouteParser();

session_start();
$container->set('flash', function () {
    return new Slim\Flash\Messages();
});

$app->get('/', function (Request $request, Response $response, array $args) use ($repo) {
    $validator = new App\Validator();
    print_r($validator->validate(['name' => '', 'body' => '']));
    return $this->get('renderer')->render($response, 'index.phtml');
});

$app->get('/posts/new', function (Request $request, Response $response, array $args) use ($repo) {
    $params = [
        'post' => ['name' => '', 'body' => ''],
        'errors' => []
    ];
    return $this->get('renderer')->render($response, 'posts/new.phtml', $params);
})->setName('posts/new');

$app->post('/posts', function (Request $request, Response $response, array $args) use ($router) {
    $post = $request->getParsedBodyParam('post');
    $validator = new App\Validator();
    $errors = $validator->validate($post);
    if (count($errors)) {
        $params = [
            'post' => $post,
            'errors' => $errors
        ];
        return $this->get('renderer')->render($response->withStatus(422), 'posts/new.phtml', $params);
    }
    $this->get('flash')->addMessage('success', 'Post has been created');
    return $response->withRedirect($router->urlFor('posts'));
});


$app->get('/posts', function (Request $request, Response $response, array $args) use ($repo) {
    $page = $request->getQueryParam('page', 1);
    $messages = $this->get('flash')->getMessages();
    $per = 5;
    $posts = $repo->all();
    $chunks = collect($posts)->chunk($per);
    
    if ($page > count($chunks) || $page < 1) {
        return $response->withStatus(404)->write('Page not found');
    }

    $slice = $chunks[$page - 1]->all();
    $params = [
        'posts' => $slice,
        'page' => $page,
        'flash' => $messages
        ];
    return $this->get('renderer')->render($response, 'posts/index.phtml', $params);
})->setName('posts');


$app->get('/posts/{slug}', function (Request $request, Response $response, array $args) use ($repo) {
    $slug = $args['slug'];
    $posts = $repo->all();
    $post = collect($posts)->firstWhere('slug', $slug);

        if (!$post) {
        return $response->withStatus(404)->write('Page not found');
    }

    $params = [
        'post' => $post
        ];
    return $this->get('renderer')->render($response, 'posts/show.phtml', $params);
})->setName('post');


$app->run();
