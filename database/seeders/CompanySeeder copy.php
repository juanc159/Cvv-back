<?php

namespace Database\Seeders;

use App\Models\CompanyDetail;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $dataArray = array(
            array('id' => '1','company_id' => '1','type_detail_id' => '1','icon' => 'tabler-brand-facebook','color' => 'success','content' => 'https://www.facebook.com/ColegioVirgenDelValleArjona'),
            array('id' => '2','company_id' => '1','type_detail_id' => '2','icon' => 'tabler-brand-instagram','color' => 'x','content' => 'https://www.instagram.com/cvirgendelvalle/'),
            array('id' => '3','company_id' => '1','type_detail_id' => '3','icon' => 'tabler-brand-tiktok-filled','color' => 's','content' => 'https://www.tiktok.com/@cvirgendelvalle?_t=8cymV9IJMdJ&_r=1'),
            array('id' => '4','company_id' => '1','type_detail_id' => '4','icon' => 'tabler-brand-twitter-filled','color' => 's','content' => 'https://twitter.com/i/flow/login?redirect_after_login=%2FCVirgendelValle'),
            array('id' => '5','company_id' => '1','type_detail_id' => '5','icon' => 'tabler-brand-youtube-filled','color' => 's','content' => 'https://www.youtube.com/@unidadeducativacolegiovirg5233'),
            array('id' => '6','company_id' => '1','type_detail_id' => '6','icon' => 'tabler-map-pin','color' => 's','content' => 'Arjona - Las Vegas de Táriba - Municipio Cardenas - Edo Tachira'),
            array('id' => '7','company_id' => '2','type_detail_id' => '6','icon' => 'tabler-map-pin','color' => 'x','content' => 'Avenida 10 N°. 10-35 - EDIF Virgen de Fatima -Barrio El Llano, centro de San José de Cúcuta, Departamento Norte de Santander'),
            array('id' => '8','company_id' => '2','type_detail_id' => '7','icon' => 'tabler-phone-filled','color' => 'x','content' => '5754290,(318) 3049840'),
            array('id' => '9','company_id' => '2','type_detail_id' => '8','icon' => 'tabler-mail','color' => 's','content' => 'virgendelvalle.intl.school@gmail.com'),
            array('id' => '10','company_id' => '2','type_detail_id' => '2','icon' => 'tabler-brand-instagram','color' => 'a','content' => 'https://www.instagram.com/virgendelvalle_intlschool/'),
            array('id' => '11','company_id' => '1','type_detail_id' => '8','icon' => 'tabler-mail','color' => 's','content' => 'colegiovirgendelvallearjona@gmail.com,cvvdireccion@hotmail.com,cvvdireccion@gmail.com,colegiovirgendelvalle.adm@gmail.com'),
            array('id' => '12','company_id' => '3','type_detail_id' => '6','icon' => 'tabler-map-pin','color' => 'a','content' => 'Sector el Tamá. Av. Juan Maldonado. N° 126. Frente al parque los Escritores. Pirineos'),
            array('id' => '13','company_id' => '3','type_detail_id' => '7','icon' => 'tabler-phone-filled','color' => 'c','content' => '(0424) 7242423,(0424) 7375276'),
            array('id' => '14','company_id' => '3','type_detail_id' => '8','icon' => 'tabler-mail','color' => 'a','content' => 'ue.colegiololitarobles@gmail.com'),
            array('id' => '15','company_id' => '3','type_detail_id' => '2','icon' => 'tabler-brand-instagram','color' => 'a','content' => 'https://www.instagram.com/colegiololitarobles/'),
            array('id' => '16','company_id' => '3','type_detail_id' => '1','icon' => 'tabler-brand-facebook-filled','color' => 'a','content' => 'https://www.facebook.com/profile.php?id=100095290159764'),
            array('id' => '17','company_id' => '1','type_detail_id' => '7','icon' => 'tabler-phone','color' => 'a','content' => '(0276) 3946955')
          );


        foreach ($dataArray as $key => $value) {
            $data = new CompanyDetail();
            $data->id = $value['id'];
            $data->company_id = $value['company_id'];
            $data->type_detail_id = $value['type_detail_id'];
            $data->icon = $value['icon'];
            $data->color = $value['color'];
            $data->content = $value['content'];
            $data->save();
        }
    }
}
