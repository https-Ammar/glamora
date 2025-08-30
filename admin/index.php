<?php
session_start();
require('./config/db.php');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if (!isset($_SESSION['userId']) || empty($_SESSION['userId'])) {
  header("Location: ./auth/signin.php");
  exit();
}

$userid = $_SESSION['userId'];

$select = $conn->prepare("SELECT * FROM usersadmin WHERE id = ?");
$select->bind_param("i", $userid);
$select->execute();
$fetchname = $select->get_result()->fetch_assoc();
$select->close();

function getStatusColor($status)
{
  switch ($status) {
    case 'done':
      return 'success';
    case 'inprogress':
      return 'warning';
    case 'accepted':
      return 'primary';
    default:
      return 'danger';
  }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
  if ($_POST['action'] === 'delete' && isset($_POST['id'])) {
    $id = intval($_POST['id']);
    if ($id > 0) {
      $stmt = $conn->prepare("DELETE FROM order_items WHERE order_id = ?");
      $stmt->bind_param("i", $id);
      $stmt->execute();
      $stmt->close();

      $stmt = $conn->prepare("DELETE FROM orders WHERE id = ?");
      $stmt->bind_param("i", $id);
      $result = $stmt->execute();
      $stmt->close();

      echo json_encode(['success' => $result]);
      exit();
    }
  }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'], $_POST['status'])) {
  $orderId = intval($_POST['order_id']);
  $status = $_POST['status'];

  $stmt = $conn->prepare("UPDATE orders SET orderstate = ? WHERE id = ?");
  $stmt->bind_param("si", $status, $orderId);
  $stmt->execute();
  $stmt->close();

  header('Location: orders.php');
  exit();
}
?>

<!doctype html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Admin admin</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <link href="./assets/css/main.css" rel="stylesheet">
</head>

<body
  x-data="{ page: 'ecommerce', 'loaded': true, 'darkMode': false, 'stickyMenu': false, 'sidebarToggle': false, 'scrollTop': false }"
  x-init="darkMode = JSON.parse(localStorage.getItem('darkMode')); $watch('darkMode', value => localStorage.setItem('darkMode', JSON.stringify(value)))"
  :class="{'dark bg-gray-900': darkMode === true}">
  <div x-show="loaded" x-transition.opacity
    x-init="window.addEventListener('DOMContentLoaded', () => {setTimeout(() => loaded = false, 500)})"
    class="fixed inset-0 z-999999 flex items-center justify-center bg-white dark:bg-black">
    <div class="h-16 w-16 animate-spin rounded-full border-4 border-solid border-brand-500 border-t-transparent"></div>
  </div>
  <div class="flex h-screen overflow-hidden">
    <?php require('./includes/header.php'); ?>
    <div class="relative flex flex-1 flex-col overflow-x-hidden overflow-y-auto">
      <div :class="sidebarToggle ? 'block xl:hidden' : 'hidden'" class="fixed z-50 h-screen w-full bg-gray-900/50">
      </div>
      <main>
        <?php require('./includes/nav.php'); ?>
        <div class="mx-auto max-w-(--breakpoint-2xl) p-4 md:p-6">
          <div class="grid grid-cols-12 gap-4 md:gap-6">
            <div class="col-span-12 space-y-6 xl:col-span-7">
              <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 md:gap-6">
                <div
                  class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] md:p-6">
                  <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-gray-100 dark:bg-gray-800">
                    <i class="bi bi-people text-xl text-gray-800 dark:text-white/90"></i>
                  </div>
                  <div class="mt-5 flex items-end justify-between">
                    <div>
                      <span class="text-sm text-gray-500 dark:text-gray-400">Customers</span>
                      <h4 class="mt-2 text-title-sm font-bold text-gray-800 dark:text-white/90">
                        <?php echo $conn->query("SELECT COUNT(*) as total FROM users")->fetch_assoc()['total']; ?>
                      </h4>
                    </div>
                    <span
                      class="flex items-center gap-1 rounded-full bg-success-50 py-0.5 pl-2 pr-2.5 text-sm font-medium text-success-600 dark:bg-success-500/15 dark:text-success-500">
                      <i class="bi bi-arrow-up"></i> 11.01%
                    </span>
                  </div>
                </div>
                <div
                  class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] md:p-6">
                  <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-gray-100 dark:bg-gray-800">
                    <i class="bi bi-border-style text-xl text-gray-800 dark:text-white/90"></i>
                  </div>
                  <div class="mt-5 flex items-end justify-between">
                    <div>
                      <span class="text-sm text-gray-500 dark:text-gray-400">Total Orders</span>
                      <h4 class="mt-2 text-title-sm font-bold text-gray-800 dark:text-white/90">
                        <?php echo $conn->query("SELECT COUNT(*) as total FROM orders")->fetch_assoc()['total']; ?>
                      </h4>
                    </div>
                    <span
                      class="flex items-center gap-1 rounded-full bg-error-50 py-0.5 pl-2 pr-2.5 text-sm font-medium text-error-600 dark:bg-error-500/15 dark:text-error-500">
                      <i class="bi bi-arrow-down"></i> 9.05%
                    </span>
                  </div>
                </div>
              </div>
              <div
                class="overflow-hidden rounded-2xl border border-gray-200 bg-white px-5 pt-5 sm:px-6 sm:pt-6 dark:border-gray-800 dark:bg-white/[0.03]">
                <div class="flex items-center justify-between">
                  <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Monthly Sales</h3>
                  <button class="text-gray-400 hover:text-gray-700 dark:hover:text-white">
                    <i class="bi bi-three-dots"></i>
                  </button>
                </div>
                <div class="max-w-full overflow-x-auto custom-scrollbar">
                  <div id="chartOne" class="-ml-5 h-full min-w-[690px] pl-2 xl:min-w-full"></div>
                </div>
              </div>
            </div>
            <div class="col-span-12 xl:col-span-5">
              <div class="rounded-2xl border border-gray-200 bg-gray-100 dark:border-gray-800 dark:bg-white/[0.03]">
                <div class="shadow-default rounded-2xl bg-white px-5 pb-11 pt-5 dark:bg-gray-900 sm:px-6 sm:pt-6">
                  <div class="flex justify-between">
                    <div>
                      <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Monthly Target</h3>
                      <p class="mt-1 text-theme-sm text-gray-500 dark:text-gray-400">Target you've set for each month
                      </p>
                    </div>
                    <button class="text-gray-400 hover:text-gray-700 dark:hover:text-white">
                      <i class="bi bi-three-dots"></i>
                    </button>
                  </div>
                  <div class="relative max-h-[195px]">
                    <div id="chartTwo" class="h-full"></div>
                    <span
                      class="absolute left-1/2 top-[85%] -translate-x-1/2 -translate-y-[85%] rounded-full bg-success-50 px-3 py-1 text-xs font-medium text-success-600 dark:bg-success-500/15 dark:text-success-500">+10%</span>
                  </div>
                  <p class="mx-auto mt-1.5 w-full max-w-[380px] text-center text-sm text-gray-500 sm:text-base">
                    You earn <span>
                      <?php
                      $totalSales = $conn->query("SELECT SUM(finaltotalprice) as total FROM orders WHERE orderstate = 'done'")->fetch_assoc()['total'];
                      ?>
                      <?= number_format($totalSales ?? 0, 2) ?>
                    </span> today, it's higher than last month. Keep up your good work!
                  </p>
                </div>

                <div class="flex items-center justify-center gap-5 px-6 py-3.5 sm:gap-8 sm:py-5">
                  <div>
                    <p class="mb-1 text-center text-theme-xs text-gray-500 dark:text-gray-400 sm:text-sm">Monthly O</p>
                    <p
                      class="flex items-center justify-center gap-1 text-base font-semibold text-gray-800 dark:text-white/90 sm:text-lg">
                      <?php echo $conn->query("SELECT COUNT(*) as total FROM orders WHERE MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW())")->fetch_assoc()['total']; ?>
                      <i class="bi bi-arrow-down text-error-500"></i>
                    </p>
                  </div>

                  <div class="h-7 w-px bg-gray-200 dark:bg-gray-800"></div>

                  <div>
                    <p class="mb-1 text-center text-theme-xs text-gray-500 dark:text-gray-400 sm:text-sm">Weekly O</p>
                    <p
                      class="flex items-center justify-center gap-1 text-base font-semibold text-gray-800 dark:text-white/90 sm:text-lg">
                      <?php echo $conn->query("SELECT COUNT(*) as total FROM orders WHERE YEARWEEK(created_at) = YEARWEEK(NOW())")->fetch_assoc()['total']; ?>
                      <i class="bi bi-arrow-up text-success-500"></i>
                    </p>
                  </div>

                  <div class="h-7 w-px bg-gray-200 dark:bg-gray-800"></div>

                  <div>
                    <p class="mb-1 text-center text-theme-xs text-gray-500 dark:text-gray-400 sm:text-sm">Today's O</p>
                    <p
                      class="flex items-center justify-center gap-1 text-base font-semibold text-gray-800 dark:text-white/90 sm:text-lg">
                      <?php echo $conn->query("SELECT COUNT(*) as total FROM orders WHERE DATE(created_at) = CURDATE()")->fetch_assoc()['total']; ?>
                      <i class="bi bi-arrow-up text-success-500"></i>
                    </p>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-span-12">
              <div
                class="overflow-hidden rounded-2xl border border-gray-200 bg-white pt-4 dark:border-gray-800 dark:bg-white/[0.03]">
                <div class="flex flex-col gap-5 px-6 mb-4 sm:flex-row sm:items-center sm:justify-between">
                  <div>
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Recent Orders</h3>
                  </div>
                  <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                    <form id="search-form">
                      <div class="relative">
                        <span class="absolute -translate-y-1/2 pointer-events-none top-1/2 left-4">
                          <i class="bi bi-search text-gray-500 dark:text-gray-400"></i>
                        </span>
                        <input type="text" id="search-input" placeholder="Search..."
                          class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-10 w-full rounded-lg border border-gray-300 bg-transparent py-2.5 pr-4 pl-[42px] text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden xl:w-[300px] dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30">
                      </div>
                    </form>
                    <button
                      class="text-theme-sm shadow-theme-xs inline-flex h-10 items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 font-medium text-gray-700 hover:bg-gray-50 hover:text-gray-800 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03] dark:hover:text-gray-200">
                      <i class="bi bi-funnel"></i> Filter
                    </button>
                  </div>
                </div>
                <div class="max-w-full overflow-x-auto custom-scrollbar">
                  <table class="min-w-full">
                    <thead class="border-gray-100 border-y bg-gray-50 dark:border-gray-800 dark:bg-gray-900">
                      <tr>
                        <th class="px-6 py-3 whitespace-nowrap">
                          <div class="flex items-center gap-3">
                            <div x-data="{checked: false}" @click="checked = !checked"
                              class="flex h-5 w-5 cursor-pointer items-center justify-center rounded-md border-[1.25px]"
                              :class="checked ? 'border-brand-500 bg-brand-500' : 'border-gray-300 dark:border-gray-700'">
                              <i :class="checked ? 'block bi bi-check text-white' : 'hidden'"></i>
                            </div>
                            <span class="block font-medium text-gray-500 text-theme-xs dark:text-gray-400">Deal
                              ID</span>
                          </div>
                        </th>
                        <th class="px-6 py-3 whitespace-nowrap">
                          <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Customer</p>
                        </th>
                        <th class="px-6 py-3 whitespace-nowrap">
                          <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Phone</p>
                        </th>
                        <th class="px-6 py-3 whitespace-nowrap">
                          <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Price Value</p>
                        </th>
                        <th class="px-6 py-3 whitespace-nowrap">
                          <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Date</p>
                        </th>
                        <th class="px-6 py-3 whitespace-nowrap">
                          <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Time</p>
                        </th>
                        <th class="px-6 py-3 whitespace-nowrap">
                          <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Status</p>
                        </th>
                        <th class="px-6 py-3 whitespace-nowrap">
                          <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Action</p>
                        </th>
                      </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800" id="orders-table-body">
                      <?php
                      $orders = $conn->query("
                        SELECT o.*, u.email 
                        FROM orders o
                        JOIN users u ON o.user_id = u.id
                        ORDER BY o.created_at DESC
                      ");
                      while ($order = $orders->fetch_assoc()):
                        $email = $order['email'] ?? 'Not available';
                        $nameParts = explode(' ', $order['name']);
                        $initials = '';
                        if (count($nameParts) >= 1) {
                          $initials .= strtoupper(substr($nameParts[0], 0, 1));
                          if (count($nameParts) >= 2) {
                            $initials .= strtoupper(substr($nameParts[1], 0, 1));
                          }
                        }
                        $dateTime = new DateTime($order['created_at']);
                        $date = $dateTime->format('Y-m-d');
                        $time = $dateTime->format('H:i');

                        $statusColor = '';
                        $statusTextColor = '';
                        $statusBgColor = '';

                        switch (strtolower($order['orderstate'])) {
                          case 'pending':
                            $statusTextColor = 'text-yellow-600';
                            $statusBgColor = 'bg-yellow-50 dark:bg-yellow-500/15';
                            break;
                          case 'completed':
                            $statusTextColor = 'text-green-600';
                            $statusBgColor = 'bg-green-50 dark:bg-green-500/15';
                            break;
                          case 'cancelled':
                            $statusTextColor = 'text-red-600';
                            $statusBgColor = 'bg-red-50 dark:bg-red-500/15';
                            break;
                          case 'processing':
                            $statusTextColor = 'text-blue-600';
                            $statusBgColor = 'bg-blue-50 dark:bg-blue-500/15';
                            break;
                          case 'shipped':
                            $statusTextColor = 'text-purple-600';
                            $statusBgColor = 'bg-purple-50 dark:bg-purple-500/15';
                            break;
                          default:
                            $statusTextColor = 'text-gray-600';
                            $statusBgColor = 'bg-gray-50 dark:bg-gray-500/15';
                        }
                        ?>
                        <tr id="order-row-<?= $order['id'] ?>" class="order-row">
                          <td class="px-6 py-3 whitespace-nowrap">
                            <div class="flex items-center gap-3">
                              <div x-data="{checked: false}" @click="checked = !checked"
                                class="flex h-5 w-5 cursor-pointer items-center justify-center rounded-md border-[1.25px]"
                                :class="checked ? 'border-brand-500 bg-brand-500' : 'border-gray-300 dark:border-gray-700'">
                                <i :class="checked ? 'block bi bi-check text-white' : 'hidden'"></i>
                              </div>
                              <span
                                class="block font-medium text-gray-700 text-theme-sm dark:text-gray-400"><?= $order['id'] ?></span>
                            </div>
                          </td>
                          <td class="px-6 py-3 whitespace-nowrap">
                            <div class="flex items-center gap-3">
                              <div class="flex items-center justify-center w-10 h-10 rounded-full bg-brand-100">
                                <span class="text-xs font-semibold text-brand-500"><?= $initials ?></span>
                              </div>
                              <div>
                                <span
                                  class="text-theme-sm mb-0.5 block font-medium text-gray-700 dark:text-gray-400"><?= htmlspecialchars($order['name']) ?></span>
                                <span
                                  class="text-gray-500 text-theme-sm dark:text-gray-400"><?= htmlspecialchars($email) ?></span>
                              </div>
                            </div>
                          </td>
                          <td class="px-6 py-3 whitespace-nowrap">
                            <p class="text-gray-700 text-theme-sm dark:text-gray-400">
                              <?= htmlspecialchars($order['phoneone']) ?>
                            </p>
                          </td>
                          <td class="px-6 py-3 whitespace-nowrap">
                            <p class="text-gray-700 text-theme-sm dark:text-gray-400">
                              <?= number_format($order['finaltotalprice'], 2) ?> <sub>EG</sub>
                            </p>
                          </td>
                          <td class="px-6 py-3 whitespace-nowrap">
                            <p class="text-gray-700 text-theme-sm dark:text-gray-400"><?= $date ?></p>
                          </td>
                          <td class="px-6 py-3 whitespace-nowrap">
                            <p class="text-gray-700 text-theme-sm dark:text-gray-400">
                              <?= date('h:i A') ?>
                            </p>

                          </td>
                          <td class="px-6 py-3 whitespace-nowrap">
                            <span
                              class="text-theme-xs rounded-full px-2 py-0.5 font-medium <?= $statusBgColor ?> <?= $statusTextColor ?>">
                              <?= $order['orderstate'] ?>
                            </span>
                          </td>
                          <td class="px-6 py-3 whitespace-nowrap">
                            <div class="flex justify-center">
                              <i onclick="deleteOrder(<?= $order['id'] ?>)"
                                class="bi bi-trash cursor-pointer text-gray-700 hover:text-error-500 dark:text-gray-400 dark:hover:text-error-500"></i>
                            </div>
                          </td>
                        </tr>
                      <?php endwhile; ?>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
        </div>
      </main>
    </div>
  </div>

  <script defer src="./assets/js/bundle.js"></script>
  <script>
    function deleteOrder(orderId) {
      if (confirm('Are you sure you want to delete this order?')) {
        fetch('', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
          },
          body: 'action=delete&id=' + orderId
        })
          .then(response => response.json())
          .then(data => {
            if (data.success) {
              document.getElementById('order-row-' + orderId).remove();
            } else {
              alert('Error deleting order');
            }
          })
          .catch(error => {
            console.error('Error:', error);
            alert('Error deleting order');
          });
      }
    }

    document.getElementById('search-input').addEventListener('input', function () {
      const searchTerm = this.value.toLowerCase();
      const rows = document.querySelectorAll('#orders-table-body tr');

      rows.forEach(row => {
        const textContent = row.textContent.toLowerCase();
        row.style.display = textContent.includes(searchTerm) ? '' : 'none';
      });
    });

    function showSection(sectionId) {
      document.querySelectorAll('.content-section').forEach(section => {
        section.classList.remove('active');
      });
      document.getElementById(sectionId).classList.add('active');

      document.querySelectorAll('.nav-link').forEach(link => {
        link.classList.remove('active');
      });
      event.target.classList.add('active');
    }
  </script>

  <style>
    span.mr-3.h-11.w-11.overflow-hidden.rounded-full .img_admin {
      background-size: cover !important;
      background-repeat: no-repeat;
      background: url(./assets/img/admin.jpg);
    }
  </style>
</body>

</html>