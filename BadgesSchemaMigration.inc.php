<?php

/**
 * @file BadgesSchemaMigration.inc.php
 *
 * Copyright (c) 2024+ glaux.es
 *
 * @class BadgesSchemaMigration
 * @brief Describe database table structures.
 */

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class BadgesSchemaMigration extends Migration
{
	/**
	 * Initialize the database connection for Capsule.
	 */
	private function initCapsule()
	{
		// Create a new Capsule instance
		$capsule = new Capsule();

		// Load configuration from config.inc.php
		$configFile = dirname(__DIR__, 3) . '/config.inc.php';
		if (!file_exists($configFile)) {
			error_log('Error: config.inc.php not found at ' . $configFile);
			throw new Exception('Configuration file config.inc.php not found.');
		}

		$config = parse_ini_file($configFile, true);
		if ($config === false) {
			error_log('Error: Failed to parse config.inc.php');
			throw new Exception('Failed to parse configuration file.');
		}

		$dbConfig = $config['database'] ?? [];
		$driver = $dbConfig['driver'] ?? 'mysql';
		// Map 'mysqli' to 'mysql' for Laravel compatibility
		if ($driver === 'mysqli') {
			$driver = 'mysql';
			error_log('Driver mapped from mysqli to mysql for Laravel compatibility.');
		}

		$connectionSettings = [
			'driver' => $driver,
			'host' => $dbConfig['host'] ?? 'localhost',
			'database' => $dbConfig['name'] ?? '',
			'username' => $dbConfig['username'] ?? '',
			'password' => $dbConfig['password'] ?? '',
			'charset' => 'utf8',
			'collation' => 'utf8_unicode_ci',
			'prefix' => '',
		];

		if (empty($connectionSettings['database']) || empty($connectionSettings['username'])) {
			error_log('Error: Database name or username not specified in config.inc.php');
			throw new Exception('Invalid database configuration in config.inc.php');
		}

		error_log('Database Config: ' . print_r($connectionSettings, true));

		try {
			$capsule->addConnection($connectionSettings, 'default');
			$capsule->setAsGlobal();
			$capsule->bootEloquent();
			error_log('Capsule connection initialized successfully: ' . $connectionSettings['database']);

			// Verify connection
			$capsule->getConnection()->getPdo();
			error_log('Database connection verified successfully.');
		} catch (Exception $e) {
			error_log('Failed to initialize Capsule connection: ' . $e->getMessage());
			throw $e;
		}
	}

	/**
	 * Run the migrations.
	 * @return void
	 */
	public function up()
	{
		try {
			$this->initCapsule();

			if (!Capsule::schema()->hasTable('badge')) {
				Capsule::schema()->create('badge', function (Blueprint $table) {
					$table->bigInteger('badge_id')->autoIncrement();
					$table->string('badge_name', 255);
					$table->longText('badge_description');
					$table->string('badge_image', 255);
				});
				error_log('Table badge created successfully.');
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
				error_log('Table badge_settings created successfully.');
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
				error_log('Table badge_submission created successfully.');
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
				error_log('Table badge_submission_settings created successfully.');
			}
		} catch (Exception $e) {
			error_log('Error during migration: ' . $e->getMessage());
			throw $e;
		}
	}

	/**
	 * Reverse the migrations.
	 * @return void
	 */
	public function down()
	{
		try {
			$this->initCapsule();

			Capsule::schema()->dropIfExists('badge_submission_settings');
			Capsule::schema()->dropIfExists('badge_submission');
			Capsule::schema()->dropIfExists('badge_settings');
			Capsule::schema()->dropIfExists('badge');
			error_log('Tables dropped successfully.');
		} catch (Exception $e) {
			error_log('Error during migration rollback: ' . $e->getMessage());
			throw $e;
		}
	}
}
