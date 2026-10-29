<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            ['key' => 'store_name', 'value' => 'Dreamcatcher.id', 'group' => 'general'],
            ['key' => 'store_address', 'value' => 'Perum. Mutiara Bekasi Jaya Blok A2 No.6, RT 001/RW 008, Kec. Cibarusah, Kab. Bekasi', 'group' => 'contact'],
            ['key' => 'store_phone', 'value' => '081234567890', 'group' => 'contact'],
            ['key' => 'store_whatsapp', 'value' => '6281234567890', 'group' => 'contact'],
            ['key' => 'store_email', 'value' => 'ihsanptr123@gmail.com', 'group' => 'contact'],
            ['key' => 'shipping_cost', 'value' => '15000', 'group' => 'shipping'],
            ['key' => 'shipping_method', 'value' => 'flat', 'group' => 'shipping'],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}
