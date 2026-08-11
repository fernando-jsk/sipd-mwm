<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$r = \App\Models\Receipt::where('payer_name', 'like', '%IGD%')->first();
echo json_encode($r ? $r->toArray() : 'not found', JSON_PRETTY_PRINT);
