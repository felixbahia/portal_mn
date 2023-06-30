<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableChequesDepositoDinheiro extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('cheques', function ($table) {
            $table->string('tipo')->default('cheque');
            $table->string('banco')->nullable()->change();
            $table->string('agencia')->nullable()->change();
            $table->string('conta')->nullable()->change();
            $table->string('numero_cheque')->nullable()->change();
            $table->date('bom_para')->nullable()->change();
        });

        Schema::table('cheques', function ($table) {
            $table->string('tipo')->default(NULL)->change();
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('cheques', function ($table) {
            $table->dropColumn('tipo');
            $table->string('banco')->nullable(false)->change();
            $table->string('agencia')->nullable(false)->change();
            $table->string('conta')->nullable(false)->change();
            $table->string('numero_cheque')->nullable(false)->change();
            $table->date('bom_para')->nullable(false)->change();
        });
    }
}
