# AJAX Implementation for Member Pages

## Overview
This document describes the AJAX implementation to prevent full page reloads when performing CRUD operations on member pages.

## Benefits
- ✅ No page reloads - header and footer stay in place
- ✅ Faster user experience
- ✅ Smooth transitions
- ✅ Better mobile experience
- ✅ Reduced server load

## Implementation Status

### ✅ Completed
1. **member-citations.php**
   - AJAX save citation
   - AJAX delete citation
   - Dynamic list updates

### 🔄 In Progress / To Do
2. **member-notes.php** - AJAX handler created, needs JavaScript integration
3. **member-bibliography.php** - Needs AJAX implementation
4. **member-research.php** - Needs AJAX for delete operations
5. **Other member pages** - As needed

## Files Created

### 1. `include/ajax_citation_handler.php`
AJAX endpoint for citation operations:
- `action=save` - Save a new citation
- `action=delete` - Delete a citation

**Usage:**
```javascript
// Save citation
fetch('include/ajax_citation_handler.php', {
    method: 'POST',
    body: formData
})

// Delete citation
fetch('include/ajax_citation_handler.php?action=delete&citation_id=123')
```

### 2. `include/ajax_notes_handler.php`
AJAX endpoint for notes operations:
- `action=create` - Create a new note
- `action=update` - Update an existing note
- `action=delete` - Delete a note

### 3. `assets/js/ajax-utils.js`
Reusable utility functions:
- `showAlert(message, type, duration)` - Show alert without page reload
- `ajaxDelete(url, itemId, element, onSuccess)` - Delete helper
- `ajaxPost(url, formData, onSuccess, onError)` - POST helper
- `setupAjaxForm(formId, handlerUrl, onSuccess)` - Form AJAX setup

## How to Implement on Other Pages

### Step 1: Create AJAX Handler
Create a file like `include/ajax_[page]_handler.php`:

```php
<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['member_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

include 'config.php';
// Include your handler functions

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'delete') {
    $id = intval($_POST['id'] ?? $_GET['id'] ?? 0);
    // Perform delete operation
    echo json_encode(['success' => true, 'message' => 'Deleted successfully!']);
} elseif ($action === 'create') {
    // Handle create
} elseif ($action === 'update') {
    // Handle update
}
?>
```

### Step 2: Update JavaScript
Replace form submissions and delete links with AJAX:

**Before:**
```html
<a href="?delete=123" onclick="return confirm('Delete?')">Delete</a>
```

**After:**
```html
<button onclick="deleteItem(123, this)">Delete</button>
```

```javascript
function deleteItem(id, element) {
    ajaxDelete('include/ajax_handler.php', id, element, function(data, el) {
        // Custom success handler
        el.closest('.item').remove();
    });
}
```

### Step 3: Update PHP Page
Modify the page to only handle non-AJAX requests (for fallback):

```php
// Only process if not AJAX request
if ($_SERVER["REQUEST_METHOD"] == "POST" && !isset($_POST['ajax'])) {
    // Handle form submission (fallback)
}
```

## Example: Notes Page Implementation

### Update Delete Links
```html
<!-- Before -->
<a href="?delete=<?php echo $note['id']; ?>" onclick="return confirm('Delete?')">Delete</a>

<!-- After -->
<button class="btn btn-sm btn-danger" onclick="deleteNote(<?php echo $note['id']; ?>, this)">
    <i class="fas fa-trash"></i> Delete
</button>
```

### Add JavaScript
```javascript
function deleteNote(noteId, element) {
    ajaxDelete('include/ajax_notes_handler.php', noteId, element, function(data, el) {
        // Remove note card from DOM
        el.closest('.note-card').remove();
        
        // Show empty state if no notes left
        if (document.querySelectorAll('.note-card').length === 0) {
            document.getElementById('notes-container').innerHTML = 
                '<p class="text-muted">No notes yet. Create your first note!</p>';
        }
    });
}
```

## Best Practices

1. **Always show loading states** - Disable buttons and show spinners during AJAX requests
2. **Handle errors gracefully** - Show user-friendly error messages
3. **Update UI dynamically** - Add/remove items from DOM without page reload
4. **Maintain fallback** - Keep non-AJAX handlers for users without JavaScript
5. **Use consistent patterns** - Follow the same structure across all pages

## Testing Checklist

- [ ] Save operation works without page reload
- [ ] Delete operation works without page reload
- [ ] Success messages appear correctly
- [ ] Error messages appear correctly
- [ ] Loading states show during operations
- [ ] UI updates correctly after operations
- [ ] Works on mobile devices
- [ ] Fallback works if JavaScript is disabled

## Notes

- All AJAX handlers return JSON responses
- Headers and footers remain static during operations
- Only the content area updates dynamically
- Alerts auto-dismiss after 3 seconds
- All operations maintain security checks (member_id validation)

