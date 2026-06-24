<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;

class Campaign extends Model implements AuditableContract
{
    use BelongsToCompany, SoftDeletes, Auditable;

    protected $fillable = [
        'uuid',
        'company_id',
        'name',
        'send_type_id',
        'scheduled_at',
        'no_send_rules',
        'notification_email',
        'total_recipients',
        'campaign_status',
        'created_by',
        'completed_at',
    ];

    // C-09: exclude high-frequency counter fields from audit log to avoid 500
    // INSERT INTO audits per 250k-message campaign (one per progress webhook).
    protected array $auditExclude = [
        'sent_count',
        'failed_count',
        'updated_at',
        'worker_id',
    ];

    protected $appends = ['status_label', 'status_color'];

    protected function casts(): array
    {
        return [
            'no_send_rules'   => 'array',
            'scheduled_at'    => 'datetime',
            'completed_at'    => 'datetime',
            'charged_at'      => 'datetime',
            'deleted_at'      => 'datetime',
            'campaign_status' => 'integer',
            'send_type_id'    => 'integer',
            'charged_cost'    => 'decimal:4',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Campaign $campaign) {
            if (empty($campaign->uuid)) {
                $campaign->uuid = (string) Str::uuid();
            }
        });
    }

    // -------------------------------------------------------------------------
    // Accessors — delegan al catálogo campaign_statuses
    // -------------------------------------------------------------------------

    public function getStatusLabelAttribute(): string
    {
        return $this->statusCatalog?->name ?? 'Desconocido';
    }

    public function getStatusColorAttribute(): string
    {
        return $this->statusCatalog?->color ?? 'pill--draft';
    }

    // -------------------------------------------------------------------------
    // Relations
    // -------------------------------------------------------------------------

    /**
     * The company() relation is provided by BelongsToCompany trait.
     */

    public function statusCatalog()
    {
        return $this->belongsTo(CampaignStatus::class, 'campaign_status', 'id');
    }

    public function sendType()
    {
        return $this->belongsTo(CampaignSendType::class, 'send_type_id');
    }

    public function recipients()
    {
        return $this->hasMany(CampaignRecipient::class, 'campaign_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
