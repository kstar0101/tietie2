<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Maintenance extends Model
{
	
	use SoftDeletes;
	
	protected $primaryKey = 'maintenance_id';
	
    protected $fillable = [
		'shop_id',
		'applicant_id',
		'equipment_id'
    ];
	
	protected $dates = ['deleted_at'];

	public function shop()
	{
		return $this->belongsTo('App\Shop', 'shop_id');
	}
	
	public function orderType()
	{
		return $this->belongsTo('App\Order_type', 'order_type_id');
	}
	
	public function equipment()
	{
		return $this->belongsTo('App\Equipment', 'equipment_id');
	}
	
	public function client()
	{
		return $this->belongsTo('App\Client', 'client_id');
	}
	
	public function progress()
	{
		return $this->belongsTo('App\Progress', 'progress_id');
	}
	
	public function user()
	{
		return $this->belongsTo('App\User', 'applicant_id');
	}
	
	public function maintenanceProgress()
	{
		return $this->hasMany('App\Maintenance_progress');
	}

	public function maintenanceImages()
	{
		return $this->hasMany('App\Maintenance_image');
	}

	public function orderReasons()
	{
		return $this->belongsToMany('App\Order_reason', 'maintenance_order_reasons', 'maintenance_id', 'order_reason_id');
	}

	public function category()
	{
		return $this->belongsTo('App\Category', 'category_id');
	}

	public function subCategory()
	{
		return $this->belongsTo('App\Sub_Category', 'sub_category_id');
	}

	public function maintenanceMatters()
	{
		return $this->hasMany('App\Maintenance_matter');
	}

}
