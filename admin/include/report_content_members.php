<?php
// Members Report Content
?>
<div class="row">
    <div class="col-md-3">
        <div class="card widget-flat text-bg-primary">
            <div class="card-body">
                <h6 class="text-uppercase mt-0">Total Members</h6>
                <h2 class="my-2"><?php echo $report_data['total_members']; ?></h2>
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
        <div class="card widget-flat text-bg-danger">
            <div class="card-body">
                <h6 class="text-uppercase mt-0">Active</h6>
                <h2 class="my-2"><?php echo $report_data['active_members']; ?></h2>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($report_data['qualification_breakdown'])): ?>
<div class="row mt-3">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h4 class="header-title">Qualification Breakdown</h4>
            </div>
            <div class="card-body">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Qualification</th>
                            <th>Count</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($report_data['qualification_breakdown'] as $qual): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($qual['qualification']); ?></td>
                            <td><?php echo $qual['count']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="row mt-3">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h4 class="header-title">Member Details</h4>
            </div>
            <div class="card-body">
                <table class="table table-striped datatable">
                    <thead>
                        <tr>
                            <th>Membership ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Qualification</th>
                            <th>Status</th>
                            <th>Approval Status</th>
                            <th>Registration Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($report_data['members_list'] as $member): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($member['membership_id'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($member['fullname']); ?></td>
                            <td><?php echo htmlspecialchars($member['email']); ?></td>
                            <td><?php echo htmlspecialchars($member['qualification']); ?></td>
                            <td>
                                <span class="badge bg-<?php echo $member['status'] == 'active' ? 'success' : ($member['status'] == 'expired' ? 'danger' : 'warning'); ?>">
                                    <?php echo ucfirst($member['status']); ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-<?php echo $member['approval_status'] == 'approved' ? 'success' : ($member['approval_status'] == 'rejected' ? 'danger' : 'warning'); ?>">
                                    <?php echo ucfirst($member['approval_status']); ?>
                                </span>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($member['created_at'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

