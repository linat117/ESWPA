<?php
// Finance Report Content
?>
<div class="row">
    <div class="col-md-12">
        <div class="card widget-flat text-bg-success">
            <div class="card-body">
                <h6 class="text-uppercase mt-0">Total Registrations</h6>
                <h2 class="my-2"><?php echo $report_data['total_registrations']; ?></h2>
            </div>
        </div>
    </div>
</div>

<div class="row mt-3">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h4 class="header-title">Payment Duration Breakdown</h4>
            </div>
            <div class="card-body">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Duration</th>
                            <th>Count</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($report_data['payment_breakdown'] as $item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['payment_duration']); ?></td>
                            <td><?php echo $item['count']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h4 class="header-title">Monthly Trend</h4>
            </div>
            <div class="card-body">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Month</th>
                            <th>Registrations</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($report_data['monthly_trend'] as $month): ?>
                        <tr>
                            <td><?php echo date('M Y', strtotime($month['month'] . '-01')); ?></td>
                            <td><?php echo $month['count']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

