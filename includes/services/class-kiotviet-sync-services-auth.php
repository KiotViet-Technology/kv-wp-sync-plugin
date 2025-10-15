<?php
/**
 * Created by KiotvietSync.
 *
 * Name: khanht
 * Email: khanh.t@citigo.com.vn
 * Date: 15/02/19
 */
require_once plugin_dir_path(dirname(__FILE__)) . '.././vendor/autoload.php';

use Kiotviet\Kiotviet;
use Kiotviet\KiotvietConfig;
use Kiotviet\Kiotviet\HttpClient;

class Kiotviet_Sync_Service_Auth
{
    private $Kiotviet;
    private $HttpClient;

    public function __construct()
    {
        $this->Kiotviet = new Kiotviet();
        $this->HttpClient = new HttpClient();
    }

    public function getAccessToken()
    {
        $clientId = kiotviet_sync_get_request('client_id', get_option('kiotviet_sync_client_id'));
        $clientSecret = kiotviet_sync_get_request('client_secret', get_option('kiotviet_sync_client_secret'));
        $retailer = kiotviet_sync_get_request('retailer', get_option('kiotviet_sync_retailer'));
        $json = kiotviet_sync_get_request('json', false);
        $kiotVietConfig = new KiotvietConfig($clientId, $clientSecret, $retailer);
        try {
            $accessToken = $this->Kiotviet->getAccessToken($kiotVietConfig);
        } catch (Exception $e) {
            wp_send_json($this->HttpClient->responseError($e->getMessage(), "Thông tin đăng nhập chưa chính xác!", 100));
        }

        $this->saveAccessToken($kiotVietConfig, $accessToken);
        if ($json) {
            wp_send_json($this->HttpClient->responseSuccess($accessToken));
        }
    }

    private function saveAccessToken(KiotvietConfig $kiotVietConfig, $accessToken)
    {
        update_option('kiotviet_sync_client_id', $kiotVietConfig->getClientID());
        update_option('kiotviet_sync_client_secret', $kiotVietConfig->getClientSecret());
        update_option('kiotviet_sync_access_token', $accessToken['access_token']);
        update_option('kiotviet_sync_expires_in', time() + $accessToken['expires_in']);
    }

    public function saveConfigRetailer()
    {
        $retailer = kiotviet_sync_get_request('retailer', "");
        if ($retailer) {
            update_option('kiotviet_sync_retailer', $retailer);
        }
        wp_send_json($this->HttpClient->responseSuccess(true));
    }

    private function checkAccessToken()
    {
        // Check token exits & expires_in
        if (!empty(get_option('kiotviet_sync_access_token')) && !empty(get_option('kiotviet_sync_expires_in'))) {
            if (time() > (get_option('kiotviet_sync_expires_in') - 3600)) {
                return $this->getAccessToken();
            }
        } else {
            return $this->getAccessToken();
        }
    }

    public function doRequest()
    {
        $method = kiotviet_sync_get_request('method');
        $url = kiotviet_sync_get_request('url');
        $params = kiotviet_sync_get_request('params', []);

        if($url === "https://public.kiotapi.com/categories" && $method === "get") {
            return wp_send_json($this->getAllCategory());
        }

        $response = $this->request($method, $url, $params);


        wp_send_json($response);
    }


    public function getAllCategory() {
        $from = 0;
        $size = 100;

        $result = false;
        do {

            $params = [
                "currentItem" => $from,
                "pageSize" => $size,
                "orderBy" => "categoryId",
                "orderDirection" => "asc"
            ];
    
            $response = $this->request("get", "https://public.kiotapi.com/categories", $params);


            if(!$result) {
                $result = $response;
                $result['data']['removedIds'] = [];
            } else {
                if(isset($response['data']['data']) && count($response['data']['data'])) {
                    $result['data']['data'] = array_merge($result['data']['data'], $response['data']['data']);
                } else {
                    break;
                }
            }

            // next
            $from = $from + $size;
        } while(true);
         
        return $result;
    }

    public function request($method, $url, $params = [], $bodyType = '', $headers = [])
    {
        $method = strtolower($method);
        $this->checkAccessToken();
        $accessToken = kiotviet_sync_get_request("accessToken", get_option('kiotviet_sync_access_token'));
        $retailer = kiotviet_sync_get_request("retailer", get_option('kiotviet_sync_retailer'));
        $response = null;
        try {
            if ($bodyType == 'json') {
                $response = $this->Kiotviet->raw($method, $url, $params, $accessToken, $retailer, $headers);
            } else {
                switch ($method) {
                    case 'get':
                        $response = $this->Kiotviet->get($url, $params, $accessToken, $retailer, $headers);
                        break;
                    case 'post':
                        $response = $this->Kiotviet->post($url, $params, $accessToken, $retailer, $headers);
                        break;
                    case 'put':
                        $response = $this->Kiotviet->put($url, $params, $accessToken, $retailer, $headers);
                        break;
                    case 'delete':
                        $response = $this->Kiotviet->delete($url, $params, $accessToken, $retailer, $headers);
                        break;
                    default:
                        break;
                }
            }
        } catch (Exception $exception) {
            $response = $exception->getMessage();
        }

        if (isset($response['error']['responseStatus']['errorCode']) && $response['error']['responseStatus']['errorCode'] == "TokenException") {
            $this->getAccessToken();
            $this->doRequest();
        }
        return $response;
    }
}
