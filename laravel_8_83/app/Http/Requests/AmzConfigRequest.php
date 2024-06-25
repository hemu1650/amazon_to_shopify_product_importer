<?php

namespace App\Http\Requests;

use App\Http\Requests\Request;
use Illuminate\Http\JsonResponse;

class AmzConfigRequest extends Request
{


	public function response(array $errors){
        return new JsonResponse(['error' => $errors], 406);
    }
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {		
		$rules = ['associate_id' => 'required',
				/*'aws_access_id' => 'required',
				'aws_secret_key' => 'required',*/
                'country' => 'required'
              ];				
        return $rules;
    }
	
	public function messages() {
		$messages = [];
		$messages['associate_id.required'] = 'Please enter Associate Tag.';	
		$messages['aws_access_id.required'] = 'Please enter AWS Access ID.';
		$messages['aws_secret_key.required'] = 'Please enter AWS Secret Key.';
        $messages['country.required'] = 'Please choose a country.';
		return $messages;
	}	
}