<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\Member;

class MemberSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $csvFile = storage_path('app/csv/members.csv');

        $file = fopen($csvFile, 'r');

        $header = fgetcsv($file);

        while (($row = fgetcsv($file)) !== false) {
            $data = array_combine($header, $row);
            Member::updateOrCreate(
                ['name' => $data['name'],'prenom' => $data['prenom']],
                [
                    'type' => $data['type'],
                    'licence' => $data['licence'],
                ]
            );
        }

        // Alternative pour inserer en un bloc
        /*
        $members = [];
        while (($row = fgetcsv($file)) !== false) {
            $data = array_combine($header, $row);

            $members[] = [
                'name' => $data['name'],
                'prenom' => $data['prenom'],
                'type' => $data['type'],
                'licence' => $data['licence'],
            ];
        }
        Member::insert($members);
        */

        fclose($file);
    }
}

