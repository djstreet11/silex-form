<?php



require_once __DIR__.'/vendor/autoload.php';

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Silex\Provider\FormServiceProvider;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

$app = new Silex\Application();


$app->register(new Silex\Provider\SessionServiceProvider());
$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__.'/templates',
));
$app->register(new FormServiceProvider());

$app->register(new Silex\Provider\LocaleServiceProvider());
$app->register(new Silex\Provider\TranslationServiceProvider(), array(
    'translator.domains' => array(),
));
$app->register(new Silex\Provider\VarDumperServiceProvider());


$app->get('/', function() use($app) {
    return $app['twig']->render('index.twig');
})->bind('index');



$app->match('/login', function (Request $request) use ($app) {

    $user = $app['session']->get('user');

    if (isset($user)) {
        return $app->redirect('/account');
    }

    $data = array(
        'login' => 'test',
        'password' => 'test',
    );

    $form = $app['form.factory']->createBuilder(FormType::class, $data)
        ->add('login')
        ->add('password')
        ->add('submit', SubmitType::class, [
            'label' => 'Sign In',
        ])
        ->getForm();

    $form->handleRequest($request);

    if ($form->isValid()) {
        $data = $form->getData();
        $username = $data['login'];
        $password = $data['password'];

        if ('test' === $username && 'test' === $password) {
            $app['session']->set('user', array('username' => $username));
            return $app->redirect('/account');
        }
        return $app['twig']->render('login.twig', array('form' => $form->createView(), 'res'=> 'fail'));
    }

    // display the form
    return $app['twig']->render('login.twig', array('form' => $form->createView(), 'res' => ''));




})->bind('login');

$app->get('/account', function () use ($app) {

    if (null === $user = $app['session']->get('user')) {
        return $app->redirect('/login');
    }
    return $app['twig']->render('admin.twig', array(
        'name' => $user['username'],
    ));
})->bind('account');


$app->get('/account', function () use ($app) {

    if (null === $user = $app['session']->get('user')) {
        return $app->redirect('/login');
    }
    return $app['twig']->render('admin.twig', array(
        'name' => $user['username'],
    ));
})->bind('account');


$app->get('/exit', function () use ($app) {

    $app['session']->clear();

    return $app->redirect('/');

})->bind('exit');


















$app['debug'] = true;
$app->run();