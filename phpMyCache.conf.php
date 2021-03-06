<?php
$phpMyCacheConfig = array(
    // This is the where cache files are to be stored with a trailing slash. For security purposes, it should not be accessible via the web.
    'cacheDirectory'                => 'cache/',
    // If a timeout isn't provided at call-time, cached data will automatically expire after this period
    'defaultExpiry'                 => 3600,

    // Optional configuration settings

    //What, if any, prefix would you like to be prepended to the cache filenames? If this is changed, previous cache files will essentially be invalidated
    'cacheFilePrefix'               => '',
    // What, if any, suffix would you like to be added to cache filenames? If this is changed, previous cache files will essentially be invalidated.
    'cacheFileSuffix'               => '.cache',
    // This function will be executed when a query fails, if you wish not to use a callback at all, set this to NULL
    'errorCallback'                 => function ($databaseObject) {
            throw new InvalidQueryException($databaseObject->error);
        },
    // If true, this will return the metadata (including the result set data) instead of just the result set data
    'returnMeta'                    => FALSE,
    //Throw exceptions when invalid options are passed into the option getter/setters
    'throwExceptionOnInvalidOption' => TRUE

);