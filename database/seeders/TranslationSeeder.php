<?php

namespace Database\Seeders;

use App\Models\Translation;
use App\Models\Tag;
use Illuminate\Database\Seeder;

class TranslationSeeder extends Seeder
{
    public function run(): void
    {
        $tags = Tag::all();

        // Create sample translations
        $translations = [
            ['key' => 'welcome.message', 'locale' => 'en', 'content' => 'Welcome to our application!'],
            ['key' => 'welcome.message', 'locale' => 'fr', 'content' => 'Bienvenue dans notre application !'],
            ['key' => 'welcome.message', 'locale' => 'es', 'content' => '¡Bienvenido a nuestra aplicación!'],

            ['key' => 'auth.login.title', 'locale' => 'en', 'content' => 'Sign In'],
            ['key' => 'auth.login.title', 'locale' => 'fr', 'content' => 'Se connecter'],
            ['key' => 'auth.login.title', 'locale' => 'es', 'content' => 'Iniciar sesión'],

            ['key' => 'auth.register.title', 'locale' => 'en', 'content' => 'Create Account'],
            ['key' => 'auth.register.title', 'locale' => 'fr', 'content' => 'Créer un compte'],
            ['key' => 'auth.register.title', 'locale' => 'es', 'content' => 'Crear cuenta'],
        ];

        foreach ($translations as $data) {
            $translation = Translation::create($data);

            // Attach random tags
            if ($tags->isNotEmpty()) {
                $randomTags = $tags->random(rand(1, 3));
                $translation->tags()->attach($randomTags);
            }
        }
    }
}
