<?php

namespace App\Models;

use App\Services\Claude\AssistantAccess;
use App\Services\Claude\DepartmentAgentRunner;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;
use Throwable;

class AssistantConversation extends Model
{
    // Nhân viên xóa = xóa mềm (ẩn khỏi danh sách của họ, Admin vẫn xem được). Admin xóa = xóa hẳn (purge).
    use SoftDeletes;

    protected $fillable = ['user_id', 'department', 'title', 'claude_session_id', 'last_message_at', 'deleted_by'];

    protected $casts = ['last_message_at' => 'datetime'];

    public function deleter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    /** Nhân viên xóa cuộc trò chuyện của mình: chỉ ẩn, giữ nội dung và session Claude để Admin xem lại */
    public function hide(User $by): void
    {
        $this->forceFill(['deleted_by' => $by->id])->save();
        $this->delete();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(AssistantMessage::class, 'conversation_id')->orderBy('id');
    }

    /** Chỉ Admin (không gồm SubAdmin) xem được lịch sử chat của mọi nhân viên */
    public static function canViewAll(?User $user): bool
    {
        return AssistantAccess::isFullAdmin($user);
    }

    public function canView(User $user): bool
    {
        return (int) $this->user_id === (int) $user->id || self::canViewAll($user);
    }

    /** Chỉ chủ cuộc trò chuyện mới hỏi tiếp / đổi tên được */
    public function isOwnedBy(User $user): bool
    {
        return (int) $this->user_id === (int) $user->id;
    }

    /** Xóa hẳn ở cả Medicen và session trên Claude (lỗi phía Claude không chặn việc xóa). Chỉ Admin. */
    public function purge(): void
    {
        if ($this->claude_session_id) {
            try {
                app(DepartmentAgentRunner::class)->deleteSession($this->claude_session_id);
            } catch (Throwable $e) {
                Log::warning('Không xóa được session Claude', ['session' => $this->claude_session_id, 'error' => $e->getMessage()]);
            }
        }
        $this->forceDelete();
    }
}
