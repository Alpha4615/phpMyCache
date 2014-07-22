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
     * Returns data for the query provided. Data will be cached into a file for the time specified in the $expiry argument. If no $expiry is specified, the default provided in the config will be used
     *
     * @param string       $query
     * @param null|integer $expiry
     * @return array Associative array of the results where the key is the fieldName and the value is the corresponding value
     */

    public function queryCache($query, $expiry = NULL)
    {
        //Validate our config and throw exceptions if there's anything invalid going on.
        $this->option->validate();

        $expiry         = ($expiry != NULL && is_numeric($expiry) ? intval($expiry) : intval($this->option->get('defaultExpiry')));
        $querySignature = sha1($query);
        $filename       = $this->option->get('cacheFilePrefix') . $querySignature . $this->option->get('cacheFileSuffix');

        $path = $this->option->get('cacheDirectory') . $filename;

        //First we check if this query has been cached
        if (file_exists($path)) {
            $cache  = file_get_contents($path);
            $result = unserialize($cache);

            $createdDate = $result['createdDate'];
            $expiration  = $result['expires'];

            if ($createdDate + $expiration < time()) {
                // cache has expired, get new results and replace current cache.
                return $this->proceedQuery($query, $expiry, $querySignature);
            } else {
                return $result['data'];
            }
        } else {
            // no cache exists at all, let's make query;
            return $this->proceedQuery($query, $expiry, $querySignature);

        }

    }

    /**
     * This is a hand-off method used by queryCache() when it decides to actually perform the query against the mySQL database.
     *
     * @param string $query
     * @param integer|string $expiry
     * @param string $signature SHA1() Signature of the query.
     * @return array
     */
    protected function proceedQuery($query, $expiry, $signature)
    {
        $cacheWrite                = array();
        $cacheWrite['createdDate'] = time();
        $cacheWrite['expires']     = $expiry;

        $result = $this->db->query($query);
        $data   = array();

        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }

        $cacheWrite['data'] = $data;
        $filename           = $this->option->get('cacheFilePrefix') . $signature . $this->option->get('cacheFileSuffix');

        file_put_contents($this->option->get('cacheDirectory') . $filename, serialize($cacheWrite));

        return $data;
    }

}
