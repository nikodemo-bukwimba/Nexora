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

            /*
            |--------------------------------------------------------------------------
            | Organization Ownership
            |--------------------------------------------------------------------------
            | org_id:
            |     Root organization / tenant owner.
            |
            | home_branch_id:
            |     Original or primary branch associated with the customer.
            |
            | assigned_officer_id:
            |     Marketing/sales officer responsible for the customer.
            |--------------------------------------------------------------------------
            */

            $table->char('org_id', 26)->index();

            $table->char('home_branch_id', 26)
                ->nullable()
                ->index();

            $table->char('assigned_officer_id', 26)
                ->nullable()
                ->index();

            $table->char('platform_user_id', 26)
                ->nullable()
                ->index();

            /*
            |--------------------------------------------------------------------------
            | Registration
            |--------------------------------------------------------------------------
            */

            // admin | self | import
            $table->string('registration_source', 30)
                ->default('admin')
                ->index();

            /*
            |--------------------------------------------------------------------------
            | Customer Identity
            |--------------------------------------------------------------------------
            */

            // b2b | b2c | hospital | pharmacy | wholesaler etc
            $table->string('customer_type', 50)
                ->default('b2b')
                ->index();

            $table->string('name', 255);

            $table->string('code', 100)
                ->nullable();

            $table->string('category', 100)
                ->nullable()
                ->index();

            $table->string('tier', 50)
                ->default('standard')
                ->index();

            // active | inactive | blocked | archived
            $table->string('status', 50)
                ->default('active')
                ->index();

            /*
            |--------------------------------------------------------------------------
            | Business Details
            |--------------------------------------------------------------------------
            */

            $table->string('business_registration', 100)
                ->nullable();

            $table->string('tax_pin', 100)
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Location
            |--------------------------------------------------------------------------
            */

            $table->text('address')
                ->nullable();

            // Tanzania hierarchy:
            // country -> region(county) -> district(city) -> ward -> street

            $table->string('county', 100)
                ->nullable();

            $table->string('city', 100)
                ->nullable();

            $table->string('ward', 100)
                ->nullable();

            $table->string('street', 100)
                ->nullable();

            $table->string('country', 100)
                ->default('TANZANIA');

            $table->decimal('latitude', 10, 7)
                ->nullable();

            $table->decimal('longitude', 10, 7)
                ->nullable();

            $table->integer('gps_accuracy_meters')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Contact Information
            |--------------------------------------------------------------------------
            */

            $table->string('phone', 30)
                ->nullable();

            $table->string('alt_phone', 30)
                ->nullable();

            $table->string('email', 255)
                ->nullable();

            $table->string('whatsapp_number', 30)
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Communication Preferences
            |--------------------------------------------------------------------------
            */

            $table->boolean('receives_whatsapp')
                ->default(true);

            $table->boolean('receives_sms')
                ->default(true);

            $table->boolean('receives_in_app')
                ->default(true);

            /*
            |--------------------------------------------------------------------------
            | Financial
            |--------------------------------------------------------------------------
            */

            $table->decimal('credit_limit', 15, 4)
                ->default(0);

            $table->char('currency', 3)
                ->default('TZS');

            /*
            |--------------------------------------------------------------------------
            | Extra Data
            |--------------------------------------------------------------------------
            */

            $table->text('notes')
                ->nullable();

            $table->jsonb('metadata')
                ->nullable();

            $table->timestamps();
            $table->softDeletes();

            /*
            |--------------------------------------------------------------------------
            | Constraints
            |--------------------------------------------------------------------------
            |
            | Customer codes are branch-scoped within a root organization.
            |--------------------------------------------------------------------------
            */

            $table->unique([
                'org_id',
                'home_branch_id',
                'code',
            ], 'pm_customers_org_branch_code_unique');
        });
    }

    public function down(): void
    {
        Schema::connection('pharma_marketing')
            ->dropIfExists('pm_customers');
    }
};