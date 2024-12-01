<?php

namespace Database\Seeders;

use App\Helpers\Constants;
use App\Models\Company;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $arrayData = [
            [
                'id' => Constants::COMPANY_UUID,
                'name' => 'U.E Colegio Virgen del Valle',
                'slogan' => '¡Dónde la educación del fuituro es hoy!',
                'iframeGoogleMap' => '<iframe height="450"style="border-radius: 1rem; inline-size: 100%" src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3952.667600402255!2d-72.20886082426563!3d7.824962306671257!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x8e666c2486e7b9c9%3A0x6b3c0c32ba495b0a!2sColegio%20Virgen%20del%20Valle!5e0!3m2!1ses-419!2sco!4v1698927326479!5m2!1ses-419!2sco" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>',
            ],
        ];

        // Inicializar la barra de progreso
        $this->command->info('Starting Seed Data ...');
        $bar = $this->command->getOutput()->createProgressBar(count($arrayData));

        foreach ($arrayData as $value) {
            $data = new Company;
            $data->id = $value['id'];
            $data->name = $value['name'];
            $data->slogan = $value['slogan'];
            $data->iframeGoogleMap = $value['iframeGoogleMap'];
            $data->save();
        }

        $bar->finish(); // Finalizar la barra

    }
}
