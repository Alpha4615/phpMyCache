<?php

class phpMyCache
{
    /**
     * @var mysqli Database connection resource
     */
    protected $db;
    /**
     * @var phpMyCacheOptions Holds the configuration and provides a getter and setter for those settings
     */
    protected $option;

    const SOURCE_CACHE    = 1;
    const SOURCE_DATABASE = 2;

    /**
     * @param mysqli     $databaseResource The resource provided by a successful call to mysqli_connect();
     * @param null|array $config
     * @throws InvalidArgumentException
     */
    function phpMyCache($databaseResource, $config = NULL)
    {
        // We need to be able to load in the external class files as they are needed.
        $autoloadCallback = function ($className) {
            require_once(dirname(__FILE__) . '/../class/' . $className . ".class.php");
        };
        spl_autoload_register($autoloadCallback);

        $this->option = new phpMyCacheOptions(TRUE);

        if (is_array($config) == TRUE) {
            $this->option->import($config);
        }
        if (is_a($databaseResource, 'mysqli') == FALSE) {
            throw new InvalidArgumentException ("Database resource must be a valid mysqli connection resource.");
        }
        $this->db = $databaseResource;
    }

    /**
     * @param string $propertyName
     * @param mixed  $value
     * @return $this
     */

    public function setOption($propertyName, $value)
    {
        $this->option->set($propertyName, $value);

        // We're in another context, so we cannot return the returnval of set();
        return $this;
    }

    /**
     * @param $propertyName
     * @return mixed
     */
    public function getOption($propertyName)
    {
        return $this->option->get($propertyName);
    }

    /**
     * Validates an value to confirm if it's compatible array. To pass, $cacheTest must be an array and contain the keys 'expires' (which contains an integers), 'createdDate' (which contains an integer) and data (which contains an array)
     *
     * @param mixed $cacheTest
     * @return bool
     */
    protected static function isCacheValid($cacheTest)
    {
        if (is_array($cacheTest) == FALSE) return FALSE;
        if (!(isset($cacheTest['expires']) == TRUE && is_int($cacheTest['expires']) == TRUE)) return FALSE;
        if (!(isset($cacheTest['createdDate']) == TRUE && is_int($cacheTest['createdDate']) == TRUE)) return FALSE;
        if (!(isset($cacheTest['data']) == TRUE && is_array($cacheTest['data']) == TRUE)) return FALSE;

        return TRUE;
    }

    /**
     * Returns data for the query provided. Data will be cached into a file for the time specified in the $expiry argument. If no $expiry is specified, the default provided in the config will be used
     *
     * @param string       $query
     * @param null|integer $expiry
     * @param boolean      $ignoreCache Specify true if you want this query to bypass cache checking.
     * @return array Associative array of the results where the key is the fieldName and the value is the corresponding value
     */

    public function queryCache($query, $expiry = NULL, $ignoreCache = FALSE)
    {
        //Check to see if expiry is a strotime'able string, per issue #3
        if ($expiry !== NULL && (is_string($expiry) === TRUE && is_numeric($expiry) === FALSE)) {
            $strtotime = strtotime($expiry);
            if ($strtotime !== FALSE) {
                $expiry = $strtotime-time();
            }
        }
        //Validate our config and throw exceptions if there's anything invalid going on.
        $this->option->validate();

        $expiry         = ($expiry != NULL && is_numeric($expiry) ? intval($expiry) : intval($this->option->get('defaultExpiry')));
        $querySignature = sha1($query);
        $filename       = $this->option->get('cacheFilePrefix') . $querySignature . $this->option->get('cacheFileSuffix');
        $path           = $this->option->get('cacheDirectory') . $filename;

        //First we check if this query has been cached
        if ($ignoreCache === FALSE && file_exists($path)) {
            $cache  = file_get_contents($path);
            $result = json_decode($cache, TRUE);

            // Is cache valid? e.g., is it corrupted?
            if (self::isCacheValid($result) == FALSE) {
                // If it's not valid, then we ignore it and delegate to the database

                return $this->proceedQuery($query, $expiry, $querySignature);
            }

            $createdDate = $result['createdDate'];
            $expiration  = $result['expires'];

            if ($createdDate + $expiration < time()) {
                // cache has expired, get new results and replace current cache.
                return $this->proceedQuery($query, $expiry, $querySignature);
            } else {
                $result['source'] = self::SOURCE_CACHE;

                return ($this->option->get('returnMeta') === TRUE ? $result : $result['data']);
            }
        } else {
            // no cache exists at all, let's make query;
            return $this->proceedQuery($query, $expiry, $querySignature);

        }

    }

    /**
     * Executes the error callback if one exists.
     *
     * @return bool Always returns true (to be stored in variable to basically change lifecycle of parent function)
     */
    protected function handleQueryError()
    {
        $callback = $this->option->get('errorCallback');
        if ($callback === NULL) {
            return TRUE;
        } else {
            if ($callback instanceof Closure) {
                $callback($this->db);
            }

            return TRUE;
        }
    }

    /**
     * This is a hand-off method used by queryCache() when it decides to actually perform the query against the mySQL database.
     *
     * @param string         $query
     * @param integer|string $expiry    If an expiry of 0 (integer zero) is provided, cache will not be written.
     * @param string         $signature SHA1() Signature of the query.
     * @return array|boolean Returns array of data. If query caused error, it returns false.
     */
    protected function proceedQuery($query, $expiry, $signature)
    {
        $cacheWrite                = array();
        $cacheWrite['createdDate'] = time();
        $cacheWrite['expires']     = $expiry;
        $queryError                = FALSE;

        $result = $this->db->query($query) or $queryError = $this->handleQueryError();
        $data = array();

        if ($queryError == FALSE) {
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }

            if ($expiry !== 0) {
                $cacheWrite['data'] = $data;
                $filename           = $this->option->get('cacheFilePrefix') . $signature . $this->option->get('cacheFileSuffix');

                file_put_contents($this->option->get('cacheDirectory') . $filename, json_encode($cacheWrite));
            }
            // We define this after the write because it doesn't make sense to write it to cache.
            $cacheWrite['source'] = self::SOURCE_DATABASE;

            return ($this->option->get('returnMeta') === TRUE ? $cacheWrite : $data);
        } else {
            return FALSE;
        }
    }

}
