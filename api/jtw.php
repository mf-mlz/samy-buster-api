<?php
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

function generateSessionToken($user_data, $secret)
{
    $token = JWT::encode($user_data, $secret, 'HS256');
    return $token;
}

function verifyJWT($jwt, $secret_key, $allowed_algs)
{
    try {
        $decoded = JWT::decode($jwt, new Key($secret_key, 'HS256'));
        return $decoded;
    } catch (Exception $e) {
        return null; // Token inválido
    }
}

function getTokenFromHeader()
{
    $headers = apache_request_headers();
    if (isset($headers['Authorization'])) {
        $authorizationHeader = $headers['Authorization'];
        if (preg_match('/Bearer\s+(.*)$/i', $authorizationHeader, $matches)) {
            return $matches[1];
        }
    }
    return null;
}

?>