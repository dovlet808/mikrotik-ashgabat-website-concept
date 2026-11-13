<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inquiries', function (Blueprint $table) {
            $table->id();
            $table->string('type', 20)->default('contact');
            $table->string('status', 20)->default('new')->index();
            $table->string('name', 120);
            $table->string('phone', 40);
            $table->string('email', 160)->nullable();
            $table->string('preferred_contact', 20)->default('phone');
            $table->text('message')->nullable();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('service_id')->nullable()->constrained()->nullOnDelete();
            $table->string('product_snapshot')->nullable();
            $table->string('locale', 5)->default('ru');
            $table->string('page_url', 500)->nullable();
            $table->char('ip_hash', 64)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->timestamp('handled_at')->nullable();
            $table->timestamps();

            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inquiries');
    }
};
