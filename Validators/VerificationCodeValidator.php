<?php namespace OidConnect\Validators;
/**
 * User: Fulup Ar Foll
 * Date: 24/11/14
 * Time: 22:22
 * Reference: http://mattstauffer.co/blog/laravel-5.0-form-requests
 */

use Illuminate\Foundation\Http\FormRequest;
use OidConnect\UserManagement\UserProfileFacade as UserProfile;
use Lang;


    class VerificationCodeValidator  extends FormRequest  {

    public function rules()  {
        return [
             'checkcode'  => 'required',
        ];
    }

    public function getValidatorInstance() {
        $validator = parent::getValidatorInstance();

        $validator->after(function() use ($validator) {
            $input = $this->formatInput();

            $checkcode = trim (strtoupper($input['checkcode']));
            if ($checkcode [2] != '-' || strlen ($checkcode) != 9) {
                $validator->errors()->add('checkcode', 'profile.invalide-code-format');
                return $validator;
            }

            $localuser= UserProfile::findLocalUserByVerificationCode($input['checkcode']);

            if ($localuser == null)  {
                $validator->errors()->add('checkcode', Lang::get ("profile.invalid-code-value"));
            }

        });
        return $validator;
    }

    public function authorize()  {
        return true;
    }
}