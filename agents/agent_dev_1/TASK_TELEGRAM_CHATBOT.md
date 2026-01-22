# Task: Telegram Chatbot Integration

**Agent**: agent_dev_1  
**Priority**: Medium-High  
**Status**: 📋 Ready for Development  
**Estimated Time**: 6-8 hours

---

## Objective
Implement a Telegram chatbot integration with a floating chat button on the frontend that allows visitors to send messages directly to a Telegram account or bot.

---

## Current Status

### ✅ Existing Infrastructure:
- Telegram settings tab in admin panel (`admin/settings.php`)
- Settings fields: `telegram_bot_token` and `telegram_chat_id`
- Settings table exists in database

### ❌ Missing Components:
- Telegram Bot API integration
- Chat button on frontend
- Chat widget/interface
- Message handling
- Webhook setup (optional)

---

## Requirements

### 1. Telegram Bot Setup

**Prerequisites**:
1. Create bot via @BotFather on Telegram
2. Get bot token
3. Get chat ID (where messages will be sent)
4. Configure in admin settings

**Bot Features** (Optional - for two-way communication):
- `/start` - Welcome message
- `/help` - Help commands
- Message forwarding from website to Telegram
- Message forwarding from Telegram to website (optional)

---

### 2. Database Setup (Optional - for message logging)

**Create Migration File**: `Sql/migration_create_telegram_messages.sql`

```sql
CREATE TABLE `telegram_messages` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_name` VARCHAR(255) NULL,
  `user_email` VARCHAR(191) NULL,
  `user_phone` VARCHAR(50) NULL,
  `message` TEXT NOT NULL,
  `telegram_chat_id` VARCHAR(100) NULL,
  `telegram_message_id` VARCHAR(100) NULL,
  `status` ENUM('sent', 'failed', 'pending') NOT NULL DEFAULT 'pending',
  `ip_address` VARCHAR(45) NULL,
  `user_agent` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_status` (`status`),
  INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

---

### 3. Frontend Components

#### A. Floating Chat Button

**Location**: Bottom-right corner of all pages

**Features**:
- Fixed position (position: fixed)
- Telegram icon/logo
- Animated pulse effect
- Hover effects
- Z-index: 9999 (above all content)
- Mobile responsive
- Click to open chat widget

**Styling**:
- Circular button
- Purple/blue gradient background
- White Telegram icon
- Smooth animations
- Shadow effect

#### B. Chat Widget/Modal

**Features**:
- Opens when button clicked
- Chat interface with message history
- Input field for message
- Optional fields: Name, Email, Phone
- Send button
- Close button
- Loading states
- Success/error messages
- Auto-close after sending (optional)

**Design**:
- Modern chat interface
- Matches site theme
- Mobile-responsive
- Smooth slide-up animation
- Telegram-like design

---

### 4. Backend Components

#### A. Telegram Bot Handler (`include/telegram_bot.php`)

**Functions**:
- `sendTelegramMessage($message, $chat_id, $bot_token)`
  - Send message to Telegram
  - Handle errors
  - Return success/error

- `formatWebsiteMessage($name, $email, $phone, $message)`
  - Format message for Telegram
  - Include user details
  - Include timestamp

- `getTelegramSettings()`
  - Get bot token and chat ID from settings table
  - Return array with settings

**API Integration**:
- Use Telegram Bot API: `https://api.telegram.org/bot{token}/sendMessage`
- POST request with JSON payload
- Error handling and logging

#### B. Message Handler (`include/telegram_handler.php`)

**Functions**:
- `processChatMessage($name, $email, $phone, $message, $ip, $user_agent)`
  - Validate input
  - Get Telegram settings
  - Format message
  - Send to Telegram
  - Log to database (optional)
  - Return response

#### C. AJAX Endpoint (`include/telegram_ajax.php`)

**Purpose**: Handle AJAX chat message requests

**Response Format**:
```json
{
  "success": true/false,
  "message": "Success/Error message",
  "data": {}
}
```

#### D. Webhook Handler (Optional - for two-way communication)
- `api/telegram_webhook.php`
- Receive messages from Telegram
- Process bot commands
- Send responses

---

### 5. Admin Panel Components

#### A. Telegram Settings Enhancement (`admin/settings.php`)
- Already exists, enhance if needed
- Add bot status checker
- Test message button
- Webhook URL display

#### B. Message Log Viewer (`admin/telegram_messages.php`)
- View all messages sent via chat widget
- Filter by date, status
- Export messages
- Reply functionality (optional)

---

## Implementation Options

### Option A: Simple Message Forwarding (Recommended)
- User fills form on website
- Message sent directly to Telegram chat
- No bot responses needed
- Simpler implementation

### Option B: Full Bot Integration
- Two-way communication
- Bot can respond to messages
- Webhook required
- More complex but more features

**Recommendation**: Start with Option A, upgrade to Option B later if needed.

---

## Implementation Steps

### Step 1: Telegram Bot Setup
1. Create bot via @BotFather
2. Get bot token
3. Get chat ID (use @userinfobot or @getidsbot)
4. Configure in admin settings

### Step 2: Database Setup (Optional)
1. Create migration SQL file
2. Run migration
3. Verify table creation

### Step 3: Backend Handler
1. Create `include/telegram_bot.php`
2. Implement message sending function
3. Create `include/telegram_handler.php`
4. Create `include/telegram_ajax.php`
5. Test with sample message

### Step 4: Frontend Chat Button
1. Create chat button component
2. Add to `header-v1.2.php` or `footer.php`
3. Style with CSS
4. Add JavaScript for click handler

### Step 5: Chat Widget
1. Create chat widget HTML/CSS/JS
2. Implement form submission
3. Add AJAX integration
4. Test message sending

### Step 6: Integration
1. Include chat button on all public pages
2. Test end-to-end flow
3. Verify messages arrive in Telegram

### Step 7: Admin Panel
1. Enhance settings page (if needed)
2. Create message log viewer (optional)
3. Add menu item to sidebar
4. Test admin features

### Step 8: Testing
1. Test message sending
2. Test error handling
3. Test mobile responsiveness
4. Test with different message lengths
5. Verify messages in Telegram

---

## Files to Create

1. `Sql/migration_create_telegram_messages.sql` (optional)
2. `include/telegram_bot.php`
3. `include/telegram_handler.php`
4. `include/telegram_ajax.php`
5. `include/chat_widget.php`
6. `admin/telegram_messages.php` (optional)
7. `api/telegram_webhook.php` (optional, for Option B)

## Files to Modify

1. `header-v1.2.php` or `footer.php` - Add chat button
2. `admin/settings.php` - Enhance Telegram settings (if needed)
3. `admin/sidebar.php` - Add Telegram messages menu item (optional)

---

## Telegram Bot API Reference

**Send Message Endpoint**:
```
POST https://api.telegram.org/bot{token}/sendMessage
```

**Request Body**:
```json
{
  "chat_id": "123456789",
  "text": "Message text",
  "parse_mode": "HTML"
}
```

**Response**:
```json
{
  "ok": true,
  "result": {
    "message_id": 123,
    "date": 1234567890,
    "chat": {...},
    "text": "Message text"
  }
}
```

---

## Design Guidelines

### Chat Button:
- Circular, 60px diameter
- Purple/blue gradient background
- White Telegram icon (SVG)
- Pulse animation
- Shadow: 0 4px 15px rgba(102, 126, 234, 0.4)

### Chat Widget:
- Width: 350px (desktop), 90% (mobile)
- Height: 500px (desktop), 80vh (mobile)
- Slide-up animation
- Modern chat interface design
- Telegram-inspired UI

---

## Testing Checklist

- [ ] Chat button displays on all pages
- [ ] Chat widget opens on button click
- [ ] Form validation works
- [ ] Message sends successfully
- [ ] Message appears in Telegram
- [ ] Error handling works
- [ ] Mobile responsive
- [ ] No JavaScript errors
- [ ] Admin can view messages (if logging enabled)
- [ ] Settings page works correctly

---

## Security Considerations

- Validate all input
- Sanitize messages before sending
- Rate limiting (prevent spam)
- CSRF protection
- Don't expose bot token in frontend
- Log messages for audit (optional)

---

## Notes

- Start with simple message forwarding (Option A)
- Can upgrade to full bot later (Option B)
- Use existing settings infrastructure
- Follow existing code patterns
- Test thoroughly with actual Telegram account
- Document bot setup process

---

**Last Updated**: December 16, 2025

