<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Auditable;

class Translation extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    const CREATED_AT = 'created';
    const UPDATED_AT = 'modified';

    protected $fillable = [
        'key',
        'locale',
        'value',
        'group',
        'namespace',
        'is_active',
        'is_system',
        'created_by',
        'metadata',
    ];

    protected $casts = [
        'created' => 'datetime',
        'modified' => 'datetime',
        'deleted_at' => 'datetime',
        'is_active' => 'boolean',
        'is_system' => 'boolean',
        'metadata' => 'array',
    ];

    /**
     * Get the user who created this translation
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Check if translation is active
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Check if translation is system translation (cannot be deleted)
     */
    public function isSystem(): bool
    {
        return $this->is_system;
    }

    /**
     * Get full translation key with namespace and group
     */
    public function getFullKeyAttribute(): string
    {
        $parts = array_filter([$this->namespace, $this->group, $this->key]);
        return implode('.', $parts);
    }

    /**
     * Get locale flag for UI
     */
    public function getLocaleFlagAttribute(): string
    {
        return match($this->locale) {
            'en' => '🇺🇸',
            'fr' => '🇫🇷',
            'es' => '🇪🇸',
            'de' => '🇩🇪',
            'it' => '🇮🇹',
            'pt' => '🇵🇹',
            'ar' => '🇸🇦',
            'zh' => '🇨🇳',
            'ja' => '🇯🇵',
            'ko' => '🇰🇷',
            'ru' => '🇷🇺',
            'hi' => '🇮🇳',
            'sw' => '🇰🇪',
            'am' => '🇪🇹',
            'ha' => '🇳🇬',
            'yo' => '🇳🇬',
            'ig' => '🇳🇬',
            'zu' => '🇿🇦',
            'af' => '🇿🇦',
            default => '🌐',
        };
    }

    /**
     * Get locale name for UI
     */
    public function getLocaleNameAttribute(): string
    {
        return match($this->locale) {
            'en' => 'English',
            'fr' => 'Français',
            'es' => 'Español',
            'de' => 'Deutsch',
            'it' => 'Italiano',
            'pt' => 'Português',
            'ar' => 'العربية',
            'zh' => '中文',
            'ja' => '日本語',
            'ko' => '한국어',
            'ru' => 'Русский',
            'hi' => 'हिन्दी',
            'sw' => 'Kiswahili',
            'am' => 'አማርኛ',
            'ha' => 'Hausa',
            'yo' => 'Yorùbá',
            'ig' => 'Igbo',
            'zu' => 'isiZulu',
            'af' => 'Afrikaans',
            default => ucfirst($this->locale),
        };
    }

    /**
     * Get character count
     */
    public function getCharacterCountAttribute(): int
    {
        return mb_strlen($this->value);
    }

    /**
     * Get word count
     */
    public function getWordCountAttribute(): int
    {
        return str_word_count(strip_tags($this->value));
    }

    /**
     * Check if translation needs review (contains placeholders or is empty)
     */
    public function needsReview(): bool
    {
        if (empty($this->value)) {
            return true;
        }

        // Check for common placeholder patterns
        $placeholders = ['TODO', 'TRANSLATE', 'MISSING', '{{', '}}', '[PLACEHOLDER]'];
        foreach ($placeholders as $placeholder) {
            if (str_contains(strtoupper($this->value), $placeholder)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get translation progress for a locale
     */
    public static function getLocaleProgress(string $locale): array
    {
        $total = static::where('locale', 'en')->count(); // English as base
        $translated = static::where('locale', $locale)->whereNotNull('value')->where('value', '!=', '')->count();
        $needsReview = static::where('locale', $locale)->get()->filter(fn($t) => $t->needsReview())->count();

        return [
            'total' => $total,
            'translated' => $translated,
            'needs_review' => $needsReview,
            'percentage' => $total > 0 ? round(($translated / $total) * 100, 2) : 0,
        ];
    }

    /**
     * Scope for active translations
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for translations by locale
     */
    public function scopeByLocale($query, string $locale)
    {
        return $query->where('locale', $locale);
    }

    /**
     * Scope for translations by group
     */
    public function scopeByGroup($query, string $group)
    {
        return $query->where('group', $group);
    }

    /**
     * Scope for translations by namespace
     */
    public function scopeByNamespace($query, string $namespace)
    {
        return $query->where('namespace', $namespace);
    }

    /**
     * Scope for system translations
     */
    public function scopeSystem($query)
    {
        return $query->where('is_system', true);
    }

    /**
     * Scope for custom translations
     */
    public function scopeCustom($query)
    {
        return $query->where('is_system', false);
    }

    /**
     * Scope for translations that need review
     */
    public function scopeNeedsReview($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('value')
              ->orWhere('value', '')
              ->orWhere('value', 'like', '%TODO%')
              ->orWhere('value', 'like', '%TRANSLATE%')
              ->orWhere('value', 'like', '%MISSING%')
              ->orWhere('value', 'like', '%{{%')
              ->orWhere('value', 'like', '%[PLACEHOLDER]%');
        });
    }

    /**
     * Scope for missing translations (empty values)
     */
    public function scopeMissing($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('value')->orWhere('value', '');
        });
    }

    /**
     * Common locales supported
     */
    const LOCALE_ENGLISH = 'en';
    const LOCALE_FRENCH = 'fr';
    const LOCALE_SPANISH = 'es';
    const LOCALE_GERMAN = 'de';
    const LOCALE_ITALIAN = 'it';
    const LOCALE_PORTUGUESE = 'pt';
    const LOCALE_ARABIC = 'ar';
    const LOCALE_CHINESE = 'zh';
    const LOCALE_JAPANESE = 'ja';
    const LOCALE_KOREAN = 'ko';
    const LOCALE_RUSSIAN = 'ru';
    const LOCALE_HINDI = 'hi';
    const LOCALE_SWAHILI = 'sw';
    const LOCALE_AMHARIC = 'am';
    const LOCALE_HAUSA = 'ha';
    const LOCALE_YORUBA = 'yo';
    const LOCALE_IGBO = 'ig';
    const LOCALE_ZULU = 'zu';
    const LOCALE_AFRIKAANS = 'af';

    /**
     * Common translation groups
     */
    const GROUP_COMMON = 'common';
    const GROUP_AUTH = 'auth';
    const GROUP_NAVIGATION = 'navigation';
    const GROUP_FORMS = 'forms';
    const GROUP_MESSAGES = 'messages';
    const GROUP_ERRORS = 'errors';
    const GROUP_VALIDATION = 'validation';
    const GROUP_EMAILS = 'emails';
    const GROUP_DASHBOARD = 'dashboard';
    const GROUP_ORGANIZATIONS = 'organizations';
    const GROUP_EVENTS = 'events';
    const GROUP_FORUMS = 'forums';
    const GROUP_USERS = 'users';

    /**
     * Get all supported locales
     */
    public static function getSupportedLocales(): array
    {
        return [
            self::LOCALE_ENGLISH,
            self::LOCALE_FRENCH,
            self::LOCALE_SPANISH,
            self::LOCALE_GERMAN,
            self::LOCALE_ITALIAN,
            self::LOCALE_PORTUGUESE,
            self::LOCALE_ARABIC,
            self::LOCALE_CHINESE,
            self::LOCALE_JAPANESE,
            self::LOCALE_KOREAN,
            self::LOCALE_RUSSIAN,
            self::LOCALE_HINDI,
            self::LOCALE_SWAHILI,
            self::LOCALE_AMHARIC,
            self::LOCALE_HAUSA,
            self::LOCALE_YORUBA,
            self::LOCALE_IGBO,
            self::LOCALE_ZULU,
            self::LOCALE_AFRIKAANS,
        ];
    }

    /**
     * Get all translation groups
     */
    public static function getTranslationGroups(): array
    {
        return [
            self::GROUP_COMMON,
            self::GROUP_AUTH,
            self::GROUP_NAVIGATION,
            self::GROUP_FORMS,
            self::GROUP_MESSAGES,
            self::GROUP_ERRORS,
            self::GROUP_VALIDATION,
            self::GROUP_EMAILS,
            self::GROUP_DASHBOARD,
            self::GROUP_ORGANIZATIONS,
            self::GROUP_EVENTS,
            self::GROUP_FORUMS,
            self::GROUP_USERS,
        ];
    }

    /**
     * Get African locales specifically
     */
    public static function getAfricanLocales(): array
    {
        return [
            self::LOCALE_SWAHILI,
            self::LOCALE_AMHARIC,
            self::LOCALE_HAUSA,
            self::LOCALE_YORUBA,
            self::LOCALE_IGBO,
            self::LOCALE_ZULU,
            self::LOCALE_AFRIKAANS,
            self::LOCALE_ARABIC, // Also widely spoken in North Africa
            self::LOCALE_FRENCH, // Also widely spoken in West/Central Africa
            self::LOCALE_PORTUGUESE, // Also spoken in some African countries
        ];
    }
}
