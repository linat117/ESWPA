<?php
// Audit Log Report Content
?>
<div class="row">
    <div class="col-md-12">
        <div class="card widget-flat text-bg-info">
            <div class="card-body">
                <h6 class="text-uppercase mt-0">Total Actions Logged</h6>
                <h2 class="my-2"><?php echo $report_data['total_actions']; ?></h2>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($report_data['actions_by_type'])): ?>
<div class="row mt-3">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h4 class="header-title">Actions by Type</h4>
            </div>
            <div class="card-body">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Action</th>
                            <th>Count</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($report_data['actions_by_type'] as $action): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($action['action']); ?></td>
                            <td><?php echo $action['count']; ?></td>
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
                <h4 class="header-title">Top Users by Activity</h4>
            </div>
            <div class="card-body">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>User ID</th>
                            <th>User Type</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($report_data['actions_by_user'] as $user): ?>
                        <tr>
                            <td><?php echo $user['user_id'] ?? 'N/A'; ?></td>
                            <td><span class="badge bg-<?php echo $user['user_type'] == 'admin' ? 'primary' : 'success'; ?>"><?php echo ucfirst($user['user_type']); ?></span></td>
                            <td><?php echo $user['count']; ?></td>
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
                <h4 class="header-title">Recent Audit Logs</h4>
            </div>
            <div class="card-body">
                <table class="table table-striped datatable">
                    <thead>
                        <tr>
                            <th>Timestamp</th>
                            <th>User</th>
                            <th>Action</th>
                            <th>Table</th>
                            <th>Record ID</th>
                            <th>IP Address</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($report_data['audit_logs'] as $log): ?>
                        <tr>
                            <td><?php echo date('M d, Y H:i:s', strtotime($log['created_at'])); ?></td>
                            <td>
                                <span class="badge bg-<?php echo $log['user_type'] == 'admin' ? 'primary' : 'success'; ?>">
                                    <?php echo ucfirst($log['user_type']); ?> #<?php echo $log['user_id'] ?? 'N/A'; ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($log['action']); ?></td>
                            <td><?php echo htmlspecialchars($log['table_name'] ?? 'N/A'); ?></td>
                            <td><?php echo $log['record_id'] ?? 'N/A'; ?></td>
                            <td><?php echo htmlspecialchars($log['ip_address'] ?? 'N/A'); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

