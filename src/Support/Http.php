<?php
/**
 * Http.php
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @author    overtrue <i@overtrue.me>
 * @author    Mouyong <my24251325@gmail.com>
 * @copyright 2015 overtrue <i@overtrue.me>
 * @link      https://github.com/overtrue
 * @link      http://overtrue.me
 */

namespace Young\Support;

class Http
{
    use HasAttributes;

    /**
     * Constants for available HTTP methods
     */
    const GET     = 'GET';
    const POST    = 'POST';
    const PUT     = 'PUT';
    const PATCH   = 'PATCH';
    const DELETE  = 'DELETE';

    /**
     * @var resource handle
     */
    private $curl;

    private $response;

    private $isApi;

    /**
     * Create the cURL resource
     *
     * @param bool $isApi
     */
    public function __construct($isApi = true)
    {
        $this->curl = curl_init();
        $this->isApi = $isApi;
    }

    /**
     * Clean up the cURL handle
     */
    public function __destruct()
    {
        if (is_resource($this->curl)) {
            curl_close($this->curl);
        }
    }

    /**
     * Get the cURL handle
     *
     * @return resource cURL handle
     */
    public function getCurl()
    {
        return $this->curl;
    }

    /**
     * Make a HTTP GET request
     *
     * @param string $url
     * @param array  $params
     * @param array  $options
     *
     * @return mixed
     */
    public function get($url, $params = array(), $options = array())
    {
        $url .= (stripos($url, '?') ? '&' : '?') . http_build_query($params);
        return $this->request($url, self::GET, array(), $options);
    }

    /**
     * Make a HTTP POST request
     *
     * @param string $url
     * @param array  $params
     * @param array  $options
     *
     * @return mixed
     */
    public function post($url, $params = array(), $options = array())
    {
        return $this->request($url, self::POST, $params, $options);
    }

    /**
     * Make a HTTP PUT request
     *
     * @param string $url
     * @param array  $params
     * @param array  $options
     *
     * @return mixed
     */
    public function put($url, $params = array(), $options = array())
    {
        return $this->request($url, self::PUT, $params, $options);
    }
    /**
     * Make a HTTP PATCH request
     *
     * @param string $url
     * @param array  $params
     * @param array  $options
     *
     * @return mixed
     */
    public function patch($url, $params = array(), $options = array())
    {
        return $this->request($url, self::PATCH, $params, $options);
    }

    /**
     * Make a HTTP DELETE request
     *
     * @param string $url
     * @param array  $params
     * @param array  $options
     *
     * @return mixed
     */
    public function delete($url, $params = array(), $options = array())
    {
        $url .= (stripos($url, '?') ? '&' : '?') . http_build_query($params);
        return $this->request($url, self::DELETE, array(), $options);
    }

    /**
     * Make a HTTP request
     *
     * @param string $url
     * @param string $method
     * @param array  $params
     * @param array  $options
     *
     * @return mixed
     */
    protected function request($url, $method = self::GET, $params = array(), $options = array())
    {
        if ($method === self::GET || $method === self::DELETE) {
            $url .= (stripos($url, '?') ? '&' : '?').http_build_query($params);
            $params = array();
        }

        curl_setopt($this->curl, CURLOPT_HEADER, 1);

        // Show page
        if ($this->isApi) {
            curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, 1);
        }

        curl_setopt($this->curl, CURLOPT_URL, $url);
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($this->curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);

        curl_setopt($this->curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER, 0);

        curl_setopt($this->curl, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($this->curl, CURLOPT_TIMEOUT, 30);

        curl_setopt($this->curl, CURLOPT_USERAGENT, 'PHP HTTP Client');

        // Check for files
        if (isset($options['files']) && count($options['files'])) {
            foreach($options['files'] as $index => $file) {
                $params[$index] = $this->createCurlFile($file);
            }
            phpversion() < '5.5' || curl_setopt($this->curl, CURLOPT_SAFE_UPLOAD, false);

            curl_setopt($this->curl, CURLOPT_POST, 1);
            curl_setopt($this->curl, CURLOPT_POSTFIELDS, $params);
        } else {
            if (isset($options['json'])) {
                $params = JSON::encode($params);
                $options['headers'][] = 'content-type:application/json';
            }
            curl_setopt($this->curl, CURLOPT_POSTFIELDS, $params);
        }

        // Check for custom headers
        if (isset($options['headers']) && count($options['headers'])) {
            curl_setopt($this->curl, CURLOPT_HTTPHEADER, $options['headers']);
        }

        // Check for basic auth
        if (isset($options['auth']['type']) && "basic" === $options['auth']['type']) {
            curl_setopt($this->curl, CURLOPT_USERPWD, $options['auth']['username'] . ':' . $options['auth']['password']);
        }
        $response = $this->doCurl();

        $headerSize = $response['curl_info']['header_size'];
        $header     = substr($response['response'], 0, $headerSize);
        $body       = substr($response['response'], $headerSize);
        $results = array(
            'curl_info'    => $response['curl_info'],
            'content_type' => $response['curl_info']['content_type'],
            'status'       => $response['curl_info']['http_code'],
            'headers'      => $this->splitHeaders($header),
            'data'         => $body,
        );

        $this->attributes = $results;

        return $this;
    }

    /**
     * make cURL file
     *
     * @param string $filename
     *
     * @return \CURLFile|string
     */
    protected function createCurlFile($filename)
    {
        if (function_exists('curl_file_create')) {
            return curl_file_create($filename);
        }
        return "@$filename;filename=" . basename($filename);
    }

    /**
     * Split the HTTP headers
     *
     * @param string $rawHeaders
     *
     * @return array
     */
    protected function splitHeaders($rawHeaders)
    {
        $headers = array();
        $headerLines     = explode("\n", $rawHeaders);
        $headers['HTTP'] = array_shift($headerLines);
        foreach ($headerLines as $line) {
            $line = trim($line);

            if (empty($line)) {
                continue;
            }

            list($key, $value) = array_filter(explode(":", $line, 2));

            $headers[trim($key)] = trim($value);
        }
        return $headers;
    }

    public function getContents($key = 'data')
    {
        return $this->getAttribute($key);
    }

    public function getHeaders()
    {
        return $this->getAttribute('headers');
    }

    public function getStatus()
    {
        return $this->getAttribute('status');
    }

    public function getContentType()
    {
        return $this->getAttribute('content_type');
    }

    public function getCurlInfo()
    {
        return $this->getAttribute('curl_info');
    }

    /**
     * Perform the Curl request
     *
     * @return array
     */
    protected function doCurl()
    {
        $response = curl_exec($this->curl);
        $curlInfo = curl_getinfo($this->curl);
        $results = array(
            'curl_info' => $curlInfo,
            'response'  => $response,
        );
        return $results;
    }
}