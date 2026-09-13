<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class CondominioScope implements Scope
{
    /**
     * Aplica el scope global para filtrar por condominio_id
     * Lee dinámicamente la cabecera X-Condominio-Id con validación de seguridad
     */
    public function apply(Builder $builder, Model $model): void
    {
        if (Auth::check()) {
            $user = Auth::user();
            $table = $model->getTable();
            
            // 1. Obtener ID solicitado en cabecera HTTP o parámetro
            $solicitado = request()->header('X-Condominio-Id') ?? request()->condominio_id;

            if ($user->esMaster()) {
                if ($solicitado) {
                    $builder->where($table . '.condominio_id', (int)$solicitado);
                }
                return;
            }

            // 2. Si hay cabecera X-Condominio-Id y el usuario tiene acceso a ella
            if ($solicitado) {
                $solicitadoId = (int)$solicitado;
                if ($user->puedeAccederCondominio($solicitadoId)) {
                    $builder->where($table . '.condominio_id', $solicitadoId);
                    return;
                }
            }

            // 3. Fallback al condominio asignado por defecto
            if ($user->condominio_id) {
                $builder->where($table . '.condominio_id', (int)$user->condominio_id);
                return;
            }

            // 4. Fallback al primer condominio asignado en tabla pivote
            $primerCondoId = $user->condominios()->value('condominios.id');
            if ($primerCondoId) {
                $builder->where($table . '.condominio_id', (int)$primerCondoId);
                return;
            }
        }
    }
}
