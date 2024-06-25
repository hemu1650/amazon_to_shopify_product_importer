<?php

namespace App\Http\Controllers;

use App\Models\RoleUser;
use Illuminate\Http\Request;
use App\Models\Role;
use App\Models\User;
use App\Models\Setting;
use App\Models\AmzKey;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;

class AuthenticateController extends Controller
{
    public function getActiveThemeID($token, $shopurl)
    {
        $theme_id = "";
        $url = "https://".$shopurl."/admin/themes.json";
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTPGET, 1);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('X-Shopify-Access-Token:'.$token));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($curl);
        Log::info("fetched theme id is".$response);
        curl_close($curl);
        if ($response) {
            $resObj = json_decode($response, true);
            if (isset($resObj['themes'])) {
                $themes = $resObj['themes'];
                foreach ($themes as $theme) {
                    if (isset($theme['role']) && $theme['role'] == 'main') {
                        $theme_id = $theme['id'];
                        break;
                    }
                }
            }
        }
        return $theme_id;
    }

    public function iscodeCopied(User $user)
    {
        return $this->get_html_content($user->shopurl, $user->token);
    }

    private function get_html_content($url, $token)
    {
        Log::info("start ".$url."  ".$token);
        $theme_id = $this->getActiveThemeID($token, $url);
        Log::info("theme id is".$theme_id);
        $url = "https://".$url."/admin/themes/".$theme_id."/assets.json?asset[key]=layout/theme.liquid&theme_id=".$theme_id;
        Log::info("url is".$url);
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json; charset=utf-8', 'X-Shopify-Access-Token:'.$token));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($curl, CURLOPT_HTTPGET, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($curl);
        curl_close($curl);
        Log::info("wow wow wow we did it".$response);

        if (strstr($response, "include 'is_aac'")) {
            Log::info("wow wow wow we did it");
            return 1;
        }
        return 0;
    }

    public function authenticate(Request $request)
    {
        $permissions = array();
        if (!$request->has("key")) {
            return response()->json(['error' => 'Invalid Credentials'], 401);
        }
        $tempcode = $request->input("key");
        $user = User::where('tempcode', $tempcode)->first(["id", "shopurl", "status", "tempcode", "membershiptype", "plan", "skulimit", "skuconsumed", "review", "created_at", "paid_at", "usermsg", "token"]);

        if (!$user) {
            return response()->json(['error' => 'Invalid Credentials'], 401);
        }

        $membershiptype = $user->membershiptype;

        $user->amzurl = "www.amazon.com";
        $amzKey = $user->amzKey()->first();
        $settings = $user->settings()->first();
        if (!$settings) {
            $settingObj = new Setting;
            $settingObj->published = 1;
            $settingObj->tags = "";
            $settingObj->vendor = "";
            $settingObj->product_type = "";
            $settingObj->inventory_policy = "";
            $settingObj->defquantity = 1;
            $settingObj->inventory_sync = 0;
            $settingObj->price_sync = 0;
            $settingObj->outofstock_action = "outofstock";
            $settingObj->buynow = 0;
            $settingObj->buynowtext = "View on Amazon";
            $settingObj->scripttagid = "";
            $settingObj->markupenabled = 0;
            $settingObj->markuptype = 'FIXED';
            $settingObj->markupval = 0;
            $settingObj->markupround = 0;
            $settingObj->reviewenabled = 0;
            $settingObj->reviewwidth = 500;
            $user->settings()->save($settingObj);
        }
        $settings = $user->settings()->first();
        Log::info("settings".json_encode($settings));
        $user->redirectdisabled = '';
        $user->amztag = '';
        $user->redirectCopied = '';
        if (($settings->buynow == 0) && $user->skuconsumed > 0) {
            $user->redirectdisabled = 1;
        }
        if (($settings->buynow == 1) && $user->skuconsumed > 0) {
            if (!$amzKey) {
                $user->amztag = 0;
            }
            if ($amzKey && $amzKey->associate_id == '') {
                $user->amztag = 0;
            }
            if ($amzKey && $amzKey->associate_id !== '' && $this->iscodeCopied($user) == 0) {
                $user->amztag = 1;
                Log::info("code copied or not ".$this->iscodeCopied($user));
                $user->redirectCopied = 0;
            }
        }
        Log::info("user buy now". $user->redirectdisabled);
        Log::info("user amz tag". $user->amztag);
        Log::info("user redirect now". $user->redirectCopied);
        Log::info($amzKey);
        if (!$amzKey) {
            Log::info("User Details Not Found In AmzKey");
            $user->amztoken = 0;
            $user->aws_access_id = '';
            $user->aws_secret_key = '';
            $user->associate_id = '';
        } else if ($amzKey->aws_access_id != '') {
            $user->amztoken = 1;
            $user->aws_access_id = sizeof($amzKey->aws_access_id);
            $user->aws_secret_key = sizeof($amzKey->aws_secret_key);
            $user->associate_id = $amzKey->associate_id;
            $country = $amzKey->country;
            $countryMapping = array("com" => "www.amazon.com", "ca" => "www.amazon.ca", "co.uk" => "www.amazon.co.uk", "in" => "www.amazon.in", "com.br" => "www.amazon.com.br", "com.mx" => "www.amazon.com.mx", "de" => "www.amazon.de", "es" => "www.amazon.es", "fr" => "www.amazon.fr", "it" => "www.amazon.it", "co.jp" => "www.amazon.co.jp", "cn" => "www.amazon.cn");
            if (array_key_exists($country, $countryMapping)) {
                $user->amzurl = $countryMapping[$country];
            }
        } else {
            $user->aws_access_id = $amzKey->aws_access_id;
            $user->aws_secret_key = $amzKey->aws_secret_key;
            $user->amztoken = 0;
            $user->associate_id = $amzKey->associate_id;
        }

        $created_at = $user->created_at;
        $fromtime = new Carbon($created_at);
        $now = Carbon::now();
        $difference = $fromtime->diffInDays($now);
        if ($difference > 7 && $user->sync == 0) {
            $user->expired = 1;
        } else {
            $user->expired = 0;
        }
        Log::info($user);
        Log::info("user array above");
        try {
            // verify the credentials and create a token for the user
            if (! $token = JWTAuth::fromUser($user)) {
                return response()->json(['error' => 'Invalid Credentials'], 401);
            }
        } catch (JWTException $e) {
            // something went wrong
            return response()->json(['error' => 'could_not_create_token'], 500);
        }

        return response()->json(compact('token', 'user'));

        
    }
}
