# VoucherHistory API Documentation

## 概述
VoucherHistory 模块用于记录代金券的所有操作历史，包括创建、消费、编辑和退款等操作。

## 模块结构

### 文件列表
- **Model**: `app/Models/VoucherHistory.php`
- **Contract**: `app/Contracts/VoucherHistoryContract.php`
- **Repository**: `app/Repositories/VoucherHistoryRepository.php`
- **Service**: `app/Services/VoucherHistoryService.php`
- **Controller**: `app/Http/Controllers/VoucherHistoryController.php`
- **Request**: `app/Http/Requests/VoucherHistoryRequest.php`
- **Migration**: `database/migrations/2025_07_29_033150_create_voucher_histories_table.php`

## API 端点

### 基础 CRUD 操作

#### 获取所有代金券历史记录（分页）
```http
GET /api/voucher-histories?start=0&count=10&filter=consume&sortBy=created_at&descending=true
```

#### 获取单个代金券历史记录
```http
GET /api/voucher-histories/{id}
```

#### 创建代金券历史记录
```http
POST /api/voucher-histories
Content-Type: application/json

{
    "voucher_id": 1,
    "user_id": 1,
    "appointment_id": 1,
    "phone": "123456789",
    "name": "张三",
    "service": "按摩服务",
    "action": "consume",
    "description": "服务消费",
    "pre_amount": 100.00,
    "after_amount": 70.00
}
```

#### 更新代金券历史记录
```http
PUT /api/voucher-histories/{id}
Content-Type: application/json

{
    "description": "更新描述"
}
```

#### 删除代金券历史记录
```http
DELETE /api/voucher-histories/{id}
```

### 特殊查询操作

#### 根据代金券ID获取历史记录
```http
GET /api/voucher-histories/voucher/{voucherId}
```

#### 根据用户ID获取历史记录
```http
GET /api/voucher-histories/user/{userId}
```

#### 根据操作类型获取历史记录
```http
GET /api/voucher-histories/action/{action}
```
支持的操作类型：`init`, `consume`, `edit`, `refund`

### 快速记录操作

#### 记录代金券消费
```http
POST /api/voucher-histories/record-consumption
Content-Type: application/json

{
    "voucher_id": 1,
    "consume_amount": 30.00,
    "user_id": 1,
    "appointment_id": 1,
    "phone": "123456789",
    "name": "张三",
    "service": "按摩服务",
    "description": "按摩服务消费"
}
```

#### 记录代金券初始化
```http
POST /api/voucher-histories/record-init
Content-Type: application/json

{
    "voucher_id": 1,
    "initial_amount": 100.00,
    "user_id": 1,
    "phone": "123456789",
    "name": "张三",
    "description": "代金券初始化"
}
```

#### 记录代金券编辑
```http
POST /api/voucher-histories/record-edit
Content-Type: application/json

{
    "voucher_id": 1,
    "old_amount": 100.00,
    "new_amount": 120.00,
    "user_id": 1,
    "description": "代金券金额调整"
}
```

#### 记录代金券退款
```http
POST /api/voucher-histories/record-refund
Content-Type: application/json

{
    "voucher_id": 1,
    "refund_amount": 50.00,
    "user_id": 1,
    "appointment_id": 1,
    "phone": "123456789",
    "name": "张三",
    "service": "按摩服务",
    "description": "服务退款"
}
```

## 数据模型

### VoucherHistory 字段说明

| 字段名 | 类型 | 描述 | 是否必填 |
|--------|------|------|----------|
| id | bigint | 主键ID | 自动生成 |
| voucher_id | bigint | 代金券ID（外键） | 必填 |
| user_id | int | 用户ID | 可选 |
| appointment_id | int | 预约ID | 可选 |
| phone | string | 电话号码 | 可选 |
| name | string | 姓名 | 可选 |
| service | string | 服务名称 | 可选 |
| action | string | 操作类型 | 必填 |
| description | string | 描述 | 可选 |
| pre_amount | double | 操作前金额 | 必填 |
| after_amount | double | 操作后金额 | 必填 |
| created_at | timestamp | 创建时间 | 自动生成 |
| updated_at | timestamp | 更新时间 | 自动生成 |

### 操作类型（action）说明

- **init**: 代金券初始化/创建
- **consume**: 代金券消费
- **edit**: 代金券编辑/修改
- **refund**: 代金券退款

## 关联关系

### VoucherHistory 模型关联
- `belongsTo(Voucher::class)` - 属于某个代金券
- `belongsTo(User::class)` - 属于某个用户（可选）
- `belongsTo(Appointment::class)` - 属于某个预约（可选）

### Voucher 模型关联
- `hasMany(VoucherHistory::class)` - 拥有多个历史记录

## 使用示例

### 在代码中记录代金券消费
```php
use App\Services\VoucherHistoryService;

$voucherHistoryService = app(VoucherHistoryService::class);

// 记录代金券消费
$history = $voucherHistoryService->recordVoucherConsumption(
    $voucherId = 1,
    $consumeAmount = 30.00,
    [
        'user_id' => 1,
        'appointment_id' => 1,
        'phone' => '123456789',
        'name' => '张三',
        'service' => '按摩服务',
        'description' => '60分钟全身按摩'
    ]
);
```

### 查询代金券的所有历史记录
```php
use App\Services\VoucherHistoryService;

$voucherHistoryService = app(VoucherHistoryService::class);

// 获取特定代金券的所有历史记录
$histories = $voucherHistoryService->getVoucherHistoriesByVoucherId(1);

// 获取特定用户的所有代金券历史
$userHistories = $voucherHistoryService->getVoucherHistoriesByUserId(1);

// 获取所有消费记录
$consumeHistories = $voucherHistoryService->getVoucherHistoriesByAction('consume');
```

## 响应格式

### 成功响应示例
```json
{
    "id": 1,
    "voucher_id": 1,
    "user_id": 1,
    "appointment_id": 1,
    "phone": "123456789",
    "name": "张三",
    "service": "按摩服务",
    "action": "consume",
    "description": "60分钟全身按摩",
    "pre_amount": 100.00,
    "after_amount": 70.00,
    "created_at": "2025-07-29T03:31:50.000000Z",
    "updated_at": "2025-07-29T03:31:50.000000Z",
    "voucher": {
        "id": 1,
        "code": "VOUCHER001",
        "amount": 100.00,
        "remaining_amount": 70.00
    },
    "user": {
        "id": 1,
        "name": "张三",
        "email": "zhangsan@example.com"
    }
}
```

### 分页响应示例
```json
{
    "rows": [
        {
            "id": 1,
            "voucher_id": 1,
            "action": "consume",
            "pre_amount": 100.00,
            "after_amount": 70.00,
            "created_at": "2025-07-29T03:31:50.000000Z"
        }
    ],
    "total": 1
}
```

## 注意事项

1. **自动更新代金券余额**: 使用 `recordVoucherConsumption` 和 `recordVoucherRefund` 方法会自动更新对应代金券的 `remaining_amount` 字段。

2. **数据完整性**: 所有涉及金额变化的操作都需要记录 `pre_amount` 和 `after_amount`，确保可以追溯每一次金额变化。

3. **软删除**: 目前历史记录不使用软删除，一旦创建就应该保持不变，除非有特殊需求。

4. **权限控制**: 在实际使用时，建议在路由中添加适当的中间件来控制访问权限。

5. **数据验证**: 使用 `VoucherHistoryRequest` 类进行请求数据验证，确保数据的完整性和正确性。
