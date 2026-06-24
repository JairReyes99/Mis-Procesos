<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn('slug');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('slug', 80)->nullable()->after('name');
        });

        // SQL Server no permite múltiples NULLs en UNIQUE convencional
        \Illuminate\Support\Facades\DB::statement(
            'CREATE UNIQUE INDEX companies_slug_unique ON companies (slug) WHERE slug IS NOT NULL'
        );
    }
};
