<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    // PLACEHOLDER commercial values — confirm with Midad before launch (REL-04).
    public function run(): void
    {
        $settings = [
            'announcements' => ['الدفع عند الاستلام في كل المحافظات', 'عينة ٢ مل هدية مع كل طلب', 'شحن مجاني للطلبات فوق ١٥٠٠ ج.م'],
            'free_shipping_threshold_piasters' => 150000,
            'discovery_box' => ['price_piasters' => 35000, 'samples' => 6, 'sample_ml' => 2, 'credit_days' => 60],
            'gift_wrap_fee_piasters' => 0,
            'reservation_minutes' => 30,
            'whatsapp_number' => '',
        ];
        foreach ($settings as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}
