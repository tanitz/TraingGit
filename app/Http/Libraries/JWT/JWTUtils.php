<?php

namespace App\Http\Libraries\JWT;


// define("PRIVATE_KEY", env('JWT_SECRET'));
define("PRIVATE_KEY", "-----BEGIN RSA PRIVATE KEY-----
MIIBOgIBAAJAYN9RINAXXuNND068tlNYlxTDNPgXFb74AfsiE8JEt6Q8dtMIQi6+
QRWqcbrZyAIPCZqkW9o1DqyQPYGxIifTGwIDAQABAkA/PkxW4cQAPOE4Vy04010I
9ZMj57wahFyh3nS29aOrSAwCZUDbd2a5gztOQf/z9IvALp8CcDNlUwg+qR8jQo7R
AiEAtxveBhJQVe7oI2cKfMVZ9pv6MbNOayt/GmISW4VR49cCIQCHb1EJH46NvzF1
qE3jtLcy7gNwmxOS529i9r8OgVaiXQIhAKrS4KqhYzkIDKEae/oy0t7yXNMJCFuK
1KT0YVPoaKE5AiAv4sgANcwtiiBuvWdsz4TG2SkWM36kPng/wYakFk8PcQIhAK3s
w8q4clitBpDuYAAYtNZIPvDJh9MmJTmmwOcdBkUx
-----END RSA PRIVATE KEY-----");

class JWTUtils
{
     public function generateToken($payload)
     {
          $token = JWT::encode($payload, PRIVATE_KEY, 'HS256');
          return $token;
     }

     public function verifyToken($header)
     {
          $token = null;
          // extract the token from the header
          if (!empty($header)) {
               if (preg_match('/Bearer\s(\S+)/', $header, $matches)) {
                    $token = $matches[1];
               }
          }

          // check if token is null or empty
          if (is_null($token) || empty($token)) {
               return (object)['state' => false, 'msg' => 'Access denied', 'decoded' => []];
          }

          try {
               $decoded = JWT::decode($token, new Key(PRIVATE_KEY, 'HS256'));
               return (object)['state' => true, 'msg' => 'OK', 'decoded' => $decoded];
          } catch (\Exception $e) {
               return (object)['state' => false, 'msg' => $e->getMessage(), 'decoded' => []];
          }
     }
}
