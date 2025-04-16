<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('itenant__organizations', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            // Your fields...
            $table->integer('user_id')->unsigned()->nullable();
            $table->foreign('user_id')->references('id')->on(config('auth.table', 'users'))->onDelete('restrict');
            $table->integer('featured')->default(0);
            $table->integer('sort_order')->default(0);
            $table->tinyInteger('status')->default(1)->unsigned();
            $table->text('options')->nullable();
            $table->text('permissions')->nullable();
            $table->text('data')->nullable();
            $table->integer('enable')->default(0);
            $table->text('maintenance_mode')->nullable();

            $table->integer('category_id')->unsigned()->nullable();
            $table->foreign('category_id')->references('id')->on('itenant__categories')->onDelete('restrict');

            // Audit fields
            $table->timestamps();
            $table->auditStamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('itenant__organizations');
    }
};
