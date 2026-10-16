<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->foreignId('author_id')->constrained()->restrictOnDelete();
            $table->string('title', 200);
            $table->string('slug', 220)->unique();
            $table->string('excerpt', 500)->nullable();
            $table->longText('body');
            $table->foreignId('featured_image_id')->nullable()->constrained('media')->nullOnDelete();
            $table->foreignId('og_image_id')->nullable()->constrained('media')->nullOnDelete();
            $table->string('status', 20)->default('draft');
            $table->dateTime('published_at')->nullable();
            $table->boolean('is_breaking')->default(false)->index();
            $table->boolean('is_featured')->default(false)->index();
            $table->unsignedInteger('view_count')->default(0);
            $table->string('meta_title', 70)->nullable();
            $table->string('meta_description', 160)->nullable();
            $table->string('canonical_url', 255)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['category_id', 'status', 'published_at']);
            $table->index(['status', 'published_at']);

            if (in_array(Schema::getConnection()->getDriverName(), ['mysql', 'mariadb'], true)) {
                $table->fullText(['title', 'excerpt']);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
