<?php

use App\Models\Employee;
use App\Models\EmployeeWithdrawal;
use App\Support\View\Employee\EmployeeReturn;

return new class extends clsDetalhe {
    public $titulo;

    /**
     * Atributos de dados
     */
    public $cod_servidor = null;
    public $ref_idesco = null;
    public $ref_cod_funcao = null;
    public $carga_horaria = null;
    public $data_cadastro = null;
    public $data_exclusao = null;
    public $ativo = null;
    public $ref_cod_instituicao = null;
    public $alocacao_array = [];
    public $is_professor = false;

    /**
     * Implementação do método Gerar()
     */
    public function Gerar()
    {
        $this->titulo = 'Servidor - Detalhe';

        $this->cod_servidor        = $_GET['cod_servidor'];
        $this->ref_cod_instituicao = $_GET['ref_cod_instituicao'];

        $tmp_obj = new clsPmieducarServidor($this->cod_servidor, null, null, null, null, null, null, $this->ref_cod_instituicao);

        $registro = $tmp_obj->detalhe();

        if (!$registro) {
            $this->simpleRedirect('educar_servidor_lst.php');
        }

        // Escolaridade
        $obj_ref_idesco = new clsCadastroEscolaridade($registro['ref_idesco']);
        $det_ref_idesco = $obj_ref_idesco->detalhe();
        $registro['ref_idesco'] = $det_ref_idesco['descricao'];

        // Função
        $obj_ref_cod_funcao = new clsPmieducarFuncao($registro['ref_cod_funcao'], null, null, null, null, null, null, null, null, $this->ref_cod_instituicao);
        $det_ref_cod_funcao = $obj_ref_cod_funcao->detalhe();
        $registro['ref_cod_funcao'] = $det_ref_cod_funcao['nm_funcao'];

        // Nome
        $obj_cod_servidor      = new clsFuncionario($registro['cod_servidor']);
        $det_cod_servidor      = $obj_cod_servidor->detalhe();
        $registro['matricula'] = $det_cod_servidor['matricula'];

        $obj_cod_servidor      = new clsPessoaFisica($registro['cod_servidor']);
        $det_cod_servidor      = $obj_cod_servidor->detalhe();
        $registro['nome'] = $det_cod_servidor['nome'];

        // Instituição
        $obj_ref_cod_instituicao = new clsPmieducarInstituicao($registro['ref_cod_instituicao']);
        $det_ref_cod_instituicao = $obj_ref_cod_instituicao->detalhe();
        $registro['ref_cod_instituicao'] = $det_ref_cod_instituicao['nm_instituicao'];

        // Alocação do servidor
        $obj = new clsPmieducarServidorAlocacao();
        $obj->setOrderby('periodo, carga_horaria');
        $lista = $obj->lista(
            null,
            $this->ref_cod_instituicao,
            null,
            null,
            null,
            $this->cod_servidor,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            1,
            date('Y')
        );

        if ($lista) {
            // Passa todos os valores do registro para atributos do objeto
            foreach ($lista as $campo => $val) {
                $temp = [];
                $temp['carga_horaria'] = $val['carga_horaria'];
                $temp['periodo'] = $val['periodo'];

                $obj_escola = new clsPmieducarEscola($val['ref_cod_escola']);
                $det_escola = $obj_escola->detalhe();
                $det_escola = $det_escola['nome'];
                $temp['ref_cod_escola'] = $det_escola;

                $this->alocacao_array[] = $temp;
            }
        }

        if ($registro['cod_servidor']) {
            $this->addDetalhe(['Servidor', $registro['cod_servidor']]);
        }

        if ($registro['nome']) {
            $this->addDetalhe(['Nome', $registro['nome']]);
        }

        // Dados no Educacenso/Inep.
        $docenteMapper = new Educacenso_Model_DocenteDataMapper();

        $docenteInep = null;
        try {
            $docenteInep = $docenteMapper->find(['docente' => $registro['cod_servidor']]);
        } catch (Exception $e) {
        }

        if (isset($docenteInep)) {
            $this->addDetalhe(['Código Educacenso/Inep', $docenteInep->docenteInep]);

            if (isset($docenteInep->nomeInep)) {
                $this->addDetalhe(['Nome Educacenso/Inep', $docenteInep->nomeInep]);
            }
        }

        if ($registro['idpes']) {
            $this->addDetalhe(['Nome', $registro['Nome']]);
        }

        if ($registro['ref_cod_instituicao']) {
            $this->addDetalhe([ 'Instituição', $registro['ref_cod_instituicao']]);
        }

        if ($registro['ref_idesco']) {
            $this->addDetalhe(['Escolaridade', $registro['ref_idesco']]);
        }

        if ($registro['ref_cod_subnivel']) {
            $obj_nivel = new clsPmieducarSubnivel($registro['ref_cod_subnivel']);
            $det_nivel = $obj_nivel->detalhe();

            $this->addDetalhe(['Nível', $det_nivel['nm_subnivel']]);
        }

        if ($registro['ref_cod_funcao']) {
            $this->addDetalhe(['Função', $registro['ref_cod_funcao']]);
        }

        $this->addDetalhe(
            [
        'Multisseriado',
        dbBool($registro['multi_seriado']) ? 'Sim' : 'Não'
      ]
        );

        $obj_funcao = new clsPmieducarServidorFuncao();
        $lst_funcao = $obj_funcao->lista($this->ref_cod_instituicao, $this->cod_servidor);

        if ($lst_funcao) {
            $tabela .= '
        <table cellspacing=\'0\' cellpadding=\'0\' border=\'0\'>
          <tr bgcolor=\'#ccdce6\' align=\'center\'>
            <td width=\'150\'>Função</td>
            <td width=\'150\'>Matrícula</td>
          </tr>';

            $class = 'formlttd';

            $tab_disc = null;

            $employee = Employee::findOrFail($this->cod_servidor);
            $disciplines = $employee->disciplines;

            if ($disciplines) {
                $tab_disc .= '<table cellspacing=\'0\' cellpadding=\'0\' width=\'200\' border=\'0\'';

                $class2 = $class2 == 'formlttd' ? 'formmdtd' : 'formlttd' ;
                $tab_disc .= '
          <tr>
            <td bgcolor=\'#ccdce6\' align=\'center\'>Componentes Curriculares</td>
          </tr>';

                $componenteMapper = new ComponenteCurricular_Model_ComponenteDataMapper();
                foreach ($disciplines as $discipline) {
                    $tab_disc .= "
            <tr class='$class2' align='center'>
              <td align='left'>{$discipline->name}</td>
            </tr>";

                    $class2 = $class2 == 'formlttd' ? 'formmdtd' : 'formlttd' ;
                }

                $tab_disc .= '</table>';
            }

            $obj_servidor_curso = new clsPmieducarServidorCursoMinistra();
            $lst_servidor_curso = $obj_servidor_curso->lista(null, $this->ref_cod_instituicao, $this->cod_servidor);

            if ($lst_servidor_curso) {
                $tab_curso .= '<table cellspacing=\'0\' cellpadding=\'0\' width=\'200\' border=\'0\'';

                $class2 = $class2 == 'formlttd' ? 'formmdtd' : 'formlttd' ;
                $tab_curso .= '
          <tr>
            <td bgcolor=\'#ccdce6\' align=\'center\'>Cursos Ministrados</td>
          </tr>';

                foreach ($lst_servidor_curso as $curso) {
                    $obj_curso = new clsPmieducarCurso($curso['ref_cod_curso']);
                    $det_curso = $obj_curso->detalhe();

                    $tab_curso .= "
            <tr class='$class2' align='center'>
              <td align='left'>{$det_curso['nm_curso']}</td>
            </tr>";

                    $class2 = $class2 == 'formlttd' ? 'formmdtd' : 'formlttd' ;
                }

                $tab_curso .= '</table>';
            }

            foreach ($lst_funcao as $funcao) {
                $obj_funcao = new clsPmieducarFuncao($funcao['ref_cod_funcao']);
                $det_funcao = $obj_funcao->detalhe();

                $tabela .= "
          <tr class='$class' align='left'>
            <td><b>{$det_funcao['nm_funcao']}</b></td>
            <td align='center'>{$funcao['matricula']}</td>
          </tr>";
                if (!$this->is_professor) {
                    $this->is_professor = (bool) $det_funcao['professor'];
                }

                $class = $class == 'formlttd' ? 'formmdtd' : 'formlttd' ;
            }

            if ($tab_curso) {
                $tabela .= "
          <tr class='$class' align='center'>
            <td style='padding:5px'>$tab_curso</td>
          </tr>";
            }

            if ($tab_disc) {
                $tabela .= "
          <tr class='$class' align='center'>
            <td style='padding:5px'>$tab_disc</td>
          </tr>";
            }

            $tabela .= '</table>';
            $this->addDetalhe(['Função', '<a href=\'javascript:trocaDisplay("det_f");\' >Mostrar detalhe</a><div id=\'det_f\' name=\'det_f\' style=\'display:none;\'>'.$tabela.'</div>']);
        }

        $tabela = null;

        /**
         * @todo  Criar função de transformação de hora decimal. Ver educar_servidor_cad.php em 276
         */
        if ($registro['carga_horaria']) {
            $cargaHoraria = $registro['carga_horaria'];
            $horas   = (int)$cargaHoraria;
            $minutos = round(($cargaHoraria - $horas) * 60);
            $cargaHoraria = sprintf('%02d:%02d', $horas, $minutos);
            $this->addDetalhe(['Carga Horária', $cargaHoraria]);
        }

        $dias_da_semana = [
      '' => 'Selecione',
      1  => 'Domingo',
      2  => 'Segunda',
      3  => 'Terça',
      4  => 'Quarta',
      5  => 'Quinta',
      6  => 'Sexta',
      7  => 'Sábado'
    ];

        if ($this->alocacao_array) {
            $tabela .= '
        <table cellspacing=\'0\' cellpadding=\'0\' border=\'0\'>
          <tr bgcolor=\'#ccdce6\' align=\'center\'>
            <td width=\'150\'>Carga Horária</td>
            <td width=\'80\'>Período</td>
            <td width=\'150\'>Escola</td>
          </tr>';

            $class = 'formlttd';
            foreach ($this->alocacao_array as $alocacao) {
                switch ($alocacao['periodo']) {
          case 1:
            $nm_periodo = 'Matutino';

            break;
          case 2:
            $nm_periodo = 'Vespertino';

            break;
          case 3:
            $nm_periodo = 'Noturno';

            break;
        }

                $tabela .= "
          <tr class='$class' align='center'>
            <td>{$alocacao['carga_horaria']}</td>
            <td>{$nm_periodo}</td>
            <td>{$alocacao['ref_cod_escola']}</td>
          </tr>";

                $class = $class == 'formlttd' ? 'formmdtd' : 'formlttd';
            }

            $tabela .= '</table>';

            $this->addDetalhe(['Horários de trabalho',
        '<a href=\'javascript:trocaDisplay("det_pree");\' >Mostrar detalhe</a><div id=\'det_pree\' name=\'det_pree\' style=\'display:none;\'>'.$tabela.'</div>']);
        }

        // Horários do professor
        $horarios = $tmp_obj->getHorariosServidor($registro['cod_servidor'], $this->ref_cod_instituicao);

        if ($horarios) {
            $tabela = '
        <table cellspacing=\'0\' cellpadding=\'0\' border=\'0\'>
          <tr bgcolor=\'#ccdce6\' align=\'center\'>
            <td width=\'150\'>Escola</td>
            <td width=\'100\'>Curso</td>
            <td width=\'70\'>Série</td>
            <td width=\'70\'>Turma</td>
            <td width=\'100\'>Componente curricular</td>
            <td width=\'70\'>Dia da semana</td>
            <td width=\'70\'>Hora inicial</td>
            <td width=\'70\'>Hora final</td>
          </tr>';

            foreach ($horarios as $horario) {
                $class = $class == 'formlttd' ? 'formmdtd' : 'formlttd';

                $tabela .= sprintf(
                    '
          <tr class="%s" align="center">
            <td>%s</td>
            <td>%s</td>
            <td>%s</td>
            <td>%s</td>
            <td>%s</td>
            <td>%s</td>
            <td>%s</td>
            <td>%s</td>
          </tr>',
                    $class,
                    $horario['nm_escola'],
                    $horario['nm_curso'],
                    $horario['nm_serie'],
                    $horario['nm_turma'],
                    $horario['nome'],
                    $dias_da_semana[$horario['dia_semana']],
                    $horario['hora_inicial'],
                    $horario['hora_final']
                );
            }

            $tabela .= '</table>';

            $this->addDetalhe([
        'Horários de aula',
        '<a href=\'javascript:trocaDisplay("horarios");\' >Mostrar detalhes</a>' .
        '<div id=\'horarios\' name=\'det_pree\' style=\'display:none;\'>' . $tabela . '</div>'
      ]);
        }

        $obj_permissoes = new clsPermissoes();
        if ($obj_permissoes->permissao_cadastra(635, $this->pessoa_logada, 7)) {
            $this->url_novo   = 'educar_servidor_cad.php';
            $this->url_editar = "educar_servidor_cad.php?cod_servidor={$registro['cod_servidor']}&ref_cod_instituicao={$this->ref_cod_instituicao}";

            $get_padrao ="ref_cod_servidor={$registro['cod_servidor']}&ref_cod_instituicao={$this->ref_cod_instituicao}";

            $this->array_botao = [];
            $this->array_botao_url_script = [];

            $this->array_botao[] = 'Avaliação de Desempenho';
            $this->array_botao_url_script[] = "go(\"educar_avaliacao_desempenho_lst.php?{$get_padrao}\");";
            /***************************************************************************************************************
             *** Avaliando remoção pois será criado aba nova no próprio cadastro/edit do servidor com informações de cursos
             *** e escolaridade normalizados pelo censo
             ***************************************************************************************************************
            $this->array_botao[] = 'Formação';
            $this->array_botao_url_script[] = "go(\"educar_servidor_formacao_lst.php?{$get_padrao}\");";

            $this->array_botao[] = 'Cursos superiores/Licenciaturas';
            $this->array_botao_url_script[] = sprintf(
              "go(\"../module/Docente/index?servidor=%d&instituicao=%d\");",
              $registro['cod_servidor'], $this->ref_cod_instituicao
            );*/

            $this->array_botao[] = 'Faltas/Atrasos';
            $this->array_botao_url_script[] = "go(\"educar_falta_atraso_lst.php?{$get_padrao}\");";

            $this->array_botao[] = 'Alocar Servidor';
            $this->array_botao_url_script[] = "go(\"educar_servidor_alocacao_lst.php?{$get_padrao}\");";

            $this->array_botao[] = 'Alterar Nível';
            $this->array_botao_url_script[] = 'popless();';

            $obj_servidor_alocacao = new clsPmieducarServidorAlocacao();
            $lista_alocacao = $obj_servidor_alocacao->lista(
                null,
                $this->ref_cod_instituicao,
                null,
                null,
                null,
                $this->cod_servidor,
                null,
                null,
                null,
                null,
                null,
                null,
                null,
                null,
                null,
                1
            );

            if ($lista) {
                $this->array_botao[] = 'Substituir Horário Servidor';
                $this->array_botao_url_script[] = "go(\"educar_servidor_substituicao_cad.php?{$get_padrao}\");";
            }

            $obj_afastamento = new clsPmieducarServidorAfastamento();
            $afastamento = $obj_afastamento->afastado($this->cod_servidor, $this->ref_cod_instituicao);

            if (is_numeric($afastamento) && $afastamento == 0) {
                $this->array_botao[] = 'Afastar Servidor';
                $this->array_botao_url_script[] = "go(\"educar_servidor_afastamento_cad.php?{$get_padrao}\");";
            } elseif (is_numeric($afastamento)) {
                $this->array_botao[] = 'Retornar Servidor';
                $this->array_botao_url_script[] = "go(\"educar_servidor_afastamento_cad.php?{$get_padrao}&sequencial={$afastamento}&retornar_servidor=" . EmployeeReturn::SIM . '");';
            }

            if ($this->is_professor) {
                $this->array_botao[] = 'Vincular professor a turmas';
                $this->array_botao_url_script[] = "go(\"educar_servidor_vinculo_turma_lst.php?{$get_padrao}\");";
            }
        }

        $withdrawals = EmployeeWithdrawal::query()->where('ref_cod_servidor', $this->cod_servidor)->get();

        if (count($withdrawals) > 0) {
            $this->addHtml(view('employee-withdrawal.employee-withdrawal', ['withdrawals' => $withdrawals])->render());
        }

        $this->url_cancelar = 'educar_servidor_lst.php';
        $this->largura = '100%';

        $this->breadcrumb('Funções do servidor', [
        url('intranet/educar_servidores_index.php') => 'Servidores',
    ]);
    }

    public function makeExtra()
    {
        return str_replace(
            ['#cod_servidor','#ref_cod_instituicao'],
            [$_GET['cod_servidor'],$_GET['ref_cod_instituicao']],
            file_get_contents(__DIR__ . '/scripts/extra/educar-servidor-det.js')
        );
    }

    public function Formular()
    {
        $this->title = 'Servidores - Servidor';
        $this->processoAp = 635;
    }
};
