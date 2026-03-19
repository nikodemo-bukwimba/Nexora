<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'pharma_marketing';

    public function up(): void
    {
        Schema::connection('pharma_marketing')->create('pm_visit_attachments', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->char('visit_id', 26);
            $table->char('uploaded_by', 26);                // actor_id of uploader
            $table->string('type', 50);                     // photo|document
            $table->string('file_name', 255);
            $table->string('file_url', 1000);               // URL in Laravel storage
            $table->string('mime_type', 100)->nullable();
            $table->bigInteger('file_size_bytes')->nullable();
            $table->integer('width')->nullable();
            $table->integer('height')->nullable();
            $table->text('caption')->nullable();
            $table->decimal('latitude', 10, 7)->nullable(); // GPS embedded in photo (EXIF)
            $table->decimal('longitude', 10, 7)->nullable();
            $table->jsonb('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('visit_id');

            $table->foreign('visit_id')
                  ->references('id')->on('pm_field_visits')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::connection('pharma_marketing')->dropIfExists('pm_visit_attachments');
    }
};
