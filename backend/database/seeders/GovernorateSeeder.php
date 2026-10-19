<?php

namespace Database\Seeders;

use App\Models\Governorate;
use Illuminate\Database\Seeder;

class GovernorateSeeder extends Seeder
{
    // PLACEHOLDER fees (EGP) and delivery days — set the real rates from the admin (BE-50).
    private const ZONES = [
        [60, 1, 2, ['القاهرة', 'الجيزة']],
        [70, 2, 3, ['الإسكندرية', 'القليوبية', 'الشرقية', 'الدقهلية', 'الغربية', 'المنوفية', 'البحيرة', 'كفر الشيخ', 'دمياط', 'بورسعيد', 'الإسماعيلية', 'السويس']],
        [85, 3, 4, ['الفيوم', 'بني سويف', 'المنيا', 'أسيوط', 'سوهاج', 'قنا', 'الأقصر', 'أسوان']],
        [120, 4, 6, ['البحر الأحمر', 'الوادي الجديد', 'مطروح', 'شمال سيناء', 'جنوب سيناء']],
    ];

    public function run(): void
    {
        $sort = 0;
        foreach (self::ZONES as [$fee, $min, $max, $names]) {
            foreach ($names as $name) {
                Governorate::updateOrCreate(['name' => $name], [
                    'shipping_fee_piasters' => $fee * 100,
                    'delivery_days_min' => $min,
                    'delivery_days_max' => $max,
                    'is_active' => true,
                    'sort' => $sort++,
                ]);
            }
        }
    }
}
