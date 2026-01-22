<?php
// Payment Report Content
?>
<div class="row">
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
                            <th>Percentage</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $total = $report_data['total_registrations'];
                        foreach ($report_data['payment_duration'] as $item): 
                            $percentage = $total > 0 ? round(($item['count'] / $total) * 100, 2) : 0;
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['payment_duration']); ?></td>
                            <td><?php echo $item['count']; ?></td>
                            <td><?php echo $percentage; ?>%</td>
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
                <h4 class="header-title">Payment Option Breakdown</h4>
            </div>
            <div class="card-body">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Payment Option</th>
                            <th>Count</th>
                            <th>Percentage</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        foreach ($report_data['payment_option'] as $item): 
                            $percentage = $total > 0 ? round(($item['count'] / $total) * 100, 2) : 0;
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['payment_option']); ?></td>
                            <td><?php echo $item['count']; ?></td>
                            <td><?php echo $percentage; ?>%</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="row mt-3">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h4 class="header-title">Member Payment Details</h4>
            </div>
            <div class="card-body">
                <table class="table table-striped datatable">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Payment Duration</th>
                            <th>Payment Option</th>
                            <th>Registration Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($report_data['members_list'] as $member): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($member['fullname']); ?></td>
                            <td><?php echo htmlspecialchars($member['email']); ?></td>
                            <td><?php echo htmlspecialchars($member['payment_duration']); ?></td>
                            <td><?php echo htmlspecialchars($member['payment_option']); ?></td>
                            <td><?php echo date('M d, Y', strtotime($member['created_at'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

