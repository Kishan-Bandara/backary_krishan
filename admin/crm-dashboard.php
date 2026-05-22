<?php
ob_start();
error_reporting(E_ALL ^ E_NOTICE);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include('include/database.php');
include('include/check_login.php');
include('include/crm_master.php');
include('get_url.php');
date_default_timezone_set("Asia/Colombo");

$db = new Database();
crmEnsureSchema($db);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['seed_crm_sample_data'])) {
    $seedSummary = crmSeedSampleData($db);
    $createdTotal = array_sum($seedSummary);

    if ($createdTotal > 0) {
        $messageLabels = [
            'sales_cycles' => 'sales cycle',
            'stages' => 'pipeline stages',
            'activities' => 'activities',
            'activity_lines' => 'activity tasks',
            'companies' => 'companies',
            'persons' => 'contacts',
            'opportunities' => 'opportunities',
        ];
        $messageParts = [];

        foreach ($messageLabels as $key => $label) {
            $count = (int) ($seedSummary[$key] ?? 0);
            if ($count > 0) {
                $messageParts[] = $count . ' ' . $label;
            }
        }

        $_SESSION['crm_dashboard_message'] = 'CRM sample data added to the system: ' . implode(', ', $messageParts) . '.';
        $_SESSION['crm_dashboard_message_class'] = 'alert-success';
    } else {
        $_SESSION['crm_dashboard_message'] = 'CRM sample data already exists. No duplicate records were added.';
        $_SESSION['crm_dashboard_message_class'] = 'alert-info';
    }

    header('Location: crm-dashboard.php');
    exit;
}

$dashboardMessage = isset($_SESSION['crm_dashboard_message']) ? (string) $_SESSION['crm_dashboard_message'] : '';
$dashboardMessageClass = isset($_SESSION['crm_dashboard_message_class']) ? (string) $_SESSION['crm_dashboard_message_class'] : 'alert-info';
unset($_SESSION['crm_dashboard_message'], $_SESSION['crm_dashboard_message_class']);

$sampleOpportunityCount = crmCountSampleOpportunities($db);

$currencyRow = $db->getRow('SELECT * FROM currency WHERE activated = ? LIMIT 1', ["Y"]);
$currSymbol = $currencyRow ? $currencyRow['currency'] : '$';

// --- Dashboard Metrics ---
$totalContactsRow = $db->getRow('SELECT COUNT(*) AS total FROM crm_person_master');
$totalCompaniesRow = $db->getRow('SELECT COUNT(*) AS total FROM crm_company_master');
$totalOpportunitiesRow = $db->getRow('SELECT COUNT(*) AS total FROM crm_opportunity');

$totalContacts = isset($totalContactsRow['total']) ? (int)$totalContactsRow['total'] : 0;
$totalCompanies = isset($totalCompaniesRow['total']) ? (int)$totalCompaniesRow['total'] : 0;
$totalOpportunities = isset($totalOpportunitiesRow['total']) ? (int)$totalOpportunitiesRow['total'] : 0;

// Calculate Total Estimated Sales Value (Active vs Lost vs Won)
$wonOpportunities = 0;
$lostOpportunities = 0;
$inProgressOpportunities = 0;
$totalEstimatedRevenue = 0;
$totalWonRevenue = 0;

$opportunities = crmFetchOpportunities($db);
$stageCounts = [];
$funnelStages = [];
$monthlyOpportunities = array_fill(1, 12, 0); // 1 to 12

foreach ($opportunities as $index => $opp) {
    $stage = trim((string) ($opp['current_stage_description'] ?? ''));
    if ($stage === '') {
        $stage = 'No Stage';
    }

    $stageNo = (int) ($opp['current_stage_no'] ?? 0);
    $chance = (float) ($opp['chance_of_success_percent'] ?? 0);
    $val = (float) ($opp['estimated_sales_value'] ?? 0);
    $createdDate = trim((string) ($opp['creation_date'] ?? $opp['created_at'] ?? ''));
    $description = trim((string) ($opp['description'] ?? ''));
    if ($description === '') {
        $description = 'Unnamed Opportunity';
    }

    $opportunities[$index]['dashboard_stage_name'] = $stage;
    $opportunities[$index]['dashboard_success_pct'] = $chance;
    $opportunities[$index]['dashboard_estimated_value'] = $val;
    $opportunities[$index]['dashboard_created_date'] = $createdDate;
    $opportunities[$index]['dashboard_title'] = $description;

    if (!isset($stageCounts[$stage])) {
        $stageCounts[$stage] = 0;
    }
    $stageCounts[$stage]++;

    if (!isset($funnelStages[$stage])) {
        $funnelStages[$stage] = [
            'label' => $stage,
            'count' => 0,
            'order' => ($stageNo > 0 ? $stageNo : 9999),
        ];
    }
    $funnelStages[$stage]['count']++;
    if ($stageNo > 0 && $stageNo < $funnelStages[$stage]['order']) {
        $funnelStages[$stage]['order'] = $stageNo;
    }

    $totalEstimatedRevenue += $val;

    if ($chance >= 100) {
        $wonOpportunities++;
        $totalWonRevenue += $val;
    } elseif ($chance <= 0) {
        $lostOpportunities++;
    } else {
        $inProgressOpportunities++;
    }

    if ($createdDate !== '' && strtotime($createdDate) !== false) {
        $month = (int) date('n', strtotime($createdDate));
        if ($month >= 1 && $month <= 12) {
            $monthlyOpportunities[$month]++;
        }
    }
}

// Chart Data Setup
arsort($stageCounts);
$stageLabels = json_encode(array_keys($stageCounts));
$stageData = json_encode(array_values($stageCounts));

$funnelStageRows = array_values($funnelStages);
usort($funnelStageRows, function ($left, $right) {
    if ((int) $left['order'] === (int) $right['order']) {
        return strcmp((string) $left['label'], (string) $right['label']);
    }

    return ((int) $left['order'] < (int) $right['order']) ? -1 : 1;
});

$funnelStageLabelsData = [];
$funnelStageCountsData = [];
foreach ($funnelStageRows as $funnelStageRow) {
    $funnelStageLabelsData[] = $funnelStageRow['label'];
    $funnelStageCountsData[] = (int) $funnelStageRow['count'];
}

$funnelStageLabels = json_encode($funnelStageLabelsData);
$funnelStageData = json_encode($funnelStageCountsData);
$monthlyData = json_encode(array_values($monthlyOpportunities));
$currentMonthStr = date('F');

// Fetch Top 5 Latest Opportunities
$topOpportunities = array_slice($opportunities, 0, 5);

// Team Leaderboard: aggregate estimated_sales_value by sales_person_name
$teamLeaderboard = [];
foreach ($opportunities as $opp) {
    $spName = trim((string) ($opp['sales_person_name'] ?? ''));
    if ($spName === '') $spName = 'Unassigned';
    if (!isset($teamLeaderboard[$spName])) {
        $teamLeaderboard[$spName] = 0;
    }
    $teamLeaderboard[$spName] += (float) ($opp['estimated_sales_value'] ?? 0);
}
arsort($teamLeaderboard);
$teamLeaderboard = array_slice($teamLeaderboard, 0, 10, true);

// My Top Opportunities: sorted by estimated_sales_value desc
$myTopOpportunities = $opportunities;
usort($myTopOpportunities, function ($a, $b) {
    return (float) ($b['estimated_sales_value'] ?? 0) <=> (float) ($a['estimated_sales_value'] ?? 0);
});
$myTopOpportunities = array_slice($myTopOpportunities, 0, 10);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>CRM Dashboard | Admin Panel</title>

    <?php include('common/head.php'); ?>
    <link href="assets/global/plugins/datatables/datatables.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/global/plugins/datatables/plugins/bootstrap/datatables.bootstrap.css" rel="stylesheet" type="text/css" />


    <!-- Custom styling for CRM dashboard -->
    <style>
        .crm-widget {
            border-radius: 12px;
            padding: 2.2rem;
            color: #fff;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.06);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            margin-bottom: 25px;
            min-height: 140px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .crm-widget:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .bg-gradient-blue {
            background: linear-gradient(135deg, #4A00E0 0%, #8E2DE2 100%);
        }

        .bg-gradient-green {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
        }

        .bg-gradient-orange {
            background: linear-gradient(135deg, #f12711 0%, #f5af19 100%);
        }

        .bg-gradient-purple {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .crm-w-title {
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 15px;
            opacity: 0.9;
            font-weight: 600;
        }

        .crm-w-val {
            font-size: 2.6rem;
            font-weight: 700;
            line-height: 1.1;
            margin: 0;
            display: flex;
            align-items: center;
        }

        .crm-w-icon {
            position: absolute;
            right: 0px;
            bottom: -15px;
            font-size: 110px;
            opacity: 0.15;
            line-height: 1;
        }

        .white-box {
            background: #fff;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.04);
            margin-bottom: 25px;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .white-box h3 {
            margin-top: 0;
            margin-bottom: 20px;
            font-size: 1.15rem;
            font-weight: 600;
            color: #2d3748;
            border-bottom: 2px solid #edf2f7;
            padding-bottom: 12px;
            display: flex;
            align-items: center;
        }

        .white-box h3 i {
            margin-right: 10px;
            color: #4299e1;
        }

        /* Table enhancements */
        .table-crm th {
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
            color: #718096;
            background: #f7fafc;
            border-top: none;
            padding: 12px 15px;
        }

        .table-crm td {
            vertical-align: middle;
            padding: 15px;
            color: #4a5568;
            border-top: 1px solid #edf2f7;
        }

        .badge-soft {
            padding: 6px 12px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 0.75rem;
        }

        .badge-soft-success {
            background: #c6f6d5;
            color: #22543d;
        }

        .badge-soft-warning {
            background: #feebc8;
            color: #7b341e;
        }

        .badge-soft-danger {
            background: #fed7d7;
            color: #742a2a;
        }

        .chart-container-c {
            position: relative;
            height: 300px;
            width: 100%;
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

                    <div class="row mb-3">
                        <div class="col-sm-12 text-center text-sm-left" style="display:flex; justify-content:space-between; align-items:center;">
                            <div>
                                <h4 class="page-title m-0 font-weight-bold" style="font-size: 1.5rem; color: #2d3748;">
                                    <i class="fa fa-tachometer text-primary"></i> CRM Dashboard
                                </h4>
                                <ol class="breadcrumb mt-2 p-0 bg-transparent">
                                    <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                                    <li class="breadcrumb-item"><a href="#">CRM</a></li>
                                    <li class="breadcrumb-item active">Dashboard</li>
                                </ol>
                            </div>
                            <div>
                                <a href="crm-opportunity.php" class="btn btn-primary shadow-sm" style="border-radius: 20px; font-weight: 600; padding: 8px 20px;">
                                    <i class="fa fa-plus"></i> New Opportunity
                                </a>
                                <a href="crm.php?type=person" class="btn btn-success shadow-sm" style="border-radius: 20px; font-weight: 600; padding: 8px 20px; margin-left: 10px;">
                                    <i class="fa fa-user-plus"></i> New Contact
                                </a>
                                <form method="post" style="display:inline-block; margin-left: 10px;">
                                    <button type="submit" name="seed_crm_sample_data" value="1" class="btn btn-warning shadow-sm" style="border-radius: 20px; font-weight: 600; padding: 8px 20px;">
                                        <i class="fa fa-database"></i> <?php echo $sampleOpportunityCount > 0 ? 'Reload' : 'Load'; ?> CRM Sample Data
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <?php if ($dashboardMessage !== ''): ?>
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="alert <?php echo htmlspecialchars($dashboardMessageClass); ?>" style="border-radius: 10px; margin-bottom: 20px;">
                                    <?php echo htmlspecialchars($dashboardMessage); ?>
                                </div>
                            </div>
                        </div>
                    <?php elseif ($totalOpportunities === 0): ?>
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="alert alert-info" style="border-radius: 10px; margin-bottom: 20px;">
                                    The CRM dashboard is empty. Use Load CRM Sample Data to add demo contacts, companies, activities, and opportunities for the funnel and monthly charts.
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Top Metric Cards -->
                    <div class="row">
                        <div class="col-lg-3 col-md-6">
                            <div class="crm-widget bg-gradient-orange">
                                <div class="crm-w-title">Total Active Opportunities</div>
                                <div class="crm-w-val"><?php echo $inProgressOpportunities; ?></div>
                                <i class="fa fa-briefcase crm-w-icon"></i>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <div class="crm-widget bg-gradient-blue">
                                <div class="crm-w-title">Total Estimated Est. Sales Value</div>
                                <div class="crm-w-val" style="font-size: 2rem;">
                                    <?php echo $currSymbol . number_format($totalEstimatedRevenue, 2); ?>
                                </div>
                                <i class="fa fa-line-chart crm-w-icon"></i>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <div class="crm-widget bg-gradient-green">
                                <div class="crm-w-title">Total Won Revenue</div>
                                <div class="crm-w-val" style="font-size: 2rem;">
                                    <?php echo $currSymbol . number_format($totalWonRevenue, 2); ?>
                                </div>
                                <i class="fa fa-money crm-w-icon"></i>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <div class="crm-widget bg-gradient-purple">
                                <div class="crm-w-title">Total Contacts / Companies</div>
                                <div class="crm-w-val">
                                    <?php echo $totalContacts . ' / ' . $totalCompanies; ?>
                                </div>
                                <i class="fa fa-users crm-w-icon"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Charts Area -->
                    <div class="row">
                        <!-- Bar Chart: Opportunities by Month -->
                        <div class="col-lg-8">
                            <div class="white-box shadow-sm">
                                <h3><i class="fa fa-bar-chart"></i> Opportunities Created By Month (<?php echo date('Y'); ?>)</h3>
                                <div class="chart-container-c">
                                    <canvas id="monthlyOppChart"></canvas>
                                </div>
                            </div>
                        </div>
                        <!-- Donut Chart: Opportunities by Stage -->
                        <div class="col-lg-4">
                            <div class="white-box shadow-sm">
                                <h3><i class="fa fa-pie-chart"></i> Opportunities by Stage</h3>
                                <div class="chart-container-c">
                                    <canvas id="stageOppChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Funnel Chart + Team Leaderboard Row -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="white-box shadow-sm">
                                <h3><i class="fa fa-filter text-primary"></i> Sales Pipeline Funnel</h3>
                                <div class="chart-container-c" style="min-height: 350px;">
                                    <div id="funnelOppChart" style="height: 100%;"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="white-box shadow-sm">
                                <h3><i class="fa fa-trophy text-warning"></i> Team Leaderboard</h3>
                                <div class="chart-container-c" style="min-height: 350px;">
                                    <div id="teamLeaderboardChart" style="height: 100%;"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- My Top Opportunities and Latest CRM Opportunities -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="white-box shadow-sm">
                                <h3><i class="fa fa-star text-warning"></i> My Top Opportunities</h3>
                                <div class="table-responsive">
                                    <table class="table table-hover table-crm mb-0">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Opportunity Name</th>
                                                <th>Stage</th>
                                                <th>Sum of Amount</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($myTopOpportunities)): ?>
                                                <tr>
                                                    <td colspan="4" class="text-center text-muted py-4">
                                                        <i class="fa fa-inbox fa-3x mb-2" style="opacity: 0.2"></i><br>
                                                        No opportunities found.
                                                    </td>
                                                </tr>
                                            <?php else: ?>
                                                <?php
                                                $topOppColors = ['#4299e1', '#48bb78', '#ecc94b', '#f56565', '#ed64a6', '#9f7aea', '#38b2ac', '#dd6b20', '#667eea', '#a0aec0'];
                                                $topOppBgColors = ['#ebf8ff', '#f0fff4', '#fffff0', '#fff5f5', '#fff5f7', '#faf5ff', '#e6fffa', '#fffaf0', '#ebf4ff', '#f7fafc'];
                                                foreach ($myTopOpportunities as $idx => $topOpp):
                                                    $rowColor = $topOppColors[$idx % count($topOppColors)];
                                                    $rowBg = $topOppBgColors[$idx % count($topOppBgColors)];
                                                    $stageName = $topOpp['dashboard_stage_name'] ?? 'No Stage';
                                                    $stageColorMap = [
                                                        'Proposal/Quote' => ['bg' => '#ebf4ff', 'color' => '#2b6cb0'],
                                                        'Negotiation' => ['bg' => '#fefcbf', 'color' => '#975a16'],
                                                        'Qualification' => ['bg' => '#e6fffa', 'color' => '#285e61'],
                                                        'Needs Analysis' => ['bg' => '#faf5ff', 'color' => '#6b46c1'],
                                                        'Closed Won' => ['bg' => '#f0fff4', 'color' => '#276749'],
                                                        'Closed Lost' => ['bg' => '#fff5f5', 'color' => '#c53030'],
                                                    ];
                                                    $stgStyle = isset($stageColorMap[$stageName]) ? $stageColorMap[$stageName] : ['bg' => '#edf2f7', 'color' => '#4a5568'];
                                                ?>
                                                    <tr style="border-left: 4px solid <?php echo $rowColor; ?>;">
                                                        <td style="font-weight:700; color:<?php echo $rowColor; ?>; font-size:1.1rem;">
                                                            <?php echo $idx + 1; ?>
                                                        </td>
                                                        <td class="font-weight-bold">
                                                            <a href="crm-opportunity-update.php?id=<?php echo htmlspecialchars($topOpp['opportunity_id']); ?>" style="color:<?php echo $rowColor; ?>; text-decoration:none;">
                                                                <?php echo htmlspecialchars(trim($topOpp['description'] ?? 'Unnamed')); ?>
                                                            </a>
                                                        </td>
                                                        <td>
                                                            <span style="background:<?php echo $stgStyle['bg']; ?>; color:<?php echo $stgStyle['color']; ?>; padding:7px 16px; border-radius:24px; font-weight:700; font-size:0.95rem; white-space:nowrap; letter-spacing:0.3px;">
                                                                <?php echo htmlspecialchars($stageName); ?>
                                                            </span>
                                                        </td>
                                                        <td style="font-weight:700; color:#2d3748; font-size:1.05rem;">
                                                            <?php echo $currSymbol . number_format((float) ($topOpp['estimated_sales_value'] ?? 0), 2); ?>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="white-box shadow-sm">
                                <div style="display: flex; justify-content: space-between; align-items:center;">
                                    <h3 style="border-bottom:none; margin-bottom:0;"><i class="fa fa-list-alt"></i> Latest CRM Opportunities</h3>
                                    <a href="crm.php?type=person" class="btn btn-sm btn-outline-primary" style="margin-bottom: 20px;">View All CRM Records</a>
                                </div>
                                <div class="table-responsive mt-3">
                                    <table class="table table-hover table-crm mb-0">
                                        <thead>
                                            <tr>
                                                <th>Opportunity Name</th>
                                                <th>Created Date</th>
                                                <th>Stage</th>
                                                <th>Success %</th>
                                                <th>Est. Value</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($topOpportunities)): ?>
                                                <tr>
                                                    <td colspan="6" class="text-center text-muted py-4">
                                                        <i class="fa fa-inbox fa-3x mb-2" style="opacity: 0.2"></i><br>
                                                        No opportunities found yet.
                                                    </td>
                                                </tr>
                                            <?php else: ?>
                                                <?php foreach ($topOpportunities as $opp):
                                                    $chance = (float) ($opp['dashboard_success_pct'] ?? 0);
                                                    $progressWidth = max(5, min(100, (int) round($chance)));
                                                    $badgeClass = 'badge-soft-warning';
                                                    if ($chance >= 100) $badgeClass = 'badge-soft-success';
                                                    elseif ($chance <= 0) $badgeClass = 'badge-soft-danger';
                                                ?>
                                                    <tr>
                                                        <td class="font-weight-bold">
                                                            <a href="crm-opportunity-update.php?id=<?php echo htmlspecialchars($opp['opportunity_id']); ?>" class="text-dark font-weight-bold">
                                                                <?php echo htmlspecialchars($opp['dashboard_title'] ?? 'Unnamed Opportunity'); ?>
                                                            </a>
                                                        </td>
                                                        <td>
                                                            <?php echo !empty($opp['dashboard_created_date']) && strtotime($opp['dashboard_created_date']) !== false ? date('M d, Y', strtotime($opp['dashboard_created_date'])) : '-'; ?>
                                                        </td>
                                                        <td>
                                                            <?php echo htmlspecialchars($opp['dashboard_stage_name'] ?? 'No Stage'); ?>
                                                        </td>
                                                        <td>
                                                            <span class="badge-soft <?php echo $badgeClass; ?>">
                                                                <?php echo rtrim(rtrim(number_format($chance, 2, '.', ''), '0'), '.'); ?>%
                                                            </span>
                                                            <div class="progress mt-1" style="height: 4px; background: #edf2f7;">
                                                                <?php
                                                                $progColor = '#f5af19';
                                                                if ($chance >= 100) $progColor = '#38ef7d';
                                                                if ($chance <= 0) $progColor = '#f12711';
                                                                ?>
                                                                <div class="progress-bar" role="progressbar" style="width: <?php echo $progressWidth; ?>%; background: <?php echo $progColor; ?>;"></div>
                                                            </div>
                                                        </td>
                                                        <td class="font-weight-bold">
                                                            <?php echo $currSymbol . number_format((float) ($opp['dashboard_estimated_value'] ?? 0), 2); ?>
                                                        </td>
                                                        <td>
                                                            <a href="crm-opportunity-update.php?id=<?php echo htmlspecialchars($opp['opportunity_id']); ?>" class="btn btn-sm btn-light border shadow-sm">
                                                                <i class="fa fa-edit text-primary"></i> Update
                                                            </a>
                                                        </td>
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
    </div>
    </div>
    </div>

    <script>
        var resizefunc = [];
    </script>
    <?php include('common/footer.php'); ?>
    <script src="assets/global/plugins/jquery.min.js" type="text/javascript"></script>
    <script src="assets/global/plugins/bootstrap/js/bootstrap.min.js" type="text/javascript"></script>
    <script src="assets/global/scripts/datatable.js" type="text/javascript"></script>
    <script src="assets/global/plugins/datatables/datatables.min.js" type="text/javascript"></script>
    <script src="assets/global/plugins/datatables/plugins/bootstrap/datatables.bootstrap.js" type="text/javascript"></script>
    <script src="assets/pages/scripts/table-datatables-responsive.min.js" type="text/javascript"></script>

    <!-- Chart.js 3+ expected as modern bakery dashboard probably uses it -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- ApexCharts for Funnel chart -->
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

    <script>
        // Data populated from PHP
        const monthlyData = <?php echo $monthlyData; ?>;
        const donutStageLabels = <?php echo $stageLabels; ?>.slice();
        const donutStageValues = <?php echo $stageData; ?>.slice();
        const funnelStageLabels = <?php echo $funnelStageLabels; ?>.slice();
        const funnelStageValues = <?php echo $funnelStageData; ?>.slice();

        // Months
        const monthLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

        // Bar chart (Opportunities by Month)
        const ctxBar = document.getElementById('monthlyOppChart').getContext('2d');
        const gradientBar = ctxBar.createLinearGradient(0, 0, 0, 300);
        gradientBar.addColorStop(0, 'rgba(74, 144, 226, 0.9)');
        gradientBar.addColorStop(1, 'rgba(74, 144, 226, 0.2)');

        new Chart(ctxBar, {
            type: 'bar',
            data: {
                labels: monthLabels,
                datasets: [{
                    label: 'New Opportunities',
                    data: monthlyData,
                    backgroundColor: gradientBar,
                    borderColor: '#4A90E2',
                    borderWidth: 1,
                    borderRadius: 4,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });

        // Donut Chart Setup (Opportunities by Stage)
        const ctxDonut = document.getElementById('stageOppChart').getContext('2d');

        if (donutStageLabels.length === 0) {
            donutStageLabels.push('No Data');
            donutStageValues.push(1);
        }

        new Chart(ctxDonut, {
            type: 'doughnut',
            data: {
                labels: donutStageLabels,
                datasets: [{
                    data: donutStageValues,
                    backgroundColor: [
                        '#4299e1', // blue
                        '#48bb78', // green
                        '#ecc94b', // yellow
                        '#f56565', // red
                        '#ed64a6', // pink
                        '#9f7aea', // purple
                        '#a0aec0' // gray
                    ],
                    borderWidth: 0,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            usePointStyle: true,
                            padding: 20
                        }
                    }
                }
            }
        });

        // ApexCharts Sales Pipeline Funnel Data built from live CRM stages
        const funnelColors = ['#0f4c5c', '#2c7a7b', '#5c9ead', '#8ab17d', '#f6bd60', '#f28482'];
        const funnelData = funnelStageLabels.length === 0 ?
            [{
                x: 'No Data',
                y: 1
            }] :
            funnelStageLabels.map(function(label, index) {
                return {
                    x: label,
                    y: funnelStageValues[index]
                };
            }).sort((a, b) => b.y - a.y); // Sort descending to form a V-shape

        const funnelOptions = {
            series: [{
                name: 'Opportunities',
                data: funnelData
            }],
            chart: {
                type: 'bar',
                height: 360,
                parentHeightOffset: 0,
                toolbar: {
                    show: false
                },
                animations: {
                    enabled: true,
                    easing: 'easeinout',
                    speed: 650
                }
            },
            plotOptions: {
                bar: {
                    borderRadius: 0,
                    horizontal: true,
                    barHeight: '82%',
                    isFunnel: true,
                    distributed: true
                }
            },
            dataLabels: {
                enabled: true,
                formatter: function(val, opt) {
                    return val + ' opportunities';
                },
                style: {
                    fontSize: '13px',
                    fontWeight: 700,
                    colors: ['#ffffff']
                },
                dropShadow: {
                    enabled: false
                }
            },
            colors: funnelColors,
            fill: {
                type: 'gradient',
                gradient: {
                    shade: 'light',
                    type: 'horizontal',
                    shadeIntensity: 0.3,
                    opacityFrom: 0.98,
                    opacityTo: 0.72,
                    stops: [0, 100]
                }
            },
            grid: {
                show: false,
                padding: {
                    left: 10,
                    right: 10,
                    top: 0,
                    bottom: 0
                }
            },
            xaxis: {
                labels: {
                    show: false
                },
                axisBorder: {
                    show: false
                },
                axisTicks: {
                    show: false
                }
            },
            yaxis: {
                labels: {
                    style: {
                        fontSize: '13px',
                        fontWeight: 600,
                        colors: ['#2d3748']
                    }
                }
            },
            tooltip: {
                y: {
                    formatter: function(val) {
                        return val + ' opportunities';
                    }
                }
            },
            legend: {
                show: false
            },
            stroke: {
                show: false
            }
        };

        const funnelChart = new ApexCharts(document.querySelector("#funnelOppChart"), funnelOptions);
        funnelChart.render();

        // Team Leaderboard Chart
        const leaderboardLabels = <?php echo json_encode(array_keys($teamLeaderboard)); ?>;
        const leaderboardValues = <?php echo json_encode(array_values($teamLeaderboard)); ?>;
        const leaderboardColors = ['#4299e1', '#48bb78', '#5c9ead', '#ecc94b', '#f56565', '#ed64a6', '#9f7aea', '#38b2ac', '#dd6b20', '#a0aec0'];
        const leaderboardData = leaderboardLabels.map(function(name, i) {
            return {
                x: name,
                y: leaderboardValues[i]
            };
        });

        const leaderboardOptions = {
            series: [{
                name: 'Sum of Amount',
                data: leaderboardData
            }],
            chart: {
                type: 'bar',
                height: 350,
                toolbar: {
                    show: false
                }
            },
            plotOptions: {
                bar: {
                    horizontal: true,
                    barHeight: '60%',
                    distributed: true,
                    borderRadius: 4
                }
            },
            colors: leaderboardColors,
            dataLabels: {
                enabled: true,
                formatter: function(val) {
                    return '<?php echo $currSymbol; ?>' + val.toLocaleString();
                },
                style: {
                    fontSize: '12px',
                    fontWeight: 600,
                    colors: ['#fff']
                },
                dropShadow: {
                    enabled: false
                }
            },
            xaxis: {
                labels: {
                    formatter: function(val) {
                        return '<?php echo $currSymbol; ?>' + (val / 1000).toFixed(0) + 'k';
                    }
                },
                title: {
                    text: 'Sum of Amount'
                }
            },
            yaxis: {
                labels: {
                    style: {
                        fontSize: '13px',
                        fontWeight: 600
                    }
                }
            },
            grid: {
                borderColor: '#edf2f7',
                xaxis: {
                    lines: {
                        show: true
                    }
                },
                yaxis: {
                    lines: {
                        show: false
                    }
                }
            },
            legend: {
                show: false
            },
            tooltip: {
                y: {
                    formatter: function(val) {
                        return '<?php echo $currSymbol; ?>' + val.toLocaleString();
                    }
                }
            }
        };

        const leaderboardChart = new ApexCharts(document.querySelector('#teamLeaderboardChart'), leaderboardOptions);
        leaderboardChart.render();
    </script>
</body>

</html>