<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'company', 'email', 'phone',
        'address', 'postcode', 'city',
        'reference', 'branche', 'notes',
    ];

    protected static function booted(): void
    {
        static::creating(function (Customer $customer) {
            if (blank($customer->reference)) {
                $customer->reference = self::generateReference();
            }
        });
    }

    public static function generateReference(): string
    {
        $year = now()->year;
        $last = self::where('reference', 'like', "KL-{$year}-%")
            ->orderByDesc('id')
            ->first();
        $seq = $last ? ((int) substr($last->reference, -4)) + 1 : 1;
        return sprintf('KL-%d-%04d', $year, $seq);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function isInPilot(): bool
    {
        $numeric = (int) substr($this->postcode ?? '', 0, 4);
        return $numeric >= config('desnipperaar.pilot.postcode_start')
            && $numeric <= config('desnipperaar.pilot.postcode_end');
    }

    public function hasEverOrdered(): bool
    {
        return $this->orders()->exists();
    }
}
