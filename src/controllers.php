<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


$app['twig'] = $app->share($app->extend('twig', function($twig, $app) {
    $twig->addGlobal('user', $app['session']->get('user'));

    return $twig;
}));


$app->get('/', function () use ($app) {
    return $app['twig']->render('index.html', [
        'readme' => file_get_contents('README.md'),
    ]);
});


$app->match('/login', function (Request $request) use ($app) {
    $username = $request->get('username');
    $password = $request->get('password');

    if ($username) {
       $user = UserORM::get_user($app,$username,$password);

        if ($user){
            $app['session']->set('user', $user);
            return $app->redirect('/todo');
        }
    }

    return $app['twig']->render('login.html', array());
});


$app->get('/logout', function () use ($app) {
    $app['session']->set('user', null);
    return $app->redirect('/');
});


$app->get('/todo/{id}', function ($id) use ($app) {
    if (null === $user = $app['session']->get('user')) {
        return $app->redirect('/login');
    }
    
    if ($id){
        $todo = TodoORM::get_todo($app, $id);
        return $app['twig']->render('todo.html', [
            'todo' => $todo,
        ]);
    } else {
        $todos = TodoORM::get_todos($app, $user['id']);
        return $app['twig']->render('todos.html', [
            'todos' => $todos,
        ]);
    }
})
->value('id', null);


$app->post('/todo/add', function (Request $request) use ($app) {
    if (null === $user = $app['session']->get('user')) {
        return $app->redirect('/login');
    }

    $user_id = $user['id'];
    $description = $request->get('description');

    if(!isset($description) || trim($description) == ''){
        $app['session']->getFlashBag()->add('empty_description', 'You need a description for the note');
    }else{
        TodoORM::add_todo($app, $description, $user_id);
        $app['session']->getFlashBag()->add('confirmation', 'You created todo with description : ' . $description);
    }
    
    return $app->redirect('/todo');
});


$app->match('/todo/delete/{id}', function ($id) use ($app) {
    TodoORM::delete_todo($app,$id);
    $app['session']->getFlashBag()->add('confirmation', 'You deleted todo with id ' . $id);
    return $app->redirect('/todo');
});

$app->match('/todo/complete/{id}', function ($id) use ($app) {
    TodoORM::mark_as_completed($app,$id);
    return $app->redirect('/todo');
});

$app->match('/todo/{id}/json', function ($id) use ($app) {
    $todo = TodoORM::get_todo($app,$id);
    return json_encode($todo);
});