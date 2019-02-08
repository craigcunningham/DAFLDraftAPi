<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Team extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @type array
     */
    protected $fillable = [];
    protected $table = "team";
    public $timestamps = false;
}
