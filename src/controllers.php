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
        $sql = "SELECT * FROM users WHERE username = '$username' and password = '$password'";
        $user = $app['db']->fetchAssoc($sql);

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
        $sql = "SELECT * FROM todos WHERE id = '$id'";
        $todo = $app['db']->fetchAssoc($sql);

        return $app['twig']->render('todo.html', [
            'todo' => $todo,
        ]);
    } else {

        if(isset($_GET['p']) && is_numeric($_GET['p']) ){
            $page_number = intval( $_GET['p'] ) ;
        } else {
            $page_number = 1;
        }
              
        $page_size = 2;
        $offset = ($page_number-1) * $page_size;

        $sql_total =  "SELECT count(*) as total FROM todos WHERE user_id = '${user['id']}'";
        $total_todos = intval($app['db']->fetchAssoc($sql_total)['total']);
    
        $sql = "SELECT * FROM todos WHERE user_id = '${user['id']}' ORDER BY id LIMIT $offset, $page_size ";
        $todos = $app['db']->fetchAll($sql);

        $previous = null;
        if(($page_number - 1)>0){
            $previous = '?p=' . ($page_number - 1);
        }

        $next = null;
        if($total_todos > ($page_number * $page_size)){
            $next = '?p=' . ($page_number + 1);
        }

        $number_of_pages = ceil($total_todos / $page_size);

        $pages = array();
        for($i = 1; $i <= $number_of_pages; $i++ ){
            $link_text =$i;
            $class = "";
            if($i == $page_number){
                $class = "active";
            }
            array_push($pages, array('link_text'=> $link_text , 'link' => '?p=' . $i, 'class' => $class));
        }
     
        return $app['twig']->render('todos.html', [
            'todos' => $todos,
            'next' => $next,
            'previous' => $previous,
            'pages' => $pages
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

    $sql = "INSERT INTO todos (user_id, description) VALUES ('$user_id', '$description')";
    $app['db']->executeUpdate($sql);

    return $app->redirect('/todo');
});


$app->match('/todo/delete/{id}', function ($id) use ($app) {

    $sql = "DELETE FROM todos WHERE id = '$id'";
    $app['db']->executeUpdate($sql);

    return $app->redirect('/todo');
});