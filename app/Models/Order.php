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
        'design_file',
        'design_notes',
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
            'pending'       => 'yellow',
            'confirmed'     => 'blue',
            'waiting_item'  => 'cyan',
            'item_received' => 'teal',
            'processing'    => 'indigo',
            'done'          => 'purple',
            'revision'      => 'pink',
            'shipped'       => 'orange',
            'completed'     => 'green',
            'cancelled'     => 'red',
            default         => 'gray',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending'       => 'Menunggu Konfirmasi',
            'confirmed'     => 'Dikonfirmasi',
            'waiting_item'  => 'Menunggu Kiriman Barang',
            'item_received' => 'Barang Diterima',
            'processing'    => 'Sedang Diproses',
            'done'          => $this->getDoneLabel(),
            'revision'      => 'Menunggu Revisi',
            'shipped'       => 'Dikirim',
            'completed'     => 'Selesai',
            'cancelled'     => 'Dibatalkan',
            default         => $this->status,
        };
    }

    public function getDoneLabel(): string
    {
        $type = $this->service?->type ?? '';
        $name = strtolower($this->service?->name ?? '');
        if ($type === 'permak' || str_contains($name, 'permak')) return 'Selesai Dijahit';
        if ($type === 'design') return 'Desain Selesai';
        return 'Selesai Dibuat';
    }

    /** Tipe layanan shortcut */
    public function getServiceTypeAttribute(): string
    {
        return $this->service?->type ?? 'custom';
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

    public function buyerShipment()
    {
        return $this->hasOne(BuyerShipment::class);
    }
}
