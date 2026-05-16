<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $newsapi = new \jcobhams\NewsApi\NewsApi(env('NEWSAPI_KEY'));
    $all_articles = $newsapi->getEverything("artificial intelligence", null, null, null, null, null, 'en', 'relevancy', 5, 1);
    print_r($all_articles);
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
