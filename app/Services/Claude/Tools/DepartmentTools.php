<?php

namespace App\Services\Claude\Tools;

/**
 * Bộ tool (custom tool) của một phòng ban. Agent trên Claude gọi tool,
 * Medicen chạy hàm tương ứng trên DB local rồi trả kết quả về.
 */
interface DepartmentTools
{
    /**
     * Định nghĩa tool gửi lên agent: [['name' => ..., 'description' => ..., 'input_schema' => [...]], ...]
     */
    public function definitions(): array;

    /**
     * Chạy tool theo tên. Trả về mảng sẽ được JSON-encode làm kết quả cho agent.
     * $user là người đang chat (để kiểm quyền với tool có ghi dữ liệu); null khi chạy không có người dùng.
     *
     * @throws \InvalidArgumentException khi tên tool, tham số hoặc quyền không hợp lệ
     */
    public function call(string $name, array $input, ?\App\Models\User $user = null): array;
}
