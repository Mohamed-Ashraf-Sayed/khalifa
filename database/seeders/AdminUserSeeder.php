<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AdminUserSeeder extends Seeder
{
    /**
     * ينشئ حساب المدير الأول.
     * الباسورد بيتقري من ADMIN_PASSWORD في .env — ولو مش موجود بيتولّد
     * باسورد قوي عشوائي ويتطبع في الـconsole مرة واحدة (مش مكتوب في الكود أبداً).
     */
    public function run(): void
    {
        $email = env('ADMIN_EMAIL', 'admin@alqarwana.com');
        $password = env('ADMIN_PASSWORD');
        $generated = false;

        if (blank($password)) {
            $password = Str::password(16);
            $generated = true;
        }

        $admin = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => env('ADMIN_NAME', 'مدير النظام'),
                'password' => $password, // بيتشفّر تلقائياً عبر cast الموديل
                'is_active' => true,
            ],
        );

        $admin->syncRoles(['admin']);

        if ($generated) {
            $this->command->warn('=================================================');
            $this->command->warn('  تم توليد باسورد عشوائي لحساب المدير:');
            $this->command->warn("  Email:    {$email}");
            $this->command->warn("  Password: {$password}");
            $this->command->warn('  احفظه دلوقتي — مش هيتعرض تاني.');
            $this->command->warn('=================================================');
        } else {
            $this->command->info("تم تجهيز حساب المدير: {$email}");
        }
    }
}
