<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('feedback_analysis', function (Blueprint $table) {
            $table->id();

            // relation
            $table->foreignId('feedback_id')
                ->constrained('feedbacks')
                ->onDelete('cascade');

            // AI OUTPUT FIELDS (Ollama / Gemma 4)
            $table->text('issue')->nullable();
            $table->string('sentiment')->nullable();   // positive | negative | neutral
            $table->string('priority')->nullable();     // low | medium | high
            $table->string('department')->nullable();   // IT | Registrar | etc

            // optional future expansion (safe for your dashboard)
            $table->text('raw_response')->nullable();   // full AI output if needed

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('feedback_analysis');
    }
};
