<?php
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include('include/database.php');
include('include/check_login.php');
include('include/migration_manager.php');

$db = new Database();
$report = migrationRunnerRunAll($db);
$summary = $report['summary'];
$results = $report['results'];

function migrationJobBadgeClass($status)
{
    switch ($status) {
        case 'applied':
            return 'badge badge-success';
        case 'already_applied':
            return 'badge badge-info';
        case 'logged':
            return 'badge badge-default';
        case 'failed':
            return 'badge badge-danger';
    }

    return 'badge badge-secondary';
}

function migrationJobAlertClass(array $summary)
{
    if ($summary['failed'] > 0) {
        return 'alert-danger';
    }

    if ($summary['executed'] > 0) {
        return 'alert-success';
    }

    return 'alert-info';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Migration Job | Admin Panel</title>
    <?php include('common/head.php'); ?>
    <style>
        .migration-box {
            background: #fff;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 24px;
            box-shadow: 0 4px 18px rgba(15, 23, 42, 0.06);
            border: 1px solid rgba(148, 163, 184, 0.18);
        }
        .migration-card {
            border-radius: 12px;
            padding: 18px 20px;
            color: #fff;
            margin-bottom: 18px;
            min-height: 110px;
        }
        .migration-card h4 {
            margin: 0 0 8px;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            opacity: 0.88;
        }
        .migration-card strong {
            font-size: 34px;
            line-height: 1;
            display: block;
        }
        .migration-card small {
            display: block;
            margin-top: 8px;
            font-size: 12px;
            opacity: 0.9;
        }
        .migration-card-total { background: linear-gradient(135deg, #0f4c5c 0%, #2c7a7b 100%); }
        .migration-card-run { background: linear-gradient(135deg, #1d4ed8 0%, #3b82f6 100%); }
        .migration-card-skip { background: linear-gradient(135deg, #4b5563 0%, #9ca3af 100%); }
        .migration-card-fail { background: linear-gradient(135deg, #b91c1c 0%, #ef4444 100%); }
        .migration-table th {
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 0.05em;
            color: #64748b;
            background: #f8fafc;
            border-top: none;
        }
        .migration-table td {
            vertical-align: top;
            color: #334155;
        }
        .migration-path {
            color: #64748b;
            font-size: 12px;
            margin-top: 4px;
        }
        .migration-message {
            font-size: 13px;
            line-height: 1.5;
            white-space: normal;
        }
    </style>
</head>
<body class="page-sidebar-closed-hide-logo page-content-white">
    <?php include('common/manubar.php'); ?>
    <div class="clearfix"></div>
    <div class="page-container">
        <div class="page-sidebar-wrapper">
            <?php include('common/sidebar.php'); ?>
        </div>
        <div class="page-content-wrapper">
            <div class="page-content">
                <div class="container-fluid">
                    <br>
                    <div class="row">
                        <div class="col-sm-12" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
                            <div>
                                <h4 class="page-title m-0 font-weight-bold" style="font-size:1.5rem; color:#1e293b;">
                                    <i class="fa fa-database text-primary"></i> Migration Job
                                </h4>
                                <p style="margin:8px 0 0; color:#64748b;">Open this page on live server to detect missing migrations and run them automatically.</p>
                            </div>
                            <a href="migration-job.php" class="btn btn-primary" style="border-radius:20px; padding:8px 18px; font-weight:600;">
                                <i class="fa fa-refresh"></i> Recheck Migrations
                            </a>
                        </div>
                    </div>

                    <div class="row" style="margin-top:20px;">
                        <div class="col-lg-3 col-md-6">
                            <div class="migration-card migration-card-total">
                                <h4>Total Found</h4>
                                <strong><?php echo (int) $summary['total']; ?></strong>
                                <small>SQL and PHP migrations discovered</small>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <div class="migration-card migration-card-run">
                                <h4>Ran Now</h4>
                                <strong><?php echo (int) ($summary['executed'] + $summary['already_applied']); ?></strong>
                                <small><?php echo (int) $summary['executed']; ?> executed, <?php echo (int) $summary['already_applied']; ?> already matched</small>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <div class="migration-card migration-card-skip">
                                <h4>Previously Logged</h4>
                                <strong><?php echo (int) $summary['logged']; ?></strong>
                                <small>Skipped because they were already recorded</small>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <div class="migration-card migration-card-fail">
                                <h4>Failed</h4>
                                <strong><?php echo (int) $summary['failed']; ?></strong>
                                <small><?php echo (int) $summary['remaining']; ?> remaining after this run</small>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-lg-12">
                            <div class="alert <?php echo migrationJobAlertClass($summary); ?>" style="border-radius:12px; margin-bottom:24px;">
                                <?php if ($summary['failed'] > 0): ?>
                                    Migration job stopped on the first failing migration. Fix that item, then refresh this page to continue the remaining migrations.
                                <?php elseif ($summary['executed'] > 0 || $summary['already_applied'] > 0): ?>
                                    Migration check completed successfully at <?php echo htmlspecialchars($report['ran_at']); ?>. Missing migrations were applied and already-matched ones were recorded automatically.
                                <?php else: ?>
                                    No missing migrations were found at <?php echo htmlspecialchars($report['ran_at']); ?>. Your migration log is already up to date.
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-lg-12">
                            <div class="migration-box">
                                <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; margin-bottom:16px;">
                                    <h3 style="margin:0; color:#1e293b; font-size:1.15rem; font-weight:700;">Migration Results</h3>
                                    <span style="color:#64748b; font-size:13px;">This page auto-runs on load.</span>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-hover migration-table mb-0">
                                        <thead>
                                            <tr>
                                                <th style="width:60px;">#</th>
                                                <th style="width:320px;">Migration</th>
                                                <th style="width:110px;">Type</th>
                                                <th style="width:150px;">Status</th>
                                                <th>Details</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($results)): ?>
                                                <tr>
                                                    <td colspan="5" class="text-center text-muted" style="padding:32px 16px;">
                                                        No migration files were discovered.
                                                    </td>
                                                </tr>
                                            <?php else: ?>
                                                <?php foreach ($results as $index => $result): ?>
                                                    <tr>
                                                        <td><?php echo $index + 1; ?></td>
                                                        <td>
                                                            <strong><?php echo htmlspecialchars($result['name']); ?></strong>
                                                            <div class="migration-path"><?php echo htmlspecialchars($result['relative_path']); ?></div>
                                                        </td>
                                                        <td><?php echo strtoupper(htmlspecialchars($result['type'])); ?></td>
                                                        <td>
                                                            <span class="<?php echo migrationJobBadgeClass($result['status']); ?>">
                                                                <?php echo htmlspecialchars(str_replace('_', ' ', ucfirst($result['status']))); ?>
                                                            </span>
                                                        </td>
                                                        <td class="migration-message"><?php echo htmlspecialchars($result['message']); ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        var resizefunc = [];
    </script>
    <?php include('common/footer.php'); ?>
</body>
</html>