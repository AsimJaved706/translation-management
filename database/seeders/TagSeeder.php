<?php

namespace Database\Seeders;

use App\Models\Tag;
use Illuminate\Database\Seeder;

class TagSeeder extends Seeder
{
    public function run(): void
    {
        $tags = [
            'web', 'mobile', 'desktop', 'api', 'frontend', 'backend',
            'auth', 'profile', 'settings', 'dashboard', 'navigation',
            'error', 'success', 'warning', 'info', 'validation',
            'form', 'button', 'modal', 'tooltip', 'menu'
        ];

        foreach ($tags as $tagName) {
            Tag::firstOrCreate(['name' => $tagName]);
        }
    }
}
