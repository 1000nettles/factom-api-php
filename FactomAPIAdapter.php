<?php

/**
 * Factom-API-PHP : A simple PHP wrapper for Factom v2 API
 *
 * @category Blockchain
 * @package  Factom-API-PHP
 * @author   Corey S <1000nettles@gmail.com>
 * @license  MIT License
 * @version  0.1.0
 * @link     https://github.com/1000nettles/factom-api-php
 */
class FactomAPIAdapter
{

    /**
     * The JSON RPC spec that the API uses
     */
    const JSON_RPC              = '2.0';

    /**
     * The "ID" param provided in all requests to the API
     */
    const REQUEST_ID            = 0;

    /**
     * The header content type in all requests to the API
     */
    const HEADER_CONTENT_TYPE   = 'text/plain';

    /**
     * The generic error if cannot load server properly
     */
    const BLANK_PAGE_ERROR      = 'Page not found';

    /**
     * The URL for all API requests
     *
     * @var null|string
     */
    protected $url;

    /**
     * Path to the certificate file for using factomd over TLS
     *
     * @var null
     */
    protected $cert;

    /**
     * The provided username for interacting with factomd
     * Optional
     *
     * @var null
     */
    protected $username;

    /**
     * The provided password for interacting with factomd
     * Optional
     *
     * @var null
     */
    protected $password;

    /**
     * FactomAPIAdapter constructor. FactomAPIAdapter constructor.
     * Setup the object with params specified
     *
     * @throws \RuntimeException When the cURL lib isn't loaded
     * @throws \InvalidArgumentException When provided data is not complete to
     * instantiate the adapter
     *
     * @param $host
     * @param null $cert
     * @param null $username
     * @param null $password
     */
    public function __construct(
        $host,
        $cert = null,
        $username = null,
        $password = null
    )
    {
        if (!function_exists('curl_init')) {
            throw new \RuntimeException(
                'The Factom API integration requires the cURL extension,
                please see http://curl.haxx.se/docs/install.html to install it'
            );
        }

        if (!$host) {
            throw new \InvalidArgumentException(
                'The Factom API requires a HOST defined'
            );
        }

        if ($cert) {
            preg_match('/^(https:\/\/)/i', $host, $matches);
            if (empty($matches)) {
                throw new \InvalidArgumentException(
                    'When defining a certificate, you must ensure the host is
                    using HTTPS'
                );
            }

            if (!file_exists($cert)) {
                throw new \InvalidArgumentException(
                    'Can\'t find provided certificate file'
                );
            }
        }

        if ($username && !$password) {
            throw new \InvalidArgumentException(
                'You must provide a password with a username.'
            );
        } else if (!$username && $password) {
            throw new \InvalidArgumentException(
                'You must provide a username with a password.'
            );
        }

        $this->cert = $cert;
        $this->username = $username;
        $this->password = $password;
        $this->url = $host;
    }

    /**
     * Initialize the cURL request with all requested params
     * Within the CLI, this would look similar to:
     * curl -X POST --data-binary '{"jsonrpc": "2.0", "id": 0,
     * "method": "transaction",
     * "params":{"hash":"64251aa63e011f803c883acf2342d784b405afa59e24d9c5506c84
     * f6c91bf18b"}}' -H 'content-type:text/plain;' http://localhost:8088/v2
     *
     * @param $actionName
     * @param $method
     * @param array $binaryDataParams
     * @param array $customOptions
     * @return array
     * @throws Exception - ensures we are passing in viable methods
     */
    function gatherCurlOptions(
        $actionName,
        $method,
        $binaryDataParams = [],
        $customOptions = []
    )
    {
        if (!in_array(strtoupper($method), array('GET', 'POST'))) {
            throw new \Exception('Supplied method must match GET or POST');
        }

        $data = array();
        $data['jsonrpc'] = self::JSON_RPC;
        $data['id'] = self::REQUEST_ID;
        $data['method'] = $actionName;
        $data['params'] = $binaryDataParams;

        $headers = array('Content-Type: ' . self::HEADER_CONTENT_TYPE);

        $finalOptions = $customOptions + array(
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_HEADER => false,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_URL => $this->url,
            CURLOPT_POST => strtoupper($method) === 'POST' ? 1 : 0,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,

            /*
             * Auth related cURL params
             */
            CURLOPT_USERPWD => $this->username && $this->password
                ? "$this->username:$this->password" : false,
            CURLOPT_HTTPAUTH => $this->username && $this->password
                ? CURLAUTH_ANY : false,

            /*
             * Cert / SSL related cURL params
             */
            CURLOPT_SSL_VERIFYPEER => false
        );

        return $finalOptions;
    }

    /**
     * Call the requested endpoint.
     *
     * @param $actionName
     * @param $method
     * @param array $binaryDataParams
     * @param array $curlOptions
     * @param bool $asObj
     * @return object|string
     * @throws Exception
     */
    public function call(
        $actionName,
        $method,
        $binaryDataParams = [],
        $curlOptions = [],
        $asObj = true
    )
    {
        $curlOptions = $this->gatherCurlOptions(
            $actionName,
            $method,
            $binaryDataParams,
            $curlOptions
        );

        $curl = curl_init();
        curl_setopt_array($curl, $curlOptions);
        $result = curl_exec($curl);

        $error = curl_error($curl);

        if (
            !$error && strtoupper($result)
            === strtoupper(self::BLANK_PAGE_ERROR)
        ) {
            $error = self::BLANK_PAGE_ERROR;
        }

        if ($error) {
            curl_close($curl);
            throw new \Exception(
                'Received error "' . $error . '" when hitting "' . $actionName
                . '" within the Factom API'
            );
        } else if (!$result) {
            throw new \Exception(
                'Received an empty response when hitting "' . $actionName .
                '" within the Factom API'
            );
        }

        if ($asObj) {
            $rawResponse = json_decode($result);
            $finalResult = (Object) $rawResponse;
        } else {
            $finalResult = (String) $result;
        }

        curl_close($curl);

        return $finalResult;
    }

}
