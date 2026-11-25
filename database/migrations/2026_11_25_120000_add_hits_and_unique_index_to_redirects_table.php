<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('redirects', function (Blueprint $table) {
            $table->unsignedInteger('hits')->default(0)->after('status_code');
            $table->timestamps();
            $table->unique('old_url');
        });
    }

    public function down(): void
    {
        Schema::table('redirects', function (Blueprint $table) {
            $table->dropUnique(['old_url']);
            $table->dropTimestamps();
            $table->dropColumn('hits');
        });
    }
};
