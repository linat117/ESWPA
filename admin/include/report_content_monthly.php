<?php
// Monthly Report Content
?>
<div class="row">
    <div class="col-md-3">
        <div class="card widget-flat text-bg-primary">
            <div class="card-body">
                <h6 class="text-uppercase mt-0">New Members</h6>
                <h2 class="my-2"><?php echo $report_data['new_members']; ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card widget-flat text-bg-success">
            <div class="card-body">
                <h6 class="text-uppercase mt-0">Approved</h6>
                <h2 class="my-2"><?php echo $report_data['approved_members']; ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card widget-flat text-bg-warning">
            <div class="card-body">
                <h6 class="text-uppercase mt-0">Pending</h6>
                <h2 class="my-2"><?php echo $report_data['pending_members']; ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card widget-flat text-bg-info">
            <div class="card-body">
                <h6 class="text-uppercase mt-0">Events</h6>
                <h2 class="my-2"><?php echo $report_data['events_count']; ?></h2>
            </div>
        </div>
    </div>
</div>

<div class="row mt-3">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h4 class="header-title">Daily Breakdown</h4>
            </div>
            <div class="card-body">
                <table class="table table-striped datatable">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>New Members</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($report_data['daily_breakdown'] as $day): ?>
                        <tr>
                            <td><?php echo date('M d, Y', strtotime($day['date'])); ?></td>
                            <td><?php echo $day['count']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

