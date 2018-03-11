<?php
/**
 * Caching API call results according to object group ETag
 * 
 * @author test99555672 <test99555672@gmail.com>
 * @version 1.0
 */

namespace App\Models\Git;

use Cache;

class GitCache {
    const STATUS_UNKNOWN = -1;
    const STATUS_DIDNT_HIT = 0;
    const STATUS_HIT = 1;
    
    
    /**
     * Storing an ETag and status for each group
     * 
     * @var array
     */
    private $_cacheHits = [
    ];
    
    
    /**
     * Headers array to use with GitHub API
     * 
     * @var array|null
     */
    private $_headers = "";
    
    /**
     * Initializes caching system
     * 
     * @param array $groups an array with group names. Names can be single words or multi_words
     * @param array $headers headers to use when making an API call for ETag
     */
    public function __construct(array $groups, array $headers) {
        //for every group initialize its own data array
        foreach ($groups as $v) {
            $this->_cacheHits[$v] = [
                'status' => self::STATUS_UNKNOWN,
                'etag' => "git_etag_{$v}",
            ];
        }
        
        $this->_headers = $headers;
    }
    
    /**
     * 
     * @param string $group
     * @param string $key
     * @param type $default
     * 
     * @return mixed
     */
    public function get(string $group, string $key, $default) 
    {
        //if we have an up to date ETag - just get the value from the cache
        if ($this->_cacheHits[$group]['status'] == self::STATUS_HIT) {
            return Cache::get($key, $default);
        }
        
        return $default;
    }
    
    /**
     * 
     * @param string $group
     * @param string $url
     * 
     * @return boolean
     */
    public function init(string $group, string $url): bool
    {
        if (!isset($this->_cacheHits[$group])) {
            return false;
        }
        
        if (in_array($this->_cacheHits[$group]['status'], [self::STATUS_UNKNOWN, self::STATUS_DIDNT_HIT])) {
            $etag = $this->_getEtag($url, $group);
            
            //if we were able to get ETag
            if ($etag) {
                //check if it is the same as the one we have cached before
                $etagCache = Cache::get($this->_cacheHits[$group]['etag'], "-1");
                if ($etagCache != -1 && ($etag == $etagCache || $etag == "Limit-Reached")) {
                    $this->_cacheHits[$group]['status'] = self::STATUS_HIT;
                } else {
                    //if not, then save new ETag and set status to not hit
                    Cache::put($this->_cacheHits[$group]['etag'], $etag, 60);
                    $this->_cacheHits[$group]['status'] = self::STATUS_DIDNT_HIT;
                    
                    return false;
                }
            }
        }
        
        return true;
    }
    
    
    /**
     * 
     * @param string $group
     * @param string $key
     * @param type $value
     */
    public function set(string $key, $value): void
    {
        Cache::put($key, $value, 60);
    }
    
    
    /**
     * Gets ETag for page=1&per_page=1 request
     * 
     * @param string $url
     * 
     * @return string
     */
    private function _getEtag(string $url, string $group): string
    {
        $etag = Cache::get($this->_cacheHits[$group]['etag']);

        $headers = $this->_headers;
        if ($etag) {
            $headers = array_merge($this->_headers, ["If-None-Match: {$etag}"]);
        }
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        $content = curl_exec($ch);

        $headers = $this->_get_headers_from_curl_response($content);
            
        if ($headers && isset($headers['ETag'])) {
            return $headers['ETag'];
        } else {
            if ($headers && isset($headers["X-RateLimit-Remaining"]) && $headers["X-RateLimit-Remaining"]) {
                var_dump('Limit reached... using cache... data may be outdated');
                return "Limit-Reached";
            }
        }
        
        return "";
    }
    
    
    /**
     * 
     * @url https://stackoverflow.com/a/10590242
     * 
     * @param type $response
     * @return type
     */
    private function _get_headers_from_curl_response($response)
    {
        $headers = array();

        $header_text = substr($response, 0, strpos($response, "\r\n\r\n"));

        foreach (explode("\r\n", $header_text) as $i => $line)
            if ($i === 0)
                $headers['http_code'] = $line;
            else
            {
                list ($key, $value) = explode(': ', $line);

                $headers[$key] = $value;
            }

        return $headers;
    }
}