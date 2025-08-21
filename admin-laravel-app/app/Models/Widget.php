<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Auditable;

class Widget extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    const CREATED_AT = 'created';
    const UPDATED_AT = 'modified';

    protected $fillable = [
        'name',
        'title',
        'description',
        'type',
        'position',
        'page',
        'content',
        'settings',
        'is_active',
        'is_system',
        'sort_order',
        'visibility_rules',
        'cache_duration',
        'created_by',
        'organization_id',
        'metadata',
    ];

    protected $casts = [
        'created' => 'datetime',
        'modified' => 'datetime',
        'deleted_at' => 'datetime',
        'is_active' => 'boolean',
        'is_system' => 'boolean',
        'sort_order' => 'integer',
        'cache_duration' => 'integer',
        'content' => 'array',
        'settings' => 'array',
        'visibility_rules' => 'array',
        'metadata' => 'array',
    ];

    /**
     * Get the user who created this widget
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the organization this widget belongs to (if any)
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Check if widget is active
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Check if widget is system widget (cannot be deleted)
     */
    public function isSystem(): bool
    {
        return $this->is_system;
    }

    /**
     * Get widget content based on type
     */
    public function getRenderedContent(): string
    {
        return match($this->type) {
            'html' => $this->renderHtmlWidget(),
            'text' => $this->renderTextWidget(),
            'stats' => $this->renderStatsWidget(),
            'chart' => $this->renderChartWidget(),
            'list' => $this->renderListWidget(),
            'feed' => $this->renderFeedWidget(),
            'calendar' => $this->renderCalendarWidget(),
            'map' => $this->renderMapWidget(),
            'social' => $this->renderSocialWidget(),
            'custom' => $this->renderCustomWidget(),
            default => $this->renderDefaultWidget(),
        };
    }

    /**
     * Get widget icon based on type
     */
    public function getTypeIconAttribute(): string
    {
        return match($this->type) {
            'html' => 'code',
            'text' => 'file-text',
            'stats' => 'bar-chart',
            'chart' => 'pie-chart',
            'list' => 'list',
            'feed' => 'rss',
            'calendar' => 'calendar',
            'map' => 'map',
            'social' => 'share-2',
            'custom' => 'settings',
            default => 'square',
        };
    }

    /**
     * Get widget color based on type
     */
    public function getTypeColorAttribute(): string
    {
        return match($this->type) {
            'html' => 'blue',
            'text' => 'gray',
            'stats' => 'green',
            'chart' => 'purple',
            'list' => 'yellow',
            'feed' => 'orange',
            'calendar' => 'red',
            'map' => 'teal',
            'social' => 'pink',
            'custom' => 'indigo',
            default => 'gray',
        };
    }

    /**
     * Check if widget is visible for current context
     */
    public function isVisible(array $context = []): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if (!$this->visibility_rules) {
            return true;
        }

        // Check visibility rules
        foreach ($this->visibility_rules as $rule) {
            if (!$this->checkVisibilityRule($rule, $context)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check individual visibility rule
     */
    protected function checkVisibilityRule(array $rule, array $context): bool
    {
        $field = $rule['field'] ?? null;
        $operator = $rule['operator'] ?? 'equals';
        $value = $rule['value'] ?? null;

        if (!$field || !isset($context[$field])) {
            return true;
        }

        $contextValue = $context[$field];

        return match($operator) {
            'equals' => $contextValue == $value,
            'not_equals' => $contextValue != $value,
            'contains' => str_contains($contextValue, $value),
            'not_contains' => !str_contains($contextValue, $value),
            'in' => in_array($contextValue, (array)$value),
            'not_in' => !in_array($contextValue, (array)$value),
            'greater_than' => $contextValue > $value,
            'less_than' => $contextValue < $value,
            default => true,
        };
    }

    /**
     * Render HTML widget
     */
    protected function renderHtmlWidget(): string
    {
        return $this->content['html'] ?? '';
    }

    /**
     * Render text widget
     */
    protected function renderTextWidget(): string
    {
        $text = $this->content['text'] ?? '';
        return nl2br(e($text));
    }

    /**
     * Render stats widget
     */
    protected function renderStatsWidget(): string
    {
        $stats = $this->content['stats'] ?? [];
        $html = '<div class="stats-widget">';
        
        foreach ($stats as $stat) {
            $html .= '<div class="stat-item">';
            $html .= '<div class="stat-value">' . e($stat['value'] ?? '0') . '</div>';
            $html .= '<div class="stat-label">' . e($stat['label'] ?? '') . '</div>';
            $html .= '</div>';
        }
        
        $html .= '</div>';
        return $html;
    }

    /**
     * Render chart widget
     */
    protected function renderChartWidget(): string
    {
        $chartData = $this->content['chart'] ?? [];
        $chartId = 'chart-' . $this->id;
        
        return '<div id="' . $chartId . '" class="chart-widget" data-chart="' . e(json_encode($chartData)) . '"></div>';
    }

    /**
     * Render list widget
     */
    protected function renderListWidget(): string
    {
        $items = $this->content['items'] ?? [];
        $html = '<ul class="list-widget">';
        
        foreach ($items as $item) {
            $html .= '<li>';
            if (isset($item['link'])) {
                $html .= '<a href="' . e($item['link']) . '">' . e($item['title'] ?? '') . '</a>';
            } else {
                $html .= e($item['title'] ?? '');
            }
            $html .= '</li>';
        }
        
        $html .= '</ul>';
        return $html;
    }

    /**
     * Render feed widget
     */
    protected function renderFeedWidget(): string
    {
        $feedUrl = $this->content['feed_url'] ?? '';
        $maxItems = $this->content['max_items'] ?? 5;
        
        // This would typically fetch and parse RSS/feed data
        return '<div class="feed-widget" data-feed-url="' . e($feedUrl) . '" data-max-items="' . $maxItems . '">Loading feed...</div>';
    }

    /**
     * Render calendar widget
     */
    protected function renderCalendarWidget(): string
    {
        $calendarId = 'calendar-' . $this->id;
        return '<div id="' . $calendarId . '" class="calendar-widget"></div>';
    }

    /**
     * Render map widget
     */
    protected function renderMapWidget(): string
    {
        $mapData = $this->content['map'] ?? [];
        $mapId = 'map-' . $this->id;
        
        return '<div id="' . $mapId . '" class="map-widget" data-map="' . e(json_encode($mapData)) . '"></div>';
    }

    /**
     * Render social widget
     */
    protected function renderSocialWidget(): string
    {
        $socialData = $this->content['social'] ?? [];
        $html = '<div class="social-widget">';
        
        foreach ($socialData as $platform => $data) {
            $html .= '<div class="social-item">';
            $html .= '<a href="' . e($data['url'] ?? '#') . '" target="_blank">';
            $html .= '<i class="fab fa-' . e($platform) . '"></i>';
            $html .= e($data['label'] ?? ucfirst($platform));
            $html .= '</a>';
            $html .= '</div>';
        }
        
        $html .= '</div>';
        return $html;
    }

    /**
     * Render custom widget
     */
    protected function renderCustomWidget(): string
    {
        $template = $this->content['template'] ?? '';
        $data = $this->content['data'] ?? [];
        
        // This would typically use a template engine
        return str_replace(
            array_map(fn($key) => '{{' . $key . '}}', array_keys($data)),
            array_values($data),
            $template
        );
    }

    /**
     * Render default widget
     */
    protected function renderDefaultWidget(): string
    {
        return '<div class="default-widget">' . e($this->title) . '</div>';
    }

    /**
     * Scope for active widgets
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for widgets by page
     */
    public function scopeByPage($query, string $page)
    {
        return $query->where('page', $page);
    }

    /**
     * Scope for widgets by position
     */
    public function scopeByPosition($query, string $position)
    {
        return $query->where('position', $position);
    }

    /**
     * Scope for widgets by type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope for system widgets
     */
    public function scopeSystem($query)
    {
        return $query->where('is_system', true);
    }

    /**
     * Scope for custom widgets
     */
    public function scopeCustom($query)
    {
        return $query->where('is_system', false);
    }

    /**
     * Scope for organization widgets
     */
    public function scopeByOrganization($query, $organizationId)
    {
        return $query->where('organization_id', $organizationId);
    }

    /**
     * Scope for global widgets
     */
    public function scopeGlobal($query)
    {
        return $query->whereNull('organization_id');
    }

    /**
     * Common widget types
     */
    const TYPE_HTML = 'html';
    const TYPE_TEXT = 'text';
    const TYPE_STATS = 'stats';
    const TYPE_CHART = 'chart';
    const TYPE_LIST = 'list';
    const TYPE_FEED = 'feed';
    const TYPE_CALENDAR = 'calendar';
    const TYPE_MAP = 'map';
    const TYPE_SOCIAL = 'social';
    const TYPE_CUSTOM = 'custom';

    /**
     * Common widget positions
     */
    const POSITION_HEADER = 'header';
    const POSITION_SIDEBAR = 'sidebar';
    const POSITION_FOOTER = 'footer';
    const POSITION_CONTENT = 'content';
    const POSITION_DASHBOARD = 'dashboard';

    /**
     * Common pages
     */
    const PAGE_HOME = 'home';
    const PAGE_DASHBOARD = 'dashboard';
    const PAGE_PROFILE = 'profile';
    const PAGE_ORGANIZATION = 'organization';
    const PAGE_EVENTS = 'events';
    const PAGE_FORUMS = 'forums';
    const PAGE_ALL = '*';

    /**
     * Get all available widget types
     */
    public static function getAvailableTypes(): array
    {
        return [
            self::TYPE_HTML,
            self::TYPE_TEXT,
            self::TYPE_STATS,
            self::TYPE_CHART,
            self::TYPE_LIST,
            self::TYPE_FEED,
            self::TYPE_CALENDAR,
            self::TYPE_MAP,
            self::TYPE_SOCIAL,
            self::TYPE_CUSTOM,
        ];
    }

    /**
     * Get all available positions
     */
    public static function getAvailablePositions(): array
    {
        return [
            self::POSITION_HEADER,
            self::POSITION_SIDEBAR,
            self::POSITION_FOOTER,
            self::POSITION_CONTENT,
            self::POSITION_DASHBOARD,
        ];
    }

    /**
     * Get all available pages
     */
    public static function getAvailablePages(): array
    {
        return [
            self::PAGE_HOME,
            self::PAGE_DASHBOARD,
            self::PAGE_PROFILE,
            self::PAGE_ORGANIZATION,
            self::PAGE_EVENTS,
            self::PAGE_FORUMS,
            self::PAGE_ALL,
        ];
    }
}
