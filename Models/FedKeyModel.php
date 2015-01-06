<?php namespace OidConnect\Models;
/**
 * Author: Fulup Ar Foll
 * Date: 09/12/14
 * Time: 20:59
 * File: OrangeModel.php
 * Copyright: 2014 GeoToBe All Rights Reserved
 */
use Illuminate\Database\Eloquent\Model;

class FedKeyModel extends Model {

	protected $table = 'federation_keys';

    protected $fillable = array( 'social_uid','idp_uid','social_loa','user_id');
    protected $primaryKey = 'id';

    public function user ()  {
        return $this->belongsTo('OidConnect\Models\FedUserModel','id','user_id');
    }

}