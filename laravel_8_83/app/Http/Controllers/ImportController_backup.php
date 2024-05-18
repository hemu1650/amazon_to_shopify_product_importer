<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use ApaiIO\Configuration\GenericConfiguration;
use ApaiIO\Operations\Search;
use ApaiIO\Operations\Lookup;
use ApaiIO\ApaiIO;
use App\AmzKey;
use App\Product;
use App\ProductVariant;
use App\ProductImage;
use App\Setting;
use App\Proxy;
use Illuminate\Database\Eloquent\Collection;
use App\Reviews;
use App\importToShopify;
use App\fetchReviews;

use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\Translation\Tests\Dumper\IniFileDumperTest;
use Validator;
use File;
use App\bulkImport;
use Illuminate\Support\Facades\Log;

class ImportController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $currUser = Auth::User();
        
        return bulkImport::where('user_id',$currUser->id)->orderBy('id',"DESC")->get();
    }
    
    
    public function __construct(){
    	\set_time_limit(0);
        // Apply the jwt.auth middleware to all methods in this controller
        // except for the authenticate method. We don't want to prevent
        // the user from retrieving their token if they don't already have it
        $this->middleware('jwt.auth', ['except' => ['authenticate']]);
    }
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $currUser = Auth::User();
        //Log::info($request);
        $base_url = $request->url;
        $data = $request->file;
        $data = explode(",",$data);
        $asin_list = base64_decode($data[1]);
        $res = preg_match_all("/[a-z]/",$asin_list,$result);
        if($res){
            if(sizeof($result)>=1){
                return response()->json(["error"=>["msg"=>["Please upload ASIN in line separated format"]]], 406);
            }
        }
        $data = explode("\n",trim($asin_list));
        if(sizeof($data)<2){
            return response()->json(["error"=>["msg"=>["Please upload ASIN in line separated format"]]], 406);
        }
        
        $updated_at = $currUser->updated_at;//Carbon::now()->subDays(29)->toDateTimeString()
        $bulkImportCntCurrMonth = bulkImport::where('user_id',$currUser->id)->whereDate('updated_at', '>', $updated_at)->sum('total');
		//$bulkimportJson = json_encode($bulkimportthismonth);
		//Log::info($currUser->id."user id");
		Log::info($bulkImportCntCurrMonth." reviews count in the last month");
		//Log::info(json_encode($reviewsthismonth));		  
	    if( $bulkImportCntCurrMonth == null || $bulkImportCntCurrMonth == '' ){
	         $bulkImportCntCurrMonth = 0;
	    }
	    //Log::info($data);
	    
	    
	    if($currUser->plan == 3){
	        if($bulkImportCntCurrMonth >= 10000){
	            return response()->json(["error"=>["msg"=>['You need to upgrade your plan to bulk import products  ']]],406);
	        }else{
	            if(sizeof($data) > (10000 - $bulkImportCntCurrMonth)){
        	        if((10000 - $bulkImportCntCurrMonth) <= 0){
        	            return response()->json(["error"=>["msg"=>['Limit to bulk import products Exceeded Contact Support']]],406);
        	        }
        	        return response()->json(["error"=>["msg"=>['Limit to bulk import products Exceeded You Can Import : '.(10000 - $bulkImportCntCurrMonth).' Products']]],406);
        	    }
	        }
	        
	    }
	    if($currUser->plan == 4){
	        if($bulkImportCntCurrMonth >= 50000){
	            return response()->json(["error"=>["msg"=>['You need to contact support to bulk import products after monthly limit ']]],406);
	        }else{
	            if(sizeof($data) > (50000 - $bulkImportCntCurrMonth)){
        	        if((50000 - $bulkImportCntCurrMonth) <= 0){
        	            return response()->json(["error"=>["msg"=>['Limit to bulk import products Exceeded Contact Support']]],406);
        	        }
        	        return response()->json(["error"=>["msg"=>['Limit to bulk import products Exceeded You Can Import : '.(50000 - $bulkImportCntCurrMonth).' Products']]],406);
        	    }
	        }
	    }
        
        bulkImport::create([
                "asin" => $asin_list,
                "amazon_base_url" => $base_url,
                "failed" => "",
                "total" => sizeof($data),
                "user_id" => $currUser->id,
                "created_at" => date('Y-m-d H:i:s'),
                "updated_at" => date('Y-m-d H:i:s'),
            ]);
        return response()->json(['success',bulkImport::where('user_id',$currUser->id)->orderBy('id',"DESC")->get()], 200);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
