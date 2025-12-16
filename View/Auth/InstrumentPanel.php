<?php
require_once __DIR__ . "/../../Model/DB.php";

// --- DATA FETCHING ---

// 1. Fetch Key Performance Indicators (KPIs)
$kpiQuery = "SELECT 
                COUNT(PurchaseID) as TotalOrders, 
                SUM(TotalAmount) as TotalRevenue 
             FROM purchase_order 
             WHERE IsActive = 'Active'";
$kpiResult = mysqli_fetch_assoc(mysqli_query($conn, $kpiQuery));

// 2. Fetch Data for Bar Chart: Total Amount by Supplier
$barQuery = "SELECT s.SupplierName, SUM(po.TotalAmount) as Total 
             FROM purchase_order po 
             JOIN suppliers s ON po.SupplierID = s.SupplierID 
             WHERE po.IsActive = 'Active' 
             GROUP BY s.SupplierName";
$barResult = mysqli_query($conn, $barQuery);

$barLabels = [];
$barData = [];
while ($row = mysqli_fetch_assoc($barResult)) {
    $barLabels[] = $row['SupplierName'];
    $barData[] = $row['Total'];
}

// 3. Fetch Data for Pie Chart: Quantity by Category
$pieQuery = "SELECT p.Category, SUM(pd.Quantity) as TotalQty 
             FROM purchase_details pd 
             JOIN products p ON pd.ProductID = p.ProductID 
             WHERE pd.IsActive = 'Active' 
             GROUP BY p.Category";
$pieResult = mysqli_query($conn, $pieQuery);

$pieLabels = [];
$pieData = [];
while ($row = mysqli_fetch_assoc($pieResult)) {
    $pieLabels[] = $row['Category'];
    $pieData[] = $row['TotalQty'];
}
?>

<title>Dashboard</title>
<style>
    .dashboard-wrapper {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: flex-start;
        font-family: 'Arial', sans-serif;
        width: 100%;
        height: 100%;
        background: transparent;
        box-sizing: border-box;
        gap: 30px;
    }

    /* KPI Cards Row */
    .kpi-container {
        display: flex;
        gap: 20px;
        width: 100%;
        box-sizing: border-box;
    }

    .kpi-card {
        flex: 1;
        background: var(--card-white);
        padding: 20px;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        display: flex;
        align-items: center;
        justify-content: space-between;
        border-left: 5px solid #3498db;
    }

    .kpi-card.green { border-left-color: #2ecc71; }

    .kpi-info h3 { margin: 0; font-size: 14px; color: var(--text-grey); text-transform: uppercase; }
    .kpi-info p { margin: 5px 0 0; font-size: 28px; font-weight: bold; color: var(--text-dark); }
    .kpi-icon { font-size: 32px; color: var(--border); }

    /* Charts Grid */
    .charts-container {
        display: grid;
        grid-template-columns: 2fr 1fr; 
        gap: 25px;
        width: 100%;
    }

    .chart-card {
        background: var(--card-white);
        padding: 25px;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        /* Ensure charts have a container size */
        min-height: 350px;
        display: flex;
        flex-direction: column;
    }

    .chart-header {
        margin-bottom: 20px;
        font-size: 18px;
        font-weight: 700;
        color: var(--text-dark);
    }
    
    /* Force canvas size */
    .canvas-container {
        position: relative;
        flex-grow: 1;
        width: 100%;
        height: 300px;
    }

    @media (max-width: 900px) {
        .charts-container { grid-template-columns: 1fr; }
    }
</style>

<div class="dashboard-wrapper">
    <div class="kpi-container">
        <div class="kpi-card">
            <div class="kpi-info">
                <h3>Total Orders</h3>
                <p><?php echo number_format($kpiResult['TotalOrders'] ?? 0); ?></p>
            </div>
            <div class="kpi-icon">📦</div>
        </div>
        <div class="kpi-card green">
            <div class="kpi-info">
                <h3>Total Revenue</h3>
                <p>RM <?php echo number_format($kpiResult['TotalRevenue'] ?? 0, 2); ?></p>
            </div>
            <div class="kpi-icon">💰</div>
        </div>
    </div>

    <div class="charts-container">
        
        <div class="chart-card">
            <div class="chart-header">Total Sales by Supplier (Amount)</div>
            <div class="canvas-container">
                <canvas id="barChart"></canvas>
            </div>
        </div>

        <div class="chart-card">
            <div class="chart-header">Product Quantity Sold</div>
            <div class="canvas-container">
                <canvas id="pieChart"></canvas>
            </div>
        </div>

    </div>
</div>

<script>
    // We wrap code in a small timeout to ensure DOM is fully ready
    setTimeout(function() {
        // Check for Dark Mode to adjust chart text color
        const isDarkMode = document.body.classList.contains('dark-mode');
        const textColor = isDarkMode ? '#E0E0E0' : '#666666';
        const gridColor = isDarkMode ? '#333333' : '#e5e5e5';
        
        // --- 1. BAR CHART ---
        const barCanvas = document.getElementById('barChart');
        if (barCanvas) {
            new Chart(barCanvas.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode($barLabels); ?>,
                    datasets: [{
                        label: 'Total Sales Amount (RM)',
                        data: <?php echo json_encode($barData); ?>,
                        backgroundColor: 'rgba(52, 152, 219, 0.6)',
                        borderColor: 'rgba(52, 152, 219, 1)',
                        borderWidth: 1,
                        borderRadius: 5
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false, // Fits to container
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { color: textColor },
                            grid: { color: gridColor }
                        },
                        x: {
                            ticks: { color: textColor },
                            grid: { display: false }
                        }
                    },
                    plugins: {
                        legend: { labels: { color: textColor } }
                    }
                }
            });
        }

        // --- 2. PIE CHART ---
        const pieCanvas = document.getElementById('pieChart');
        if (pieCanvas) {
            new Chart(pieCanvas.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: <?php echo json_encode($pieLabels); ?>,
                    datasets: [{
                        data: <?php echo json_encode($pieData); ?>,
                        backgroundColor: [
                            '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF'
                        ],
                        hoverOffset: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false, // Fits to container
                    plugins: {
                        legend: { position: 'bottom', labels: { color: textColor } }
                    }
                }
            });
        }

    }, 100); // 100ms delay to be safe
</script>