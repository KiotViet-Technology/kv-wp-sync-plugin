<?php
namespace Kiotviet\Kiotviet;

use GuzzleHttp\Client;
use Kiotviet\KiotvietEndpoint;
use Kiotviet\Kiotviet\HttpClient;

class Authentication
{
    public static $accessToken;
    public static $expireIn;
    public static $retailer;

    public static function getAccessToken($clientId, $clientSecret, $retailer)
    {
        if (empty(self::$accessToken)) {
            $client = new Client(['verify' => false]);

            $options = [];

            $options['headers'] = [
                'Content-Type' => 'application/x-www-form-urlencoded'
            ];

            $options['form_params'] = [
                'scopes' => 'PublicApi.Access',
                'grant_type' => 'client_credentials',
                'client_id' => $clientId,
                'client_secret' => $clientSecret
            ];

            try {
                $response = $client->post(KiotvietEndpoint::GET_TOKEN, $options);
                $response = $response->getBody()->getContents();
                $response = json_decode($response, true);
                self::$accessToken = $response['access_token'];
                self::$expireIn = $response['expires_in'];
                self::$retailer = $retailer;
            } catch (GuzzleException $e) {
                $HttpClient = new HttpClient();
                return $HttpClient->responseError($e->getMessage(), 'Lỗi kết nối tới Kiotviet: ' . $e->getMessage());
            }

            return $response;
        }
    }
}