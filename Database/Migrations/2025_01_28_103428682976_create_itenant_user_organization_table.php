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
        Schema::create('itenant__user_organization', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            // Your fields...

            $table->integer('user_id')->unsigned();
            $table->foreign('user_id')->references('id')->on(config('auth.table', 'users'))->onDelete('cascade');

            $table->integer('role_id')->unsigned()->nullable();
            $table->foreign('role_id')->references('id')->on(config('auth.table', 'roles'))->onDelete('restrict');

            $table->text('permissions')->nullable();
            
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
        Schema::dropIfExists('itenant__user_organization');
    }
};
