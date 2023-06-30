<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AlterarTabelaCampanhaAlterarTipoComissao extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement('ALTER TABLE campanhas ALTER COLUMN tipo_comissao_representante TYPE integer USING (tipo_comissao_representante)::integer');
        DB::statement('ALTER TABLE campanhas ALTER COLUMN tipo_comissao_vendedor_interno TYPE integer USING (tipo_comissao_vendedor_interno)::integer');
        DB::statement('ALTER TABLE campanhas ALTER COLUMN tipo_comissao_gerente TYPE integer USING (tipo_comissao_gerente)::integer');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('ALTER TABLE campanhas ALTER COLUMN tipo_comissao_representante TYPE boolean USING (tipo_comissao_representante)::boolean');
        DB::statement('ALTER TABLE campanhas ALTER COLUMN tipo_comissao_vendedor_interno TYPE boolean USING (tipo_comissao_vendedor_interno)::boolean');
        DB::statement('ALTER TABLE campanhas ALTER COLUMN tipo_comissao_gerente TYPE boolean USING (tipo_comissao_gerente)::boolean');
    }
}
