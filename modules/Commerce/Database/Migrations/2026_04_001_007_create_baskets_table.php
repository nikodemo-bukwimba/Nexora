<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'commerce';

    public function up(): void
    {
        // A basket is the multi-seller shopping session.
        // On checkout it splits into one Order per seller.
        Schema::connection('commerce')->create('baskets', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->char('buyer_actor_id', 26)->unique(); // one active basket per buyer
            $table->string('status', 50)->default('active'); // active|checked_out|abandoned
            $table->char('promotion_code', 26)->nullable();  // soft FK to promotions
            $table->timestamps();

            $table->index('buyer_actor_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::connection('commerce')->dropIfExists('baskets');
    }
};
