<?php

namespace Wave\Services\Cors;

/**
 * CORS handler:
 * if request is made by a web browser the cors headers must be return
 * in response of a "preflight" of the origin (an OPTIONS request)
 */
class CorsService {
  public static function handle(string $endpoint) {
    if (isset($_SERVER['HTTP_ORIGIN']) && $_SERVER['HTTP_ORIGIN'] != '') {
      $allowedOrigins = [
        '(http(s)://)?localhost:3000',
        '(http(s)://)?wave.com',
      ];
      
      $allowedMethods = [
        'auth'    => [
          'POST',
          'PUT',
          'DELETE',
        ],
        'user'    => [
          'POST',
          'GET',
          'PUT',
          'DELETE',
        ],
        'contact' => [
          'POST',
          'GET',
          'PUT',
          'DELETE',
        ],
        'group'   => [
          'POST',
          'GET',
          'PATCH',
          'PUT',
          'DELETE',
        ],
        'member'  => [
          'POST',
          'GET',
          'PUT',
          'DELETE',
        ],
        'message' => [
          'POST',
          'GET',
          'PUT',
          'DELETE',
        ],
      ];
      
      $allowedHeaders = [
        'auth'    => [
          'username',
          'password',
          'device',
          'token',
        ],
        'user'    => [
          'username',
          'password',
          'name',
          'surname',
          'phone',
          'picture',
          'theme',
          'language',
          'token',
        ],
        'contact' => [
          'token',
          'user',
          'directive',
        ],
        'group'   => [
          'token',
          'name',
          'info',
          'picture',
          'users',
          'group',
          'directive',
        ],
        'member'  => [
          'token',
          'group',
          'user',
          'permission',
        ],
        'message' => [
          'token',
          'group',
          'contact',
          'from',
          'to',
          'content',
          'text',
          'media',
          'pinned',
          'message',
        ],
      ];
      
      foreach ($allowedOrigins as $allowedOrigin) {
        if (preg_match('#' . $allowedOrigin . '#', $_SERVER['HTTP_ORIGIN'])) {
          header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
          header(
            'Access-Control-Allow-Methods: OPTIONS, ' . join(', ', $allowedMethods[$endpoint])
          );
          header('Access-Control-Max-Age: 1000');
          header('Access-Control-Allow-Headers: ' . join(', ', $allowedHeaders[$endpoint]));
          break;
        }
      }
    }
  }
}