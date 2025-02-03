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

      // Find all foreign keys referencing isite__organizations`
      $foreignKeys = \DB::select("
        SELECT DISTINCT TABLE_SCHEMA, TABLE_NAME, CONSTRAINT_NAME
        FROM information_schema.KEY_COLUMN_USAGE
        WHERE REFERENCED_TABLE_NAME = 'isite__organizations'
        AND REFERENCED_COLUMN_NAME = 'id'
        AND TABLE_SCHEMA = '".DB::getDatabaseName()."'"
      );

      //Delete old relation | Add new relation
      \DB::statement('SET FOREIGN_KEY_CHECKS=0');

      foreach ($foreignKeys as $fk) {
        \Log::info("Itenant: Dropping foreign key: " . $fk->CONSTRAINT_NAME . " from table: " . $fk->TABLE_NAME);

        // Drop foreign key using raw SQL
        \DB::statement("ALTER TABLE {$fk->TABLE_NAME} DROP FOREIGN KEY {$fk->CONSTRAINT_NAME}");

        \Log::info("Itenant: Dropped foreign key: " . $fk->CONSTRAINT_NAME . " from table: " . $fk->TABLE_NAME);

        // Add new foreign key constraint
        Schema::table($fk->TABLE_NAME, function (Blueprint $table) {
          $table->foreign('organization_id')
            ->references('id')
            ->on('itenant__organizations')
            ->onDelete('cascade');
        });

      }

      \DB::statement('SET FOREIGN_KEY_CHECKS=1');

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }

};
