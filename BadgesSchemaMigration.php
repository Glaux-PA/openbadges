<?php

/**
 * @file BadgesSchemaMigration.php
 *
 * Copyright (c) 2024+ glaux.es
 * Distributed under the GNU GPL v3. For full terms see the file LICENSE.
 *
 * @class BadgesSchemaMigration
 *
 * @brief Describe database table structures.
 */

namespace APP\plugins\generic\openbadges;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class BadgesSchemaMigration extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('badge')) {
            Schema::create('badge', function (Blueprint $table) {
                $table->bigInteger('badge_id')->autoIncrement();
                $table->string('badge_name', 255);
                $table->longText('badge_description');
                $table->string('badge_image', 255);
            });
        }

        if (!Schema::hasTable('badge_settings')) {
            Schema::create('badge_settings', function (Blueprint $table) {
                $table->bigInteger('badge_id');
                $table->string('locale', 14)->default('');
                $table->string('setting_name', 255);
                $table->longText('setting_value')->nullable();
                $table->string('setting_type', 6)->comment('(bool|int|float|string|object)');
                $table->index(['badge_id'], 'badge_settings_id');
                $table->unique(['badge_id', 'locale', 'setting_name'], 'badge_settings_pkey');
            });
        }

        if (!Schema::hasTable('badge_submission')) {
            Schema::create('badge_submission', function (Blueprint $table) {
                $table->bigInteger('badge_submission_id')->autoIncrement();
                $table->bigInteger('badge_id');
                $table->bigInteger('submission_id');
                $table->string('badge_url', 255);
                $table->boolean('badge_status');
            });
        }

        if (!Schema::hasTable('badge_submission_settings')) {
            Schema::create('badge_submission_settings', function (Blueprint $table) {
                $table->bigInteger('badge_submission_id');
                $table->string('locale', 14)->default('');
                $table->string('setting_name', 255);
                $table->longText('setting_value')->nullable();
                $table->string('setting_type', 6)->comment('(bool|int|float|string|object)');
                $table->index(['badge_submission_id'], 'badge_submission_settings_id');
                $table->unique(['badge_submission_id', 'locale', 'setting_name'], 'badge_submission_settings_pkey');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('badge_submission_settings');
        Schema::dropIfExists('badge_submission');
        Schema::dropIfExists('badge_settings');
        Schema::dropIfExists('badge');
    }
}
