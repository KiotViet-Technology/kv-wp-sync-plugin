<?php
namespace Kiotviet;

use Kiotviet\Kiotviet\Authentication;
use Kiotviet\Kiotviet\HttpClient;

class Kiotviet
{
    const VERSION = '1.0.0';
    protected $accessToken;
    protected $retailer;
    protected $client;

    public function __construct()
    {
        $this->client = new HttpClient();
    }

    public function getAccessToken(KiotvietConfig $config)
    {
        list($clientId, $clientSecret, $retailer) = $config->getConfig();
        $accessToken = Authentication::getAccessToken($clientId, $clientSecret, $retailer);
        return $accessToken;
    }

    public function getRetailer()
    {
        return Authentication::$retailer;
    }

    public function get($url, array $params, $accessToken, $retailer, $headers = [])
    {
        return $this->client->doRequest('GET', $url, $params, $accessToken, $retailer, $headers);
    }

    public function post($url, array $params, $accessToken, $retailer, $headers = [])
    {
        return $this->client->doRequest('POST', $url, $params, $accessToken, $retailer, $headers);
    }

    public function put($url, array $params, $accessToken, $retailer, $headers = [])
    {
        return $this->client->doRequest('PUT', $url, $params, $accessToken, $retailer, $headers);
    }

    public function delete($url, array $params, $accessToken, $retailer, $headers = [])
    {
        return $this->client->doRequest('DELETE', $url, $params, $accessToken, $retailer, $headers);
    }

    public function raw($method, $url, array $params, $accessToken, $retailer, $headers = [])
    {
        return $this->client->doRequest($method, $url, $params, $accessToken, $retailer, $headers, 'json');
    }

}