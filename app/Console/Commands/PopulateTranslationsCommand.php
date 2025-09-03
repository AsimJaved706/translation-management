<?php

namespace App\Console\Commands;

use App\Models\Translation;
use App\Models\Tag;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PopulateTranslationsCommand extends Command
{
    protected $signature = 'translations:populate {count=1000 : Number of translations to create}';
    protected $description = 'Populate the database with test translation data';

    public function handle(): int
    {
        $count = (int) $this->argument('count');
        $this->info("Creating {$count} translations...");

        // Create tags first
        $tags = $this->createTags();
        $this->info('Created ' . count($tags) . ' tags');

        // Create translations in batches for better performance
        $batchSize = 1000;
        $locales = ['en', 'fr', 'es', 'de', 'it', 'pt', 'ru', 'zh', 'ja', 'ko'];

        $progressBar = $this->output->createProgressBar($count);
        $progressBar->start();

        DB::transaction(function () use ($count, $batchSize, $locales, $tags, $progressBar) {
            for ($i = 0; $i < $count; $i += $batchSize) {
                $batch = [];
                $currentBatchSize = min($batchSize, $count - $i);

                for ($j = 0; $j < $currentBatchSize; $j++) {
                    $index = $i + $j;
                    $locale = $locales[$index % count($locales)];

                    $batch[] = [
                        'key' => $this->generateKey($index),
                        'locale' => $locale,
                        'content' => $this->generateContent($index, $locale),
                        'created_at' => now()->subDays(rand(0, 365)),
                        'updated_at' => now()->subDays(rand(0, 30)),
                    ];

                    $progressBar->advance();
                }

                Translation::insert($batch);
            }
        });

        $progressBar->finish();
        $this->newLine();

        // Attach random tags to translations
        $this->info('Attaching tags to translations...');
        $this->attachRandomTags($tags);

        $this->info("Successfully created {$count} translations with tags!");
        return 0;
    }

    private function createTags(): array
    {
        $tagNames = [
            'web', 'mobile', 'desktop', 'api', 'frontend', 'backend',
            'auth', 'profile', 'settings', 'dashboard', 'navigation',
            'error', 'success', 'warning', 'info', 'validation',
            'form', 'button', 'modal', 'tooltip', 'menu'
        ];

        $tags = [];
        foreach ($tagNames as $name) {
            $tags[] = Tag::firstOrCreate(['name' => $name]);
        }

        return $tags;
    }

    private function generateKey(int $index): string
    {
        $prefixes = [
            'common', 'auth', 'profile', 'settings', 'dashboard',
            'navigation', 'forms', 'buttons', 'messages', 'errors'
        ];

        $suffixes = [
            'title', 'description', 'label', 'placeholder', 'button',
            'message', 'error', 'success', 'warning', 'info'
        ];

        $prefix = $prefixes[$index % count($prefixes)];
        $suffix = $suffixes[($index * 7) % count($suffixes)];

        return "{$prefix}.{$suffix}.{$index}";
    }

    private function generateContent(int $index, string $locale): string
    {
        $templates = [
            'en' => [
                'Welcome to our application',
                'Please enter your information',
                'Save changes successfully',
                'An error occurred',
                'Click here to continue',
                'Your profile has been updated',
                'Please confirm your action',
                'Loading content...',
                'No results found',
                'Thank you for using our service'
            ],
            'fr' => [
                'Bienvenue dans notre application',
                'Veuillez saisir vos informations',
                'Modifications sauvegardées avec succès',
                'Une erreur s\'est produite',
                'Cliquez ici pour continuer',
                'Votre profil a été mis à jour',
                'Veuillez confirmer votre action',
                'Chargement du contenu...',
                'Aucun résultat trouvé',
                'Merci d\'utiliser notre service'
            ],
            'es' => [
                'Bienvenido a nuestra aplicación',
                'Por favor ingrese su información',
                'Cambios guardados exitosamente',
                'Ocurrió un error',
                'Haga clic aquí para continuar',
                'Su perfil ha sido actualizado',
                'Por favor confirme su acción',
                'Cargando contenido...',
                'No se encontraron resultados',
                'Gracias por usar nuestro servicio'
            ]
        ];

        $defaultTemplates = $templates['en'];
        $localeTemplates = $templates[$locale] ?? $defaultTemplates;

        $template = $localeTemplates[$index % count($localeTemplates)];
        return $template . " #{$index}";
    }

    private function attachRandomTags(array $tags): void
    {
        $translations = Translation::inRandomOrder()->limit(5000)->get();

        foreach ($translations as $translation) {
            $randomTags = collect($tags)->random(rand(1, 4));
            $tagIds = collect($randomTags)->pluck('id')->toArray();
            $translation->tags()->sync($tagIds);
        }
    }
}
