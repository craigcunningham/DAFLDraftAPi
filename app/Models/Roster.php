<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Roster extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @type array
     */
    //protected $fillable = [$2];
    protected $table = "roster";
    public $timestamps = false;
}