<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'user_id',
        'service_id',
        'catalog_id',
        'measurement_id',
        'order_code',
        'description',
        'clothing_type',
        'color',
        'material',
        'reference_image',
        'address',
        'province',
        'city',
        'district',
        'village',
        'postal_code',
        'rt',
        'rw',
        'detail_address',
        'recipient_phone',
        'status',
        'total_price',
        'notes',
    ];

    /**
     * Generate unique order code: ZRT-YYYYXXXX
     */
    public static function generateOrderCode(): string
    {
        $year = date('Y');
        $lastOrder = self::whereYear('created_at', $year)->orderBy('id', 'desc')->first();

        if ($lastOrder) {
            $lastNumber = (int) substr($lastOrder->order_code, -4);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        return 'ZRT-' . $year . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Get status badge color
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'yellow',
            'confirmed' => 'blue',
            'processing' => 'indigo',
            'done' => 'purple',
            'shipped' => 'orange',
            'completed' => 'green',
            'cancelled' => 'red',
            default => 'gray',
        };
    }

    /**
     * Get status label in Bahasa Indonesia
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending'    => 'Menunggu Konfirmasi',
            'confirmed'  => 'Dikonfirmasi',
            'processing' => 'Sedang Diproses',
            'done'       => $this->getDoneLabel(),
            'shipped'    => 'Dikirim',
            'completed'  => 'Selesai',
            'cancelled'  => 'Dibatalkan',
            default      => $this->status,
        };
    }

    /**
     * Label status "done" berbeda per layanan:
     * - Permak → "Selesai Dijahit"
     * - Custom / lainnya → "Selesai Dibuat"
     */
    public function getDoneLabel(): string
    {
        $svc = strtolower($this->service?->name ?? '');
        return str_contains($svc, 'permak') ? 'Selesai Dijahit' : 'Selesai Dibuat';
    }

    // ── Relationships ──

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function catalog()
    {
        return $this->belongsTo(Catalog::class);
    }

    public function measurement()
    {
        return $this->belongsTo(Measurement::class);
    }

    public function statuses()
    {
        return $this->hasMany(OrderStatus::class)->orderBy('created_at', 'desc');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class)->orderBy('created_at', 'desc');
    }

    public function latestPayment()
    {
        return $this->hasOne(Payment::class)->latestOfMany();
    }

    public function shipment()
    {
        return $this->hasOne(Shipment::class);
    }
}
