<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visitor_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->foreignId('guard_id')->constrained()->cascadeOnDelete();
            $table->string('visitor_name');
            $table->string('mobile')->nullable();
            $table->string('purpose')->nullable();
            $table->string('person_to_meet')->nullable();
            $table->string('vehicle_number')->nullable();
            $table->string('id_type')->nullable();
            $table->string('id_number')->nullable();
            $table->string('photo')->nullable();
            $table->timestamp('entry_time');
            $table->timestamp('exit_time')->nullable();
            $table->text('remarks')->nullable();
            $table->decimal('entry_latitude', 10, 7)->nullable();
            $table->decimal('entry_longitude', 10, 7)->nullable();
            $table->decimal('exit_latitude', 10, 7)->nullable();
            $table->decimal('exit_longitude', 10, 7)->nullable();
            $table->string('status')->default('inside');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['site_id', 'status']);
            $table->index('entry_time');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visitor_entries');
    }
};
