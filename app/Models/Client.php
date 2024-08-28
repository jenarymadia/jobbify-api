<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Client extends Model
{
    use HasFactory;

    protected $guarded = [];

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        static::creating(function ($client) {
            $client->user_id = Auth::id();
            $client->team_id = Auth::user()->personalTeam->id;
        });
    }

    public function tags()
    {
        return $this->hasMany(ClientTag::class, 'client_id', 'id');
    }
}
