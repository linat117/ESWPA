# Dashboard Enhancement Plan

## 1. Analysis

The current dashboard at `admin/index.php` provides a good overview of website activity, but it can be enhanced to provide more detailed insights into member subscriptions. The `registrations` table, with its `created_at` and `payment_duration` columns, provides the necessary data to build these new metrics.

## 2. Proposed Enhancements

I will implement the following changes to create a more comprehensive and informative dashboard.

### Step 1: Add New Data Queries

I will update `admin/index.php` to include new PHP logic for fetching subscription data. This will involve:
- Querying the `registrations` table.
- Looping through the members and calculating the subscription expiry date based on their `created_at` date and `payment_duration`.
- Counting the number of "Active" and "Expired" subscribers.
- Querying the `sent_emails` table for a total count.

**Example Logic:**
```php
<?php
// Inside admin/index.php

// Initialize counters
$active_subscribers = 0;
$expired_subscribers = 0;

// Fetch all members
$result_all_members = $conn->query("SELECT created_at, payment_duration FROM registrations");
while ($member = $result_all_members->fetch_assoc()) {
    $start_date = new DateTime($member['created_at']);
    $duration = $member['payment_duration']; // e.g., "1 Year", "2 Years"
    
    // Calculate expiry date
    // Note: This logic will be robust to handle different duration strings
    $expiry_date = clone $start_date;
    if (strpos($duration, 'Year') !== false) {
        $years = (int)$duration;
        $expiry_date->modify("+$years year");
    }
    
    // Compare with today's date
    $today = new DateTime();
    if ($expiry_date > $today) {
        $active_subscribers++;
    } else {
        $expired_subscribers++;
    }
}

// Fetch total sent emails
$result_emails = $conn->query("SELECT COUNT(*) AS total_sent FROM sent_emails");
$total_sent_emails = $result_emails->fetch_assoc()['total_sent'];

?>
```

### Step 2: Redesign the Dashboard Layout

I will add new cards to display the new data points and rearrange the layout into a 2x3 grid for better visual organization.

**New Cards to be Added:**

- **Active Subscribers:** A card displaying the `$active_subscribers` count.
- **Expired Subscribers:** A card showing the `$expired_subscribers` count.
- **Total Sent Emails:** A card showing the `$total_sent_emails` count.

**Example Card HTML:**
```html
<!-- Active Subscribers -->
<div class="col-lg-4 col-sm-6">
    <div class="card widget-flat text-bg-success">
        <div class="card-body">
            <div class="float-end">
                <i class="ri-user-follow-line widget-icon"></i>
            </div>
            <h6 class="text-uppercase mt-0" title="Active Subscribers">Active Subscribers</h6>
            <h2 class="my-2"><?php echo $active_subscribers; ?></h2>
        </div>
    </div>
</div>

<!-- Expired Subscribers -->
<div class="col-lg-4 col-sm-6">
    <div class="card widget-flat text-bg-danger">
        <div class="card-body">
            <div class="float-end">
                <i class="ri-user-unfollow-line widget-icon"></i>
            </div>
            <h6 class="text-uppercase mt-0" title="Expired Subscribers">Expired Subscribers</h6>
            <h2 class="my-2"><?php echo $expired_subscribers; ?></h2>
        </div>
    </div>
</div>
```
The existing cards will be adjusted to fit this new grid structure (`col-lg-4` instead of `col-xxl-3`).

This plan will transform the dashboard into a more powerful tool for monitoring your member base and email outreach. 