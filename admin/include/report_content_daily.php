<?php
// Daily Report Content
?>
<div class="row">
    <div class="col-md-4">
        <div class="card widget-flat text-bg-primary">
            <div class="card-body">
                <h6 class="text-uppercase mt-0">New Members Today</h6>
                <h2 class="my-2"><?php echo $report_data['new_members']; ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card widget-flat text-bg-success">
            <div class="card-body">
                <h6 class="text-uppercase mt-0">Approved Today</h6>
                <h2 class="my-2"><?php echo $report_data['approved_today']; ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card widget-flat text-bg-info">
            <div class="card-body">
                <h6 class="text-uppercase mt-0">Emails Sent Today</h6>
                <h2 class="my-2"><?php echo $report_data['emails_sent']; ?></h2>
            </div>
        </div>
    </div>
</div>

<div class="row mt-3">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h4 class="header-title">New Members Today</h4>
            </div>
            <div class="card-body">
                <table class="table table-striped datatable">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Qualification</th>
                            <th>Registration Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($report_data['members_list'] as $member): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($member['fullname']); ?></td>
                            <td><?php echo htmlspecialchars($member['email']); ?></td>
                            <td><?php echo htmlspecialchars($member['phone']); ?></td>
                            <td><?php echo htmlspecialchars($member['qualification']); ?></td>
                            <td><?php echo date('H:i:s', strtotime($member['created_at'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

