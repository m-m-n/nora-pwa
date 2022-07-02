#!/usr/bin/php
<?php

function usage(bool $loadable): ?string
{
    echo "USAGE:\n";
    echo "    generater.php JSON_FORMAT_FILE\n\n";
    $items = [];
    foreach (glob(__DIR__ . "/sites/*") as $key => $item) {
        $label = basename($item);
        echo "                  [{$key}]\t{$label}\n";
        $items[] = $item;
    }
    echo "\n";

    if (!$loadable) {
        exit;
    }

    echo "load file number: ";
    $stdin = trim(fgets(STDIN));
    if ($stdin === "") {
        return null;
    }

    $stdin = (int)$stdin;
    if (empty($items[$stdin] ?? null)) {
        return null;
    }

    echo "load file: {$items[$stdin]}\n";

    return $items[$stdin];
}

function loadTemplate(string $path, array $args): ?string
{
    if (!is_file($path)) {
        return null;
    }

    extract($args);
    ob_start();
    include $path;
    return ob_get_clean();
}

if ($argc < 2) {
    $loadfile = usage(true);
} else {
    $loadfile = $argv[1];
}

// validate
if (!file_exists($loadfile)) {
    echo "no such file: {$loadfile}\n";
    usage();
}

$json = json_decode(file_get_contents($loadfile), true);
if (!$json) {
    echo "cannot decode json: {$loadfile}\n";
    exit;
}

if (!isset($json["domain"])) {
    echo "cannot find key: domain\n";
    exit;
}

if (!isset($json["redirect_to"])) {
    echo "cannot find key: redirect_to\n";
    exit;
}

if (!isset($json["site_name"])) {
    echo "cannot find key: site_name\n";
    exit;
}

if (!isset($json["icons"]) || !is_array($json["icons"])) {
    echo "cannot find key: icons or icons not array\n";
    exit;
}

$domain = $json["domain"];
foreach ($json["icons"] as $key => $value) {
    if (!isset($value["file_name"], $value["sizes"], $value["type"])) {
        echo "icons required keys: file_name, sizes and type\n";
        exit;
    }
    $json["icons"][$key]["src"] = "/{$domain}/{$value["file_name"]}";
    unset($json["icons"][$key]["file_name"]);
}

// index_tpl.html/redirect_tpl.html/manifest_tpl.json
$theme_color = $json["theme_color"] ?? "#000000";
// index_tpl.html/redirect_tpl.html
$site_name = $json["site_name"];
// index_tpl.html/redirect_tpl.html
$service_worker_scope = "/{$domain}/";
// redirect_tpl.html
$redirect_to = $json["redirect_to"];
// redirect_tpl.html
$preg_quote_domain = preg_quote($domain, "/");
// manifest_tpl.json
$name = $json["site_name"];
// manifest_tpl.json
$short_name = $json["short_name"] ?? $name;
// manifest_tpl.json
$icons = json_encode($json["icons"], JSON_UNESCAPED_SLASHES);
// manifest_tpl.json
$start_url = "/{$domain}/redirect.html?pwa";
// manifest_tpl.json
$background_color = $json["background_color"] ?? "#ffffff";
// sw_tpl.js
$date = date("Ymd");

$target_directory = dirname(__DIR__) . "/public/{$domain}";
if (file_exists($target_directory)) {
    echo "file or directory found: {$target_directory}\n";
    exit;
}

$sw_tpl = loadTemplate(__DIR__ . "/templates/sw_tpl.js", compact("date", "domain"));
$index_tpl = loadTemplate(__DIR__ . "/templates/index_tpl.html", compact("theme_color", "site_name", "service_worker_scope"));
$redirect_tpl = loadTemplate(__DIR__ . "/templates/redirect_tpl.html", compact("theme_color", "site_name", "service_worker_scope", "redirect_to", "preg_quote_domain"));
$manifest_tpl = loadTemplate(__DIR__ . "/templates/manifest_tpl.json", compact("theme_color", "name", "short_name", "icons", "start_url", "background_color"));

if (!isset($sw_tpl, $index_tpl, $redirect_tpl, $manifest_tpl)) {
    echo "cannot generate templates\n";
    exit;
}

mkdir($target_directory);
file_put_contents("{$target_directory}/sw.js", $sw_tpl);
file_put_contents("{$target_directory}/index.html", $index_tpl);
file_put_contents("{$target_directory}/redirect.html", $redirect_tpl);
file_put_contents("{$target_directory}/manifest.json", $manifest_tpl);

exec("php " . escapeshellarg(__DIR__ . "/list_update.php"));

echo "'{$target_directory}' にアイコンファイルを用意してください" . PHP_EOL;
foreach ($json["icons"] as $icon_info) {
    $filename = basename($icon_info["src"]);
    echo <<<TEXT
{$filename}
  size: {$icon_info["sizes"]}
  type: {$icon_info["type"]}
TEXT;
    echo PHP_EOL;
}
echo PHP_EOL . PHP_EOL;
