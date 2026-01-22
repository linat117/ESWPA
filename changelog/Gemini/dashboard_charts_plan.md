# Dashboard Charts: Enhancement Plan

## 1. Analysis

The current dashboard effectively displays key statistics as cards. To further enhance its utility, I will add a series of charts to visualize data trends over time. This will provide deeper insights into member engagement and organizational activity.

I will use the existing `ApexCharts` library, which is already included in the project, to create these visualizations.

## 2. Proposed Charts

### Chart 1: Member Subscription Status (Pie Chart)

This chart will offer a simple but powerful visual breakdown of the current member subscription status.

-   **Type:** Pie Chart
-   **Data:**
    -   Active Subscribers (`$active_subscribers`)
    -   Expired Subscribers (`$expired_subscribers`)
-   **Purpose:** To quickly assess the health of the member subscription base.

**Example Implementation:**
A new `div` will be added to `admin/index.php`, and JavaScript will be used to render the chart.
```html
<div id="subscription-status-chart"></div>
```
```javascript
// ApexCharts options for the pie chart
var options = {
    series: [<?php echo $active_subscribers; ?>, <?php echo $expired_subscribers; ?>],
    labels: ['Active', 'Expired'],
    chart: { type: 'pie' },
    ...
};
```

### Chart 2: New Member Registrations (Monthly Line Chart)

This chart will track the number of new member registrations over the past 12 months, illustrating growth trends.

-   **Type:** Line Chart
-   **Data:** Monthly count of new entries in the `registrations` table.
-   **Purpose:** To monitor member base growth and the effectiveness of recruitment efforts over time.

**Example Implementation:**
A new SQL query will be added to fetch the data, which will then be passed to the chart.
```php
// SQL to get monthly registrations for the last 12 months
$query = "SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(id) as count 
          FROM registrations 
          WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH) 
          GROUP BY month ORDER BY month ASC";
// ... PHP logic to process data into JSON arrays for the chart
```
```html
<div id="registrations-over-time-chart"></div>
```

### Chart 3: Events Created (Monthly Bar Chart)

This chart will show the volume of events created each month, helping to visualize the organization's activity levels.

-   **Type:** Bar Chart
-   **Data:** Monthly count of new entries in the `events` table.
-   **Purpose:** To track event creation frequency and identify patterns in organizational activity.

**Example Implementation:**
A new SQL query will fetch the monthly event counts.
```php
// SQL to get monthly event counts for the last 12 months
$query = "SELECT DATE_FORMAT(event_date, '%Y-%m') as month, COUNT(id) as count
          FROM events 
          WHERE event_date >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
          GROUP BY month ORDER BY month ASC";
// ... PHP logic to process data into JSON arrays for the chart
```
```html
<div id="events-per-month-chart"></div>
```

## 3. Layout

The new charts will be placed in a new row below the existing stat cards. They will be arranged to be fully responsive and provide a clean, organized look.

This plan will provide a much richer, data-driven view of your organization's operations directly on the dashboard. 