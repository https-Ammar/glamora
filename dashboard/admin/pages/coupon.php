<?php
session_start();
require('../config/db.php');

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$userid = $_SESSION['userId'];
$select = $conn->prepare("SELECT * FROM usersadmin WHERE id = ?");
$select->bind_param("i", $userid);
$select->execute();
$fetchname = $select->get_result()->fetch_assoc();
$select->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_coupon'])) {
    $code = trim($_POST['coupon_code']);
    $discountType = $_POST['discount_type'];
    $discountValue = floatval($_POST['discount_value']);
    $maxUses = intval($_POST['max_uses']);
    $maximumDiscount = isset($_POST['maximum_discount']) && $_POST['maximum_discount'] !== '' ? floatval($_POST['maximum_discount']) : null;
    $expiresAt = $_POST['expires_at'];

    if (!empty($code) && in_array($discountType, ['percentage', 'fixed']) && $discountValue > 0 && $maxUses > 0 && !empty($expiresAt)) {
        $check = $conn->prepare("SELECT id FROM coupons WHERE code = ?");
        $check->bind_param("s", $code);
        $check->execute();

        if ($check->get_result()->num_rows > 0) {
            $error = "This coupon code already exists!";
        } else {
            $stmt = $conn->prepare("INSERT INTO coupons (code, discount_type, discount_value, max_uses, used_count, maximum_discount, expires_at) VALUES (?, ?, ?, ?, 0, ?, ?)");
            $stmt->bind_param("ssdids", $code, $discountType, $discountValue, $maxUses, $maximumDiscount, $expiresAt);
            $stmt->execute();
            $stmt->close();
            header("Location: coupons.php?success=coupon");
            exit();
        }
    } else {
        $error = "Please fill all fields correctly.";
    }
}

if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM coupons WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    header("Location: coupons.php?success=deleted");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Coupon Management</title>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <link rel="stylesheet" href="../assets/css/main.css">
    <script>
        function copyToClipboard(elementId) {
            var copyText = document.getElementById(elementId);
            copyText.select();
            copyText.setSelectionRange(0, 99999);
            document.execCommand("copy");

            var copyBtn = copyText.nextElementSibling;
            var copyIcon = copyBtn.querySelector('.copy-icon');
            var checkIcon = copyBtn.querySelector('.check-icon');
            var copyTextSpan = copyBtn.querySelector('.copy-text');

            copyIcon.classList.add('hidden');
            checkIcon.classList.remove('hidden');
            copyTextSpan.textContent = 'Copied';

            setTimeout(function () {
                copyIcon.classList.remove('hidden');
                checkIcon.classList.add('hidden');
                copyTextSpan.textContent = 'Copy';
            }, 2000);
        }
    </script>
</head>


<body
    x-data="{ page: 'ecommerce', 'loaded': true, 'darkMode': false, 'stickyMenu': false, 'sidebarToggle': false, 'scrollTop': false }"
    x-init="
         darkMode = JSON.parse(localStorage.getItem('darkMode'));
         $watch('darkMode', value => localStorage.setItem('darkMode', JSON.stringify(value)))"
    :class="{'dark bg-gray-900': darkMode === true}">
    
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">
            <?php
            if ($_GET['success'] === 'coupon') {
                echo "Coupon added successfully!";
            } elseif ($_GET['success'] === 'deleted') {
                echo "Coupon deleted successfully!";
            }
            ?>
        </div>
    <?php endif; ?>

    <div x-show="isTaskModalModal"
        class="fixed inset-0 flex items-center justify-center p-5 overflow-y-auto modal z-99999">
        <div class="modal-close-btn fixed inset-0 h-full w-full bg-gray-400/50 backdrop-blur-[32px]"
            @click="isTaskModalModal = false"></div>
        <div @click.outside="isTaskModalModal = false"
            class="no-scrollbar relative w-full max-w-[700px] overflow-y-auto rounded-3xl bg-white p-6 dark:bg-gray-900 lg:p-11">
            <div class="px-2">
                <h4 class="mb-2 text-2xl font-semibold text-gray-800 dark:text-white/90">Add New Coupon</h4>
                <p class="mb-6 text-sm text-gray-500 dark:text-gray-400 lg:mb-7">Manage your coupons easily</p>
            </div>
            <button @click="isTaskModalModal = false"
                class="transition-color absolute right-5 top-5 z-999 flex h-8 w-8 items-center justify-center rounded-full bg-gray-100 text-gray-400 hover:bg-gray-200 hover:text-gray-600 dark:bg-gray-700 dark:bg-white/[0.05] dark:text-gray-400 dark:hover:bg-white/[0.07] dark:hover:text-gray-300 sm:h-11 sm:w-11">
                <svg class="fill-current size-5 sm:size-6" width="24" height="24" viewBox="0 0 24 24" fill="none"
                    xmlns="http://www.w3.org/2000/svg">
                    <path fill-rule="evenodd" clip-rule="evenodd"
                        d="M6.04289 16.5418C5.65237 16.9323 5.65237 17.5655 6.04289 17.956C6.43342 18.3465 7.06658 18.3465 7.45711 17.956L11.9987 13.4144L16.5408 17.9565C16.9313 18.347 17.5645 18.347 17.955 17.9565C18.3455 17.566 18.3455 16.9328 17.955 16.5423L13.4129 12.0002L17.955 7.45808C18.3455 7.06756 18.3455 6.43439 17.955 6.04387C17.5645 5.65335 16.9313 5.65335 16.5408 6.04387L11.9987 10.586L7.45711 6.04439C7.06658 5.65386 6.43342 5.65386 6.04289 6.04439C5.65237 6.43491 5.65237 7.06808 6.04289 7.4586L10.5845 12.0002L6.04289 16.5418Z"
                        fill=""></path>
                </svg>
            </button>

            <form class="flex flex-col" method="POST">
                <div class="custom-scrollbar overflow-y-auto px-2">
                    <div class="grid grid-cols-1 gap-x-6 gap-y-5 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Coupon
                                Code</label>
                            <input type="text" name="coupon_code" required
                                class="dark:bg-dark-900 h-11 w-full appearance-none rounded-lg border border-gray-300 bg-transparent bg-none px-4 py-2.5 pl-4 pr-11 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800">
                        </div>

                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Expiration
                                Date</label>
                            <div class="relative">
                                <input type="date" placeholder="Select date" type="datetime-local" name="expires_at"
                                    required
                                    class="dark:bg-dark-900 input-date-icon h-11 w-full appearance-none rounded-lg border border-gray-300 bg-transparent bg-none px-4 py-2.5 pl-4 pr-11 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800">
                                <span class="absolute right-3.5 top-1/2 -translate-y-1/2">
                                    <svg class="fill-gray-700 dark:fill-gray-400" width="14" height="14"
                                        viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd" clip-rule="evenodd"
                                            d="M4.33317 0.0830078C4.74738 0.0830078 5.08317 0.418794 5.08317 0.833008V1.24967H8.9165V0.833008C8.9165 0.418794 9.25229 0.0830078 9.6665 0.0830078C10.0807 0.0830078 10.4165 0.418794 10.4165 0.833008V1.24967L11.3332 1.24967C12.2997 1.24967 13.0832 2.03318 13.0832 2.99967V4.99967V11.6663C13.0832 12.6328 12.2997 13.4163 11.3332 13.4163H2.6665C1.70001 13.4163 0.916504 12.6328 0.916504 11.6663V4.99967V2.99967C0.916504 2.03318 1.70001 1.24967 2.6665 1.24967L3.58317 1.24967V0.833008C3.58317 0.418794 3.91896 0.0830078 4.33317 0.0830078ZM4.33317 2.74967H2.6665C2.52843 2.74967 2.4165 2.8616 2.4165 2.99967V4.24967H11.5832V2.99967C11.5832 2.8616 11.4712 2.74967 11.3332 2.74967H9.6665H4.33317ZM11.5832 5.74967H2.4165V11.6663C2.4165 11.8044 2.52843 11.9163 2.6665 11.9163H11.3332C11.4712 11.9163 11.5832 11.8044 11.5832 11.6663V5.74967Z"
                                            fill=""></path>
                                    </svg>
                                </span>
                            </div>
                        </div>

                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Discount
                                Type</label>
                            <div x-data="{ isOptionSelected: false }"
                                class="relative z-20 bg-transparent dark:bg-form-input">
                                <select name="discount_type"
                                    class="dark:bg-dark-900 h-11 w-full appearance-none rounded-lg border border-gray-300 bg-transparent bg-none px-4 py-2.5 pr-11 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800"
                                    :class="isOptionSelected && 'text-gray-800 dark:text-white/90'"
                                    @change="isOptionSelected = true" required>
                                    <option value="percentage"
                                        class="text-gray-700 dark:bg-gray-900 dark:text-gray-400">Percentage</option>
                                    <option value="fixed" class="text-gray-700 dark:bg-gray-900 dark:text-gray-400">
                                        Fixed Value</option>
                                </select>
                                <span
                                    class="absolute z-30 text-gray-500 -translate-y-1/2 right-4 top-1/2 dark:text-gray-400">
                                    <svg class="stroke-current" width="16" height="16" viewBox="0 0 16 16" fill="none"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <path d="M3.8335 5.9165L8.00016 10.0832L12.1668 5.9165" stroke=""
                                            stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                                    </svg>
                                </span>
                            </div>
                        </div>

                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Discount
                                Value</label>
                            <div x-data="{ isOptionSelected: false }"
                                class="relative z-20 bg-transparent dark:bg-form-input">
                                <input type="number" step="1" name="discount_value" required
                                    class="dark:bg-dark-900 h-11 w-full appearance-none rounded-lg border border-gray-300 bg-transparent bg-none px-4 py-2.5 pr-11 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800">
                            </div>
                        </div>

                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Maximum
                                Usage</label>
                            <div x-data="{ isOptionSelected: false }"
                                class="relative z-20 bg-transparent dark:bg-form-input">
                                <input type="number" name="max_uses" min="1" required
                                    class="dark:bg-dark-900 h-11 w-full appearance-none rounded-lg border border-gray-300 bg-transparent bg-none px-4 py-2.5 pr-11 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800">
                            </div>
                        </div>

                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Maximum
                                Discount (for % only)</label>
                            <div x-data="{ isOptionSelected: false }"
                                class="relative z-20 bg-transparent dark:bg-form-input">
                                <input type="number" name="maximum_discount" step="0.01"
                                    class="dark:bg-dark-900 h-11 w-full appearance-none rounded-lg border border-gray-300 bg-transparent bg-none px-4 py-2.5 pr-11 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="flex flex-col items-center gap-6 px-2 mt-6 sm:flex-row sm:justify-between">
                    <div class="flex items-center w-full gap-3 sm:w-auto">
                        <button @click="isTaskModalModal = false" type="button"
                            class="flex w-full justify-center rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03] sm:w-auto">Cancel</button>
                        <button type="submit" name="add_coupon"
                            class="flex w-full justify-center rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-600 sm:w-auto">Add
                            Coupon</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <main>
        <div class="p-4 mx-auto max-w-(--breakpoint-2xl) md:p-6">
            <div x-data="{ pageName: `Coupon List`}">
                <div class="flex flex-wrap items-center justify-between gap-3 pb-6">
                    <h2 class="text-xl font-semibold text-gray-800 dark:text-white/90" x-text="pageName">Coupon List
                    </h2>
                    <nav>
                        <ol class="flex items-center gap-1.5">
                            <li>
                                <a class="inline-flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400"
                                    href="index.html">
                                    Home
                                    <svg class="stroke-current" width="17" height="16" viewBox="0 0 17 16" fill="none"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <path d="M6.0765 12.667L10.2432 8.50033L6.0765 4.33366" stroke=""
                                            stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"></path>
                                    </svg>
                                </a>
                            </li>
                            <li class="text-sm text-gray-800 dark:text-white/90" x-text="pageName">Coupon List</li>
                        </ol>
                    </nav>
                </div>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white px-6 pl-5 dark:border-gray-800 dark:bg-white/3">
                <div
                    class="flex flex-col justify-between gap-5 border-b border-gray-100 py-4 sm:flex-row sm:items-center dark:border-gray-800">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">
                            Coupons
                        </h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Manage your discount coupons
                        </p>
                    </div>
                    <div>
                        <div x-data="{isModalOpen: false}">
                            <button @click="isTaskModalModal = true"
                                class="inline-flex items-center gap-2 rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-white/[0.03]">
                                <svg class="fill-current" width="20" height="20" viewBox="0 0 20 20" fill="none"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path fill-rule="evenodd" clip-rule="evenodd"
                                        d="M9.2502 4.99951C9.2502 4.5853 9.58599 4.24951 10.0002 4.24951C10.4144 4.24951 10.7502 4.5853 10.7502 4.99951V9.24971H15.0006C15.4148 9.24971 15.7506 9.5855 15.7506 9.99971C15.7506 10.4139 15.4148 10.7497 15.0006 10.7497H10.7502V15.0001C10.7502 15.4143 10.4144 15.7501 10.0002 15.7501C9.58599 15.7501 9.2502 15.4143 9.2502 15.0001V10.7497H5C4.58579 10.7497 4.25 10.4139 4.25 9.99971C4.25 9.5855 4.58579 9.24971 5 9.24971H9.2502V4.99951Z"
                                        fill=""></path>
                                </svg>
                                Add New Coupon
                            </button>
                        </div>
                    </div>
                </div>
                <div class="custom-scrollbar overflow-x-auto px-1 pb-4">
                    <table class="min-w-full">
                        <thead>
                            <tr class="border-b border-gray-100 dark:border-gray-800">
                                <th class="py-3 pr-5 text-left text-xs font-medium text-gray-500 dark:text-gray-400">
                                    Code</th>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400">
                                    Type</th>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400">
                                    Usage</th>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400">
                                    Details</th>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400">
                                    Expires</th>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400">
                                    Status</th>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400">
                                    Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            <?php
                            $coupons = $conn->query("
                                SELECT c.*, 
                                (SELECT COUNT(*) FROM orders WHERE coupon_id = c.id) AS used_count 
                                FROM coupons c 
                                ORDER BY c.id DESC
                            ");
                            if ($coupons->num_rows > 0):
                                while ($coupon = $coupons->fetch_assoc()):
                                    $is_expired = strtotime($coupon['expires_at']) < time();
                                    $is_fully_used = $coupon['used_count'] >= $coupon['max_uses'];
                                    $is_active = !$is_expired && !$is_fully_used;
                                    ?>
                                    <tr>
                                        <td class="py-3 pr-5 whitespace-nowrap">
                                            <div class="flex items-center gap-3">
                                                <div class="relative">
                                                    <input type="text" id="coupon_<?= $coupon['id'] ?>"
                                                        value="<?= htmlspecialchars($coupon['code']) ?>"
                                                        class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full min-w-[200px] rounded-lg border border-gray-300 bg-transparent py-3 pr-[90px] pl-4 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                                                        readonly>
                                                    <button type="button"
                                                        onclick="copyToClipboard('coupon_<?= $coupon['id'] ?>')"
                                                        class="copy-btn absolute top-1/2 right-0 inline-flex h-11 -translate-y-1/2 cursor-pointer items-center gap-1 rounded-r-lg border border-gray-300 py-3 pr-3 pl-3.5 text-sm font-medium text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400">
                                                        <span class="copy-icon">
                                                            <svg class="fill-current" width="20" height="20" viewBox="0 0 20 20"
                                                                fill="none" xmlns="http://www.w3.org/2000/svg">
                                                                <path fill-rule="evenodd" clip-rule="evenodd"
                                                                    d="M6.58822 4.58398C6.58822 4.30784 6.81207 4.08398 7.08822 4.08398H15.4154C15.6915 4.08398 15.9154 4.30784 15.9154 4.58398L15.9154 12.9128C15.9154 13.189 15.6916 13.4128 15.4154 13.4128H7.08821C6.81207 13.4128 6.58822 13.189 6.58822 12.9128V4.58398ZM7.08822 2.58398C5.98365 2.58398 5.08822 3.47942 5.08822 4.58398V5.09416H4.58496C3.48039 5.09416 2.58496 5.98959 2.58496 7.09416V15.4161C2.58496 16.5207 3.48039 17.4161 4.58496 17.4161H12.9069C14.0115 17.4161 14.9069 16.5207 14.9069 15.4161L14.9069 14.9128H15.4154C16.52 14.9128 17.4154 14.0174 17.4154 12.9128L17.4154 4.58398C17.4154 3.47941 16.52 2.58398 15.4154 2.58398H7.08822ZM13.4069 14.9128H7.08821C5.98364 14.9128 5.08822 14.0174 5.08822 12.9128V6.59416H4.58496C4.30882 6.59416 4.08496 6.81801 4.08496 7.09416V15.4161C4.08496 15.6922 4.30882 15.9161 4.58496 15.9161H12.9069C13.183 15.9161 13.4069 15.6922 13.4069 15.4161L13.4069 14.9128Z"
                                                                    fill=""></path>
                                                            </svg>
                                                        </span>
                                                        <span class="check-icon hidden">
                                                            <svg class="fill-current" width="20" height="20" viewBox="0 0 20 20"
                                                                fill="none" xmlns="http://www.w3.org/2000/svg">
                                                                <path fill-rule="evenodd" clip-rule="evenodd"
                                                                    d="M16.707 6.293a1 1 0 00-1.414 0L9 12.586l-2.293-2.293a1 1 0 00-1.414 1.414l3 3a1 1 0 001.414 0l7-7a1 1 0 000-1.414z"
                                                                    fill="currentColor"></path>
                                                            </svg>
                                                        </span>
                                                        <div class="copy-text">Copy</div>
                                                    </button>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-5 py-3 whitespace-nowrap">
                                            <span
                                                class="inline-flex items-center justify-center gap-1 rounded-full bg-green-50 px-2.5 py-0.5 text-xs font-medium text-green-600 dark:bg-green-500/15 dark:text-green-500">
                                                <?= $coupon['discount_type'] == 'percentage' ? 'Percentage' : 'Fixed' ?>
                                            </span>
                                        </td>
                                        <td class="px-5 py-3 whitespace-nowrap">
                                            <span
                                                class="inline-flex rounded-full bg-brand-50 px-2 py-0.5 text-theme-xs font-medium text-brand-500 dark:bg-brand-500/15 dark:text-brand-400">
                                                <?= $coupon['used_count'] ?> / <?= $coupon['max_uses'] ?>
                                                <?php if ($is_fully_used): ?>
                                                    <span class="ml-1 text-red-500">(Used)</span>
                                                <?php endif; ?>
                                            </span>
                                        </td>
                                        <td class="px-5 py-3 text-sm whitespace-nowrap text-gray-500 dark:text-gray-400">
                                            <?= number_format($coupon['discount_value'], 2) ?>
                                            <?= $coupon['discount_type'] == 'percentage' ? '%' : '$' ?>
                                            <?php if ($coupon['discount_type'] == 'percentage' && $coupon['maximum_discount']): ?>
                                                <br>( max <?= number_format($coupon['maximum_discount'], 2) ?> )
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-5 py-3 text-sm whitespace-nowrap text-gray-500 dark:text-gray-400">
                                            <?= date('Y-m-d', strtotime($coupon['expires_at'])) ?>
                                            <?php if ($is_expired): ?>
                                                <span class="ml-1 text-red-500">(Expired)</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-5 py-3 whitespace-nowrap">
                                            <div x-data="{ switcherToggle: <?= $is_active ? 'true' : 'false' ?> }">
                                                <label for="toggle_<?= $coupon['id'] ?>" class="cursor-pointer">
                                                    <div class="relative">
                                                        <input type="checkbox" id="toggle_<?= $coupon['id'] ?>" class="sr-only"
                                                            @change="switcherToggle = !switcherToggle" <?= $is_active ? 'checked' : '' ?>>
                                                        <div class="block h-6 w-11 rounded-full"
                                                            :class="switcherToggle ? 'bg-brand-500 dark:bg-brand-500' : 'bg-gray-200 dark:bg-white/10'">
                                                        </div>
                                                        <div :class="switcherToggle ? 'translate-x-full' : 'translate-x-0'"
                                                            class="shadow-theme-sm absolute top-0.5 left-0.5 h-5 w-5 rounded-full bg-white duration-200 ease-linear">
                                                        </div>
                                                    </div>
                                                </label>
                                            </div>
                                        </td>
                                        <td class="px-5 py-3 whitespace-nowrap">
                                            <div class="flex w-full items-center gap-3">
                                                <a href="coupons.php?delete=<?= $coupon['id'] ?>"
                                                    onclick="return confirm('Are you sure you want to delete this coupon?')"
                                                    class="hover:text-error-500 dark:hover:text-error-500 text-gray-500 dark:text-gray-400">
                                                    <svg class="fill-current" width="21" height="21" viewBox="0 0 21 21"
                                                        fill="none" xmlns="http://www.w3.org/2000/svg">
                                                        <path fill-rule="evenodd" clip-rule="evenodd"
                                                            d="M7.04142 4.29199C7.04142 3.04935 8.04878 2.04199 9.29142 2.04199H11.7081C12.9507 2.04199 13.9581 3.04935 13.9581 4.29199V4.54199H16.1252H17.166C17.5802 4.54199 17.916 4.87778 17.916 5.29199C17.916 5.70621 17.5802 6.04199 17.166 6.04199H16.8752V8.74687V13.7469V16.7087C16.8752 17.9513 15.8678 18.9587 14.6252 18.9587H6.37516C5.13252 18.9587 4.12516 17.9513 4.12516 16.7087V13.7469V8.74687V6.04199H3.8335C3.41928 6.04199 3.0835 5.70621 3.0835 5.29199C3.0835 4.87778 3.41928 4.54199 3.8335 4.54199H4.87516H7.04142V4.29199ZM15.3752 13.7469V8.74687V6.04199H13.9581H13.2081H7.79142H7.04142H5.62516V8.74687V13.7469V16.7087C5.62516 17.1229 5.96095 17.4587 6.37516 17.4587H14.6252C15.0394 17.4587 15.3752 17.1229 15.3752 16.7087V13.7469ZM8.54142 4.54199H12.4581V4.29199C12.4581 3.87778 12.1223 3.54199 11.7081 3.54199H9.29142C8.87721 3.54199 8.54142 3.87778 8.54142 4.29199V4.54199ZM8.8335 8.50033C9.24771 8.50033 9.5835 8.83611 9.5835 9.25033V14.2503C9.5835 14.6645 9.24771 15.0003 8.8335 15.0003C8.41928 15.0003 8.0835 14.6645 8.0835 14.2503V9.25033C8.0835 8.83611 8.41928 8.50033 8.8335 8.50033ZM12.9168 9.25033C12.9168 8.83611 12.581 8.50033 12.1668 8.50033C11.7526 8.50033 11.4168 8.83611 11.4168 9.25033V14.2503C11.4168 14.6645 11.7526 15.0003 12.1668 15.0003C12.581 15.0003 12.9168 14.6645 12.9168 14.2503V9.25033Z"
                                                            fill=""></path>
                                                    </svg>
                                                </a>

                                                </button>

                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile;
                            else: ?>
                                <tr>
                                    <td colspan="8" class="text-center">No coupons available</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="../assets/js/bundle.js"></script>
</body>

</html>