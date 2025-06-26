<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
   use HasFactory;
   
   protected $table = 'tasks';

   public $timestamps = true;
   
   protected $fillable = [
      'task_name',
      'task_description',
      'task_progress',
      'task_status',
      'department',
      'user_id',
   ];
  
   public function user()
   {
      $this->belongsTo(User::class);
   }
}
