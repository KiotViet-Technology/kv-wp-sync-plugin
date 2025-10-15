<?php
namespace Kiotviet\Kiotviet;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;

class HttpClient
{
    public function doRequest($method, $url, $params, $accessToken, $retailer, $headers = [], $bodyType = '')
    {
        $client = new Client(['verify' => false]);

        $options = [];

        $options['headers'] = [
            'Retailer' => $retailer,
            'Authorization' => 'Bearer ' . $accessToken
        ];

        if (sizeof($headers) > 0) {
            $options['headers'] = array_merge($options['headers'], $headers);
        }

        if ($method == 'GET') {
            $options['query'] = $params;
        } else {
            $options['form_params'] = $params;
        }

        if ($bodyType == 'json') {
            $options['json'] = $params;
            if ($method == 'POST') {
                $options['headers'] ['Content-Type'] = 'application/json';
            }
        }

        try {
            $response = $client->request($method, $url, $options);
        } catch (ClientException $exception){
            $response = json_decode($exception->getResponse()->getBody()->getContents(), true);
            return $this->responseError($response, 'Lỗi kết nối tới Kiotviet');
        } catch (GuzzleException $e) {
            return $this->responseError($e->getMessage(), 'Lỗi kết nối tới Kiotviet');
        }

        $response = $response->getBody()->getContents();
        $response = json_decode($response, true);

        return $this->responseSuccess($response);
    }

    public function responseSuccess($data)
    {
        return [
            'status' => 'success',
            'data' => $data,
            'message' => 'Done!'
        ];
    }

    public function responseError($errors, $message, $errorCode = "")
    {
        return [
            'status' => 'error',
            'data' => null,
            'error' => $errors,
            'errorCode' => $errorCode,
            'message' => $message
        ];
    }
}