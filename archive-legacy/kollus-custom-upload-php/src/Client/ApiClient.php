<?php

namespace Kollus\Component\Client;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\GuzzleException;
use Kollus\Component\Container;

class ApiClient extends AbstractClient
{
    /**
     * @var HttpClient
     */
    protected $client;

    /**
     * @var string
     */
    protected $apiDomain;

    /**
     * @var string
     */
    protected $uploadApiDomain;

    /**
     * getResponseJSON
     *
     * @param string $method
     * @param string $uri
     * @param array $optParams
     * @param array $postParams
     * @param array $getParams
     * @param int $retry
     * @return mixed
     * @throws ClientException
     * @throws GuzzleException
     */
    private function getResponseJSON(
        $method,
        $uri,
        array $getParams = [],
        array $postParams = [],
        array $optParams = [],
        $retry = 3
    ) {
        if (count($getParams) > 0) {
            $optParams['query'] = $getParams;
        }

        if (count($postParams) > 0) {
            $optParams['form_params'] = $postParams;
        }

        if (!isset($optParams['timeout'])) {
            $optParams['timeout'] = $this->optParams['timeout'];
        }

        do {
            $response = $this->client->request($method, $uri, $optParams);

            $statusCode = $response->getStatusCode();
            $retry --;
        } while ($statusCode != 200 && $retry > 0);

        $jsonResponse = json_decode($response->getBody()->getContents());
        if ($jsonResponse === false || (isset($jsonResponse->error) && (int)$jsonResponse->error === 1)) {
            $message = isset($jsonResponse->message) ? $jsonResponse->message : $response->getBody()->getContents();
            throw new ClientException($message, $statusCode);
        }

        return $jsonResponse;
    }

    /**
     * connect
     *
     * @param mixed|null $client
     * @return self
     * @throws ClientException
     */
    public function connect($client = null)
    {
        if (is_subclass_of($this->serviceAccount, Container\ServiceAccount::class)) {
            throw new ClientException('Service account is required.');
        }

        $serviceAccountKey = $this->serviceAccount->getKey();
        if (empty($serviceAccountKey)) {
            throw new ClientException('Service account key is empty.');
        }

        $apiAccessToken = $this->serviceAccount->getApiAccessToken();
        if (empty($apiAccessToken)) {
            throw new ClientException('Access token is empty.');
        }

        if (is_null($client)) {
            $this->client = new HttpClient([
                'base_uri' => $this->scheme . '://' . $this->getApiDomain() . '/' . $this->version . '/',
                'defaults' => ['allow_redirects' => false],
                'verify' => true,
            ]);
        } else {
            $this->client = $client;
        }

        return $this;
    }

    /**
     * disconnect
     *
     * @return self
     */
    public function disconnect()
    {
        unset($this->client);
        return $this;
    }

    /**
     * @return string
     */
    public function getApiDomain()
    {
        if (empty($this->apiDomain)) {
            return 'api.' . $this->domain;
        }

        return $this->apiDomain;
    }

    /**
     * @param string $apiDomain
     */
    public function setApiDomain($apiDomain)
    {
        $this->apiDomain = $apiDomain;
    }

    /**
     * @return string
     */
    public function getUploadApiDomain()
    {
        return $this->uploadApiDomain;
    }

    /**
     * @param string $uploadApiDomain
     */
    public function setUploadApiDomain($uploadApiDomain)
    {
        $this->uploadApiDomain = $uploadApiDomain;
    }

    /**
     * @param array $getParams
     * @param bool|false $force
     * @return Container\ContainerArray
     * @throws ClientException
     * @throws GuzzleException
     */
    public function getCategories(array $getParams = [], $force = false)
    {
        $getParams['access_token'] = $this->serviceAccount->getApiAccessToken();
        $getParams['force'] = (int)$force;
        $response = $this->getResponseJSON('GET', 'media/category', $getParams);

        if (isset($response->error) && $response->error) {
            throw new ClientException($response->message);
        } elseif (isset($response->result->count) && isset($response->result->items)) {
            if (is_array($response->result->items)) {
                $items = $response->result->items;
            } elseif (isset($response->result->items->item) && is_array($response->result->items->item)) {
                $items = $response->result->items->item;
            } else {
                throw new ClientException('Response is invalid.');
            }
        } else {
            throw new ClientException('Response is invalid.');
        }

        $categories = new Container\ContainerArray();
        foreach ($items as $item) {
            $categories->appendElement(new Container\Category($item));
        }

        return $categories;
    }

    /**
     * @param string|null $categoryKey
     * @param bool|false $useEncryption
     * @param bool|false $isAudioUpload
     * @param string $title
     * @param int $expireTime
     * @return object
     * @throws ClientException
     * @throws GuzzleException
     */
    public function getUploadURLResponse(
        $categoryKey = null,
        $useEncryption = false,
        $isAudioUpload = false,
        $title = '',
        $expireTime = 600
    ) {
        $postParams = [
            'access_token' => $this->serviceAccount->getApiAccessToken(),
            'category_key' => $categoryKey,
            'expire_time' => $expireTime,
            'is_encryption_upload' => (bool)$useEncryption,
            'is_audio_upload' => (bool)$isAudioUpload,
            'title' => (empty($title) ? null : $title)
        ];

        $uploadApiUrl = 'media_auth/upload/create_url.json';
        if ($this->uploadApiDomain) {
            $uploadApiUrl = $this->scheme . '://' . $this->uploadApiDomain . '/api/v1/create_url';
        }

        $response = $this->getResponseJSON('POST', $uploadApiUrl, [], $postParams);
        if (!isset($response->result)) {
            throw new ClientException('Response is invalid.');
        }

        return (object)$response->result;
    }

    /**
     * findUploadFilesByPage
     *
     * @param int $page
     * @param array $getParams
     * @param bool|false $force
     * @return object
     * @throws ClientException
     * @throws GuzzleException
     */
    public function findUploadFilesByPage($page = 1, array $getParams = [], $force = false)
    {
        $getParams['access_token'] = $this->serviceAccount->getApiAccessToken();
        $getParams['page'] = $page;
        $getParams['force'] = (int)$force;
        $response = $this->getResponseJSON('GET', 'media/upload_file', $getParams);

        if (isset($response->result->count) && isset($response->result->items)) {
            if (is_array($response->result->items)) {
                $items = $response->result->items;
            } elseif (isset($response->result->items->item) && is_array($response->result->items->item)) {
                $items = $response->result->items->item;
            } else {
                throw new ClientException('Response is invalid.');
            }
        } else {
            throw new ClientException('Response is invalid.');
        }

        $uploadFiles = new Container\ContainerArray();
        foreach ($items as $item) {
            $uploadFiles->appendElement(new Container\UploadFile($item));
        }

        return (object)[
            'per_page' => $response->result->per_page,
            'count' => $response->result->count,
            'items' => $uploadFiles,
        ];
    }

    /**
     * getUploadFiles
     *
     * @param array $getParams
     * @param bool|false $force
     * @return Container\ContainerArray
     * @throws ClientException
     * @throws GuzzleException
     */
    public function getUploadFiles(array $getParams = [], $force = false)
    {
        if (!isset($getParams['per_page'])) {
            $getParams['per_page'] = 100;
        }

        $page = 1;
        $result = $this->findUploadFilesByPage($page, $getParams, $force);
        $pages = (int)ceil($result->count / $result->per_page);

        /**
         * @var Container\ContainerArray $uploadFiles
         */
        $uploadFiles = $result->items;

        for ($page = 2; $page <= $pages; $page++) {
            $result = $this->findUploadFilesByPage($page, $getParams, $force);
            foreach ($result->items as $item) {
                $uploadFiles->appendElement($item);
            }
        }

        return $uploadFiles;
    }
}
