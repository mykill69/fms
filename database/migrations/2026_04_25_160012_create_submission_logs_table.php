<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('submission_logs', function (Blueprint $table) {
            $table->id();
            $table->string('ip_address', 45);
            $table->text('user_agent')->nullable();
            $table->string('session_id');
            $table->string('department');
            $table->string('device_fingerprint');
            $table->unsignedBigInteger('feedback_id')->nullable();
            $table->integer('rating')->default(0);
            $table->enum('status', ['allowed', 'blocked'])->default('allowed');
            $table->string('block_reason')->nullable();
            $table->timestamps();
            
            $table->index('ip_address');
            $table->index('department');
            $table->index('device_fingerprint');
            $table->index('status');
            $table->index('created_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('submission_logs');
    }
};