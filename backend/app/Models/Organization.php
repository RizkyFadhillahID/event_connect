<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class Organization extends Model
{
    use HasFactory;

    public static $plans = [
        'free' => [
            'name' => 'Free Plan',
            'max_users' => 5,
            'max_events' => 3,
            'features' => [
                'chat_attachments' => false,
            ]
        ],
        'business' => [
            'name' => 'Business Plan',
            'max_users' => 25,
            'max_events' => 50,
            'features' => [
                'chat_attachments' => true,
            ]
        ],
        'enterprise' => [
            'name' => 'Enterprise Plan',
            'max_users' => 9999,
            'max_events' => 9999,
            'features' => [
                'chat_attachments' => true,
            ]
        ]
    ];

    public function hasFeature(string $feature): bool
    {
        $plan = $this->plan ?? 'free';
        return self::$plans[$plan]['features'][$feature] ?? false;
    }

    protected static function booted()
    {
        static::saving(function ($organization) {
            $plan = $organization->plan ?? 'free';
            if ((!$organization->exists || $organization->isDirty('plan')) && isset(self::$plans[$plan])) {
                $organization->max_users = self::$plans[$plan]['max_users'];
                $organization->max_events = self::$plans[$plan]['max_events'];
            }
        });

        static::updated(function ($organization) {
            if ($organization->wasChanged('status') && in_array($organization->status, ['suspended', 'inactive'])) {
                $userIds = User::where('organization_id', $organization->id)->pluck('id');
                DB::table('personal_access_tokens')
                    ->where('tokenable_type', User::class)
                    ->whereIn('tokenable_id', $userIds)
                    ->delete();
            }
        });
    }

    protected $fillable = [
        'name',
        'slug',
        'email',
        'phone',
        'address',
        'logo',
        'status',
        'plan',
        'max_users',
        'max_events',
        'notes',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function events()
    {
        return $this->hasMany(Event::class);
    }

    public function inventories()
    {
        return $this->hasMany(Inventory::class);
    }
}
