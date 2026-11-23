<?php

namespace App\Models\Jobs;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class JobTemplate extends Model
{
    use SoftDeletes;

    protected $table = 'job_templates';

    protected $fillable = [
        'name', 'description', 'theme', 'sections', 'is_default', 'status', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'theme'      => 'array',
        'sections'   => 'array',
        'is_default' => 'boolean',
    ];

    /** every section a job can show, in the order used when a template does not say otherwise */
    public const SECTION_KEYS = [
        'summary', 'responsibilities', 'qualifications', 'education', 'skills', 'benefits', 'questions',
    ];

    public const DEFAULT_LABELS = [
        'summary' => 'About the job', 'responsibilities' => 'Job description', 'qualifications' => 'Qualifications',
        'education' => 'Education', 'skills' => 'Required skills', 'benefits' => 'Benefits and perks',
        'questions' => 'Screening questions',
    ];

    public const FONTS = ['verdana', 'system', 'georgia', 'trebuchet', 'tahoma', 'times', 'courier'];
    public const LAYOUTS = ['single', 'two_column', 'sidebar'];
    public const BUTTON_STYLES = ['filled', 'outline', 'soft'];
    public const BUTTON_SHAPES = ['square', 'rounded', 'pill'];
    public const HEADER_STYLES = ['gradient', 'solid', 'plain'];
    public const BACKGROUND_STYLES = ['solid', 'soft', 'gradient'];

    public function scopePublished($q)
    {
        return $q->where('status', 'published');
    }

    /** the shape sent to the browser (never trust stored JSON blindly: fill gaps with safe defaults) */
    public function toPublic(): array
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'description' => $this->description,
            'status'      => $this->status,
            'is_default'  => (bool) $this->is_default,
            'theme'       => $this->theme ?: [],
            'sections'    => self::normalizeSections($this->sections ?: []),
            'updated_at'  => optional($this->updated_at)->toIso8601String(),
        ];
    }

    /** one entry per known section, in the given order; anything missing is appended enabled */
    public static function normalizeSections(array $sections): array
    {
        $out = [];
        $seen = [];
        foreach ($sections as $s) {
            $key = $s['key'] ?? null;
            if (!in_array($key, self::SECTION_KEYS, true) || isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $label = trim((string) ($s['label'] ?? ''));
            $out[] = [
                'key'     => $key,
                'label'   => $label !== '' ? mb_substr($label, 0, 60) : self::DEFAULT_LABELS[$key],
                'enabled' => array_key_exists('enabled', $s) ? (bool) $s['enabled'] : true,
            ];
        }
        foreach (self::SECTION_KEYS as $key) {
            if (!isset($seen[$key])) {
                $out[] = ['key' => $key, 'label' => self::DEFAULT_LABELS[$key], 'enabled' => true];
            }
        }

        return $out;
    }
}
