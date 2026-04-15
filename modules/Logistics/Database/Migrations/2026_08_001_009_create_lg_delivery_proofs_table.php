<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'logistics';

    public function up(): void
    {
        // Proof of delivery — photo, signature, and/or confirmation code
        Schema::connection('logistics')->create('lg_delivery_proofs', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->char('stop_id', 26)->unique();               // one proof record per stop

            // Photo proof
            $table->string('photo_url', 1000)->nullable();
            $table->decimal('photo_latitude', 10, 7)->nullable();
            $table->decimal('photo_longitude', 10, 7)->nullable();

            // Signature proof
            $table->string('signature_url', 1000)->nullable();   // stored as image file
            $table->string('signed_by_name', 255)->nullable();   // name written by signer

            // Confirmation code proof
            $table->string('confirmation_code', 20)->nullable(); // code given to customer
            $table->timestamp('code_confirmed_at')->nullable();

            $table->char('captured_by', 26)->nullable();         // driver actor_id
            $table->timestamp('captured_at')->useCurrent();

            $table->foreign('stop_id')
                  ->references('id')->on('lg_delivery_stops')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::connection('logistics')->dropIfExists('lg_delivery_proofs');
    }
};
