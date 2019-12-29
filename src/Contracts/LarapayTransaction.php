<?php


namespace Tartan\Larapay\Contracts;


interface LarapayTransaction
{

    /**
     * model model relation.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function model();
}