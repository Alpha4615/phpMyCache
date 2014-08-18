
# phpMyCache

phpMyCache is a simple library that caches responses from _mySQL_ SELECT queries. The caching library is helpful when performing queries that does is not updated frequently and thus does not constantly require a fresh dataset from the database. This should result in less strain on the database as queries are only fetched from the database when the associated cached data has expired or does not exist. Be sure to check out the [phpMyCache webpage](http://phpmycache.com)!

## Example Use Cases

*   Count of registered users
*   List of recent popular threads or content items
*   Daily statistics

## Configuration
phpMyCache requires a few configuration settings that can be passed when the phpMyCache class is instantiated or through a getter/setter after the class is instantiated. The required configuration settings are italicized.

* __**cacheDirectory**__ - The directory of where library should store the cache files. This should be in a non-web-served directory for security reasons.
* __**defaultExpiry**__ - The duration, in seconds, that data should be considered fresh. This duration is used by the library if an expiry isn't provided when the queryCache() method is called.
* **errorCallback** - This is a callback that is called when a SQL query fails. The only parameter is the database connection resource. The query error can be examined from there.
* **cacheFilePrefix** - The text prepended to the filename when a cache file is created. If you are storing cache files from multiple web-applications, it may be useful to have them each use different prefixes.
* **cacheFileSuffix** - The text appended to the filename when a cache file is created.
* **returnMeta** - If true, it returns the metadata of the result set (created, expiry, source etc) along with the data, else returns just the result set.
* **throwExceptionOnInvalidOption** - If this is set to true, the phpMyCacheOptions class will throw an exception when set() or get() targets an invalid configuration setting name. If this is set to false, those methods will instead return **NULL**.
 

#Implementation Example
The following code would pull the latest 10 articles from a table called news. The first request of this data will query the database directly. The results are restored for subsequent requests to this exact query that happen within the next hour (3600 seconds). After that time, the cache is invalidated and the database will be queried directly again.

```
<?php
include('../phpMyCache/lib/phpMyCache.php');
include('../phpMyCache/phpMyCache.conf.php');


$database = mysqli_connect('localhost', 'root', 'password', 'database');
$cacheDB = new phpMyCache($database, $phpMyCacheConfig);
$result = $cacheDB->queryCache("SELECT headline FROM news ORDER BY pubDate DESC LIMIT 0,10", 3600);

foreach ($result as $key => $newsItem) {
    print $newsItem['headline']."<br />";
}


?>
```

#Tools
* **cleanExpiredCache.php** - This is a script that be executed by, say, a nightly cronjob. This script, once executed, will scan all files in the cache directory, check to see if it's cached data and deletes the file if it's expired. 


## Requirements

phpMyCache requires PHP 5 with the mySQLi extension installed.


## To do

Looking to contribute? These are some of the things that would be great to be added to phpMyCache. Feel free to submit your work via a pull request!
* Support for prepared queries.
