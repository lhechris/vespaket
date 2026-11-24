<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['name','lat','lng','address', 'label', 'cp','city'])]
class Place extends Model
{
    use HasFactory;
}

/*{
    "id":"200000002673451",
    "numero":"233119302",
    "libelle":"GYMNASE FRANCIS AVIGNON",
    "libelle2":"",
    "adresse":"15 avenue des PYRENEES",
    "adresseComplement":"",
    "commune": {
        "codePostal":"31430",
        "libelle":"LE FOUSSERET"
    },
    "cartographie":
        {"latitude":43.28879,"longitude":1.06765}}*/
