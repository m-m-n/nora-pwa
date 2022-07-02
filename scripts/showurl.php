#!/usr/bin/php
<?php

define("BASE_DIR", __DIR__ . "/../public");
foreach (glob(BASE_DIR . "/*") as $path) {
    if (!is_dir($path)) {
        continue;
    }
    $url = "https://YOUR-DOMAIN" . str_replace(BASE_DIR, "", $path) . "/";
    echo "{$url}\n";
}
