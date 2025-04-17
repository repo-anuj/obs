<?php
session_start();
require_once "./functions/admin.php";
$title = "Admin Dashboard";
require_once "./template/header.php";
require_once "./functions/database_functions.php";
$conn = db_connect();

// Total orders
$orderRes = mysqli_query($conn, "SELECT COUNT(*) AS total_orders FROM orders");
$orderData = mysqli_fetch_assoc($orderRes);
$total_orders = $orderData['total_orders'];

// Total revenue
$revRes = mysqli_query($conn, "SELECT SUM(amount) AS total_revenue FROM orders");
$revData = mysqli_fetch_assoc($revRes);
$total_revenue = $revData['total_revenue'] ?: 0;

// Top sellers
$topRes = mysqli_query($conn, 
    "SELECT oi.book_isbn, b.book_title, SUM(oi.quantity) AS total_sold
     FROM order_items oi
     JOIN books b ON oi.book_isbn = b.book_isbn
     GROUP BY oi.book_isbn
     ORDER BY total_sold DESC
     LIMIT 5"
);
$top_sellers = [];
while($row = mysqli_fetch_assoc($topRes)) {
    $top_sellers[] = $row;
}

// Orders per day (last 7 days)
$dateRes = mysqli_query($conn, 
    "SELECT DATE(date) AS order_date, COUNT(*) AS orders_count
     FROM orders
     GROUP BY DATE(date)
     ORDER BY DATE(date) DESC
     LIMIT 7"
);
$orders_per_day = [];
while($row = mysqli_fetch_assoc($dateRes)) {
    $orders_per_day[] = $row;
}
$orders_per_day = array_reverse($orders_per_day);

// Recent Sales & Top Customers data
$recentSalesSQL = "SELECT o.orderid, o.`date` AS sale_date, o.amount, c.name AS customer_name
                  FROM orders o
                  JOIN customers c ON o.user_id = c.customerid
                  ORDER BY o.`date` DESC
                  LIMIT 5";
$recentRes = mysqli_query($conn, $recentSalesSQL);
if(!$recentRes) {
    die("Recent Sales Query Error: " . mysqli_error($conn));
}
$recent_sales = mysqli_fetch_all($recentRes, MYSQLI_ASSOC);

$topCustomerSQL = "SELECT c.customerid, c.name, SUM(o.amount) AS total_spent
                   FROM orders o
                   JOIN customers c ON o.user_id = c.customerid
                   GROUP BY c.customerid
                   ORDER BY total_spent DESC
                   LIMIT 5";
$customerRes = mysqli_query($conn, $topCustomerSQL);
if(!$customerRes) {
    die("Top Customers Query Error: " . mysqli_error($conn));
}
$top_customers = mysqli_fetch_all($customerRes, MYSQLI_ASSOC);

?>

<div class="container mt-4">
    <h2 class="mb-4"><i class="fas fa-tachometer-alt me-2"></i>Admin Dashboard</h2>

    <!-- Summary Row -->
    <div class="row">
        <div class="col-md-3">
            <div class="card text-white bg-primary mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title mb-0">Total Orders</h5>
                            <p class="fs-3 mb-0"><?php echo $total_orders; ?></p>
                        </div>
                        <i class="fas fa-shopping-cart fa-2x text-black-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-success mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title mb-0">Total Revenue</h5>
                            <p class="fs-3 mb-0">$<?php echo number_format($total_revenue, 2); ?></p>
                        </div>
                        <i class="fas fa-dollar-sign fa-2x text-black-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-info mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title mb-0">Top Sellers</h5>
                            <p class="fs-3 mb-0"><?php echo count($top_sellers); ?></p>
                        </div>
                        <i class="fas fa-book-open fa-2x text-black-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-warning mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title mb-0">Top Customers</h5>
                            <p class="fs-3 mb-0"><?php echo count($top_customers); ?></p>
                        </div>
                        <i class="fas fa-users fa-2x text-black-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-chart-line me-1"></i>Orders Per Day (Last 7 Days)</h5>
                    <canvas id="ordersChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-book-open me-1"></i>Top Sellers</h5>
                    <canvas id="topSellersChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Details Row -->
    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-receipt me-1"></i>Recent Sales</h5>
                    <table class="table table-sm table-hover">
                        <thead><tr><th>Order #</th><th>Date</th><th>Customer</th><th>Amount</th></tr></thead>
                        <tbody>
                        <?php foreach($recent_sales as $sale): ?>
                            <tr>
                                <td>#<?php echo $sale['orderid']; ?></td>
                                <td><?php echo date('M j, Y', strtotime($sale['sale_date'])); ?></td>
                                <td><?php echo htmlspecialchars($sale['customer_name']); ?></td>
                                <td>$<?php echo number_format($sale['amount'],2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-users me-1"></i>Top Customers</h5>
                    <canvas id="topCustomersChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ordersCtx = document.getElementById('ordersChart').getContext('2d');
    new Chart(ordersCtx, {
        type: 'line',
        data: {
            labels: [<?php echo implode(',', array_map(function($d){ return "'".$d['order_date']."'"; }, $orders_per_day)); ?>],
            datasets: [{
                label: 'Orders',
                data: [<?php echo implode(',', array_map(function($d){ return $d['orders_count']; }, $orders_per_day)); ?>],
                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1
            }]
        },
        options: { scales: { y: { beginAtZero: true } } }
    });

    const topCtx = document.getElementById('topSellersChart').getContext('2d');
    new Chart(topCtx, {
        type: 'bar',
        data: {
            labels: [<?php echo implode(',', array_map(function($s){ return "'".addslashes($s['book_title'])."'"; }, $top_sellers)); ?>],
            datasets: [{
                label: 'Units Sold',
                data: [<?php echo implode(',', array_map(function($s){ return $s['total_sold']; }, $top_sellers)); ?>],
                backgroundColor: 'rgba(255, 99, 132, 0.2)',
                borderColor: 'rgba(255,99,132,1)',
                borderWidth: 1
            }]
        },
        options: { scales: { y: { beginAtZero: true } } }
    });

    const custCtx = document.getElementById('topCustomersChart').getContext('2d');
    new Chart(custCtx, {
        type: 'bar',
        data: {
            labels: [<?php echo implode(',', array_map(function($c){ return "'".addslashes($c['name'])."'"; }, $top_customers)); ?>],
            datasets: [{
                label: 'Total Spent',
                data: [<?php echo implode(',', array_map(function($c){ return $c['total_spent']; }, $top_customers)); ?>],
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                borderColor: 'rgba(75, 192, 192, 1)',
                borderWidth: 1
            }]
        },
        options: { scales: { y: { beginAtZero: true } } }
    });
</script>

<?php
if(isset($conn)) { mysqli_close($conn); }
require_once "./template/footer.php";
?>
