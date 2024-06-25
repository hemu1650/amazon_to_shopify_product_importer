<?php

namespace App\Http\Requests;

use App\Http\Requests\Request;
use Illuminate\Http\JsonResponse;

class SettingConfigRequest extends Request
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
		$rules = ['published' => 'required',
				'price_sync' => 'required',
				'inventory_sync' => 'required'
              ];				
        return $rules;
    }
	
	public function messages() {
		$messages = [];
		$messages['published.required'] = 'Please choose a published status.';	
		$messages['price_sync.required'] = 'Please choose Price Sync option.';
		$messages['inventory_sync.required'] = 'Please choose Inventory Sync option.';
        return $messages;
	}	
}