<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('translations', function (Blueprint $table) {
            $table->id();
            $table->string('key', 255);
            $table->string('locale', 10);
            $table->text('content');
            $table->timestamps();

            // Composite unique index for key-locale combination
            $table->unique(['key', 'locale'], 'translations_key_locale_unique');

            // Individual indexes for performance
            $table->index('locale', 'translations_locale_index');
            $table->index('key', 'translations_key_index');
        });

        // Add full-text index only for MySQL
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE translations ADD FULLTEXT translations_content_fulltext (content)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('translations');
    }
};
