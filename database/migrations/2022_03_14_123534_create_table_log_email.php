<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableLogEmail extends Migration
{
    public function up()
        {
            Schema::create('log_emails', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('emails_id');
                $table->string('tipo_doc',100);
                $table->string('numero_doc',10);
                $table->string('nome_pessoa',200);
                $table->string('observacao',255);
                $table->float('valor');
                $table->boolean('enviado');
                $table->timestamps();

                $table->foreign('emails_id', 'fk_emails_emails_id')->references('id')->on('emails')->onUpdate('NO ACTION')->onDelete('NO ACTION');

    
            });
    
        }
    
        /**
         * Reverse the migrations.
         *
         * @return void
         */
        public function down()
        {
            Schema::dropIfExists('log_emails');

        }
}
