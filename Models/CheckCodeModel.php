<?php namespace OidConnect\Models;
/**
 * Author: Fulup Ar Foll
 * Date: 09/12/14
 * Time: 20:59
 * File: OrangeModel.php
 * Copyright: 2014 GeoToBe All Rights Reserved
 */
use Illuminate\Database\Eloquent\Model;

class CheckCodeModel extends Model {

	protected $table = 'check_codes';

    protected $primaryKey = 'id';
    protected $fillable = array( 'code','email','user_id');

    public function user ()  {
        return $this->belongsTo('OidConnect\Models\FedUserModel','id','user_id');
    }

}