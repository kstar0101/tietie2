<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Http\File;
use Carbon\Carbon;
use Storage;

use App\Block;
use App\Block_manager;
use App\Business_category;
use App\Category;
use App\Client;
use App\Client_equipment;
use App\District_manager;
use App\Equipment;
use App\Final_status;
use App\Maintenance;
use App\Maintenance_image;
use App\Maintenance_order_reason;
use App\Maintenance_progress;
use App\Manufacturer;
use App\Order_type;
use App\Progress;
use App\Role;
use App\Shop;
use App\Sub_category;
use App\User;

use Illuminate\Support\Facades\Mail;
use App\Mail\MaintenanceRequestMail;
use App\Mail\MaintenanceEditMail;
use App\Mail\ApprovalMail;
use App\Mail\SendbackMail;
use App\Mail\SuspendMail;
use App\Mail\RejectMail;

use App\Order_reason;

use DB;
use Log;
use PDF;

class MaintenanceController extends Controller
{
	use AuthenticatesUsers;
	
	public function __construct()
	{
		//$this->middleware('auth');
	}

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
		$request->input('action');
		$business_category_id = $request->input('business_category_id');
		$shop_id = $request->input('shop_id');
		$progress = $request->input('progress');
		$limit = $request->input('limit');

		if ( isset($_GET['limit']) && isset($_GET['status_id']) && isset($_GET['shop_id']) ) {
			$limit = $request->limit;
			$status_id = explode(',', $request->status_id);
			$shop_id = $request->shop_id;
			$maintenances = Maintenance::with(['shop.business_category', 'orderType', 'progress', 'user'])->where('shop_id', $shop_id)->whereIn('progress_id', $status_id)->orderBy('maintenance_id', 'desc')->take($limit)->get();
			return response( $maintenances );
		}
		
		if ( isset($_GET['limit']) && isset($_GET['shop_id']) ) {
			$limit = $request->limit;
			$shop_id = $request->shop_id;
			$maintenances = Maintenance::with(['shop.business_category', 'orderType', 'progress', 'user'])->where('shop_id', $shop_id)->orderBy('maintenance_id', 'desc')->take($limit)->get();
			return response( $maintenances );
		}
		
		if ( isset($_GET['status_id']) && isset($_GET['shop_id']) ) {
			$status_id = explode(',', $request->status_id);
			$shop_id = $request->shop_id;
			$maintenances = Maintenance::with(['shop.business_category', 'orderType', 'progress'])->where('shop_id', $shop_id)->whereIn('progress_id', $status_id)->orderBy('maintenance_id', 'desc')->take(30)->get();
			return response( $maintenances );
		}

		if ( isset($_GET['shop_id']) ) {
			$shop_id = $request->shop_id;
			$maintenances = Maintenance::with(['shop.business_category', 'orderType', 'progress', 'user'])->where('shop_id', $shop_id)->orderBy('maintenance_id', 'desc')->take(30)->get();
			return response( $maintenances );
		}
		
		if ( $business_category_id ) {
			$maintenances = Maintenance::with(['shop.business_category', 'orderType', 'progress', 'user'])->join('shops', 'shops.shop_id', '=', 'maintenances.shop_id')->join('business_categories', 'business_categories.business_category_id', '=', 'shops.business_category_id')->where('shops.business_category_id', $business_category_id)->orderBy('maintenance_id', 'desc')->get();
			return response( $maintenances );
		}

		if (isset($_GET['limit'])) {
        	    $limit = $request->limit;
        	    $maintenances = Maintenance::with(['shop.business_category', 'orderType', 'progress', 'user'])->whereNotNull('shop_id')->orderBy('maintenance_id', 'desc')->take($limit)->get();
        	    return response($maintenances);
        	}
		
		$maintenances = Maintenance::with(['shop.business_category', 'orderType', 'progress', 'user'])->orderBy('maintenance_id', 'desc')->whereNotNull('shop_id')->get();
		return response( $maintenances );
		
    }



    public function viewPdf_one(){
        $maintenance_id = 106;

        $maintenance_data = Maintenance::with([
            'shop.business_category',
            'shop.business_category_option',
            'shop.users',
            'orderType', 'progress',
            'user',
            'maintenanceProgress.entered_by',
            'maintenanceImages',
            'orderReasons',
            'category', 'subCategory',
            'maintenanceMatters.matter_value',
            'maintenanceMatters.matter_option',
            'uploadingFiles',
            'quotationInfo', 'accountingInfo.accounting_info'
        ])->find($maintenance_id);

        $qb_maintenanceTopartners =  DB::select("SELECT partner_id, partner_no, partner_name, TEL, FAX FROM `maintenances` LEFT JOIN partners ON maintenances.partner_code=partners.partner_code WHERE maintenance_id= ?", [$maintenance_id]);

        $maintenance_data['partner_id'] = $qb_maintenanceTopartners['0']->partner_id;
        $maintenance_data['partner_no'] = $qb_maintenanceTopartners['0']->partner_no;
        $maintenance_data['partner_name'] = $qb_maintenanceTopartners['0']->partner_name;
        $maintenance_data['TEL'] = $qb_maintenanceTopartners['0']->TEL;
        $maintenance_data['FAX'] = $qb_maintenanceTopartners['0']->FAX;

        $order_reason = Order_reason::select('order_reason_id', 'reason')
            ->distinct()
            ->where('order_reason_id', $maintenance_data['order_reason_id'])
            ->get();

        if ($order_reason->isEmpty()) {
            $order_reason[0] = array(
                'order_reason_id' => '',
                'reason' => '',
            );
        }

        // var_export($maintenance_data);

        $maintenance_data['order_reason'] = $order_reason;
        $pdf = PDF::loadView('pdf_one', $maintenance_data);
        // Storage::put('public/pdf/invoice.pdf', $pdf->output());
        // return $pdf->download('pdf_file.pdf');
        // return $pdf->stream();
        // return view('pdf_one', $maintenance_data);

        $partner_staff = DB::table('partners_staff')->where('partner_id', $maintenance_data['partner_id'])->first();

        if(!empty($partner_staff)) {
            $maintenance_data['partner_email'] = $partner_staff->email;
        } else {
            $maintenance_data['partner_email'] = '';
        }

        $mail_data["email"] = $maintenance_data['partner_email'];
        $mail_data["title"] = "From ".$maintenance_data['user']['email'];
        $mail_data["body"] = $maintenance_data['partner_code'].'-'.$maintenance_data['partner_name'];

        // if($maintenance_data['partner_email'] && $maintenance_data['user']['email']){
        //     Mail::send('mail', $mail_data, function($message)use($mail_data, $pdf) {
        //         $message->to($mail_data["email"], $mail_data["email"])
        //                 ->subject($mail_data["title"])
        //                 ->attachData($pdf->output(), "partner-shop.pdf");
        //     });

        //     echo "<script>console.log('Mail sent successfully')</script>";
        // }

        echo "<script>console.log('Mail sent successfully')</script>";

        echo ("mail_parter ===================== ".$maintenance_data['partner_email'])."<br/>user_mail========================= ".$maintenance_data['user']['email'];
        return view('pdf_one', $maintenance_data);
    }


    public function sendmail()
    {
        $data["email"] = "aatmaninfotech@gmail.com";
        $data["title"] = "From ItSolutionStuff.com";
        $data["body"] = "This is Demo";
 
        // $files = [
        //     public_path('files/160031367318.pdf'),
        //     public_path('files/1599882252.png'),
        // ];
  
        Mail::send('mail', $data, function($message)use($data) {
            $message->to($data["email"], $data["email"])
                    ->subject($data["title"]);
 
            // foreach ($files as $file){
            //     $message->attach($file);
            // }
            
        });
 
        dd('Mail sent successfully');
    }
	
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function confirm(Request $request)
    {
//		$this->validate($request, [
//			'shop_id'      => 'required',
//			'equipment'    => 'required',
//			'manufacturer' => 'required',
//			'model_number' => 'required',
//			'when'         => 'required',
//			'situation'    => 'required',
//			'image_1'      => 'required|file|image',
//			'image_2'      => 'required|file|image',
//			'image_3'      => 'required|file|image',
//		]);
//
		$form = $request->except('image_1', 'image_2', 'image_3');
		$request_all = $request->all();
		$request_keys = array_keys($request_all);
		$request_images = preg_grep('/^image/', $request_keys);
		$request_images_count = count($request_images);

		$images = [];
		for ( $i = 1; $i <= $request_images_count; $i++ ) {
			$image_name = uniqid('tmp_') . "." . $request->file('image_'.$i)->guessExtension();
			$request->file('image_'.$i)->move(public_path('/img/tmp'), $image_name);
			$images[] = $image_name;
		}

		$order_type_id = $request->input('order_type_id');
		if ( $order_type_id === '1' ) {
			$order_type = '修理をしてほしい';
		}
        if ( $order_type_id === '2' ) {
			$order_type = '部品を送ってほしい';
		}
        if ( $order_type_id === '3' ) {
			$order_type = '調査してほしい';
		}
        if ( $order_type_id === '4' ) {
			$order_type = 'その他';
		}

        $request->session()->flash('form', [
            'business_category_id'  => $request->input('business_category_id'),
            'shop_id'               => $request->input('shop_id'),
            'applicant_id'          => $request->input('applicant_id'),
            'applicant_name'        => $request->input('applicant_name'),
            'equipment'             => $request->input('equipment'),
            'manufacturer'          => $request->input('manufacturer'),
            'model_number'          => $request->input('model_number'),
            'when'                  => $request->input('when'),
            'first_handling'        => $request->input('first_handling'),
            'situation'             => $request->input('situation'),
            'order_type_id'         => $request->input('order_type_id'),
            'order_type'            => $order_type,
            'order_reason_ids'      => $request->input('order_reason_ids'),
            'order_type_other_text' => $request->input('order_type_other_text'),
        ]);
		$request->session()->flash('images', $images);
		return response()->json([
			'url' => url('maintenance/add/confirm'),
		]);
        
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function post(Request $request)
    {
        $applicant_id   = $request->input('applicant_id');
        $applicant_name = $request->input('applicant_name');
		$shop_id        = $request->input('shop_id');
		$equipment      = $request->input('equipment');
		$manufacturer   = $request->input('manufacturer');
		$model_number   = $request->input('model_number');
		$when           = $request->input('when');
		$first_handling = $request->input('first_handling');
		$situation      = $request->input('situation');
		$order_type_id  = $request->input('order_type_id');
        $order_reason_ids      = $request->input('order_reason_ids');
        $order_type_other_text = $request->input('order_type_other_text');
		$order          = $equipment . ' ' . $manufacturer . ':' . $model_number . ' ' . $when . ' ' . $situation . ' 手配お願いします。';

		Log::debug('メンテ申請保存処理開始');

		DB::beginTransaction();
		try{
			$maintenance = new Maintenance;
            $maintenance->shop_id               = $shop_id;
            $maintenance->applicant_id          = $applicant_id;
            $maintenance->applicant_name        = $applicant_name;
            $maintenance->order_type_id         = $order_type_id;
            $maintenance->equipment             = $equipment;
            $maintenance->manufacturer          = $manufacturer;
            $maintenance->model_number          = $model_number;
            $maintenance->when                  = $when;
            $maintenance->first_handling        = $first_handling;
            $maintenance->situation             = $situation;
            $maintenance->order                 = $order;
            $maintenance->progress_id           = 1; // BM承認待ち
            $maintenance->order_type_other_text = $order_type_other_text;
            
            $now      = Carbon::now();
            $am_start = Carbon::createFromTimeString('00:00:00');
            $am_end   = Carbon::createFromTimeString('06:59:59');
            $pm_start = Carbon::createFromTimeString('18:00:00');
            $pm_end   = Carbon::createFromTimeString('23:59:59');
            if ( $now->between($am_start, $am_end) ) {
                $maintenance->notify_at = $now->setTime(07, 00, 00);
            } elseif ( $now->between($pm_start, $pm_end) ) {
                $maintenance->notify_at = $now->addDay()->setTime(07, 00, 00);
            } else {
                $maintenance->notify_at = $now;
            }
			
            $maintenance->save();

			// メンテナンス申請コード生成＆保存
			$maintenance_id = $maintenance->maintenance_id;
			$maintenance_code = 'M' . sprintf('%09d', $maintenance_id);
			$maintenance->maintenance_code = $maintenance_code;
			$maintenance->save();
			Log::debug('maintenance保存完了');
            
            // 発注理由の保存
            if ( !empty($order_reason_ids) ) {
                $order_reason_ids = explode(',', $order_reason_ids);
                foreach ( $order_reason_ids as $order_reason_id ) {
                    $maintenance_order_reason = new Maintenance_order_reason;
                    $maintenance_order_reason->maintenance_id  = $maintenance->maintenance_id;
                    $maintenance_order_reason->order_reason_id = $order_reason_id;
                    $maintenance_order_reason->save();
                }
            }
			Log::debug('発注理由保存完了');

			// 画像保存処理
			$request_all = $request->all();
			$request_keys = array_keys($request_all);
			$request_images = preg_grep('/^image/', $request_keys);
			$request_images_count = count($request_images);

			$business_category = $maintenance->shop->business_category->business_category;
			$shop_code = $maintenance->shop->shop_code;

			for ( $i = 1; $i <= $request_images_count; $i++ ) {
				//if ( !file_exists(storage_path('app/public/images/') . $maintenance_id) ) {
				//	mkdir( storage_path('app/public/images/') . $maintenance_id );
				//}
				if (!Storage::disk('s3')->exists('/zensho-mainte/images/'.$maintenance_id)) {
					Storage::disk('s3')->makeDirectory('/zensho-mainte/images/'.$maintenance_id);
				}
				$fileName = $request->input('image_'.$i);
				Log::debug('添付ファイルの一時ファイルが存在するか？');
				Log::debug(public_path('img/tmp/').$fileName);
				Log::debug(file_exists(public_path('img/tmp/') .$fileName));
				// if(file_exists($fileName)){
				if(file_exists(public_path('img/tmp/') .$fileName)){
					$newFileName = $maintenance_code . '_' . $business_category . '_' . $shop_code . '_' . date('Ymd') . '_' . $i . '.jpg';// マイクロ秒タイムスタンプ使う。$iも使用。
                    Storage::disk('s3')->putFileAs('/zensho-mainte/images/'.$maintenance_id, new File(public_path('img/tmp/').$fileName), $newFileName, 'private');
					$maintenance_image = new Maintenance_image;
					$maintenance_image->maintenance_id = $maintenance_id;
					$maintenance_image->file_name = $newFileName;
					$maintenance_image->save();
                    
				}else{
					Log::debug("添付ファイル:".$fileName."が存在しません。");
					$request->session()->flash('errorText',"添付ファイル:".$fileName."が存在しません。");
					return redirect('/maintenance/error');
				}
			}
			Log::debug('画像保存完了');

			DB::commit();


		}catch(\Exception $e){
			Log::debug('エラー'. $e->getMessage());
			$request->session()->flash('errorText', $e->getMessage());
			return redirect('/maintenance/error');
		}

		// メール
        Log::debug('申請メール処理開始');
		$shop_name       = $maintenance->shop->shop_name;
		$shop_code       = $maintenance->shop->shop_code;
        $order_type      = $maintenance->orderType->type;
        if ( $order_type === '発注依頼' ) {
            $maintenance_order_reasons = $maintenance->orderReasons;
            $x = 1;
            $order_type .= '（理由：';
            foreach ( $maintenance_order_reasons as $order_reason ) {
                if ( $x !== 1 ) {
                    $order_type .= '・';
                }
                $order_type .= $order_reason->reason;
                $x++;
            }
            $order_type .= '）';
        }
        if ( $order_type === 'その他' ) {
            $order_type = $order_type_other_text;
        }
        $progress_status = $maintenance->progress->status;
		$subject         = $progress_status.' '.$business_category.' '.$shop_code.' '.$shop_name.' '.$equipment;
        
        $block_managers  = Block_manager::where('block_id', $maintenance->shop->block_id)->get();
        foreach ( $block_managers as $block_manager ) {
            $BM[] = User::find($block_manager->user_id);
        }
        
		$data = [
			'subject'          => $subject,
			'shop_name'        => $shop_name,
			'shop_code'        => $shop_code,
			'maintenance_code' => $maintenance_code,
			'equipment'        => $equipment,
			'manufacturer'     => $manufacturer,
			'model_number'     => $model_number,
			'when'             => $when,
            'first_handling'   => $first_handling,
			'situation'        => $situation,
			'order_type'       => $order_type,
			'progress_status'  => $progress_status,
            'applicant_name'   => $applicant_name,
            'BM'               => $BM,
		];
        
		$files = Maintenance_image::where('maintenance_id', $maintenance_id)->get();
		foreach ($files as $file) {
			$attachedFiles[] = array(
				'data' => Storage::disk('s3')->get("zensho-mainte/images/$maintenance_id/$file->file_name"),
				'file_name' => $file->file_name
			);
		}
        
        $cc = explode(',', $maintenance->shop->business_category->request_email);
        
        if ( preg_match('/冷凍庫|エアコン/', $equipment) ) {
            $cc[] = $maintenance->shop->business_category->headquarters_email;
        }
        
        if ( config('app.env') == 'staging' ){
			$cc = [];
        }
        
		if ( config('app.env') == 'local' ) {
			$BM = [
                'yasu.fukuhara@interface-design.jp',
                'nads1012+test1@gmail.com',
            ];
			$cc = [];
		}

        Mail::to($BM)->cc($cc)->send(new MaintenanceRequestMail($data, $attachedFiles));
		Log::debug('request email sent.'.print_r($data,true));

		return redirect('/maintenance/add/completed');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($maintenance_id)
    {
		$maintenance = Maintenance::with([
            'shop.business_category',
            'orderType', 'progress',
            'user',
            'maintenanceProgress.entered_by',
            'maintenanceImages',
            'orderReasons',
        ])->find($maintenance_id);
		return response($maintenance);
    }

	/**
     * Display the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function error(Request $request)
    {
		$business_category_id = Auth::user()->business_category_id;
		$shop_id              = Auth::user()->shop_id;
		$role                 = Role::find( Auth::user()->role_id )->role_name;
		$block_name           = '';
		if ( 'BM' === $role ) {
			$block_id   = Block_manager::where('user_id', Auth::user()->user_id)->first()->block_id;
			$block_name = Block::find($block_id)->block_name;
		}

		$errorText = 'エラーが発生しました。';
		if($request->session()->get('errorText')){
			$errorText = $request->session()->get('errorText');
		}

		return view('maintenance.error', [
			'page_title'        => '新規メンテナンス申請',
			'text'              => $errorText,
			'business_category' => Business_category::find($business_category_id),
			'block_name'        => $block_name,
			'shop'              => Shop::find($shop_id),
			'role'              => $role,
		]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $maintenance_id)
    {
		$selected_progress = $request->input('selected_progress');
		$user = Auth::user();

		if($selected_progress){
			// return DB::transaction(function () use ($request, $selected_progress, $maintenance_id) {
				DB::beginTransaction();
				try{
					if('9999' == $selected_progress){
						Maintenance::find($maintenance_id)->delete();
						
						DB::commit();

						return response()->json([
							'progress_update' => 'success',
							'url' => url('maintenance'),
						]);
					}
				}catch(\Exception $e){
					return response()->json([
						'progress_update' => 'error',
						'message' => $e->getMessage(),
					]);
				}
				try{
					
					$maintenance = Maintenance::find($maintenance_id);
					$maintenance->progress_id = $selected_progress;
					$maintenance->save();

					// abort(500, 'Something went wrong');

					$maintenance_progress = new Maintenance_progress;
					$maintenance_progress->maintenance_id = $maintenance_id;
					$maintenance_progress->progress_id    = $selected_progress;
					$maintenance_progress->comment    = "管理画面より進捗状況が変更されました。";
					$maintenance_progress->entered_by     = 1; // 現状 管理者
					$maintenance_progress->save();

					DB::commit();

					return response()->json([
						'progress_update' => 'success',
					]);

				}catch(\Exception $e){
					return response()->json([
						'progress_update' => 'error',
						'message' => $e->getMessage(),
					]);
				}
			// });
		}

		$this->validate($request, [
			'equipment'    => 'required',
			'manufacturer' => 'required',
			'model_number' => 'required',
			'when'         => 'required',
			'situation'    => 'required',
		]);
		
        $equipment             = $request->input('equipment');
        $manufacturer          = $request->input('manufacturer');
        $model_number          = $request->input('model_number');
        $when                  = $request->input('when');
        $situation             = $request->input('situation');
        $order_type_id         = $request->input('order_type_id');
        $order_reason_ids      = $request->input('order_reason_ids');
        $order_type_other_text = $request->input('order_type_other_text');
        $order                 = $equipment . ' ' . $manufacturer . ':' . $model_number . ' ' . $when . ' ' . $situation . '手配お願いします。';

        $maintenance = Maintenance::find($maintenance_id);
        $maintenance->equipment             = $equipment;
        $maintenance->manufacturer          = $manufacturer;
        $maintenance->model_number          = $model_number;
        $maintenance->when                  = $when;
        $maintenance->situation             = $situation;
        $maintenance->order                 = $order;
        $maintenance->order_type_id         = $order_type_id;
        $maintenance->order_type_other_text = $order_type_other_text;
        $maintenance->progress_id           = 1; // BM承認待ち

        $now      = Carbon::now();
        $am_start = Carbon::createFromTimeString('00:00:00');
        $am_end   = Carbon::createFromTimeString('06:59:59');
        $pm_start = Carbon::createFromTimeString('18:00:00');
        $pm_end   = Carbon::createFromTimeString('23:59:59');
        if ( $now->between($am_start, $am_end) ) {
            $maintenance->notify_at = $now->setTime(07, 00, 00);
        } elseif ( $now->between($pm_start, $pm_end) ) {
            $maintenance->notify_at = $now->addDay()->setTime(07, 00, 00);
        } else {
            $maintenance->notify_at = $now;
        }			

        $maintenance->save();
        
        // 発注理由
        Maintenance_order_reason::where('maintenance_id', $maintenance_id)->delete();
        if ( $order_reason_ids ) {
            foreach ( $order_reason_ids as $order_reason_id ) {
                $maintenance_order_reason = new Maintenance_order_reason;
                $maintenance_order_reason->maintenance_id  = $maintenance_id;
                $maintenance_order_reason->order_reason_id = $order_reason_id;
                $maintenance_order_reason->save();
            }            
        }

        $maintenance_progress = new Maintenance_progress;
        $maintenance_progress->maintenance_id = $maintenance_id;
        $maintenance_progress->progress_id    = 1; // BM承認待ち
        $maintenance_progress->entered_by     = $request->input('entered_by');
        $maintenance_progress->save();

        // 画像の保存＆更新	
        // 旧リスト作成
        $old_images = Maintenance_image::where('maintenance_id', $maintenance_id)->get();
        $old_image_files = [];
        foreach ( $old_images as $old_image ) {
            $old_image_files[] = $old_image['file_name'];
        }

        // 新リスト作成
        $request_all    = $request->all();
        $request_keys   = array_keys($request_all);
        $request_images = preg_grep('/^image/', $request_keys);

        $maintenance_code  = $maintenance->maintenance_code;
        $shop_code         = $maintenance->shop->shop_code;
        $business_category = $maintenance->shop->business_category->business_category;

        $new_image_files = [];
        foreach ( $request_images as $request_image ) {
            if ( !empty($request->$request_image) ) {
                if ( $request->hasFile($request_image) ) {
                    $branch_number = explode('_', $request_image)[1];
                    $new_image_files[] = $maintenance_code . '_' . $business_category . '_' . $shop_code . '_' . date('Ymd') . '_' . $branch_number . '.jpg';
                } else {
                    $new_image_files[] = $request->input($request_image);
                }
            }
        }

        // 旧リストと新リストを比べて新リストにない画像ファイルは削除
        $diff_files = array_diff( $old_image_files, $new_image_files );
        if ( !empty($diff_files) ) {
            foreach ( $diff_files as $diff_file ) {
                Storage::disk('s3')->delete("zensho-mainte/images/$maintenance_id/$diff_file");
            }
        }

        // データベース（maintenance_imagesテーブル）のデータ削除して新しく登録
        // データ削除
        $request_image_ids = preg_grep('/^maintenance_image_id_/', $request_keys);
        foreach ( $request_image_ids as $request_image_id ) {
            $maintenance_image_id = (int) $request->input($request_image_id);
            Maintenance_image::destroy($maintenance_image_id);
        }

        // 新しく送られた画像のアップロード
        foreach ( $request_images as $request_image ) {
            if ( $request->hasFile($request_image) ) {
                $branch_number = explode('_', $request_image)[1];
                $file_name = $maintenance_code . '_' . $business_category . '_' . $shop_code . '_' . date('Ymd') . '_' . $branch_number . '.jpg';
                Storage::disk('s3')->putFileAs('/zensho-mainte/images/'.$maintenance_id, $request->file($request_image), $file_name, 'private');
            }
        }

        // データ登録
        $images = Storage::disk('s3')->files('/zensho-mainte/images/' . $maintenance_id);
        // この時点では$imagesは画像ファイル名の日付順になってるので枝番の小さい順に並び替える
        foreach ( $images as $image ) {
            $exploded_image_name    = explode('_', basename($image, '.jpg'));
            $branch                 = (int) $exploded_image_name[4]; // 枝番
            $sorted_images[$branch] = $image;
        }
        ksort($sorted_images);
        
        $i = 1;
        foreach ( $sorted_images as $image ) {
            $old_file_name = basename($image);
            list($maintenance_code, $business_category, $shop_code, $date, $branch) = explode('_', basename($image, '.jpg')); // ファイル名を分解、新しくファイル名を作成する際に便利そうだったのでこの形、date('Ymd')だと日付変わってしまうから$dateは地味に必要
            $new_file_name = $maintenance_code . '_' . $business_category . '_' . $shop_code . '_' . $date . '_' . $i . '.jpg';
            if ( $old_file_name !== $new_file_name ) {
                Storage::disk('s3')->move($image, '/zensho-mainte/images/' . $maintenance_id . '/' . $new_file_name); // ファイル名を変更して枝番のズレを直してる
            }
            $maintenance_image = new Maintenance_image;
            $maintenance_image->maintenance_id = $maintenance_id;
            $maintenance_image->file_name = $new_file_name;
            $maintenance_image->save();
            $i++;
        }

        // メール
        $applicant_user    = $maintenance->user;
        $shop_name         = $maintenance->shop->shop_name;
        $shop_code         = $maintenance->shop->shop_code;
        $order_type        = $maintenance->orderType->type;
        if ( $order_type === '発注依頼' ) {
            $maintenance_order_reasons = $maintenance->orderReasons;
            $x = 1;
            $order_type .= '（理由：';
            foreach ( $maintenance_order_reasons as $order_reason ) {
                if ( $x !== 1 ) {
                    $order_type .= '・';
                }
                $order_type .= $order_reason->reason;
                $x++;
            }
            $order_type .= '）';
        }
        if ( $order_type === 'その他' ) {
            $order_type = $order_type_other_text;
        }
        $progress_status   = $maintenance->progress->status;
        $subject           = $progress_status.' '.$business_category.' '.$shop_code.' '.$shop_name.' '.$equipment;
        $block_managers    = Block_manager::where('block_id', $maintenance->shop->block_id)->get();
        foreach ($block_managers as $block_manager) {
            $BM[] = User::find($block_manager->user_id);
        }
        $data = [
            'subject'          => $subject,
            'maintenance_code' => $maintenance_code,
            'shop_name'        => $shop_name,
            'shop_code'        => $shop_code,
            'equipment'        => $equipment,
            'manufacturer'     => $manufacturer,
            'model_number'     => $model_number,
            'when'             => $when,
            'situation'        => $situation,
            'order_type'       => $order_type,
            'progress_status'  => $progress_status,
            'applicant_user'   => $applicant_user,
            'BM'               => $BM,
        ];
        $files = Maintenance_image::where('maintenance_id', $maintenance_id)->get();
        foreach ($files as $file) {
			$attachedFiles[] = array(
				'data'      => Storage::disk('s3')->get("zensho-mainte/images/$maintenance_id/$file->file_name"),
				'file_name' => $file->file_name
			);
        }

        $cc = explode(',', $maintenance->shop->business_category->request_email);

        if ( preg_match('/冷凍庫|エアコン/', $equipment) ) {
            $cc[] = $maintenance->shop->business_category->headquarters_email;
        }
        
        if (config('app.env') == 'staging') {
			$cc = [];
        }

        if (config('app.env') == 'local') {
            $BM = [
                'yasu.fukuhara@interface-design.jp',
                'nads1012+test1@gmail.com'
            ];
            $cc = [];
        }

		Mail::to($BM)->cc($cc)->send(new MaintenanceEditMail($data, $attachedFiles));
		Log::debug('update email sent.'.print_r($data,true));
		
        return response()->json([
            'url' => url('maintenance/'.$maintenance_id.'/edit/completed'),
        ]);
            
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($maintenance_id)
    {
        Maintenance::find($maintenance_id)->delete(); // 論理削除です
        return redirect('/maintenance/'.$maintenance_id.'/delete/completed');
    }

    public function approve(Request $request, $maintenance_id)
    {
        return DB::transaction(function () use ($request, $maintenance_id) {
            
            $maintenance = Maintenance::find($maintenance_id);
            
            if ( $maintenance->progress_id === 6 ) {
                
                logger('メンテナンスID '.$maintenance_id.' この申請は承認済みです');
                
            } else {
                
                $maintenance->progress_id = 6; // 本部受付前
                $maintenance->save();

                $maintenance_progress = new Maintenance_progress;
                $maintenance_progress->maintenance_id = $maintenance_id;
                $maintenance_progress->progress_id    = 6; // 本部受付前
                $maintenance_progress->comment        = $request->input('comment');
                $maintenance_progress->entered_by     = $request->input('entered_by');
                $maintenance_progress->save();

                // メール
                $applicant_user        = $maintenance->user;
                $maintenance_code      = $maintenance->maintenance_code;
                $shop_name             = $maintenance->shop->shop_name;
                $shop_code             = $maintenance->shop->shop_code;
                $business_category     = $maintenance->shop->business_category->business_category;
                $equipment             = $maintenance->equipment;
                $manufacturer          = $maintenance->manufacturer;
                $model_number          = $maintenance->model_number;
                $when                  = $maintenance->when;
                $situation             = $maintenance->situation;
                $order_type            = $maintenance->orderType->type;
                $order_type_other_text = $maintenance->order_type_other_text;
                $progress_status       = $maintenance->progress->status;
                $comment               = $maintenance_progress->comment;
                if ($progress_status == '本部受付前'){
                    $subject           = 'BM承認済（本部受付前）';
                } else {
                    $subject           = $progress_status;
                }
                $subject          .= ' '.$business_category.' '.$shop_code.' '.$shop_name.' '.$equipment;
                if ($order_type === '発注依頼') {
                    $maintenance_order_reasons = $maintenance->orderReasons;
                    $x = 1;
                    $order_type .= '（理由：';
                    foreach ( $maintenance_order_reasons as $order_reason ) {
                        if ( $x !== 1 ) {
                            $order_type .= '・';
                        }
                        $order_type .= $order_reason->reason;
                        $x++;
                    }
                    $order_type .= '）';
                }
                if ($order_type === 'その他') {
                    $order_type = $order_type_other_text;
                }

                $block_managers = Block_manager::where('block_id', $maintenance->shop->block_id)->get();
                foreach ($block_managers as $block_manager) {
                    $BM_email = User::find($block_manager->user_id)->email;
                }
                if (config('app.env') == 'staging') {
                    $BM_email = 'test@idsweb.cc';
                }
                $BM_email = 'ml_k_mainte@zensho.com'; //IDS pre env.
                $data = [
                    'subject'          => $subject,
                    'maintenance_id'   => $maintenance_id,
                    'maintenance_code' => $maintenance_code,
                    'shop_name'        => $shop_name,
                    'shop_code'        => $shop_code,
                    'equipment'        => $equipment,
                    'manufacturer'     => $manufacturer,
                    'model_number'     => $model_number,
                    'when'             => $when,
                    'situation'        => $situation,
                    'order_type'       => $order_type,
                    'progress_status'  => $progress_status,
                    'comment'          => $comment,
                    'applicant_user'   => $applicant_user,
                    'updater'          => Auth::user(),
                    'BM_email'         => $BM_email,
                ];
                $files = Maintenance_image::where('maintenance_id', $maintenance_id)->get();
                foreach ($files as $file) {
                    $attachedFiles[] = array(
                        'data'      => Storage::disk('s3')->get("zensho-mainte/images/$maintenance_id/$file->file_name"),
                        'file_name' => $file->file_name
                    );
                }

                $headquarters = $maintenance->shop->business_category->headquarters_email;

                $cc  = explode(',', $maintenance->shop->business_category->approve_email);

                $bcc = [
                    'satoru.maeki@zensho.com',
                    'kazuya.arakaki@zensho.com',
                    'nishioka@interface-design.jp',
                    'yasu.fukuhara@interface-design.jp'
                ];

                if (config('app.env') == 'staging') {
                    $headquarters = [
                        'yasu.fukuhara@interface-design.jp',
                    ];
                    $bcc = [
                        'fukuhara810@gmail.com'
                    ];
                }

                if (config('app.env') == 'local') {
                    $headquarters = [
                        'yasu.fukuhara@interface-design.jp',
                        'nishioka@interface-design.jp'
                    ];
                    $bcc = [];
                }

                Mail::to($headquarters)->cc($cc)->bcc($bcc)->send(new ApprovalMail($data, $attachedFiles));
                Log::debug('approve email sent.'.print_r($data,true));

            }
            
            return redirect('maintenance/' . $maintenance_id . '/approve/completed');

        });
    }
	
    public function sendback(Request $request, $maintenance_id)
    {
        $maintenance_progress = new Maintenance_progress;
        $maintenance_progress->maintenance_id = $maintenance_id;
        $maintenance_progress->progress_id    = 3; // BM差戻し
        $maintenance_progress->comment        = $request->input('comment');
        $maintenance_progress->entered_by     = $request->input('entered_by');
        $maintenance_progress->save();

        $maintenance = Maintenance::find($maintenance_id);
        $maintenance->progress_id = 3; // BM差戻し
        $maintenance->save();

        Log::debug('sendback completed.');

        return redirect('/maintenance/' . $maintenance_id . '/sendback/completed');
    }
    
    public function reject(Request $request, $maintenance_id)
    {
        return DB::transaction(function () use ($request, $maintenance_id) {
            try {
                $maintenance_progress = new Maintenance_progress;
                $maintenance_progress->maintenance_id = $maintenance_id;
                $maintenance_progress->progress_id    = 4; // BM却下
                $maintenance_progress->comment        = $request->input('comment');
                $maintenance_progress->entered_by     = $request->input('entered_by');
                $maintenance_progress->save();
            } catch(\Exception $e) {
                
            }

            try {
                $maintenance = Maintenance::find($maintenance_id);
                $maintenance->progress_id = 4; // BM却下
                $maintenance->save();
            } catch(\Exception $e) {
                
            }
            
            Log::debug('reject completed.');

            return redirect('/maintenance/'.$maintenance_id.'/reject/completed');
        
        });
    }
    
    public function suspend(Request $request, $maintenance_id)
    {
        return DB::transaction(function () use ($request, $maintenance_id) {
            try {
                $maintenance_progress = new Maintenance_progress;
                $maintenance_progress->maintenance_id = $maintenance_id;
                $maintenance_progress->progress_id    = 5; // BM保留
                $maintenance_progress->comment        = $request->input('comment');
                $maintenance_progress->entered_by     = $request->input('entered_by');
                $maintenance_progress->save();

            } catch(\Exception $e) {
                
            }

            try {
                $maintenance = Maintenance::find($maintenance_id);
                $maintenance->progress_id = 5; // BM保留
                $maintenance->save();
            } catch(\Exception $e) {
                
            }
            
            Log::debug('suspend completed.');
  
            return redirect('/maintenance/'.$maintenance_id.'/suspend/completed');
            
        });
    }

	public function changeProgress(Request $request, $maintenance_id)
	{
		$progress_id = $request->input('progress_id');
		$role = Role::find( Auth::user()->role_id )->role_name;
		if ( $role === '管理者' ) {

			DB::beginTransaction();
			try {
				$maintenance = Maintenance::find($maintenance_id);
				$maintenance->progress_id = $progress_id;
				$maintenance->save();

				$maintenance_progress = new Maintenance_progress;
				$maintenance_progress->maintenance_id = $maintenance_id;
				$maintenance_progress->progress_id    = $progress_id;
				$maintenance_progress->comment        = '';
				$maintenance_progress->entered_by     = Auth::user()->user_id;
				$maintenance_progress->save();

				DB::commit();
				
				$request->session()->flash('flash_message', 'ID:'.$maintenance_id.'のステータスを更新しました');

			} catch(\Exception $e) {

				DB::rollback();
				$request->session()->flash('flash_message', $e->getMessage());
				return redirect('maintenance');

			}
			
			return redirect('maintenance');

		}

	}
}
