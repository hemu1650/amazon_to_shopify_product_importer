<?php

namespace App\Http\Controllers;


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
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Collection;
use App\Reviews;
use App\fetchReviews;
use Illuminate\Http\Response;

use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\Translation\Tests\Dumper\IniFileDumperTest;
use Validator;
use File;
use Illuminate\Support\Facades\Log;

class ReviewController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function __construct(){
        \set_time_limit(0);
        // Apply the jwt.auth middleware to all methods in this controller
        // except for the authenticate method. We don't want to prevent
        // the user from retrieving their token if they don't already have it
        $this->middleware('jwt.auth', ['except' => ['authenticate']]);
    }

    public function index(Request $request)
    {
        Log::info('Index');
        $per_page = \Request::get('per_page') ?: 20;
        $id = \Request::get('id');
        $currUser = Auth::User();
        //$product = ProductVariant::where('user_id',$currUser->id)->paginate($per_page);
        $reviews = Reviews::where('product_asin',$id)->where('user_id',$currUser->id)->where('status', 'No reviews Fetched')->with('variants')->paginate($per_page);
        Log::info(json_encode($product));
        Log::info($request);
        Log::info($currUser);
        Log::info(Reviews::where('user_id',$currUser->id)->paginate($per_page));
        return response()->json($reviews,200);
    }

    // public function search(Request $request)
	// {
	// 	$per_page = \Request::get('per_page') ?: 20;
	// 	$id = \Request::get('id');
    //     $currUser = Auth::User();
		
    //  	if ($request['query']) {
	// 		$reviews = Reviews::where('product_asin',$id)->where('reviewTitle','like','%'.$request['query'].'%')->Orwhere('status','like','%'.$request['query'].'%')->where('user_id',$currUser->id)->with('variants');
	// 		return $reviews->paginate($per_page);			
    //     } else {
	//  		return $this->index();
	//  	}
	//  }

     //added harsh

     public function search(Request $request)
     {
         $per_page = \Request::get('per_page') ?: 20;
         $currUser = Auth::User();
 
         ### search
         $query =$request['query'];
         if($query == 'Imported' || $query == 'Ready to Import' || $query == 'Import in progress'){
             $products = $currUser->products()->where('status', '=', $request['query'])->with('variants')->with('variantsCount')->with('variants.mainImage')->orderBy('product_id', 'DESC');
             return $products->paginate($per_page);	
         }
            else{
         
           $products = $currUser->products()->where('title','like','%'.$query.'%')->with('variants')->with('variantsCount')->with('variants.mainImage')->orderBy('product_id', 'DESC');
           return $products->paginate($per_page);		
         }
        }


    /*public function refetchAmzReviews(Request $request){
        $asin = \Request::get('id');
        $currUser = Auth::User();
        $permission = fetchReviews::where("user_id",$currUser->id)->where('product_asin',$asin)->get();
        if(sizeof($permission)>0){
            $permission[0]->update([
               "status" => 0
            ]);
            $permission = Reviews::where("user_id",$currUser->id)->where('product_asin',$asin)->get();
            if(sizeof($permission)>0){
                foreach($permission as $key => $row ){
                    $row->delete();
                }
            }
            return response()->json(["Refetch Review Request Accepted"],200);
        }else{
            fetchReviews::create([
                "user_id" => $currUser->id,
                "product_asin" => $asin,
                "status" => 0
            ]);
            return response()->json(["Refetch Review Request Accepted"],200);
        }
        return response()->json(["Invalid Data"],406);
    }*/
    
    public function refetchAmzReviews(Request $request){
       $asin = \Request::get('id');
       $currUser = Auth::User();
       $permission = fetchReviews::where("user_id",$currUser->id)->where('product_asin',$asin)->get();
       if(sizeof($permission)>0){
           $permission[0]->update([
              "status" => 0
           ]);
           return response()->json(["Refetch Review Request Accepted"],200);
       }else{
           fetchReviews::create([
               "user_id" => $currUser->id,
               "product_asin" => $asin,
               "status" => 0
           ]);
           return response()->json(["Refetch Review Request Accepted"],200);
       }
       return response()->json(["Invalid Data"],406);
   }
    
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        Log::info('Create');
        return response()->json(['Create'],200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        Log::info('Store');
        return response()->json(['Store'],200);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
	 
	 public function getById($id)
    {
        Log::info('Show');
        //$per_page = \Request::get('per_page') ?: 20;
        //$id = \Request::get('id')?:return response()->json(['Error'],406);
        $currUser = Auth::User();
        
        $review = Reviews::find($id);
        //Log::info(json_encode($product));
        //Log::info($request);
        //Log::info($currUser);
        //Log::info(Reviews::where('user_id',$currUser->id)->paginate($per_page));
        return response()->json($review,200);
    }
	
    public function show($id)
    {
        Log::info('Show');
        //$per_page = \Request::get('per_page') ?: 20;
        //$id = \Request::get('id')?:return response()->json(['Error'],406);
        $currUser = Auth::User();
        
        $review = Reviews::find($id);
        //Log::info(json_encode($product));
        //Log::info($request);
        //Log::info($currUser);
        //Log::info(Reviews::where('user_id',$currUser->id)->paginate($per_page));
        return response()->json($review,200);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        Log::info('Edit');
        return response()->json(['Edit'],200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        Log::info('Update');
		$id = \Request::get('id');
        $permission = Reviews::find($id);
        //Log::info($permission);
        if($permission){
            $permission->update($request->all());
            return response()->json(['Updated'],200);
        }else{
            return response()->json(['Review Not Found'],406);
        }
    }

    public function publish($id){
        $data = explode(',',$id);
        Log::info($data);
        $currUser = Auth::User();
        Log::info($currUser);
        $permission = Reviews::find($data[1]);
        if($permission){
            $permission->update([
                "status" => 'Published'
            ]);
            $data = Reviews::where("user_id",$currUser->id)->where("product_asin",$data[0])->paginate(20);
            Log::info($data);
            return response()->json($data,200);
        }else{
            return response()->json(["No Record Found"],403);
        }
    }
    
    public function exportReviews($id){
        Log::info("Export Reviews");
        Log::info($id);
		
        $currUser = Auth::User();
        $variants = $currUser->variants()->where('asin',$id)->with('reviews')->get()->toArray();
        if(sizeof($variants)>0){
            
            //array_unshift($reviews, array_keys($reviews[0]));
            
            $headers = ['Content-Type: application/csv'];
        	$newName = 'reviews-csv-file-'.time().'.csv';
            
                
            $FH = fopen('reviews.csv', 'w');
            fputcsv($FH,["product_handle","state","rating","title","author","email","location","body","reply","created_at","replied_at"]);
            
            foreach($variants as $key => $variant){
                Log::info($variant);
                foreach ($variant['reviews'] as $ke => $row) { 
				
				$formatted_datetime = date("d/m/y, H:i:s", strtotime($row['reviewDate']));
                    //Log::info($row);
                    //if($key != 0){
                        fputcsv($FH, [$variant['handle'],$row['status'],$row['rating'],trim(strip_tags($row['reviewTitle'])),strip_tags($row['authorName']),$currUser->email,"",trim(strip_tags($row['reviewDetails'])),"",$formatted_datetime,""]);
                   // }
                }
            }
            fclose($FH);
            //return response()->download($callback, 200);
            return response()->json(["https://shopify.infoshore.biz/aac/api/public/reviews.csv"],200);
        }else{
            return response()->json(["No Reviews Found"],404);
        }
        //return response()->json(["success"],200);
    }
    
    
   
	
	
	 public function  downloadAllSelected($id){
       Log::info($id);
       $currUser = Auth::User();
       $reviews = Reviews::where("user_id",$currUser->id)->where("product_asin",$id)->get()->toArray();
       
       
           $filename = 'reviewsAll'.time().'.csv';
       
           $FH = fopen($filename , 'w');
           fputcsv($FH,["asin","state","rating","title","author","email","location","body","reply","created_at","replied_at"]);
           foreach ($reviews as $key => $row) { 
               Log::info($row);
               if($key != 0){
                   fputcsv($FH, [$row['product_asin'],$row['status'],$row['rating'],strip_tags($row['reviewTitle']),strip_tags($row['authorName']),"","",strip_tags($row['reviewDetails']),"",$row['reviewDate'],""]);
               }
           }
           fclose($FH);
   return response()->json(['http://shopify.infoshore.biz/aac/api/public/'.$filename],200);
}

	
    
    public function unpublish($id){
        $data = explode(',',$id);
        Log::info($data);
        $currUser = Auth::User();
        Log::info($currUser);
        $permission = Reviews::find($data[1]);
        if($permission){
            $permission->update([
                "status" => 'Unpublished'
            ]);
            $data = Reviews::where("user_id",$currUser->id)->where("product_asin",$data[0])->paginate(20);
            Log::info($data);
            return response()->json($data,200);
        }else{
            return response()->json(["No Record Found"],403);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $data = explode(',',$id);
        Log::info($data);
        $currUser = Auth::User();
        Log::info($currUser);
        $permission = Reviews::find($data[1]);
        if($permission){
            $permission->delete();
            $data = Reviews::where("user_id",$currUser->id)->where("product_asin",$data[0])->paginate(20);
            Log::info($data);
            return response()->json($data,200);
        }else{
            return response()->json(["No Record Found"],403);
        }
        Log::info('Destroy');
        return response()->json(['Destroy'],200);
    }
}