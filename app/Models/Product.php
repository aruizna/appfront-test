<?php

namespace App\Models;

use App\Observers\ProductObserve;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[ObservedBy(ProductObserve::class)]
class Product extends Model
{
    protected $guarded = ['id'];
}
