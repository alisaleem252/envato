<?php
/**
 * Envato API class (v3, personal token / OAuth bearer auth).
 *
 * The old http://marketplace.envato.com/api/edge/ (username + API key) API is
 * retired. This class talks to https://api.envato.com using a personal token
 * created at https://build.envato.com/create-token/ (or an OAuth access token).
 *
 * Backward compatibility: set_api_set('popular:codecanyon') and
 * set_api_set('verify-purchase:CODE') followed by request() still work and are
 * mapped to the new endpoints. set_api_key() is an alias of set_token().
 */

class envatoAPI
{
    private $base_url   = 'https://api.envato.com';
    private $token;
    private $api_set;
    private $username;                 // kept for backward compatibility, unused
    private $timeout    = 15;
    private $user_agent = 'envatoAPI PHP class (github.com/alisaleem252/envato)';
    private $cache_dir  = null;        // set with set_cache() to enable caching
    private $cache_ttl  = 300;

    /** Last HTTP status code and error message, for debugging. */
    public $last_status = 0;
    public $last_error  = '';

    /* ---------------------------------------------------------------- setup */

    public function __construct($token = null)
    {
        if ($token) {
            $this->token = $token;
        } elseif (getenv('ENVATO_TOKEN')) {
            $this->token = getenv('ENVATO_TOKEN');
        }
    }

    public function set_token($token)       { $this->token = $token; }
    public function set_api_key($api_key)   { $this->token = $api_key; } // legacy alias
    public function set_username($username) { $this->username = $username; } // legacy, ignored
    public function set_api_set($api_set)   { $this->api_set = $api_set; }
    public function set_api_url($url)       { $this->base_url = rtrim($url, '/'); }
    public function get_api_url()           { return $this->base_url; }
    public function set_timeout($seconds)   { $this->timeout = (int) $seconds; }
    public function set_user_agent($ua)     { $this->user_agent = $ua; }

    /** Enable simple file caching of GET responses. */
    public function set_cache($dir, $ttl = 300)
    {
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        $this->cache_dir = rtrim($dir, '/');
        $this->cache_ttl = (int) $ttl;
    }

    /* ------------------------------------------------------------ core call */

    /**
     * Perform a GET request against any API path.
     *
     * @param  string $path   e.g. '/v3/market/author/sale'
     * @param  array  $params query string parameters
     * @return array|false    decoded JSON, or false on error (see $last_error)
     */
    public function get($path, array $params = array())
    {
        $url = $this->base_url . '/' . ltrim($path, '/');
        if ($params) {
            $url .= '?' . http_build_query($params);
        }

        $cache_file = null;
        if ($this->cache_dir) {
            $cache_file = $this->cache_dir . '/' . md5($this->token . $url) . '.json';
            if (is_file($cache_file) && (time() - filemtime($cache_file)) < $this->cache_ttl) {
                return json_decode(file_get_contents($cache_file), true);
            }
        }

        $data = $this->http_get($url);

        if ($data !== false && $cache_file) {
            @file_put_contents($cache_file, json_encode($data), LOCK_EX);
        }
        return $data;
    }

    private function http_get($url, $retry = true)
    {
        $this->last_status = 0;
        $this->last_error  = '';

        if (empty($this->token)) {
            $this->last_error = 'No API token set. Use set_token() or the ENVATO_TOKEN environment variable.';
            return false;
        }

        $headers = array(
            'Authorization: Bearer ' . $this->token,
            'User-Agent: ' . $this->user_agent,
            'Accept: application/json',
        );

        $ch = curl_init($url);
        curl_setopt_array($ch, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_HEADER         => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ));
        $response = curl_exec($ch);

        if ($response === false) {
            $this->last_error = 'cURL error: ' . curl_error($ch);
            curl_close($ch);
            return false;
        }

        $status      = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $header_size = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        curl_close($ch);

        $raw_headers = substr($response, 0, $header_size);
        $body        = substr($response, $header_size);
        $this->last_status = $status;

        // Rate limited: wait for Retry-After once, then try again.
        if ($status === 429 && $retry) {
            $wait = 2;
            if (preg_match('/^Retry-After:\s*(\d+)/mi', $raw_headers, $m)) {
                $wait = min((int) $m[1], 10);
            }
            sleep($wait);
            return $this->http_get($url, false);
        }

        $json = json_decode($body, true);

        if ($status < 200 || $status >= 300) {
            $msg = is_array($json) ? (isset($json['error']) ? $json['error'] : (isset($json['description']) ? $json['description'] : '')) : '';
            $this->last_error = 'HTTP ' . $status . ($msg ? ': ' . (is_string($msg) ? $msg : json_encode($msg)) : '');
            return false;
        }

        if ($json === null) {
            $this->last_error = 'Invalid JSON in API response.';
            return false;
        }
        return $json;
    }

    /* --------------------------------------------------------- author sales */

    /** Look up a sale by purchase code. Returns sale array, or false if invalid. */
    public function verify_purchase($purchase_code)
    {
        $purchase_code = trim($purchase_code);
        if (!preg_match('/^[a-f0-9]{8}-([a-f0-9]{4}-){3}[a-f0-9]{12}$/i', $purchase_code)) {
            $this->last_error = 'Malformed purchase code.';
            return false;
        }
        return $this->get('/v3/market/author/sale', array('code' => $purchase_code));
    }

    /** Verify code and check it belongs to the given item id. */
    public function verify_purchase_for_item($purchase_code, $item_id)
    {
        $sale = $this->verify_purchase($purchase_code);
        if ($sale && isset($sale['item']['id']) && (string) $sale['item']['id'] === (string) $item_id) {
            return $sale;
        }
        return false;
    }

    /** True if the support period of a sale (from verify_purchase) is still active. */
    public function is_supported($sale)
    {
        return !empty($sale['supported_until']) && strtotime($sale['supported_until']) > time();
    }

    /** Paginated list of the author's sales (page starts at 1). */
    public function author_sales($page = 1)
    {
        return $this->get('/v3/market/author/sales', array('page' => (int) $page));
    }

    /** Walk through all pages of author sales (max_pages guards against runaway loops). */
    public function author_sales_all($max_pages = 50)
    {
        $all = array();
        for ($page = 1; $page <= $max_pages; $page++) {
            $res = $this->author_sales($page);
            if (!$res) {
                break;
            }
            $all = array_merge($all, $res);
        }
        return $all;
    }

    /* ---------------------------------------------------------------- buyer */

    public function buyer_purchase($purchase_code)
    {
        return $this->get('/v3/market/buyer/purchase', array('code' => $purchase_code));
    }

    public function buyer_purchases()
    {
        return $this->get('/v3/market/buyer/list-purchases');
    }

    /** Download links for a purchased item (by item id or purchase code). */
    public function buyer_download($item_id = null, $purchase_code = null)
    {
        $params = array();
        if ($item_id)       { $params['item_id'] = $item_id; }
        if ($purchase_code) { $params['purchase_code'] = $purchase_code; }
        return $this->get('/v3/market/buyer/download', $params);
    }

    /* ------------------------------------------------------------- catalogue */

    public function item($item_id)
    {
        return $this->get('/v3/market/catalog/item', array('id' => $item_id));
    }

    public function collection($collection_id)
    {
        return $this->get('/v3/market/catalog/collection', array('id' => $collection_id));
    }

    /**
     * Search items. $params examples: site (e.g. 'codecanyon.net'), term,
     * category, sort_by, page, page_size.
     */
    public function search_items(array $params)
    {
        return $this->get('/v1/discovery/search/search/item', $params);
    }

    /** Popular items for a marketplace, e.g. popular('codecanyon'). */
    public function popular($site)
    {
        return $this->get('/v1/market/popular:' . rawurlencode($site) . '.json');
    }

    /* ----------------------------------------------------------------- user */

    public function username()
    {
        return $this->get('/v1/market/private/user/username.json');
    }

    public function email()
    {
        return $this->get('/v1/market/private/user/email.json');
    }

    public function account()
    {
        return $this->get('/v1/market/private/user/account.json');
    }

    /* ------------------------------------------------- legacy request() API */

    /**
     * Legacy entry point. Supports 'popular:site' and 'verify-purchase:CODE'
     * set via set_api_set(); anything else is treated as a raw path.
     * Returns the decoded array, or an error string like the original class.
     */
    public function request()
    {
        $set = (string) $this->api_set;
        $data = false;

        if (strpos($set, 'popular:') === 0) {
            $data = $this->popular(substr($set, 8));
        } elseif (strpos($set, 'verify-purchase:') === 0) {
            $sale = $this->verify_purchase(substr($set, 16));
            // Mimic the old response shape used by earlier verify-purchase.php.
            $data = $sale ? array('verify-purchase' => array(
                'item_id'    => isset($sale['item']['id']) ? $sale['item']['id'] : null,
                'buyer'      => isset($sale['buyer']) ? $sale['buyer'] : null,
                'created_at' => isset($sale['sold_at']) ? $sale['sold_at'] : null,
                'licence'    => isset($sale['license']) ? $sale['license'] : null,
            )) : false;
        } elseif ($set !== '') {
            $data = $this->get($set);
        }

        return $data !== false ? $data : 'We are unable to retrieve any information from the API. ' . $this->last_error;
    }
}
