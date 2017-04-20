<?php

require_once __DIR__.'/vendor/autoload.php';

//namespaces
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Silex\Provider\FormServiceProvider;
use Symfony\Component\Form;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Validator\Constraints as Assert;





$app = new Silex\Application();

// registration services
$app->register(new Silex\Provider\SessionServiceProvider());
$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__.'/templates',
));
$app->register(new FormServiceProvider());
$app->register(new Silex\Provider\LocaleServiceProvider());
$app->register(new Silex\Provider\ValidatorServiceProvider());
$app->register(new Silex\Provider\TranslationServiceProvider(), array(
    'translator.domains' => array(),
));
$app->register(new Silex\Provider\VarDumperServiceProvider());
$app->register(new Silex\Provider\DoctrineServiceProvider(), array(
    'db.options' => array(
        'driver'   => 'mysqli',
        'host'     => 'localhost',
        'user'     => 'root',
        'password'     => 'root',
        'port'     => '3306',
        'dbname'     => 'silex-examples',
    ),
));



// configs
$app['debug'] = true;

//routes

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

    return $app['twig']->render('login.twig', array('form' => $form->createView(), 'res' => ''));
})->bind('login');



$app->match('/account', function (Request $request) use ($app) {
    if (null === $user = $app['session']->get('user')) {
        return $app->redirect('/login');
    }else{
        $res = array(
            'name' => $user['username'],
        );
    }

    $form = $app['form.factory']->createBuilder(FormType::class)
        ->add('user_id', TextType::class, array(
            'constraints' => array(new Assert\NotBlank())
        ))
        ->add('device', ChoiceType::class, array(
            'constraints' => array(new Assert\NotBlank()),
            'choices' => array('desktop' => 1, 'tablet' => 2, 'phone' => 3),
            'expanded' => true,
        ))
        ->add('browser', ChoiceType::class, array(
            'constraints' => array(new Assert\NotBlank()),
            'choices' => array('chrome' => 1, 'opera' => 2, 'safari' => 3),
            'expanded' => true,
        ))
        ->add('submit', SubmitType::class, [
            'label' => 'save to DB',
        ])
        ->getForm();
    $form->handleRequest($request);

    $res['form'] = $form->createView();
    if ($form->isValid()) {
        $data = $form->getData();
        $res['res'] = $data;
        return $app['twig']->render('admin.twig', $res);
    }

    return $app['twig']->render('admin.twig', $res);
})->method('GET|POST')->bind('account');


$app->get('/exit', function () use ($app) {
    $app['session']->clear();
    return $app->redirect('/');
})->bind('exit');


$app->run();