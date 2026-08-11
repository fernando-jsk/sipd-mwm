<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$receiptTypes = \App\Models\ReceiptType::all();
$parentName = 'Pendapatan Pasien Umum';
$subName = 'IGD';

$parentType = $receiptTypes->where('name', $parentName)->whereNull('parent_id')->first();
if (!$parentType && $parentName === 'Pendapatan Pasien Umum') {
    $parentType = $receiptTypes->where('name', 'Pendapatan Pasien Umum')->first();
}

$parentTypeId = $parentType ? $parentType->id : 6;
echo "Parent ID: " . $parentTypeId . "\n";

$subType = $receiptTypes->filter(function($t) use ($subName, $parentTypeId) {
    $dbName = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $t->name));
    $searchName = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $subName));
    return $t->parent_id == $parentTypeId && 
        (str_contains($dbName, $searchName) || str_contains($searchName, $dbName));
})->first();

echo "Sub ID: " . ($subType ? $subType->id : 'null') . "\n";
