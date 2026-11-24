<?php
// mon-test.php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Place;

$places = Place::all();

foreach ($places as $place) {

    $city="";
    $label="";
    $cp="";
    $newaddress="";

    $name = explode(',',$place->name);
    if (count($name)>=2) {
        $city=trim($name[0]);
        $label=trim($name[1]);
    }

    $str = $place->address;

    if (preg_match('/^(.*?)\s+(\d{5})\s+(.+)$/', $str, $matches)) {
        $newaddress = trim($matches[1]);
        $cp = $matches[2];
        $city = trim($matches[3]);
    }

    #echo("label:".$label." city:".$city." cp:".$cp." address=".$newaddress."\n");
    $place->update([
        "city" => $city,
        "label" => $label,
        "cp" => $cp,
        "address" => $newaddress,
    ]);
    
}

