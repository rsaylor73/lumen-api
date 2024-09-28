<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/', function () use ($router) {
    $array = [
        'I find your lack of faith disturbing',
        'Why, you stuck-up, half-witted, scruffy-looking nerf herder!',
        'Do. Or do not. There is no try.',
        'No. I am your father.',
        'Power! Unlimited power!'
    ];
    $key = array_rand($array);
    return $array[$key];
    //return $router->app->version();
});

$router->group(['prefix' => 'api'], function () use ($router) {

    /* v2 */
    $router->get('/v2/servers', ['middleware' => 'auth', 'uses' => 'v2\TestingServerController@listServers']);
    $router->post('/v2/servers', ['middleware' => 'auth', 'uses' => 'v2\TestingServerController@newServer']);
    $router->delete('/v2/servers/{id}', ['middleware' => 'auth', 'uses' => 'v2\TestingServerController@deleteServer']);
    $router->post('/v2/demos', ['middleware' => 'auth', 'uses' => 'v2\DemoServerController@newServer']);
    $router->delete('/v2/demos/{id}', ['middleware' => 'auth', 'uses' => 'v2\DemoServerController@deleteServer']);

    // testing servers
    $router->get('/servers', ['middleware' => 'auth', 'uses' => 'TestingServerController@listServers']);
    $router->post('/servers', ['middleware' => 'auth', 'uses' => 'TestingServerController@newServer']);
    $router->put('/servers/{id}/{action}', ['middleware' => 'auth', 'uses' => 'TestingServerController@modifyServer']);
    $router->delete('/servers/{id}', ['middleware' => 'auth', 'uses' => 'TestingServerController@deleteServer']);
    $router->get('/servers/{id}/log', ['middleware' => 'auth', 'uses' => 'AwsS3LogController@streamAwsLog']);

    // demo servers
    $router->get('/demos', ['middleware' => 'auth', 'uses' => 'DemoServerController@listServers']);
    $router->post('/demos', ['middleware' => 'auth', 'uses' => 'DemoServerController@newServer']);
    $router->put('/demos/{id}/{action}', ['middleware' => 'auth', 'uses' => 'DemoServerController@modifyServer']);

    // rackspace server monitoring
    $router->get('/monitoring/servers', ['middleware' => 'auth', 'uses' => 'ServerMonitorRSController@refreshServers']);
    $router->get('/monitoring/loadbalancers', ['middleware' => 'auth', 'uses' => 'ServerMonitorRSController@listLoadBalancers']);

    // stats
    $router->get('/stats', ['middleware' => 'auth', 'uses' => 'StatsController@stats']);

    // sentry
    $router->get('/sentry', ['middleware' => 'auth', 'uses' => 'SentryController@list']);
    $router->get('/sentry/{id}', ['middleware' => 'auth', 'uses' => 'SentryController@view']);
    $router->post('/sentry', ['middleware' => 'auth', 'uses' => 'SentryController@save']);
    $router->put('/sentry/{id}', ['middleware' => 'auth', 'uses' => 'SentryController@update']);
    $router->delete('/sentry/{id}', ['middleware' => 'auth', 'uses' => 'SentryController@delete']);


    /**
     * Used with Ansible : Some future refactors possible
     */

    /* Testing Servers */
    $router->post('/servers/ansible/updatestatus/{id}', ['middleware' => 'auth', 'uses' => 'TestingServerController@updateServerStatus']);
    $router->post('/servers/ansible/collectlog/{id}', ['middleware' => 'auth', 'uses' => 'AwsS3LogController@collectS3Logs']);
    $router->post('/servers/ansible/checkip/{ip}', ['middleware' => 'auth', 'uses' => 'TestingServerController@checkIpActive']);

    /* Demo Servers */
    $router->post('/demos/ansible/updatestatus/{id}', ['middleware' => 'auth', 'uses' => 'DemoServerController@updateServerStatus']);

});
