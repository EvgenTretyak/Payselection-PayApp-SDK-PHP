<?php

namespace PaySelection;

use PaySelection\Exceptions\BadTypeException;

/**
 *
 */
class BaseHook
{
    /**
     * @var array
     */
    protected array   $request;
    protected string  $siteId;
    protected string  $secretKey;
    private   array   $configParams;

    /**
     *
     * @throws BadTypeException
     */
    public function __construct(array $request, string $filePath = null)
    {
        $this->loadConfiguration($filePath);
        $this->siteId    = $this->configParams['site_id'];
        $this->secretKey = $this->configParams['secret_key'];

        $headers = getallheaders();
        if (
            empty($request) ||
            empty($headers['X-SITE-ID']) ||
            $this->siteId != $headers['X-SITE-ID'] ||
            empty($headers['X-WEBHOOK-SIGNATURE'])
        )
            throw new BadTypeException('Not found');

        // Check signature
        $signBody = 'POST' . PHP_EOL .
            '/' . PHP_EOL .
            $this->siteId . PHP_EOL .
            json_encode($request);

        if ($headers['X-WEBHOOK-SIGNATURE'] !== self::getSignature($signBody, $this->secretKey))
            throw new BadTypeException('Signature error');

        $request = json_decode($request, true);
        //$this->request = $request;
        $this->fill();
    }

    public function getRequest(): array
    {
        return $this->request;
    }

    protected static function getSignature(string $body, string $secretKey): string
    {
        if (empty($body)) {
            return ";";
        }

        return hash_hmac("sha256", $body, $secretKey);
    }

    /**
     *
     */
    private function fill()
    {
        $modelFields = get_object_vars($this);
        foreach ($modelFields as $key => $field) {
            $requestKey = ucfirst($key);
            if (isset($this->request[$requestKey])) {
                $this->$key = $this->request[$requestKey];
            }
        }
    }

    public function loadConfiguration($filePath = null): BaseHook
    {
        if ($filePath) {
            $data = file_get_contents($filePath);
        } else {
            $data = file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . "configuration.json");
        }

        $paramsArray = json_decode($data, true);
        $this->configParams = $paramsArray;

        return $this;
    }
}