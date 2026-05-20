<?php

namespace Database\Seeders;

use App\Models\Bonus;
use App\Models\Site;
use App\Models\Worker;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DataSeeder extends Seeder
{
    public function run()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Site::truncate();
        Worker::truncate();
        Bonus::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        Site::create([
            'id' => 1,
            'uuid'=> 'eb200d8b-ecd7-4215-a078-4f618b1554ec',
            'active'=>true,
            'name'=>'Casino Vale',
            'token'=>'6c332348-b96b-4f84-b693-3118c526bfe8',
        ]);
        Worker::create([
            'id' => 1,
            'uuid'=> 'c4f24dd3-4fb4-435d-8205-085fee04ca30',
            'active'=> true,
            'name'=> 'Casino Vale W1',
            'site_id'=> 1,
        ]);

        $bonu = new Bonus();
        $bonu->setAppends([]); // 👈 image accessor tetiklenmesin
        $bonu->fill([
            'id' => 1,
            'uuid'=> '527d745f-269a-4e88-9677-3943a057093e',
            'active'=>true,
            'sourceid'=>"3336",
            'name' => "%30'a Varan Anlık Kayıp Bonusu",
            'category' => "genel",
            'priority' => 1,
            'ordering' => 1,
            'description' => "%100 Hoş Geldin Bonusu – İlk Yatırımınıza Özel",
            'function_name' => "f3336",
            'delay' => "30",
            'auto_assign' => true,
            'start_at' => null,
            'end_at' => null,
            'timezone' => "Europe/Istanbul",
            'site_id' => 1,
        ]);
        $bonu->save();
    }
}
