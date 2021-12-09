<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Position extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @type array
     */
    //protected $fillable = [$2];
    protected $table = "position";
    public $timestamps = false;
}