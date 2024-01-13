<?php

namespace Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $dataArray = [
            [
                'name' => 'U.E Colegio Virgen del Valle',
                'slogan' => "¡Dónde la educación del fuituro es hoy!",
                'iframeGoogleMap' => "<iframe height=\"450\"style=\"border-radius: 1rem; inline-size: 100%\" src=\"https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3952.667600402255!2d-72.20886082426563!3d7.824962306671257!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x8e666c2486e7b9c9%3A0x6b3c0c32ba495b0a!2sColegio%20Virgen%20del%20Valle!5e0!3m2!1ses-419!2sco!4v1698927326479!5m2!1ses-419!2sco\" allowfullscreen=\"\" loading=\"lazy\" referrerpolicy=\"no-referrer-when-downgrade\"></iframe>",
            ],
            [
                'name' => 'Virgen del Valle International School',
                'slogan' => null,
                'iframeGoogleMap' => "<iframe height=\"450\"style=\"border-radius: 1rem; inline-size: 100%\" src=\"https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3952.090780569737!2d-72.51150012426548!3d7.885570705836759!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x8e6645723892afa9%3A0xd140a5e920bd0ca2!2sColegio%20Virgen%20del%20Valle%20International%20School!5e0!3m2!1ses-419!2sco!4v1698928160697!5m2!1ses-419!2sco\"  allowfullscreen=\"\" loading=\"lazy\" referrerpolicy=\"no-referrer-when-downgrade\"></iframe>",
            ],
            [
                'name' => 'U.E Colegio Lolita Robles de Mora',
                'slogan' => null,
                "iframeGoogleMap"=>"<iframe height=\"450\"style=\"border-radius: 1rem; inline-size: 100%\" src=\"https://www.google.com/maps/embed?pb=!1m14!1m8!1m3!1d1976.5765607723215!2d-72.212318!3d7.773583000000001!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x8e666ceb4f163265%3A0x2a479afbcc97c483!2sParque%20de%20los%20Escritores%20Tachirenses!5e0!3m2!1ses-419!2sco!4v1698928283177!5m2!1ses-419!2sco\"  allowfullscreen=\"\" loading=\"lazy\" referrerpolicy=\"no-referrer-when-downgrade\"></iframe>"
            ],
        ];

        foreach ($dataArray as $key => $value) {
            $data = new Company();
            $data->name = $value['name'];
            $data->slogan = $value['slogan'];
            $data->iframeGoogleMap = $value['iframeGoogleMap'];
            $data->save();
        }
    }
}
