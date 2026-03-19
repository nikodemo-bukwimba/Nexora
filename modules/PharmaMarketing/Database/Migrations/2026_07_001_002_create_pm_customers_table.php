<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'pharma_marketing';

    public function up(): void
    {
        Schema::connection('pharma_marketing')->create('pm_customers', function (Blueprint $table) {
            $table->char('id', 26)->primary();

            // Owning org + assigned officer
            $table->char('org_id', 26);                       // Barick org that owns this customer
            $table->char('assigned_officer_id', 26)->nullable(); // Platform actor_id of field officer

            // Customer identity
            $table->string('customer_type', 50)->default('b2b'); // b2b|b2c
            $table->string('name', 255);                       // Business name or full name
            $table->string('code', 100)->nullable();            // Internal customer code
            $table->string('category', 100)->nullable();        // clinic|hospital|pharmacy|wholesaler|individual|other
            $table->string('tier', 50)->default('standard');   // standard|silver|gold|platinum
            $table->string('status', 50)->default('active');   // active|inactive|blacklisted

            // B2B specific
            $table->string('business_registration', 100)->nullable();
            $table->string('tax_pin', 100)->nullable();

            // Location
            $table->text('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('county', 100)->nullable();
            $table->string('country', 100)->default('Kenya');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->integer('gps_accuracy_meters')->nullable();

            // Contact
            $table->string('phone', 30)->nullable();
            $table->string('alt_phone', 30)->nullable();
            $table->string('email', 255)->nullable();
            $table->string('whatsapp_number', 30)->nullable();   // may differ from phone

            // Preferences
            $table->boolean('receives_whatsapp')->default(true);
            $table->boolean('receives_sms')->default(true);
            $table->boolean('receives_in_app')->default(true);

            // Business metrics
            $table->decimal('credit_limit', 15, 4)->default(0);
            $table->char('currency', 3)->default('KES');

            $table->text('notes')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('org_id');
            $table->index('assigned_officer_id');
            $table->index('customer_type');
            $table->index('status');
            $table->index('category');
            $table->index('tier');
            $table->unique(['org_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::connection('pharma_marketing')->dropIfExists('pm_customers');
    }
};
