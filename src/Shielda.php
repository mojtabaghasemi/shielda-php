<?php

namespace Shielda;

/**
 * Official Shielda Captcha PHP Client.
 */
class Shielda
{
    const VERSION = '1.0.0';
    const DEFAULT_VERIFY_URL = 'https://shielda.ir/api/v1/siteverify';
    const DEFAULT_WIDGET_URL = 'https://shielda.ir/widget/api.js';
    const DEFAULT_INPUT_NAME = 'darban-captcha-response';

    /**
     * @var string
     */
    protected $siteKey;

    /**
     * @var string
     */
    protected $secretKey;

    /**
     * @var array
     */
    protected $options;

    /**
     * @var array|null
     */
    protected $lastResult = null;

    /**
     * @var string|null
     */
    protected $lastError = null;

    /**
     * @param string $siteKey
     * @param string $secretKey
     * @param array $options
     */
    public function __construct($siteKey, $secretKey, array $options = [])
    {
        $this->siteKey = (string) $siteKey;
        $this->secretKey = (string) $secretKey;
        $this->options = array_merge([
            'verify_url' => self::DEFAULT_VERIFY_URL,
            'widget_url' => self::DEFAULT_WIDGET_URL,
            'input_name' => self::DEFAULT_INPUT_NAME,
            'timeout'    => 10,
            'theme'      => 'auto',
            'lang'       => 'fa',
        ], $options);
    }

    /**
     * Verify captcha response token.
     *
     * @param string|null $response If null, automatically read from $_POST
     * @param string|null $remoteIp Optional client IP address
     * @return bool
     */
    public function verify($response = null, $remoteIp = null)
    {
        $result = $this->verifyWithDetails($response, $remoteIp);
        return !empty($result['success']) && $result['success'] === true;
    }

    /**
     * Verify captcha and return full API response array.
     *
     * @param string|null $response
     * @param string|null $remoteIp
     * @return array
     */
    public function verifyWithDetails($response = null, $remoteIp = null)
    {
        if ($response === null) {
            $inputName = $this->options['input_name'];
            $response = isset($_POST[$inputName]) ? $_POST[$inputName] : null;
        }

        if (empty($response)) {
            $this->lastError = 'Missing captcha response token.';
            $this->lastResult = [
                'success' => false,
                'error_codes' => ['missing-input-response'],
            ];
            return $this->lastResult;
        }

        if (empty($this->secretKey)) {
            $this->lastError = 'Missing secret key.';
            $this->lastResult = [
                'success' => false,
                'error_codes' => ['missing-secret-key'],
            ];
            return $this->lastResult;
        }

        if ($remoteIp === null && !empty($_SERVER['REMOTE_ADDR'])) {
            $remoteIp = $_SERVER['REMOTE_ADDR'];
        }

        $payload = [
            'secret'   => $this->secretKey,
            'response' => $response,
        ];

        if (!empty($remoteIp)) {
            $payload['remoteip'] = $remoteIp;
        }

        $verifyUrl = $this->options['verify_url'];
        $timeout = (int) $this->options['timeout'];

        $ch = curl_init($verifyUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json',
            'User-Agent: Shielda-PHP/' . self::VERSION,
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        $responseBody = curl_exec($ch);
        $curlError = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($responseBody === false) {
            $this->lastError = 'cURL Error: ' . $curlError;
            $this->lastResult = [
                'success' => false,
                'error_codes' => ['connection-failed'],
                'curl_error' => $curlError,
            ];
            return $this->lastResult;
        }

        $decoded = json_decode($responseBody, true);
        if (!is_array($decoded)) {
            $this->lastError = 'Invalid JSON response from server (HTTP ' . $httpCode . ')';
            $this->lastResult = [
                'success' => false,
                'error_codes' => ['invalid-json-response'],
                'http_code' => $httpCode,
                'raw_body' => $responseBody,
            ];
            return $this->lastResult;
        }

        $this->lastResult = $decoded;
        $this->lastError = null;
        return $decoded;
    }

    /**
     * Get the last verification result.
     *
     * @return array|null
     */
    public function getLastResult()
    {
        return $this->lastResult;
    }

    /**
     * Get the last error string.
     *
     * @return string|null
     */
    public function getLastError()
    {
        return $this->lastError;
    }

    /**
     * Render HTML script tag.
     *
     * @param array $attributes
     * @return string
     */
    public function getScriptTag(array $attributes = [])
    {
        $attrs = array_merge([
            'src'   => $this->options['widget_url'],
            'async' => true,
            'defer' => true,
        ], $attributes);

        return '<script' . $this->buildAttributes($attrs) . '></script>';
    }

    /**
     * Render HTML container tag for widget.
     *
     * @param array $attributes
     * @return string
     */
    public function getWidgetTag(array $attributes = [])
    {
        $attrs = array_merge([
            'class'         => 'darban-captcha',
            'data-sitekey'  => $this->siteKey,
            'data-theme'    => $this->options['theme'],
            'data-lang'     => $this->options['lang'],
        ], $attributes);

        return '<div' . $this->buildAttributes($attrs) . '></div>';
    }

    /**
     * Render both script and widget tag together.
     *
     * @param array $attributes
     * @return string
     */
    public function render(array $attributes = [])
    {
        return $this->getScriptTag() . "\n" . $this->getWidgetTag($attributes);
    }

    /**
     * @param array $attributes
     * @return string
     */
    protected function buildAttributes(array $attributes)
    {
        $html = [];
        foreach ($attributes as $key => $value) {
            if (is_bool($value)) {
                if ($value) {
                    $html[] = $key;
                }
            } elseif ($value !== null) {
                $html[] = sprintf('%s="%s"', $key, htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'));
            }
        }
        return $html ? ' ' . implode(' ', $html) : '';
    }
}
