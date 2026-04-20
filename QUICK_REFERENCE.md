# 🔍 QUICK REFERENCE - Database Core Migration

## Database Connection
```php
'database' => 'core',
'char_set' => 'utf8mb4',
'dbcollat' => 'utf8mb4_unicode_ci',
```

## Field Quick Reference

### Customer
```php
// OLD → NEW
'nama'           → 'customer_name'
'telepon'        → 'phone'
'jenis_kelamin'  → 'gender' (MALE/FEMALE/OTHER)
'tanggal_lahir'  → 'birth_date'
'alamat'         → 'address'
'status'         → 'is_active' (1/0)
```

### Order
```php
// OLD → NEW
'final_amount'   → 'grand_total'
'order_status'   → 'status'
'no_transaksi'   → 'order_no'
```

### Voucher
```php
// OLD → NEW
'discount_type'  → 'voucher_type'
'discount_value' → 'voucher_value'
'expires_at'     → 'expired_at' (wallet)
'status'         → 'voucher_status'
```

### Stamp
```php
// OLD → NEW
'stamp_count'    → 'stamp_amount'
'required_stamps'→ 'stamp_target'
```

## Table Quick Reference

```php
// Customer
'pr_customer' → 'crm_customer' + 'crm_member_account'

// Points
'pr_customer_poin' → 'pos_point_ledger'

// Vouchers
'pr_voucher' → 'pos_voucher_wallet' + 'pos_voucher_campaign'

// Stamps
'pr_customer_stamp' → 'pos_stamp_ledger' + 'pos_stamp_campaign'

// Orders
'pr_transaksi' → 'pos_order'
```

## Common Queries

### Get Member with Account
```php
$this->db->select('c.*, m.member_no, m.tier_code, m.status as member_status');
$this->db->from('crm_customer c');
$this->db->join('crm_member_account m', 'm.customer_id = c.id', 'left');
$this->db->where('c.phone', $phone);
$this->db->where('c.is_active', 1);
```

### Get Point Balance
```php
$this->db->select('balance_after');
$this->db->from('pos_point_ledger');
$this->db->where('member_account_id', $member_account_id);
$this->db->order_by('created_at', 'DESC');
$this->db->limit(1);
```

### Get Active Vouchers
```php
$this->db->select('vw.*, vc.campaign_name, vc.voucher_type, vc.voucher_value');
$this->db->from('pos_voucher_wallet vw');
$this->db->join('pos_voucher_campaign vc', 'vc.id = vw.campaign_id', 'left');
$this->db->where('vw.member_account_id', $member_account_id);
$this->db->where('vw.voucher_status', 'AVAILABLE');
$this->db->where('(vw.expired_at >= NOW() OR vw.expired_at IS NULL)');
```

## Status Values

### Customer
- `is_active`: 1 (active), 0 (inactive)

### Member Account
- `status`: ACTIVE, INACTIVE, SUSPENDED, EXPIRED

### Voucher
- `voucher_status`: AVAILABLE, RESERVED, REDEEMED, VOID, EXPIRED

### Point Ledger
- `ledger_type`: EARN, REDEEM, EXPIRE, ADJUST

### Stamp Ledger
- `ledger_type`: EARN, REDEEM, EXPIRE, ADJUST

### Order
- `status`: DRAFT, PENDING, CONFIRMED, PAID_PARTIAL, PAID, IN_KITCHEN, READY, SERVED, VOID, REFUND_PARTIAL, REFUND_FULL

---

**Quick Tips:**
- Always get `member_account_id` from `crm_member_account` first
- Use `grand_total` instead of `final_amount` in pos_order
- Voucher wallet uses `expired_at`, not `expires_at`
- Check `voucher_status` for availability, not `remaining_usage`
- Point/Stamp balance uses ledger system with `balance_after`

