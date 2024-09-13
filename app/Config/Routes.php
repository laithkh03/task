<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');

$routes->get('test', 'AuthController::anotherTest');

$routes->post('auth/register', 'AuthController::register');
$routes->post('auth/login', 'AuthController::login');
$routes->post('tasks', 'TaskController::createTask', ['filter' => 'auth']);
$routes->get('tasks', 'TaskController::getAllTasks', ['filter' => 'auth']);
$routes->get('tasks/(:num)', 'TaskController::getTask/$1', ['filter' => 'auth']);
$routes->put('tasks/(:num)', 'TaskController::updateTask/$1', ['filter' => 'auth']);
$routes->delete('tasks/(:num)', 'TaskController::deleteTask/$1', ['filter' => 'auth']);