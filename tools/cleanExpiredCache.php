<?php

if (!isset($cacheDirectory)) {
    die('Please define $cacheDirectory before including this tool.');
}

$scan = scandir($cacheDirectory);

foreach ($scan as $key => $val) {
    if ($val != '..' && $val != '.' && is_dir($val) == FALSE) {
        $val       = $cacheDirectory . $val;
        $contents  = file_get_contents($val);
        $cacheData = json_decode($contents, TRUE);
        $date      = "m/d/y H:i:s";
        echo date($date) . " -- " . date($date, $cacheData['createdDate'] + $cacheData['expires']) . PHP_EOL;

        if ($cacheData != NULL && isset($cacheData['createdDate']) && isset($cacheData['expires'])) {
            if ($cacheData['createdDate'] + $cacheData['expires'] < time()) {
                echo "Deleting " . $val . " because cache has expired" . PHP_EOL;
                unlink($val);
            }
        }
    }
}

?>
