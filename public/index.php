<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;
use DI\Container;
use Fakebook\Model\User;
use Fakebook\Model\Feed;
use Fakebook\Repository\UsersRepository;
use Fakebook\Repository\FeedRepository;

require __DIR__ . '/../vendor/autoload.php';


session_start();

$db = [
    'host' => 'localhost',
    'dbname' => 'fakebook',
    'pass' => '',
    'username' => 'root'
];
$pdo = new PDO('mysql:host=' . $db['host'] . ';dbname=' . $db['dbname'], $db['username'], $db['pass']);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);


$app = AppFactory::create();
$twig = Twig::create('../src/templates', ['cache' => false]);
$app->add(TwigMiddleware::create($app, $twig));

$app->get('/', function (Request $request, Response $response, $args) use ($pdo) {
    $view = Twig::fromRequest($request);
    $feedRepository = new FeedRepository($pdo);
    return $view->render($response, 'general.html', [
        'logged_user' => $_SESSION['logged_user'] ?? false,
        'user' => $_SESSION['logged_user'] ?? false,
        'feed' => $feedRepository->getFeed(),
    ]);
});

$app->get('/signup', function(Request $request, Response $response, $args) {
    $view = Twig::fromRequest($request);
    return $view->render($response, 'signup.html', []);
});

$app->post('/signup', function(Request $request, Response $response, $args) use ($pdo) {
    $data = $request->getParsedBody();
    try {
        $picture = $request->getUploadedFiles();
        $user = new User($data['name'], $data['email'], $data['password'], $picture['picture']->getClientFilename() ?? '');
        $usersRepository = new UsersRepository($pdo);        
        $usersRepository->checkUserExists($user);
    } catch (RuntimeException $e) {
        $view = Twig::fromRequest($request);
        return $view->render($response, 'signup.html', [
            'errors' => $e->getMessage()
        ]);
    }
    $user = $usersRepository->create($user);
    if ($picture['picture']->getError() === UPLOAD_ERR_OK) {
        mkdir("storage/users/{$user->id}", 0777, true);
        $picture['picture']->moveTo("storage/users/{$user->id}/{$user->picture}");
    }
    return $response->withStatus(301)->withHeader('Location', '/login');
});

$app->get('/login', function(Request $request, Response $response, $args) {
    $view = Twig::fromRequest($request);
    return $view->render($response, 'login.html', []);
});

$app->post('/login', function(Request $request, Response $response, $args) use ($pdo) {
    $data = $request->getParsedBody();
    try {
        $usersRepository = new UsersRepository($pdo);        
        $user = $usersRepository->authenticateUser($data['email'], $data['password']);
        $_SESSION['logged_user'] = $user;
        return $response->withStatus(301)->withHeader('Location', '/');
    } catch (RuntimeException $e) {
        $view = Twig::fromRequest($request);
        return $view->render($response, 'login.html', [
            'errors' => $e->getMessage()
        ]);
    }

});

$app->get('/logout', function(Request $request, Response $response, $args) {
    session_destroy();
    return $response->withStatus(301)->withHeader('Location', '/');
});

$app->get('/profile/{id}', function(Request $request, Response $response, $args) use ($pdo) {
    try {
        $usersRepository = new UsersRepository($pdo);
        $user = $usersRepository->getUser($args['id']);
    } catch (RuntimeException $e) {
        return $response->withStatus(301)->withHeader('Location', '/');
    }
    $view = Twig::fromRequest($request);
    return $view->render($response, 'profile.html', [
        'user' => $user,
        'can_update' => $user->id == $_SESSION['logged_user']->id ?? 0,
        'logged_user' => $_SESSION['logged_user'] ?? false
    ]);
});

$app->get('/profile/{id}/update', function(Request $request, Response $response, $args) use ($pdo) {
    try {
        $usersRepository = new UsersRepository($pdo);
        $user = $usersRepository->getUser($args['id']);
    } catch (RuntimeException $e) {
        return $response->withStatus(301)->withHeader('Location', '/');
    }
    $view = Twig::fromRequest($request);
    return $view->render($response, 'profile_update.html', [
        'user' => $user,
        'can_update' => $user->id == $_SESSION['logged_user']->id ?? 0,
        'logged_user' => $_SESSION['logged_user'] ?? false
    ]);
});

$app->post('/profile/{id}/update', function(Request $request, Response $response, $args) use ($pdo) {
    $data = $request->getParsedBody();
    try {
        $picture = $request->getUploadedFiles();
        $usersRepository = new UsersRepository($pdo);
        $user = $usersRepository->getUser($args['id']);
        $user = new User($data['name'], $user->email, $data['password'], $picture['picture']->getClientFilename() ?? $user->picture, $user->id);
        $usersRepository->update($user);
    } catch (RuntimeException $e) {
        $view = Twig::fromRequest($request);
        return $view->render($response, 'profile_update.html', [
            'errors' => $e->getMessage(),
            'logged_user' => $_SESSION['logged_user'] ?? false
        ]);
    }
    if ($picture['picture']->getError() === UPLOAD_ERR_OK) {
        if (!is_dir("storage/users/{$user->id}")) {
            mkdir("storage/users/{$user->id}", 0777, true);    
        }
        $picture['picture']->moveTo("storage/users/{$user->id}/{$user->picture}");
    }
    return $response->withStatus(301)->withHeader('Location', "/profile/{$user->id}");
});

$app->post('/feed',function(Request $request, Response $response, $args) use ($pdo) {
    $data = $request->getParsedBody();
    try {
        $picture = $request->getUploadedFiles();
        $feedRepository = new FeedRepository($pdo);
        $usersRepository = new UsersRepository($pdo);
        $user = $usersRepository->getUser($_SESSION['logged_user']->id);
        $post = new Feed($user, $data['post'], $picture['picture']->getClientFilename() ?? '');
        $feedRepository->create($post);
        
    } catch (RuntimeException $e) {
        return $response->withStatus(301)->withHeader('Location', "/");
    }
    if ($picture['picture']->getError() === UPLOAD_ERR_OK) {
        if (!is_dir("storage/feed/{$user->id}")) {
            mkdir("storage/feed/{$user->id}", 0777, true);    
        }
        $picture['picture']->moveTo("storage/feed/{$user->id}/{$post->image}");
    }
    return $response->withStatus(301)->withHeader('Location', "/");
});

$app->run();