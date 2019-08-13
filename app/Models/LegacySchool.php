<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * LegacySchool
 *
 * @property LegacyInstitution $institution
 */
class LegacySchool extends Model
{
    /**
     * @var string
     */
    protected $table = 'pmieducar.escola';

    /**
     * @var string
     */
    protected $primaryKey = 'cod_escola';

    /**
     * @var array
     */
    protected $fillable = [
        'cod_escola',
        'ref_usuario_cad',
        'ref_cod_instituicao',
        'ref_cod_escola_rede_ensino',
        'sigla',
        'data_cadastro',
    ];

    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @return int
     */
    public function getIdAttribute()
    {
        return $this->cod_escola;
    }

    /**
     * Relacionamento com a instituição.
     *
     * @return BelongsTo
     */
    public function institution()
    {
        return $this->belongsTo(LegacyInstitution::class, 'ref_cod_instituicao');
    }

    /**
     * @return BelongsTo
     */
    public function person()
    {
        return $this->belongsTo(LegacyPerson::class, 'ref_idpes');
    }

    /**
     * @return BelongsToMany
     */
    public function courses()
    {
        return $this->belongsToMany(
            LegacyCourse::class,
            'pmieducar.escola_curso',
            'ref_cod_escola',
            'ref_cod_curso'
        )->withPivot('ativo', 'anos_letivos');
    }

    /**
     * @return BelongsTo
     */
    public function organization()
    {
        return $this->belongsTo(LegacyOrganization::class, 'ref_idpes');
    }
}
