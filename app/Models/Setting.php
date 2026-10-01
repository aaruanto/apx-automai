<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Key/value store for admin-configurable settings.
 *
 * Settings used to be read via config('apx.*') with inline fallbacks, but no
 * config/apx.php ever existed and nothing wrote to it, so every value silently
 * reverted to its default. Reads and writes both go through this table now.
 */
class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    /** Defaults used when a key has never been saved. */
    public const DEFAULTS = [
        'business_name'    => 'APX Motors Service Center',
        'branch_name'      => 'Tandang Sora Branch',
        'address'          => 'Tandang Sora Ave., Quezon City',
        'contact_number'   => '',
        'contact_email'    => '',
        'open_time'          => '09:00',
        'close_time'         => '21:00',
        'weekend_open_time'  => '09:00',
        'weekend_close_time' => '12:00',
        'max_bookings'       => 20,
        'slot_duration'      => 30,
        'allow_walkin'     => true,
        'email_reminders'  => false,
        'default_theme'    => 'light',
        'show_id_prefix'   => true,
        'maintenance'      => false,
    ];

    /** Keys stored as booleans, so reads cast back instead of returning "0"/"1". */
    public const BOOLEAN_KEYS = ['allow_walkin', 'email_reminders', 'show_id_prefix', 'maintenance'];

    protected static ?array $cache = null;

    protected static function booted(): void
    {
        static::saved(fn () => static::$cache = null);
        static::deleted(fn () => static::$cache = null);
    }

    /** All stored values keyed by name, loaded once per request. */
    public static function allValues(): array
    {
        return static::$cache ??= static::query()->pluck('value', 'key')->all();
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $value = static::allValues()[$key] ?? null;

        if ($value === null) {
            return $default ?? (static::DEFAULTS[$key] ?? null);
        }

        return in_array($key, static::BOOLEAN_KEYS, true)
            ? filter_var($value, FILTER_VALIDATE_BOOLEAN)
            : $value;
    }

    public static function set(string $key, mixed $value): void
    {
        if (is_bool($value)) {
            $value = $value ? '1' : '0';
        }

        static::updateOrCreate(['key' => $key], ['value' => $value]);
    }

    /** Bulk write — used by the settings screens. */
    public static function setMany(array $pairs): void
    {
        foreach ($pairs as $key => $value) {
            static::set($key, $value);
        }
    }

    /** Every known setting, saved value or default, ready for the view. */
    public static function withDefaults(): array
    {
        $out = [];
        foreach (static::DEFAULTS as $key => $default) {
            $out[$key] = static::get($key, $default);
        }

        return $out;
    }
}
