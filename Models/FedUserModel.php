<?php namespace OidConnect\Models;

use Illuminate\Database\Eloquent\Model;

class FedUserModel extends Model  {

    public function fedkeys ()  {
        return $this->hasMany('\OidConnect\Models\FedKeyModel','user_id','id');
    }
}
