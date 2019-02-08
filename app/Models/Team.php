<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DociTeam extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @type array
     */
    protected $fillable = [];
    protected $table = "dociteam";
    public $timestamps = false;

    /*
    public $id;
    public $name;
    */
    
    public function owner()
    {
        return $this->belongsTo('App\Models\DociOwner', 'owner_id');
    }
}
