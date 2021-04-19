<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use DI\Container;
use Slim\Middleware\MethodOverrideMiddleware;

require __DIR__ . '/../vendor/autoload.php';

$container = new Container();
$container->set('renderer', function () {
    return new \Slim\Views\PhpRenderer(__DIR__ . '/../templates');
});

$app = AppFactory::createFromContainer($container);
$app->add(MethodOverrideMiddleware::class);
$app->addErrorMiddleware(true, true, true);

$repo = new App\PostRepository();
$router = $app->getRouteCollector()->getRouteParser();

session_start();
$container->set('flash', function () {
    return new Slim\Flash\Messages();
});

$app->get('/', function (Request $request, Response $response, array $args) {
    return $this->get('renderer')->render($response, 'index.phtml');
});

$app->get('/posts/new', function (Request $request, Response $response, array $args) use ($repo) {
    $params = [
        'post' => ['name' => '', 'body' => ''],
        'errors' => []
    ];
    return $this->get('renderer')->render($response, 'posts/new.phtml', $params);
})->setName('posts/new');

$app->post('/posts', function (Request $request, Response $response, array $args) use ($router, $repo) {
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
    $repo->save($post);
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

$app->get('/posts/{id}', function (Request $request, Response $response, array $args) use ($repo) {
    $id = $args['id'];
    $posts = $repo->all();
    $post = collect($posts)->firstWhere('id', $id);

        if (!$post) {
        return $response->withStatus(404)->write('Page not found');
    }

    $params = [
        'post' => $post
        ];
    return $this->get('renderer')->render($response, 'posts/show.phtml', $params);
})->setName('post');

$app->get('/posts/{id}/edit', function (Request $request, Response $response, array $args) use ($repo) {
    $id = $args['id'];
    $post = $repo->find($id);
    
    $params = [
        'post' => $post,
        'errors' => []
    ];
     
    return $this->get('renderer')->render($response, 'posts/edit.phtml', $params);
});

$app->patch('/posts/{id}', function (Request $request, Response $response, array $args) use ($router, $repo) {
    $id = $args['id'];
    $data = $request->getParsedBodyParam('post');
    $data['id'] = $id;
    $validator = new App\Validator();
    $errors = $validator->validate($data);
    if (count($errors)) {
        $params = [
            'post' => $data,
            'errors' => $errors
        ];
        $response = $response->withStatus(422);
        return $this->get('renderer')->render($response, 'posts/edit.phtml', $params);
    }
    
    $repo->save($data);
    $this->get('flash')->addMessage('success', 'Post has been updated');
    return $response->withRedirect($router->urlFor('posts'));
});

$app->delete('/posts/{id}', function ($request, $response, array $args) use ($repo, $router) {
    $id = $args['id'];
    $repo->destroy($id);
    $this->get('flash')->addMessage('success', 'Post has been removed');
    return $response->withRedirect($router->urlFor('posts'));
});

$app->run();
