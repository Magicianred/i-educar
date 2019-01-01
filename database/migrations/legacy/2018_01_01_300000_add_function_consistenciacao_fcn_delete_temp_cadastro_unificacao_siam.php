<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddFunctionConsistenciacaoFcnDeleteTempCadastroUnificacaoSiam extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::unprepared(
            file_get_contents(__DIR__ . '/../../sqls/functions/consistenciacao.fcn_delete_temp_cadastro_unificacao_siam.sql')
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::unprepared(
            'DROP FUNCTION consistenciacao.fcn_delete_temp_cadastro_unificacao_siam(integer);'
        );
    }
}
