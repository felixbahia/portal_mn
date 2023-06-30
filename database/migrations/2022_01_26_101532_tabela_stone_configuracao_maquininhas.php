<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class TabelaStoneConfiguracaoMaquininhas extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('stone_configuracao_maquininhas', function (Blueprint $table) {
            $table->increments('id');
            $table->string('use_without_pos_config')->nullable();
            $table->string('activate_linked_pos_config')->nullable();
            $table->string('activate_unlinked_and_linked_pos_config')->nullable();
            $table->string('activate_single_information_automatic_select')->nullable();
            $table->string('activate_dispose_transaction_any_pos')->nullable();
            $table->string('lock_app')->nullable();
            $table->string('view_error_request')->nullable();
            $table->string('display_view_cancel_pre_transaction')->nullable();
            $table->string('instruction_activation_time')->nullable();
            $table->string('pos_configuration_control_id')->nullable();
            $table->string('cashier_number')->nullable();
            $table->string('pdv_number');
            $table->string('pos_link_label')->nullable();
            $table->string('pos_reference_id_to_link')->nullable();
            $table->string('pos_serial_number_to_link')->nullable();
            $table->integer('stone_cadastro_maquininha_id');
            $table->integer('created_by');
            $table->integer('updated_by')->nullable();
            $table->integer('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('stone_cadastro_maquininha_id')
                ->references('id')
                ->on('stone_cadastro_maquininhas')
                ->onDelete('NO ACTION');
            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->onDelete('NO ACTION');
            $table->foreign('updated_by')
                ->references('id')
                ->on('users')
                ->onDelete('NO ACTION');
            $table->foreign('deleted_by')
                ->references('id')
                ->on('users')
                ->onDelete('NO ACTION');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('stone_configuracao_maquininhas');
    }
}
