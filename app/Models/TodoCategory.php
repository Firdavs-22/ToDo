<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TodoCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'todo_id',
        'category_id',
        'status',
    ];


    protected $table = 'todo_category';

    public $timestamps = false;
}
