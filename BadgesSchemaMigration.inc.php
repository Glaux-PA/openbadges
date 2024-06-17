<?php

/**
 * @file BadgesSchemaMigration.inc.php
 *
 * Copyright (c) 2024+ glaux.es
 *
 * @class BadgesSchemaMigration
 * @brief Describe database table structures.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Builder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Capsule\Manager as Capsule;

class BadgesSchemaMigration extends Migration {

        /**
         * Run the migrations.
         * @return void
         */
        public function up() {
			if (!Capsule::schema()->hasTable('badge')) {
				# badge
				Capsule::schema()->create('badge', function (Blueprint $table) {
					$table->bigInteger('badge_id')->autoIncrement();
					$table->string('badge_name', 255);
					$table->longText('badge_description');
					$table->string('badge_image', 255);
				});
			}
			if (!Capsule::schema()->hasTable('badge_settings')) {
				// badge_settings
				Capsule::schema()->create('badge_settings', function (Blueprint $table) {
					$table->bigInteger('badge_id');
					$table->string('locale', 14)->default('');
					$table->string('setting_name', 255);
					$table->longText('setting_value')->nullable();
					$table->string('setting_type', 6)->comment('(bool|int|float|string|object)');
					$table->index(['badge_id'], 'badge_settings_id');
					$table->unique(['badge_id', 'locale', 'setting_name'], 'badge_settings_pkey');
				});
			}
			if (!Capsule::schema()->hasTable('badge_submission')) {
				# badge_submission
				Capsule::schema()->create('badge_submission', function (Blueprint $table) {
					$table->bigInteger('badge_submission_id')->autoIncrement();
					$table->bigInteger('badge_id');
					$table->bigInteger('submission_id');
					$table->string('badge_url', 255);
					$table->boolean('badge_status');
				});
			}
			if (!Capsule::schema()->hasTable('badge_submission_settings')) {
				// badge_submission_settings
				Capsule::schema()->create('badge_submission_settings', function (Blueprint $table) {
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

		public function down(): void {
			Capsule::schema()->dropIfExists('badge');
			Capsule::schema()->dropIfExists('badge_settings');
			Capsule::schema()->dropIfExists('badge_submission');
			Capsule::schema()->dropIfExists('badge_submission_settings');
		}
}
