<?php

use Illuminate\Routing\Router;

$router->group(['prefix' =>'/itenant/v1'], function (Router $router) {
    $router->apiCrud([
      'module' => 'itenant',
      'prefix' => 'domains',
      'controller' => 'DomainApiController',
      'permission' => 'itenant.domains',
      //'middleware' => ['create' => [], 'index' => [], 'show' => [], 'update' => [], 'delete' => [], 'restore' => []],
      // 'customRoutes' => [ // Include custom routes if needed
      //  [
      //    'method' => 'post', // get,post,put....
      //    'path' => '/some-path', // Route Path
      //    'uses' => 'ControllerMethodName', //Name of the controller method to use
      //    'middleware' => [] // if not set up middleware, auth:api will be the default
      //  ]
      // ]
    ]);
    $router->apiCrud([
      'module' => 'itenant',
      'prefix' => 'organizations',
      'controller' => 'OrganizationApiController',
      'permission' => 'itenant.organizations',
      'middleware' => ['create' => [],'delete' => []],
      'customRoutes' => [ // Include custom routes if needed
        [
          'method' => 'post',
          'path' => '/manage-modules', // Route Path
          'uses' => 'manageModules', //Name of the controller method to use
          'middleware' => ['auth:api'] // if not set up middleware, auth:api will be the default
        ],
        [
          'method' => 'post',
          'path' => '/update-layout', // Route Path
          'uses' => 'updateLayout', //Name of the controller method to use
          'middleware' => ['auth:api'] // if not set up middleware, auth:api will be the default
        ],
      ]
    ]);
    $router->apiCrud([
      'module' => 'itenant',
      'prefix' => 'userorganization',
      'controller' => 'UserOrganizationApiController',
      'permission' => 'itenant.userorganization',
      //'middleware' => ['create' => [], 'index' => [], 'show' => [], 'update' => [], 'delete' => [], 'restore' => []],
      // 'customRoutes' => [ // Include custom routes if needed
      //  [
      //    'method' => 'post', // get,post,put....
      //    'path' => '/some-path', // Route Path
      //    'uses' => 'ControllerMethodName', //Name of the controller method to use
      //    'middleware' => [] // if not set up middleware, auth:api will be the default
      //  ]
      // ]
    ]);
    $router->apiCrud([
      'module' => 'itenant',
      'prefix' => 'categories',
      'controller' => 'CategoryApiController',
      'permission' => 'itenant.categories',
      'middleware' => ['index' => [], 'show' => []],
      // 'customRoutes' => [ // Include custom routes if needed
      //  [
      //    'method' => 'post', // get,post,put....
      //    'path' => '/some-path', // Route Path
      //    'uses' => 'ControllerMethodName', //Name of the controller method to use
      //    'middleware' => [] // if not set up middleware, auth:api will be the default
      //  ]
      // ]
    ]);
// append




});
