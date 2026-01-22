# Demo Member Account Information

## Demo Account Credentials

**Email:** `demo@member.com`  
**Password:** `demo123`  
**Membership ID:** `ESWPA-2025-00001`

## Account Status

- ✅ **Approval Status:** Approved
- ✅ **Account Status:** Active
- ✅ **Expiry Date:** 1 year from creation date
- ✅ **Member Access:** Created and active

## How to Test

1. **Login as Demo Member:**
   - Go to: `http://localhost/ethiosocialworks/member-login.php`
   - Enter email: `demo@member.com`
   - Enter password: `demo123`
   - Click "Login"

2. **Access Member Dashboard:**
   - After login, you'll be redirected to the member dashboard
   - View member information
   - Check membership status
   - Access quick actions (ID card generation will be available after implementation)

3. **Test Features:**
   - Member authentication ✅
   - Session management ✅
   - Dashboard access ✅
   - Expiry checking ✅
   - Logout functionality ✅

## Database Details

The demo account is stored in:
- **Table:** `registrations` (ID: 118)
- **Table:** `member_access` (with hashed password)

## Notes

- The password is hashed using PHP's `password_hash()` function
- The account is fully approved and ready to use
- Membership expires 1 year from account creation
- All required fields are populated for testing

---

**Created:** 2025-01-XX  
**Last Updated:** 2025-01-XX

