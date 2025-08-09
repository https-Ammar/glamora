<?php
session_start();
require('./config/db.php');

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$userid = $_SESSION['userId'];
$select = $conn->prepare("SELECT * FROM usersadmin WHERE id = ?");
$select->bind_param("i", $userid);
$select->execute();
$fetchname = $select->get_result()->fetch_assoc();
$select->close();
?>





<!doctype html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport"
    content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0" />
  <meta http-equiv="X-UA-Compatible" content="ie=edge" />
  <title>
    Admin
  </title>

  <link href="./assets/css/main.css" rel="stylesheet">

</head>

<body
  x-data="{ page: 'ecommerce', 'loaded': true, 'darkMode': false, 'stickyMenu': false, 'sidebarToggle': false, 'scrollTop': false }"
  x-init="
         darkMode = JSON.parse(localStorage.getItem('darkMode'));
         $watch('darkMode', value => localStorage.setItem('darkMode', JSON.stringify(value)))"
  :class="{'dark bg-gray-900': darkMode === true}">


  <div style="display: none;">
    <?php
    $totalProducts = $conn->query("SELECT COUNT(*) as total FROM products")->fetch_assoc()['total'];
    ?>
    <?= $totalProducts ?>

    <?php
    $totalSales = $conn->query("SELECT SUM(finaltotalprice) as total FROM orders WHERE orderstate = 'done'")->fetch_assoc()['total'];
    ?>
    <?= number_format($totalSales ?? 0, 2) ?>
  </div>



  <div x-show="loaded"
    x-init="window.addEventListener('DOMContentLoaded', () => {setTimeout(() => loaded = false, 500)})"
    class="fixed left-0 top-0 z-999999 flex h-screen w-screen items-center justify-center bg-white dark:bg-black">
    <div class="h-16 w-16 animate-spin rounded-full border-4 border-solid border-brand-500 border-t-transparent">
    </div>
  </div>



  <!-- ===== Page Wrapper Start ===== -->
  <div class="flex h-screen overflow-hidden">

    <aside :class="sidebarToggle ? 'translate-x-0 xl:w-[90px]' : '-translate-x-full'"
      class="sidebar fixed top-0 left-0 z-9999 flex h-screen w-[290px] flex-col overflow-y-auto border-r border-gray-200 bg-white px-5 transition-all duration-300 xl:static xl:translate-x-0 dark:border-gray-800 dark:bg-black"
      @click.outside="sidebarToggle = false">
      <!-- SIDEBAR HEADER -->


      <div class="no-scrollbar flex flex-col overflow-y-auto duration-300 ease-linear mt-8">
        <!-- Sidebar Menu -->
        <nav x-data="{selected: $persist('Dashboard')}">

          <div>
            <h3 class="mb-4 text-xs leading-[20px] text-gray-400 uppercase">
              <span class="menu-group-title" :class="sidebarToggle ? 'xl:hidden' : ''">
                MENU
              </span>

              <svg :class="sidebarToggle ? 'xl:block hidden' : 'hidden'" class="menu-group-icon mx-auto fill-current"
                width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path fill-rule="evenodd" clip-rule="evenodd"
                  d="M5.99915 10.2451C6.96564 10.2451 7.74915 11.0286 7.74915 11.9951V12.0051C7.74915 12.9716 6.96564 13.7551 5.99915 13.7551C5.03265 13.7551 4.24915 12.9716 4.24915 12.0051V11.9951C4.24915 11.0286 5.03265 10.2451 5.99915 10.2451ZM17.9991 10.2451C18.9656 10.2451 19.7491 11.0286 19.7491 11.9951V12.0051C19.7491 12.9716 18.9656 13.7551 17.9991 13.7551C17.0326 13.7551 16.2491 12.9716 16.2491 12.0051V11.9951C16.2491 11.0286 17.0326 10.2451 17.9991 10.2451ZM13.7491 11.9951C13.7491 11.0286 12.9656 10.2451 11.9991 10.2451C11.0326 10.2451 10.2491 11.0286 10.2491 11.9951V12.0051C10.2491 12.9716 11.0326 13.7551 11.9991 13.7551C12.9656 13.7551 13.7491 12.9716 13.7491 12.0051V11.9951Z"
                  fill="currentColor" />
              </svg>
            </h3>

            <ul class="mb-6 flex flex-col gap-1">
              <!-- Menu Item Dashboard -->
              <li>
                <a href="#" @click.prevent="selected = (selected === 'Dashboard' ? '':'Dashboard')"
                  class="menu-item group"
                  :class=" (selected === 'Dashboard') || (page === 'ecommerce' || page === 'analytics' || page === 'marketing' || page === 'crm' || page === 'stocks' || page === 'saas' || page === 'logistics') ? 'menu-item-active' : 'menu-item-inactive'">
                  <svg
                    :class="(selected === 'Dashboard') || (page === 'ecommerce' || page === 'analytics' || page === 'marketing' || page === 'crm' || page === 'stocks') ? 'menu-item-icon-active'  :'menu-item-icon-inactive'"
                    width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path fill-rule="evenodd" clip-rule="evenodd"
                      d="M5.5 3.25C4.25736 3.25 3.25 4.25736 3.25 5.5V8.99998C3.25 10.2426 4.25736 11.25 5.5 11.25H9C10.2426 11.25 11.25 10.2426 11.25 8.99998V5.5C11.25 4.25736 10.2426 3.25 9 3.25H5.5ZM4.75 5.5C4.75 5.08579 5.08579 4.75 5.5 4.75H9C9.41421 4.75 9.75 5.08579 9.75 5.5V8.99998C9.75 9.41419 9.41421 9.74998 9 9.74998H5.5C5.08579 9.74998 4.75 9.41419 4.75 8.99998V5.5ZM5.5 12.75C4.25736 12.75 3.25 13.7574 3.25 15V18.5C3.25 19.7426 4.25736 20.75 5.5 20.75H9C10.2426 20.75 11.25 19.7427 11.25 18.5V15C11.25 13.7574 10.2426 12.75 9 12.75H5.5ZM4.75 15C4.75 14.5858 5.08579 14.25 5.5 14.25H9C9.41421 14.25 9.75 14.5858 9.75 15V18.5C9.75 18.9142 9.41421 19.25 9 19.25H5.5C5.08579 19.25 4.75 18.9142 4.75 18.5V15ZM12.75 5.5C12.75 4.25736 13.7574 3.25 15 3.25H18.5C19.7426 3.25 20.75 4.25736 20.75 5.5V8.99998C20.75 10.2426 19.7426 11.25 18.5 11.25H15C13.7574 11.25 12.75 10.2426 12.75 8.99998V5.5ZM15 4.75C14.5858 4.75 14.25 5.08579 14.25 5.5V8.99998C14.25 9.41419 14.5858 9.74998 15 9.74998H18.5C18.9142 9.74998 19.25 9.41419 19.25 8.99998V5.5C19.25 5.08579 18.9142 4.75 18.5 4.75H15ZM15 12.75C13.7574 12.75 12.75 13.7574 12.75 15V18.5C12.75 19.7426 13.7574 20.75 15 20.75H18.5C19.7426 20.75 20.75 19.7427 20.75 18.5V15C20.75 13.7574 19.7426 12.75 18.5 12.75H15ZM14.25 15C14.25 14.5858 14.5858 14.25 15 14.25H18.5C18.9142 14.25 19.25 14.5858 19.25 15V18.5C19.25 18.9142 18.9142 19.25 18.5 19.25H15C14.5858 19.25 14.25 18.9142 14.25 18.5V15Z"
                      fill="currentColor" />
                  </svg>

                  <span class="menu-item-text" :class="sidebarToggle ? 'xl:hidden' : ''">
                    Dashboard
                  </span>

                  <svg class="menu-item-arrow"
                    :class="[(selected === 'Dashboard') ? 'menu-item-arrow-active' : 'menu-item-arrow-inactive', sidebarToggle ? 'xl:hidden' : '' ]"
                    width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M4.79175 7.39584L10.0001 12.6042L15.2084 7.39585" stroke="currentColor" stroke-width="1.5"
                      stroke-linecap="round" stroke-linejoin="round" />
                  </svg>
                </a>

                <!-- Dropdown Menu Start -->
                <div class="translate transform overflow-hidden"
                  :class="(selected === 'Dashboard') ? 'block' :'hidden'">
                  <ul :class="sidebarToggle ? 'xl:hidden' : 'flex'" class="menu-dropdown mt-2 flex flex-col gap-1 pl-9">

                    <li>
                      <a href="./pages/Products.php" class="menu-dropdown-item group"
                        :class="page === 'analytics' ? 'menu-dropdown-item-active' : 'menu-dropdown-item-inactive'">

                        products

                      </a>
                    </li>
                    <li>
                      <a class="menu-dropdown-item group" href="./pages/add_product.php"
                        :class="page === 'marketing' ? 'menu-dropdown-item-active' : 'menu-dropdown-item-inactive'">
                        Add prodycts
                      </a>
                    </li>
                    <li>
                      <a href="./pages/coupon.php" class="menu-dropdown-item group"
                        :class="page === 'crm' ? 'menu-dropdown-item-active' : 'menu-dropdown-item-inactive'">
                        coupon
                      </a>
                    </li>
                    <li>
                      <a href="stocks.html" class="menu-dropdown-item group"
                        :class="page === 'stocks' ? 'menu-dropdown-item-active' : 'menu-dropdown-item-inactive'">
                        Stocks
                      </a>
                    </li>
                    <li>
                      <a href="saas.html" class="menu-dropdown-item group"
                        :class="page === 'saas' ? 'menu-dropdown-item-active' : 'menu-dropdown-item-inactive'">
                        SaaS
                        <span class="absolute right-3 flex items-center gap-1">
                          <span class="menu-dropdown-badge"
                            :class="page === 'saas' ? 'menu-dropdown-badge-active' : 'menu-dropdown-badge-inactive'">
                            New
                          </span>
                        </span>
                      </a>
                    </li>
                    <li>
                      <a href="logistics.html" class="menu-dropdown-item group"
                        :class="page === 'logistics' ? 'menu-dropdown-item-active' : 'menu-dropdown-item-inactive'">
                        Logistics
                        <span class="absolute right-3 flex items-center gap-1">
                          <span class="menu-dropdown-badge"
                            :class="page === 'logistics' ? 'menu-dropdown-badge-active' : 'menu-dropdown-badge-inactive'">
                            New
                          </span>
                        </span>
                      </a>
                    </li>
                  </ul>
                </div>
                <!-- Dropdown Menu End -->
              </li>
              <!-- Menu Item Dashboard -->




            </ul>
          </div>

          <!-- Support Group -->
          <div>
            <h3 class="mb-4 text-xs leading-[20px] text-gray-400 uppercase">
              <span class="menu-group-title" :class="sidebarToggle ? 'xl:hidden' : ''">
                Support
              </span>

              <svg :class="sidebarToggle ? 'xl:block hidden' : 'hidden'" class="menu-group-icon mx-auto fill-current"
                width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path fill-rule="evenodd" clip-rule="evenodd"
                  d="M5.99915 10.2451C6.96564 10.2451 7.74915 11.0286 7.74915 11.9951V12.0051C7.74915 12.9716 6.96564 13.7551 5.99915 13.7551C5.03265 13.7551 4.24915 12.9716 4.24915 12.0051V11.9951C4.24915 11.0286 5.03265 10.2451 5.99915 10.2451ZM17.9991 10.2451C18.9656 10.2451 19.7491 11.0286 19.7491 11.9951V12.0051C19.7491 12.9716 18.9656 13.7551 17.9991 13.7551C17.0326 13.7551 16.2491 12.9716 16.2491 12.0051V11.9951C16.2491 11.0286 17.0326 10.2451 17.9991 10.2451ZM13.7491 11.9951C13.7491 11.0286 12.9656 10.2451 11.9991 10.2451C11.0326 10.2451 10.2491 11.0286 10.2491 11.9951V12.0051C10.2491 12.9716 11.0326 13.7551 11.9991 13.7551C12.9656 13.7551 13.7491 12.9716 13.7491 12.0051V11.9951Z"
                  fill="currentColor" />
              </svg>
            </h3>

            <ul class="mb-6 flex flex-col gap-1">
              <!-- Menu Item Chat -->
              <li>
                <a href="chat.html" @click="selected = (selected === 'Chat' ? '':'Chat')" class="menu-item group"
                  :class=" (selected === 'Chat') && (page === 'chat') ? 'menu-item-active' : 'menu-item-inactive'">
                  <svg
                    :class="(selected === 'Chat') && (page === 'chat') ? 'menu-item-icon-active'  :'menu-item-icon-inactive'"
                    width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path fill-rule="evenodd" clip-rule="evenodd"
                      d="M4.00002 12.0957C4.00002 7.67742 7.58174 4.0957 12 4.0957C16.4183 4.0957 20 7.67742 20 12.0957C20 16.514 16.4183 20.0957 12 20.0957H5.06068L6.34317 18.8132C6.48382 18.6726 6.56284 18.4818 6.56284 18.2829C6.56284 18.084 6.48382 17.8932 6.34317 17.7526C4.89463 16.304 4.00002 14.305 4.00002 12.0957ZM12 2.5957C6.75332 2.5957 2.50002 6.849 2.50002 12.0957C2.50002 14.4488 3.35633 16.603 4.77303 18.262L2.71969 20.3154C2.50519 20.5299 2.44103 20.8525 2.55711 21.1327C2.6732 21.413 2.94668 21.5957 3.25002 21.5957H12C17.2467 21.5957 21.5 17.3424 21.5 12.0957C21.5 6.849 17.2467 2.5957 12 2.5957ZM7.62502 10.8467C6.93467 10.8467 6.37502 11.4063 6.37502 12.0967C6.37502 12.787 6.93467 13.3467 7.62502 13.3467H7.62512C8.31548 13.3467 8.87512 12.787 8.87512 12.0967C8.87512 11.4063 8.31548 10.8467 7.62512 10.8467H7.62502ZM10.75 12.0967C10.75 11.4063 11.3097 10.8467 12 10.8467H12.0001C12.6905 10.8467 13.2501 11.4063 13.2501 12.0967C13.2501 12.787 12.6905 13.3467 12.0001 13.3467H12C11.3097 13.3467 10.75 12.787 10.75 12.0967ZM16.375 10.8467C15.6847 10.8467 15.125 11.4063 15.125 12.0967C15.125 12.787 15.6847 13.3467 16.375 13.3467H16.3751C17.0655 13.3467 17.6251 12.787 17.6251 12.0967C17.6251 11.4063 17.0655 10.8467 16.3751 10.8467H16.375Z"
                      fill="currentColor" />
                  </svg>

                  <span class="menu-item-text" :class="sidebarToggle ? 'xl:hidden' : ''">
                    Chat
                  </span>
                </a>
              </li>
              <!-- Menu Item Chat -->

              <!-- Menu Item Tables -->
              <li>
                <a href="#" @click.prevent="selected = (selected === 'Support' ? '':'Support')" class="menu-item group"
                  :class="(selected === 'Support') || (page === 'ticketLists' || page === 'ticketReply') ? 'menu-item-active' : 'menu-item-inactive'">
                  <svg
                    :class="(selected === 'Support') || (page === 'ticketLists' || page === 'ticketLists') ? 'menu-item-icon-active'  :'menu-item-icon-inactive'"
                    width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path
                      d="M20 17.0518V12C20 7.58174 16.4183 4 12 4C7.58168 4 3.99994 7.58174 3.99994 12V17.0518M19.9998 14.041V19.75C19.9998 20.5784 19.3282 21.25 18.4998 21.25H13.9998M6.5 18.75H5.5C4.67157 18.75 4 18.0784 4 17.25V13.75C4 12.9216 4.67157 12.25 5.5 12.25H6.5C7.32843 12.25 8 12.9216 8 13.75V17.25C8 18.0784 7.32843 18.75 6.5 18.75ZM17.4999 18.75H18.4999C19.3284 18.75 19.9999 18.0784 19.9999 17.25V13.75C19.9999 12.9216 19.3284 12.25 18.4999 12.25H17.4999C16.6715 12.25 15.9999 12.9216 15.9999 13.75V17.25C15.9999 18.0784 16.6715 18.75 17.4999 18.75Z"
                      stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                  </svg>

                  <span class="menu-item-text" :class="sidebarToggle ? 'xl:hidden' : ''">
                    Support Ticket
                  </span>
                  <span :class="sidebarToggle ? 'xl:hidden' : ''" class="absolute right-10 flex items-center gap-1">
                    <span class="menu-dropdown-badge"
                      :class="page === 'products' ? 'menu-dropdown-badge-active' : 'menu-dropdown-badge-inactive'">
                      New
                    </span>
                  </span>

                  <svg class="menu-item-arrow absolute top-1/2 right-2.5 -translate-y-1/2 stroke-current"
                    :class="[(selected === 'Support') ? 'menu-item-arrow-active' : 'menu-item-arrow-inactive', sidebarToggle ? 'xl:hidden' : '' ]"
                    width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M4.79175 7.39584L10.0001 12.6042L15.2084 7.39585" stroke="currentColor" stroke-width="1.5"
                      stroke-linecap="round" stroke-linejoin="round" />
                  </svg>
                </a>

                <!-- Dropdown Menu Start -->
                <div class="translate transform overflow-hidden" :class="(selected === 'Support') ? 'block' :'hidden'">
                  <ul :class="sidebarToggle ? 'xl:hidden' : 'flex'" class="menu-dropdown mt-2 flex flex-col gap-1 pl-9">
                    <li>
                      <a href="support-tickets.html" class="menu-dropdown-item group"
                        :class="page === 'ticketLists' ? 'menu-dropdown-item-active' : 'menu-dropdown-item-inactive'">
                        Ticket List
                      </a>
                    </li>
                    <li>
                      <a href="support-ticket-reply.html" class="menu-dropdown-item group"
                        :class="page === 'ticketReply' ? 'menu-dropdown-item-active' : 'menu-dropdown-item-inactive'">
                        Ticket Reply
                      </a>
                    </li>
                  </ul>
                </div>
                <!-- Dropdown Menu End -->
              </li>
              <!-- Menu Item Tables -->

              <!-- Menu Item Inbox -->
              <li>
                <a href="#" @click.prevent="selected = (selected === 'Email' ? '':'Email')" class="menu-item group"
                  :class="(selected === 'Email') || (page === 'inbox' || page === 'inboxDetails') ? 'menu-item-active' : 'menu-item-inactive'">
                  <svg
                    :class="(selected === 'Email') || (page === 'inbox' || page === 'inboxDetails') ? 'menu-item-icon-active'  :'menu-item-icon-inactive'"
                    width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path fill-rule="evenodd" clip-rule="evenodd"
                      d="M3.5 8.187V17.25C3.5 17.6642 3.83579 18 4.25 18H19.75C20.1642 18 20.5 17.6642 20.5 17.25V8.18747L13.2873 13.2171C12.5141 13.7563 11.4866 13.7563 10.7134 13.2171L3.5 8.187ZM20.5 6.2286C20.5 6.23039 20.5 6.23218 20.5 6.23398V6.24336C20.4976 6.31753 20.4604 6.38643 20.3992 6.42905L12.4293 11.9867C12.1716 12.1664 11.8291 12.1664 11.5713 11.9867L3.60116 6.42885C3.538 6.38481 3.50035 6.31268 3.50032 6.23568C3.50028 6.10553 3.60577 6 3.73592 6H20.2644C20.3922 6 20.4963 6.10171 20.5 6.2286ZM22 6.25648V17.25C22 18.4926 20.9926 19.5 19.75 19.5H4.25C3.00736 19.5 2 18.4926 2 17.25V6.23398C2 6.22371 2.00021 6.2135 2.00061 6.20333C2.01781 5.25971 2.78812 4.5 3.73592 4.5H20.2644C21.2229 4.5 22 5.27697 22.0001 6.23549C22.0001 6.24249 22.0001 6.24949 22 6.25648Z"
                      fill="currentColor" />
                  </svg>

                  <span class="menu-item-text" :class="sidebarToggle ? 'xl:hidden' : ''">
                    Email
                  </span>

                  <svg class="menu-item-arrow absolute top-1/2 right-2.5 -translate-y-1/2 stroke-current"
                    :class="[(selected === 'Email') ? 'menu-item-arrow-active' : 'menu-item-arrow-inactive', sidebarToggle ? 'xl:hidden' : '' ]"
                    width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M4.79175 7.39584L10.0001 12.6042L15.2084 7.39585" stroke="currentColor" stroke-width="1.5"
                      stroke-linecap="round" stroke-linejoin="round" />
                  </svg>
                </a>

                <!-- Dropdown Menu Start -->
                <div class="translate transform overflow-hidden" :class="(selected === 'Email') ? 'block' :'hidden'">
                  <ul :class="sidebarToggle ? 'xl:hidden' : 'flex'" class="menu-dropdown mt-2 flex flex-col gap-1 pl-9">
                    <li>
                      <a href="inbox.html" class="menu-dropdown-item group"
                        :class="page === 'inbox' ? 'menu-dropdown-item-active' : 'menu-dropdown-item-inactive'">
                        Inbox
                      </a>
                    </li>
                    <li>
                      <a href="inbox-details.html" class="menu-dropdown-item group"
                        :class="page === 'inboxDetails' ? 'menu-dropdown-item-active' : 'menu-dropdown-item-inactive'">
                        Details
                      </a>
                    </li>
                  </ul>
                </div>
                <!-- Dropdown Menu End -->
              </li>
              <!-- Menu Item Inbox -->
            </ul>
          </div>

          <!-- Others Group -->
          <div>
            <h3 class="mb-4 text-xs leading-[20px] text-gray-400 uppercase">
              <span class="menu-group-title" :class="sidebarToggle ? 'xl:hidden' : ''">
                others
              </span>

              <svg :class="sidebarToggle ? 'xl:block hidden' : 'hidden'" class="menu-group-icon mx-auto fill-current"
                width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path fill-rule="evenodd" clip-rule="evenodd"
                  d="M5.99915 10.2451C6.96564 10.2451 7.74915 11.0286 7.74915 11.9951V12.0051C7.74915 12.9716 6.96564 13.7551 5.99915 13.7551C5.03265 13.7551 4.24915 12.9716 4.24915 12.0051V11.9951C4.24915 11.0286 5.03265 10.2451 5.99915 10.2451ZM17.9991 10.2451C18.9656 10.2451 19.7491 11.0286 19.7491 11.9951V12.0051C19.7491 12.9716 18.9656 13.7551 17.9991 13.7551C17.0326 13.7551 16.2491 12.9716 16.2491 12.0051V11.9951C16.2491 11.0286 17.0326 10.2451 17.9991 10.2451ZM13.7491 11.9951C13.7491 11.0286 12.9656 10.2451 11.9991 10.2451C11.0326 10.2451 10.2491 11.0286 10.2491 11.9951V12.0051C10.2491 12.9716 11.0326 13.7551 11.9991 13.7551C12.9656 13.7551 13.7491 12.9716 13.7491 12.0051V11.9951Z"
                  fill="currentColor" />
              </svg>
            </h3>

            <ul class="mb-6 flex flex-col gap-1">
              <!-- Menu Item Charts -->
              <li>
                <a href="#" @click.prevent="selected = (selected === 'Charts' ? '':'Charts')" class="menu-item group"
                  :class="(selected === 'Charts') || (page === 'lineChart' || page === 'barChart' || page === 'pieChart') ? 'menu-item-active' : 'menu-item-inactive'">
                  <svg
                    :class="(selected === 'Charts') || (page === 'lineChart' || page === 'barChart' || page === 'pieChart') ? 'menu-item-icon-active'  :'menu-item-icon-inactive'"
                    width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path fill-rule="evenodd" clip-rule="evenodd"
                      d="M12 2C11.5858 2 11.25 2.33579 11.25 2.75V12C11.25 12.4142 11.5858 12.75 12 12.75H21.25C21.6642 12.75 22 12.4142 22 12C22 6.47715 17.5228 2 12 2ZM12.75 11.25V3.53263C13.2645 3.57761 13.7659 3.66843 14.25 3.80098V3.80099C15.6929 4.19606 16.9827 4.96184 18.0104 5.98959C19.0382 7.01734 19.8039 8.30707 20.199 9.75C20.3316 10.2341 20.4224 10.7355 20.4674 11.25H12.75ZM2 12C2 7.25083 5.31065 3.27489 9.75 2.25415V3.80099C6.14748 4.78734 3.5 8.0845 3.5 12C3.5 16.6944 7.30558 20.5 12 20.5C15.9155 20.5 19.2127 17.8525 20.199 14.25H21.7459C20.7251 18.6894 16.7492 22 12 22C6.47715 22 2 17.5229 2 12Z"
                      fill="currentColor" />
                  </svg>

                  <span class="menu-item-text" :class="sidebarToggle ? 'xl:hidden' : ''">
                    Charts
                  </span>

                  <svg class="menu-item-arrow absolute top-1/2 right-2.5 -translate-y-1/2 stroke-current"
                    :class="[(selected === 'Charts') ? 'menu-item-arrow-active' : 'menu-item-arrow-inactive', sidebarToggle ? 'xl:hidden' : '' ]"
                    width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M4.79175 7.39584L10.0001 12.6042L15.2084 7.39585" stroke="currentColor" stroke-width="1.5"
                      stroke-linecap="round" stroke-linejoin="round" />
                  </svg>
                </a>

                <!-- Dropdown Menu Start -->
                <div class="translate transform overflow-hidden" :class="(selected === 'Charts') ? 'block' :'hidden'">
                  <ul :class="sidebarToggle ? 'lg:hidden' : 'flex'" class="menu-dropdown mt-2 flex flex-col gap-1 pl-9">
                    <li>
                      <a href="line-chart.html" class="menu-dropdown-item group"
                        :class="page === 'lineChart' ? 'menu-dropdown-item-active' : 'menu-dropdown-item-inactive'">
                        Line Chart
                      </a>
                    </li>
                    <li>
                      <a href="bar-chart.html" class="menu-dropdown-item group"
                        :class="page === 'barChart' ? 'menu-dropdown-item-active' : 'menu-dropdown-item-inactive'">
                        Bar Chart
                      </a>
                    </li>
                    <li>
                      <a href="pie-chart.html" class="menu-dropdown-item group"
                        :class="page === 'pieChart' ? 'menu-dropdown-item-active' : 'menu-dropdown-item-inactive'">
                        Pie Chart
                      </a>
                    </li>
                  </ul>
                </div>
                <!-- Dropdown Menu End -->
              </li>

              <li>
                <a href="#" @click.prevent="selected = (selected === 'Authentication' ? '':'Authentication')"
                  class="menu-item group"
                  :class="(selected === 'Authentication') || (page === 'basicChart' || page === 'advancedChart') ? 'menu-item-active' : 'menu-item-inactive'">
                  <svg
                    :class="(selected === 'Authentication') || (page === 'basicChart' || page === 'advancedChart') ? 'menu-item-icon-active'  :'menu-item-icon-inactive'"
                    width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path fill-rule="evenodd" clip-rule="evenodd"
                      d="M14 2.75C14 2.33579 14.3358 2 14.75 2C15.1642 2 15.5 2.33579 15.5 2.75V5.73291L17.75 5.73291H19C19.4142 5.73291 19.75 6.0687 19.75 6.48291C19.75 6.89712 19.4142 7.23291 19 7.23291H18.5L18.5 12.2329C18.5 15.5691 15.9866 18.3183 12.75 18.6901V21.25C12.75 21.6642 12.4142 22 12 22C11.5858 22 11.25 21.6642 11.25 21.25V18.6901C8.01342 18.3183 5.5 15.5691 5.5 12.2329L5.5 7.23291H5C4.58579 7.23291 4.25 6.89712 4.25 6.48291C4.25 6.0687 4.58579 5.73291 5 5.73291L6.25 5.73291L8.5 5.73291L8.5 2.75C8.5 2.33579 8.83579 2 9.25 2C9.66421 2 10 2.33579 10 2.75L10 5.73291L14 5.73291V2.75ZM7 7.23291L7 12.2329C7 14.9943 9.23858 17.2329 12 17.2329C14.7614 17.2329 17 14.9943 17 12.2329L17 7.23291L7 7.23291Z"
                      fill="currentColor" />
                  </svg>

                  <span class="menu-item-text" :class="sidebarToggle ? 'xl:hidden' : ''">
                    Authentication
                  </span>

                  <svg class="menu-item-arrow absolute top-1/2 right-2.5 -translate-y-1/2 stroke-current"
                    :class="[(selected === 'Authentication') ? 'menu-item-arrow-active' : 'menu-item-arrow-inactive', sidebarToggle ? 'xl:hidden' : '' ]"
                    width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M4.79175 7.39584L10.0001 12.6042L15.2084 7.39585" stroke="currentColor" stroke-width="1.5"
                      stroke-linecap="round" stroke-linejoin="round" />
                  </svg>
                </a>

                <!-- Dropdown Menu Start -->
                <div class="translate transform overflow-hidden"
                  :class="(selected === 'Authentication') ? 'block' :'hidden'">
                  <ul :class="sidebarToggle ? 'lg:hidden' : 'flex'" class="menu-dropdown mt-2 flex flex-col gap-1 pl-9">
                    <li>
                      <a href="signin.html" class="menu-dropdown-item group"
                        :class="page === 'signin' ? 'menu-dropdown-item-active' : 'menu-dropdown-item-inactive'">
                        Sign In
                      </a>
                    </li>
                    <li>
                      <a href="signup.html" class="menu-dropdown-item group"
                        :class="page === 'signup' ? 'menu-dropdown-item-active' : 'menu-dropdown-item-inactive'">
                        Sign Up
                      </a>
                    </li>
                    <li>
                      <a href="reset-password.html" class="menu-dropdown-item group"
                        :class="page === 'resetPassword' ? 'menu-dropdown-item-active' : 'menu-dropdown-item-inactive'">
                        Reset Password
                      </a>
                    </li>
                    <li>
                      <a href="two-step-verification.html" class="menu-dropdown-item group"
                        :class="page === 'twoStepVerification' ? 'menu-dropdown-item-active' : 'menu-dropdown-item-inactive'">
                        Two Step Verification
                      </a>
                    </li>
                  </ul>
                </div>
                <!-- Dropdown Menu End -->
              </li>
              <!-- Menu Item Authentication -->
            </ul>
          </div>
        </nav>
        <!-- Sidebar Menu -->


      </div>
    </aside>



    <div class="relative flex flex-1 flex-col overflow-x-hidden overflow-y-auto">
      <div :class="sidebarToggle ? 'block xl:hidden' : 'hidden'" class="fixed z-50 h-screen w-full bg-gray-900/50">
      </div>

      <main>
        <!-- ===== Header Start ===== -->
        <header x-data="{menuToggle: false}"
          class="sticky top-0 z-99999 flex w-full border-gray-200 bg-white xl:border-b dark:border-gray-800 dark:bg-gray-900">
          <div class="flex grow flex-col items-center justify-between xl:flex-row xl:px-6">
            <div
              class="flex w-full items-center justify-between gap-2 border-b border-gray-200 px-3 py-3 sm:gap-4 lg:py-4 xl:justify-normal xl:border-b-0 xl:px-0 dark:border-gray-800">
              <!-- Hamburger Toggle BTN -->
              <button
                :class="sidebarToggle ? 'xl:bg-transparent dark:xl:bg-transparent bg-gray-100 dark:bg-gray-800' : ''"
                class="z-99999 flex h-10 w-10 items-center justify-center rounded-lg border-gray-200 text-gray-500 xl:h-11 xl:w-11 xl:border dark:border-gray-800 dark:text-gray-400"
                @click.stop="sidebarToggle = !sidebarToggle">
                <svg class="hidden fill-current xl:block" width="16" height="12" viewBox="0 0 16 12" fill="none"
                  xmlns="http://www.w3.org/2000/svg">
                  <path fill-rule="evenodd" clip-rule="evenodd"
                    d="M0.583252 1C0.583252 0.585788 0.919038 0.25 1.33325 0.25H14.6666C15.0808 0.25 15.4166 0.585786 15.4166 1C15.4166 1.41421 15.0808 1.75 14.6666 1.75L1.33325 1.75C0.919038 1.75 0.583252 1.41422 0.583252 1ZM0.583252 11C0.583252 10.5858 0.919038 10.25 1.33325 10.25L14.6666 10.25C15.0808 10.25 15.4166 10.5858 15.4166 11C15.4166 11.4142 15.0808 11.75 14.6666 11.75L1.33325 11.75C0.919038 11.75 0.583252 11.4142 0.583252 11ZM1.33325 5.25C0.919038 5.25 0.583252 5.58579 0.583252 6C0.583252 6.41421 0.919038 6.75 1.33325 6.75L7.99992 6.75C8.41413 6.75 8.74992 6.41421 8.74992 6C8.74992 5.58579 8.41413 5.25 7.99992 5.25L1.33325 5.25Z"
                    fill="" />
                </svg>

                <svg :class="sidebarToggle ? 'hidden' : 'block xl:hidden'" class="fill-current xl:hidden" width="24"
                  height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path fill-rule="evenodd" clip-rule="evenodd"
                    d="M3.25 6C3.25 5.58579 3.58579 5.25 4 5.25L20 5.25C20.4142 5.25 20.75 5.58579 20.75 6C20.75 6.41421 20.4142 6.75 20 6.75L4 6.75C3.58579 6.75 3.25 6.41422 3.25 6ZM3.25 18C3.25 17.5858 3.58579 17.25 4 17.25L20 17.25C20.4142 17.25 20.75 17.5858 20.75 18C20.75 18.4142 20.4142 18.75 20 18.75L4 18.75C3.58579 18.75 3.25 18.4142 3.25 18ZM4 11.25C3.58579 11.25 3.25 11.5858 3.25 12C3.25 12.4142 3.58579 12.75 4 12.75L12 12.75C12.4142 12.75 12.75 12.4142 12.75 12C12.75 11.5858 12.4142 11.25 12 11.25L4 11.25Z"
                    fill="" />
                </svg>

                <!-- cross icon -->
                <svg :class="sidebarToggle ? 'block xl:hidden' : 'hidden'" class="fill-current" width="24" height="24"
                  viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path fill-rule="evenodd" clip-rule="evenodd"
                    d="M6.21967 7.28131C5.92678 6.98841 5.92678 6.51354 6.21967 6.22065C6.51256 5.92775 6.98744 5.92775 7.28033 6.22065L11.999 10.9393L16.7176 6.22078C17.0105 5.92789 17.4854 5.92788 17.7782 6.22078C18.0711 6.51367 18.0711 6.98855 17.7782 7.28144L13.0597 12L17.7782 16.7186C18.0711 17.0115 18.0711 17.4863 17.7782 17.7792C17.4854 18.0721 17.0105 18.0721 16.7176 17.7792L11.999 13.0607L7.28033 17.7794C6.98744 18.0722 6.51256 18.0722 6.21967 17.7794C5.92678 17.4865 5.92678 17.0116 6.21967 16.7187L10.9384 12L6.21967 7.28131Z"
                    fill="" />
                </svg>
              </button>
              <!-- Hamburger Toggle BTN -->

              <a href="index.html" class="xl:hidden">
                <img class="dark:hidden" src="src/images/logo/logo.svg" alt="Logo" />
                <img class="hidden dark:block" src="src/images/logo/logo-dark.svg" alt="Logo" />
              </a>

              <!-- Application nav menu button -->
              <button
                class="z-99999 flex h-10 w-10 items-center justify-center rounded-lg text-gray-700 hover:bg-gray-100 xl:hidden dark:text-gray-400 dark:hover:bg-gray-800"
                :class="menuToggle ? 'bg-gray-100 dark:bg-gray-800' : ''" @click.stop="menuToggle = !menuToggle">
                <svg class="fill-current" width="24" height="24" viewBox="0 0 24 24" fill="none"
                  xmlns="http://www.w3.org/2000/svg">
                  <path fill-rule="evenodd" clip-rule="evenodd"
                    d="M5.99902 10.4951C6.82745 10.4951 7.49902 11.1667 7.49902 11.9951V12.0051C7.49902 12.8335 6.82745 13.5051 5.99902 13.5051C5.1706 13.5051 4.49902 12.8335 4.49902 12.0051V11.9951C4.49902 11.1667 5.1706 10.4951 5.99902 10.4951ZM17.999 10.4951C18.8275 10.4951 19.499 11.1667 19.499 11.9951V12.0051C19.499 12.8335 18.8275 13.5051 17.999 13.5051C17.1706 13.5051 16.499 12.8335 16.499 12.0051V11.9951C16.499 11.1667 17.1706 10.4951 17.999 10.4951ZM13.499 11.9951C13.499 11.1667 12.8275 10.4951 11.999 10.4951C11.1706 10.4951 10.499 11.1667 10.499 11.9951V12.0051C10.499 12.8335 11.1706 13.5051 11.999 13.5051C12.8275 13.5051 13.499 12.8335 13.499 12.0051V11.9951Z"
                    fill="" />
                </svg>
              </button>
              <!-- Application nav menu button -->

              <div class="hidden xl:block">
                <form>
                  <div class="relative">
                    <span class="pointer-events-none absolute top-1/2 left-4 -translate-y-1/2">
                      <svg class="fill-gray-500 dark:fill-gray-400" width="20" height="20" viewBox="0 0 20 20"
                        fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" clip-rule="evenodd"
                          d="M3.04175 9.37363C3.04175 5.87693 5.87711 3.04199 9.37508 3.04199C12.8731 3.04199 15.7084 5.87693 15.7084 9.37363C15.7084 12.8703 12.8731 15.7053 9.37508 15.7053C5.87711 15.7053 3.04175 12.8703 3.04175 9.37363ZM9.37508 1.54199C5.04902 1.54199 1.54175 5.04817 1.54175 9.37363C1.54175 13.6991 5.04902 17.2053 9.37508 17.2053C11.2674 17.2053 13.003 16.5344 14.357 15.4176L17.177 18.238C17.4699 18.5309 17.9448 18.5309 18.2377 18.238C18.5306 17.9451 18.5306 17.4703 18.2377 17.1774L15.418 14.3573C16.5365 13.0033 17.2084 11.2669 17.2084 9.37363C17.2084 5.04817 13.7011 1.54199 9.37508 1.54199Z"
                          fill="" />
                      </svg>
                    </span>
                    <input id="search-input" type="text" placeholder="Search or type command..."
                      class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-200 bg-transparent py-2.5 pr-14 pl-12 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden xl:w-[430px] dark:border-gray-800 dark:bg-gray-900 dark:bg-white/[0.03] dark:text-white/90 dark:placeholder:text-white/30" />

                    <button id="search-button"
                      class="absolute top-1/2 right-2.5 inline-flex -translate-y-1/2 items-center gap-0.5 rounded-lg border border-gray-200 bg-gray-50 px-[7px] py-[4.5px] text-xs -tracking-[0.2px] text-gray-500 dark:border-gray-800 dark:bg-white/[0.03] dark:text-gray-400">
                      <span> ⌘ </span>
                      <span> K </span>
                    </button>
                  </div>
                </form>
              </div>
            </div>

            <div :class="menuToggle ? 'flex' : 'hidden'"
              class="shadow-theme-md w-full items-center justify-between gap-4 px-5 py-4 xl:flex xl:justify-end xl:px-0 xl:shadow-none">
              <div class="2xsm:gap-3 flex items-center gap-2">
                <!-- Dark Mode Toggler -->
                <button
                  class="hover:text-dark-900 relative flex h-11 w-11 items-center justify-center rounded-full border border-gray-200 bg-white text-gray-500 transition-colors hover:bg-gray-100 hover:text-gray-700 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-white"
                  @click.prevent="darkMode = !darkMode">
                  <svg class="hidden dark:block" width="20" height="20" viewBox="0 0 20 20" fill="none"
                    xmlns="http://www.w3.org/2000/svg">
                    <path fill-rule="evenodd" clip-rule="evenodd"
                      d="M9.99998 1.5415C10.4142 1.5415 10.75 1.87729 10.75 2.2915V3.5415C10.75 3.95572 10.4142 4.2915 9.99998 4.2915C9.58577 4.2915 9.24998 3.95572 9.24998 3.5415V2.2915C9.24998 1.87729 9.58577 1.5415 9.99998 1.5415ZM10.0009 6.79327C8.22978 6.79327 6.79402 8.22904 6.79402 10.0001C6.79402 11.7712 8.22978 13.207 10.0009 13.207C11.772 13.207 13.2078 11.7712 13.2078 10.0001C13.2078 8.22904 11.772 6.79327 10.0009 6.79327ZM5.29402 10.0001C5.29402 7.40061 7.40135 5.29327 10.0009 5.29327C12.6004 5.29327 14.7078 7.40061 14.7078 10.0001C14.7078 12.5997 12.6004 14.707 10.0009 14.707C7.40135 14.707 5.29402 12.5997 5.29402 10.0001ZM15.9813 5.08035C16.2742 4.78746 16.2742 4.31258 15.9813 4.01969C15.6884 3.7268 15.2135 3.7268 14.9207 4.01969L14.0368 4.90357C13.7439 5.19647 13.7439 5.67134 14.0368 5.96423C14.3297 6.25713 14.8045 6.25713 15.0974 5.96423L15.9813 5.08035ZM18.4577 10.0001C18.4577 10.4143 18.1219 10.7501 17.7077 10.7501H16.4577C16.0435 10.7501 15.7077 10.4143 15.7077 10.0001C15.7077 9.58592 16.0435 9.25013 16.4577 9.25013H17.7077C18.1219 9.25013 18.4577 9.58592 18.4577 10.0001ZM14.9207 15.9806C15.2135 16.2735 15.6884 16.2735 15.9813 15.9806C16.2742 15.6877 16.2742 15.2128 15.9813 14.9199L15.0974 14.036C14.8045 13.7431 14.3297 13.7431 14.0368 14.036C13.7439 14.3289 13.7439 14.8038 14.0368 15.0967L14.9207 15.9806ZM9.99998 15.7088C10.4142 15.7088 10.75 16.0445 10.75 16.4588V17.7088C10.75 18.123 10.4142 18.4588 9.99998 18.4588C9.58577 18.4588 9.24998 18.123 9.24998 17.7088V16.4588C9.24998 16.0445 9.58577 15.7088 9.99998 15.7088ZM5.96356 15.0972C6.25646 14.8043 6.25646 14.3295 5.96356 14.0366C5.67067 13.7437 5.1958 13.7437 4.9029 14.0366L4.01902 14.9204C3.72613 15.2133 3.72613 15.6882 4.01902 15.9811C4.31191 16.274 4.78679 16.274 5.07968 15.9811L5.96356 15.0972ZM4.29224 10.0001C4.29224 10.4143 3.95645 10.7501 3.54224 10.7501H2.29224C1.87802 10.7501 1.54224 10.4143 1.54224 10.0001C1.54224 9.58592 1.87802 9.25013 2.29224 9.25013H3.54224C3.95645 9.25013 4.29224 9.58592 4.29224 10.0001ZM4.9029 5.9637C5.1958 6.25659 5.67067 6.25659 5.96356 5.9637C6.25646 5.6708 6.25646 5.19593 5.96356 4.90303L5.07968 4.01915C4.78679 3.72626 4.31191 3.72626 4.01902 4.01915C3.72613 4.31204 3.72613 4.78692 4.01902 5.07981L4.9029 5.9637Z"
                      fill="currentColor" />
                  </svg>
                  <svg class="dark:hidden" width="20" height="20" viewBox="0 0 20 20" fill="none"
                    xmlns="http://www.w3.org/2000/svg">
                    <path
                      d="M17.4547 11.97L18.1799 12.1611C18.265 11.8383 18.1265 11.4982 17.8401 11.3266C17.5538 11.1551 17.1885 11.1934 16.944 11.4207L17.4547 11.97ZM8.0306 2.5459L8.57989 3.05657C8.80718 2.81209 8.84554 2.44682 8.67398 2.16046C8.50243 1.8741 8.16227 1.73559 7.83948 1.82066L8.0306 2.5459ZM12.9154 13.0035C9.64678 13.0035 6.99707 10.3538 6.99707 7.08524H5.49707C5.49707 11.1823 8.81835 14.5035 12.9154 14.5035V13.0035ZM16.944 11.4207C15.8869 12.4035 14.4721 13.0035 12.9154 13.0035V14.5035C14.8657 14.5035 16.6418 13.7499 17.9654 12.5193L16.944 11.4207ZM16.7295 11.7789C15.9437 14.7607 13.2277 16.9586 10.0003 16.9586V18.4586C13.9257 18.4586 17.2249 15.7853 18.1799 12.1611L16.7295 11.7789ZM10.0003 16.9586C6.15734 16.9586 3.04199 13.8433 3.04199 10.0003H1.54199C1.54199 14.6717 5.32892 18.4586 10.0003 18.4586V16.9586ZM3.04199 10.0003C3.04199 6.77289 5.23988 4.05695 8.22173 3.27114L7.83948 1.82066C4.21532 2.77574 1.54199 6.07486 1.54199 10.0003H3.04199ZM6.99707 7.08524C6.99707 5.52854 7.5971 4.11366 8.57989 3.05657L7.48132 2.03522C6.25073 3.35885 5.49707 5.13487 5.49707 7.08524H6.99707Z"
                      fill="currentColor" />
                  </svg>
                </button>
                <!-- Dark Mode Toggler -->

                <!-- Notification Menu Area -->
                <div class="relative" x-data="{ dropdownOpen: false, notifying: true }"
                  @click.outside="dropdownOpen = false">
                  <button
                    class="hover:text-dark-900 relative flex h-11 w-11 items-center justify-center rounded-full border border-gray-200 bg-white text-gray-500 transition-colors hover:bg-gray-100 hover:text-gray-700 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-white"
                    @click.prevent="dropdownOpen = ! dropdownOpen; notifying = false">
                    <span :class="!notifying ? 'hidden' : 'flex'"
                      class="absolute top-0.5 right-0 z-1 h-2 w-2 rounded-full bg-orange-400">
                      <span
                        class="absolute -z-1 inline-flex h-full w-full animate-ping rounded-full bg-orange-400 opacity-75"></span>
                    </span>
                    <svg class="fill-current" width="20" height="20" viewBox="0 0 20 20" fill="none"
                      xmlns="http://www.w3.org/2000/svg">
                      <path fill-rule="evenodd" clip-rule="evenodd"
                        d="M10.75 2.29248C10.75 1.87827 10.4143 1.54248 10 1.54248C9.58583 1.54248 9.25004 1.87827 9.25004 2.29248V2.83613C6.08266 3.20733 3.62504 5.9004 3.62504 9.16748V14.4591H3.33337C2.91916 14.4591 2.58337 14.7949 2.58337 15.2091C2.58337 15.6234 2.91916 15.9591 3.33337 15.9591H4.37504H15.625H16.6667C17.0809 15.9591 17.4167 15.6234 17.4167 15.2091C17.4167 14.7949 17.0809 14.4591 16.6667 14.4591H16.375V9.16748C16.375 5.9004 13.9174 3.20733 10.75 2.83613V2.29248ZM14.875 14.4591V9.16748C14.875 6.47509 12.6924 4.29248 10 4.29248C7.30765 4.29248 5.12504 6.47509 5.12504 9.16748V14.4591H14.875ZM8.00004 17.7085C8.00004 18.1228 8.33583 18.4585 8.75004 18.4585H11.25C11.6643 18.4585 12 18.1228 12 17.7085C12 17.2943 11.6643 16.9585 11.25 16.9585H8.75004C8.33583 16.9585 8.00004 17.2943 8.00004 17.7085Z"
                        fill="" />
                    </svg>
                  </button>

                  <!-- Dropdown Start -->
                  <div x-show="dropdownOpen"
                    class="shadow-theme-lg dark:bg-gray-dark absolute -right-[240px] mt-[17px] flex h-[480px] w-[350px] flex-col rounded-2xl border border-gray-200 bg-white p-3 sm:w-[361px] lg:right-0 dark:border-gray-800">
                    <div
                      class="mb-3 flex items-center justify-between border-b border-gray-100 pb-3 dark:border-gray-800">
                      <h5 class="text-lg font-semibold text-gray-800 dark:text-white/90">
                        Notification
                      </h5>

                      <button @click="dropdownOpen = false" class="text-gray-500 dark:text-gray-400">
                        <svg class="fill-current" width="24" height="24" viewBox="0 0 24 24" fill="none"
                          xmlns="http://www.w3.org/2000/svg">
                          <path fill-rule="evenodd" clip-rule="evenodd"
                            d="M6.21967 7.28131C5.92678 6.98841 5.92678 6.51354 6.21967 6.22065C6.51256 5.92775 6.98744 5.92775 7.28033 6.22065L11.999 10.9393L16.7176 6.22078C17.0105 5.92789 17.4854 5.92788 17.7782 6.22078C18.0711 6.51367 18.0711 6.98855 17.7782 7.28144L13.0597 12L17.7782 16.7186C18.0711 17.0115 18.0711 17.4863 17.7782 17.7792C17.4854 18.0721 17.0105 18.0721 16.7176 17.7792L11.999 13.0607L7.28033 17.7794C6.98744 18.0722 6.51256 18.0722 6.21967 17.7794C5.92678 17.4865 5.92678 17.0116 6.21967 16.7187L10.9384 12L6.21967 7.28131Z"
                            fill="" />
                        </svg>
                      </button>
                    </div>

                    <ul class="custom-scrollbar flex h-auto flex-col overflow-y-auto">
                      <li>
                        <a class="flex gap-3 rounded-lg border-b border-gray-100 p-3 px-4.5 py-3 hover:bg-gray-100 dark:border-gray-800 dark:hover:bg-white/5"
                          href="#">
                          <span class="relative z-1 block h-10 w-full max-w-10 rounded-full">
                            <img src="src/images/user/user-02.jpg" alt="User" class="overflow-hidden rounded-full" />
                            <span
                              class="bg-success-500 absolute right-0 bottom-0 z-10 h-2.5 w-full max-w-2.5 rounded-full border-[1.5px] border-white dark:border-gray-900"></span>
                          </span>

                          <span class="block">
                            <span class="text-theme-sm mb-1.5 block text-gray-500 dark:text-gray-400">
                              <span class="font-medium text-gray-800 dark:text-white/90">Terry Franci</span>
                              requests permission to change
                              <span class="font-medium text-gray-800 dark:text-white/90">Project - Nganter App</span>
                            </span>

                            <span class="text-theme-xs flex items-center gap-2 text-gray-500 dark:text-gray-400">
                              <span>Project</span>
                              <span class="h-1 w-1 rounded-full bg-gray-400"></span>
                              <span>5 min ago</span>
                            </span>
                          </span>
                        </a>
                      </li>


                    </ul>

                    <a href="#"
                      class="text-theme-sm shadow-theme-xs mt-3 flex justify-center rounded-lg border border-gray-300 bg-white p-3 font-medium text-gray-700 hover:bg-gray-50 hover:text-gray-800 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03] dark:hover:text-gray-200">
                      View All Notification
                    </a>
                  </div>
                  <!-- Dropdown End -->
                </div>
                <!-- Notification Menu Area -->
              </div>

              <!-- User Area -->
              <div class="relative" x-data="{ dropdownOpen: false }" @click.outside="dropdownOpen = false">
                <a class="flex items-center text-gray-700 dark:text-gray-400" href="#"
                  @click.prevent="dropdownOpen = ! dropdownOpen">
                  <span class="mr-3 h-11 w-11 overflow-hidden rounded-full">
                    <img src="src/images/user/owner.png" alt="User" />
                  </span>

                  <span class="text-theme-sm mr-1 block font-medium"> Musharof </span>

                  <svg :class="dropdownOpen && 'rotate-180'" class="stroke-gray-500 dark:stroke-gray-400" width="18"
                    height="20" viewBox="0 0 18 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M4.3125 8.65625L9 13.3437L13.6875 8.65625" stroke="" stroke-width="1.5"
                      stroke-linecap="round" stroke-linejoin="round" />
                  </svg>
                </a>

                <!-- Dropdown Start -->
                <div x-show="dropdownOpen"
                  class=" shadow-theme-lg dark:bg-gray-dark absolute right-0 mt-[17px] flex w-[260px] flex-col rounded-2xl border border-gray-200 bg-white p-3 dark:border-gray-800">
                  <div>
                    <span class="text-theme-sm block font-medium text-gray-700 dark:text-gray-400">
                      Musharof Chowdhury
                    </span>
                    <span class="text-theme-xs mt-0.5 block text-gray-500 dark:text-gray-400">
                      <a href="/cdn-cgi/l/email-protection" class="__cf_email__"
                        data-cfemail="fd8f9c93999290888e988fbd8d94909792d39e9290">[email&#160;protected]</a>
                    </span>
                  </div>

                  <button
                    class="group text-theme-sm mt-3 flex items-center gap-3 rounded-lg px-3 py-2 font-medium text-gray-700 hover:bg-gray-100 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-white/5 dark:hover:text-gray-300">
                    <svg class="fill-gray-500 group-hover:fill-gray-700 dark:group-hover:fill-gray-300" width="24"
                      height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                      <path fill-rule="evenodd" clip-rule="evenodd"
                        d="M15.1007 19.247C14.6865 19.247 14.3507 18.9112 14.3507 18.497L14.3507 14.245H12.8507V18.497C12.8507 19.7396 13.8581 20.747 15.1007 20.747H18.5007C19.7434 20.747 20.7507 19.7396 20.7507 18.497L20.7507 5.49609C20.7507 4.25345 19.7433 3.24609 18.5007 3.24609H15.1007C13.8581 3.24609 12.8507 4.25345 12.8507 5.49609V9.74501L14.3507 9.74501V5.49609C14.3507 5.08188 14.6865 4.74609 15.1007 4.74609L18.5007 4.74609C18.9149 4.74609 19.2507 5.08188 19.2507 5.49609L19.2507 18.497C19.2507 18.9112 18.9149 19.247 18.5007 19.247H15.1007ZM3.25073 11.9984C3.25073 12.2144 3.34204 12.4091 3.48817 12.546L8.09483 17.1556C8.38763 17.4485 8.86251 17.4487 9.15549 17.1559C9.44848 16.8631 9.44863 16.3882 9.15583 16.0952L5.81116 12.7484L16.0007 12.7484C16.4149 12.7484 16.7507 12.4127 16.7507 11.9984C16.7507 11.5842 16.4149 11.2484 16.0007 11.2484L5.81528 11.2484L9.15585 7.90554C9.44864 7.61255 9.44847 7.13767 9.15547 6.84488C8.86248 6.55209 8.3876 6.55226 8.09481 6.84525L3.52309 11.4202C3.35673 11.5577 3.25073 11.7657 3.25073 11.9984Z"
                        fill="" />
                    </svg>

                    Sign out
                  </button>
                </div>
                <!-- Dropdown End -->
              </div>
              <!-- User Area -->
            </div>
          </div>
        </header>
        <!-- ===== Header End ===== -->
        <div class="mx-auto max-w-(--breakpoint-2xl) p-4 md:p-6">
          <div class="grid grid-cols-12 gap-4 md:gap-6">
            <div class="col-span-12 space-y-6 xl:col-span-7">
              <!-- Metric Group One -->
              <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 md:gap-6">
                <!-- Metric Item Start -->
                <div
                  class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] md:p-6">
                  <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-gray-100 dark:bg-gray-800">
                    <svg class="fill-gray-800 dark:fill-white/90" width="24" height="24" viewBox="0 0 24 24" fill="none"
                      xmlns="http://www.w3.org/2000/svg">
                      <path fill-rule="evenodd" clip-rule="evenodd"
                        d="M8.80443 5.60156C7.59109 5.60156 6.60749 6.58517 6.60749 7.79851C6.60749 9.01185 7.59109 9.99545 8.80443 9.99545C10.0178 9.99545 11.0014 9.01185 11.0014 7.79851C11.0014 6.58517 10.0178 5.60156 8.80443 5.60156ZM5.10749 7.79851C5.10749 5.75674 6.76267 4.10156 8.80443 4.10156C10.8462 4.10156 12.5014 5.75674 12.5014 7.79851C12.5014 9.84027 10.8462 11.4955 8.80443 11.4955C6.76267 11.4955 5.10749 9.84027 5.10749 7.79851ZM4.86252 15.3208C4.08769 16.0881 3.70377 17.0608 3.51705 17.8611C3.48384 18.0034 3.5211 18.1175 3.60712 18.2112C3.70161 18.3141 3.86659 18.3987 4.07591 18.3987H13.4249C13.6343 18.3987 13.7992 18.3141 13.8937 18.2112C13.9797 18.1175 14.017 18.0034 13.9838 17.8611C13.7971 17.0608 13.4132 16.0881 12.6383 15.3208C11.8821 14.572 10.6899 13.955 8.75042 13.955C6.81096 13.955 5.61877 14.572 4.86252 15.3208ZM3.8071 14.2549C4.87163 13.2009 6.45602 12.455 8.75042 12.455C11.0448 12.455 12.6292 13.2009 13.6937 14.2549C14.7397 15.2906 15.2207 16.5607 15.4446 17.5202C15.7658 18.8971 14.6071 19.8987 13.4249 19.8987H4.07591C2.89369 19.8987 1.73504 18.8971 2.05628 17.5202C2.28015 16.5607 2.76117 15.2906 3.8071 14.2549ZM15.3042 11.4955C14.4702 11.4955 13.7006 11.2193 13.0821 10.7533C13.3742 10.3314 13.6054 9.86419 13.7632 9.36432C14.1597 9.75463 14.7039 9.99545 15.3042 9.99545C16.5176 9.99545 17.5012 9.01185 17.5012 7.79851C17.5012 6.58517 16.5176 5.60156 15.3042 5.60156C14.7039 5.60156 14.1597 5.84239 13.7632 6.23271C13.6054 5.73284 13.3741 5.26561 13.082 4.84371C13.7006 4.37777 14.4702 4.10156 15.3042 4.10156C17.346 4.10156 19.0012 5.75674 19.0012 7.79851C19.0012 9.84027 17.346 11.4955 15.3042 11.4955ZM19.9248 19.8987H16.3901C16.7014 19.4736 16.9159 18.969 16.9827 18.3987H19.9248C20.1341 18.3987 20.2991 18.3141 20.3936 18.2112C20.4796 18.1175 20.5169 18.0034 20.4837 17.861C20.2969 17.0607 19.913 16.088 19.1382 15.3208C18.4047 14.5945 17.261 13.9921 15.4231 13.9566C15.2232 13.6945 14.9995 13.437 14.7491 13.1891C14.5144 12.9566 14.262 12.7384 13.9916 12.5362C14.3853 12.4831 14.8044 12.4549 15.2503 12.4549C17.5447 12.4549 19.1291 13.2008 20.1936 14.2549C21.2395 15.2906 21.7206 16.5607 21.9444 17.5202C22.2657 18.8971 21.107 19.8987 19.9248 19.8987Z"
                        fill="" />
                    </svg>
                  </div>

                  <div class="mt-5 flex items-end justify-between">
                    <div>
                      <span class="text-sm text-gray-500 dark:text-gray-400">Customers</span>
                      <h4 class="mt-2 text-title-sm font-bold text-gray-800 dark:text-white/90">
                        <?php
                        $totalCustomers = $conn->query("SELECT COUNT(*) as total FROM users")->fetch_assoc()['total'];
                        ?>
                        <?= $totalCustomers ?>
                      </h4>
                    </div>

                    <span
                      class="flex items-center gap-1 rounded-full bg-success-50 py-0.5 pl-2 pr-2.5 text-sm font-medium text-success-600 dark:bg-success-500/15 dark:text-success-500">
                      <svg class="fill-current" width="12" height="12" viewBox="0 0 12 12" fill="none"
                        xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" clip-rule="evenodd"
                          d="M5.56462 1.62393C5.70193 1.47072 5.90135 1.37432 6.12329 1.37432C6.1236 1.37432 6.12391 1.37432 6.12422 1.37432C6.31631 1.37415 6.50845 1.44731 6.65505 1.59381L9.65514 4.5918C9.94814 4.88459 9.94831 5.35947 9.65552 5.65246C9.36273 5.94546 8.88785 5.94562 8.59486 5.65283L6.87329 3.93247L6.87329 10.125C6.87329 10.5392 6.53751 10.875 6.12329 10.875C5.70908 10.875 5.37329 10.5392 5.37329 10.125L5.37329 3.93578L3.65516 5.65282C3.36218 5.94562 2.8873 5.94547 2.5945 5.65248C2.3017 5.35949 2.30185 4.88462 2.59484 4.59182L5.56462 1.62393Z"
                          fill="" />
                      </svg>

                      11.01%
                    </span>
                  </div>
                </div>
                <div
                  class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] md:p-6">
                  <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-gray-100 dark:bg-gray-800">
                    <svg class="fill-gray-800 dark:fill-white/90" width="24" height="24" viewBox="0 0 24 24" fill="none"
                      xmlns="http://www.w3.org/2000/svg">
                      <path fill-rule="evenodd" clip-rule="evenodd"
                        d="M11.665 3.75621C11.8762 3.65064 12.1247 3.65064 12.3358 3.75621L18.7807 6.97856L12.3358 10.2009C12.1247 10.3065 11.8762 10.3065 11.665 10.2009L5.22014 6.97856L11.665 3.75621ZM4.29297 8.19203V16.0946C4.29297 16.3787 4.45347 16.6384 4.70757 16.7654L11.25 20.0366V11.6513C11.1631 11.6205 11.0777 11.5843 10.9942 11.5426L4.29297 8.19203ZM12.75 20.037L19.2933 16.7654C19.5474 16.6384 19.7079 16.3787 19.7079 16.0946V8.19202L13.0066 11.5426C12.9229 11.5844 12.8372 11.6208 12.75 11.6516V20.037ZM13.0066 2.41456C12.3732 2.09786 11.6277 2.09786 10.9942 2.41456L4.03676 5.89319C3.27449 6.27432 2.79297 7.05342 2.79297 7.90566V16.0946C2.79297 16.9469 3.27448 17.726 4.03676 18.1071L10.9942 21.5857L11.3296 20.9149L10.9942 21.5857C11.6277 21.9024 12.3732 21.9024 13.0066 21.5857L19.9641 18.1071C20.7264 17.726 21.2079 16.9469 21.2079 16.0946V7.90566C21.2079 7.05342 20.7264 6.27432 19.9641 5.89319L13.0066 2.41456Z"
                        fill="" />
                    </svg>
                  </div>

                  <div class="mt-5 flex items-end justify-between">
                    <div>
                      <span class="text-sm text-gray-500 dark:text-gray-400">Total Orders</span>
                      <h4 class="mt-2 text-title-sm font-bold text-gray-800 dark:text-white/90">
                        <?php
                        $totalOrders = $conn->query("SELECT COUNT(*) as total FROM orders")->fetch_assoc()['total'];
                        ?>
                        <?= $totalOrders ?>
                      </h4>
                    </div>

                    <span
                      class="flex items-center gap-1 rounded-full bg-error-50 py-0.5 pl-2 pr-2.5 text-sm font-medium text-error-600 dark:bg-error-500/15 dark:text-error-500">
                      <svg class="fill-current" width="12" height="12" viewBox="0 0 12 12" fill="none"
                        xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" clip-rule="evenodd"
                          d="M5.31462 10.3761C5.45194 10.5293 5.65136 10.6257 5.87329 10.6257C5.8736 10.6257 5.8739 10.6257 5.87421 10.6257C6.0663 10.6259 6.25845 10.5527 6.40505 10.4062L9.40514 7.4082C9.69814 7.11541 9.69831 6.64054 9.40552 6.34754C9.11273 6.05454 8.63785 6.05438 8.34486 6.34717L6.62329 8.06753L6.62329 1.875C6.62329 1.46079 6.28751 1.125 5.87329 1.125C5.45908 1.125 5.12329 1.46079 5.12329 1.875L5.12329 8.06422L3.40516 6.34719C3.11218 6.05439 2.6373 6.05454 2.3445 6.34752C2.0517 6.64051 2.05185 7.11538 2.34484 7.40818L5.31462 10.3761Z"
                          fill="" />
                      </svg>

                      9.05%
                    </span>
                  </div>
                </div>
                <!-- Metric Item End -->
              </div>
              <!-- Metric Group One -->

              <!-- ====== Chart One Start -->
              <div
                class="overflow-hidden rounded-2xl border border-gray-200 bg-white px-5 pt-5 sm:px-6 sm:pt-6 dark:border-gray-800 dark:bg-white/[0.03]">
                <div class="flex items-center justify-between">
                  <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">
                    Monthly Sales
                  </h3>

                  <div x-data="{openDropDown: false}" class="relative h-fit">
                    <button @click="openDropDown = !openDropDown"
                      :class="openDropDown ? 'text-gray-700 dark:text-white' : 'text-gray-400 hover:text-gray-700 dark:hover:text-white'">
                      <svg class="fill-current" width="24" height="24" viewBox="0 0 24 24" fill="none"
                        xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" clip-rule="evenodd"
                          d="M10.2441 6C10.2441 5.0335 11.0276 4.25 11.9941 4.25H12.0041C12.9706 4.25 13.7541 5.0335 13.7541 6C13.7541 6.9665 12.9706 7.75 12.0041 7.75H11.9941C11.0276 7.75 10.2441 6.9665 10.2441 6ZM10.2441 18C10.2441 17.0335 11.0276 16.25 11.9941 16.25H12.0041C12.9706 16.25 13.7541 17.0335 13.7541 18C13.7541 18.9665 12.9706 19.75 12.0041 19.75H11.9941C11.0276 19.75 10.2441 18.9665 10.2441 18ZM11.9941 10.25C11.0276 10.25 10.2441 11.0335 10.2441 12C10.2441 12.9665 11.0276 13.75 11.9941 13.75H12.0041C12.9706 13.75 13.7541 12.9665 13.7541 12C13.7541 11.0335 12.9706 10.25 12.0041 10.25H11.9941Z"
                          fill="" />
                      </svg>
                    </button>

                  </div>
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
                      <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">
                        Monthly Target
                      </h3>
                      <p class="mt-1 text-theme-sm text-gray-500 dark:text-gray-400">
                        Target you’ve set for each month
                      </p>
                    </div>
                    <div x-data="{openDropDown: false}" class="relative h-fit">
                      <button @click="openDropDown = !openDropDown"
                        :class="openDropDown ? 'text-gray-700 dark:text-white' : 'text-gray-400 hover:text-gray-700 dark:hover:text-white'">
                        <svg class="fill-current" width="24" height="24" viewBox="0 0 24 24" fill="none"
                          xmlns="http://www.w3.org/2000/svg">
                          <path fill-rule="evenodd" clip-rule="evenodd"
                            d="M10.2441 6C10.2441 5.0335 11.0276 4.25 11.9941 4.25H12.0041C12.9706 4.25 13.7541 5.0335 13.7541 6C13.7541 6.9665 12.9706 7.75 12.0041 7.75H11.9941C11.0276 7.75 10.2441 6.9665 10.2441 6ZM10.2441 18C10.2441 17.0335 11.0276 16.25 11.9941 16.25H12.0041C12.9706 16.25 13.7541 17.0335 13.7541 18C13.7541 18.9665 12.9706 19.75 12.0041 19.75H11.9941C11.0276 19.75 10.2441 18.9665 10.2441 18ZM11.9941 10.25C11.0276 10.25 10.2441 11.0335 10.2441 12C10.2441 12.9665 11.0276 13.75 11.9941 13.75H12.0041C12.9706 13.75 13.7541 12.9665 13.7541 12C13.7541 11.0335 12.9706 10.25 12.0041 10.25H11.9941Z"
                            fill="" />
                        </svg>
                      </button>

                    </div>
                  </div>
                  <div class="relative max-h-[195px]">
                    <div id="chartTwo" class="h-full"></div>
                    <span
                      class="absolute left-1/2 top-[85%] -translate-x-1/2 -translate-y-[85%] rounded-full bg-success-50 px-3 py-1 text-xs font-medium text-success-600 dark:bg-success-500/15 dark:text-success-500">+10%</span>
                  </div>
                  <p class="mx-auto mt-1.5 w-full max-w-[380px] text-center text-sm text-gray-500 sm:text-base">
                    You earn $3287 today, it's higher than last month. Keep up your good work!
                  </p>
                </div>

                <div class="flex items-center justify-center gap-5 px-6 py-3.5 sm:gap-8 sm:py-5">
                  <div>
                    <p class="mb-1 text-center text-theme-xs text-gray-500 dark:text-gray-400 sm:text-sm">
                      Monthly O
                    </p>
                    <p
                      class="flex items-center justify-center gap-1 text-base font-semibold text-gray-800 dark:text-white/90 sm:text-lg">
                      <?php
                      $monthlyOrders = $conn->query("SELECT COUNT(*) as total FROM orders WHERE MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW())")->fetch_assoc()['total'];
                      ?>
                      <?= $monthlyOrders ?>
                      <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" clip-rule="evenodd"
                          d="M7.26816 13.6632C7.4056 13.8192 7.60686 13.9176 7.8311 13.9176C7.83148 13.9176 7.83187 13.9176 7.83226 13.9176C8.02445 13.9178 8.21671 13.8447 8.36339 13.6981L12.3635 9.70076C12.6565 9.40797 12.6567 8.9331 12.3639 8.6401C12.0711 8.34711 11.5962 8.34694 11.3032 8.63973L8.5811 11.36L8.5811 2.5C8.5811 2.08579 8.24531 1.75 7.8311 1.75C7.41688 1.75 7.0811 2.08579 7.0811 2.5L7.0811 11.3556L4.36354 8.63975C4.07055 8.34695 3.59568 8.3471 3.30288 8.64009C3.01008 8.93307 3.01023 9.40794 3.30321 9.70075L7.26816 13.6632Z"
                          fill="#D92D20" />
                      </svg>
                    </p>
                  </div>

                  <div class="h-7 w-px bg-gray-200 dark:bg-gray-800"></div>

                  <div>
                    <p class="mb-1 text-center text-theme-xs text-gray-500 dark:text-gray-400 sm:text-sm">
                      Weekly O
                    </p>
                    <p
                      class="flex items-center justify-center gap-1 text-base font-semibold text-gray-800 dark:text-white/90 sm:text-lg">
                      <?php
                      $weeklyOrders = $conn->query("SELECT COUNT(*) as total FROM orders WHERE YEARWEEK(created_at) = YEARWEEK(NOW())")->fetch_assoc()['total'];
                      ?>
                      <?= $weeklyOrders ?>
                      <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" clip-rule="evenodd"
                          d="M7.60141 2.33683C7.73885 2.18084 7.9401 2.08243 8.16435 2.08243C8.16475 2.08243 8.16516 2.08243 8.16556 2.08243C8.35773 2.08219 8.54998 2.15535 8.69664 2.30191L12.6968 6.29924C12.9898 6.59203 12.9899 7.0669 12.6971 7.3599C12.4044 7.6529 11.9295 7.65306 11.6365 7.36027L8.91435 4.64004L8.91435 13.5C8.91435 13.9142 8.57856 14.25 8.16435 14.25C7.75013 14.25 7.41435 13.9142 7.41435 13.5L7.41435 4.64442L4.69679 7.36025C4.4038 7.65305 3.92893 7.6529 3.63613 7.35992C3.34333 7.06693 3.34348 6.59206 3.63646 6.29926L7.60141 2.33683Z"
                          fill="#039855" />
                      </svg>
                    </p>
                  </div>

                  <div class="h-7 w-px bg-gray-200 dark:bg-gray-800"></div>

                  <div>
                    <p class="mb-1 text-center text-theme-xs text-gray-500 dark:text-gray-400 sm:text-sm">
                      Today's O
                    </p>
                    <p
                      class="flex items-center justify-center gap-1 text-base font-semibold text-gray-800 dark:text-white/90 sm:text-lg">
                      <?php
                      $todayOrders = $conn->query("SELECT COUNT(*) as total FROM orders WHERE DATE(created_at) = CURDATE()")->fetch_assoc()['total'];
                      ?>
                      <?= $todayOrders ?>
                      <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" clip-rule="evenodd"
                          d="M7.60141 2.33683C7.73885 2.18084 7.9401 2.08243 8.16435 2.08243C8.16475 2.08243 8.16516 2.08243 8.16556 2.08243C8.35773 2.08219 8.54998 2.15535 8.69664 2.30191L12.6968 6.29924C12.9898 6.59203 12.9899 7.0669 12.6971 7.3599C12.4044 7.6529 11.9295 7.65306 11.6365 7.36027L8.91435 4.64004L8.91435 13.5C8.91435 13.9142 8.57856 14.25 8.16435 14.25C7.75013 14.25 7.41435 13.9142 7.41435 13.5L7.41435 4.64442L4.69679 7.36025C4.4038 7.65305 3.92893 7.6529 3.63613 7.35992C3.34333 7.06693 3.34348 6.59206 3.63646 6.29926L7.60141 2.33683Z"
                          fill="#039855" />
                      </svg>
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
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">
                      Recent Orders
                    </h3>
                  </div>
                  <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                    <form>
                      <div class="relative">
                        <span class="absolute -translate-y-1/2 pointer-events-none top-1/2 left-4">
                          <svg class="fill-gray-500 dark:fill-gray-400" width="20" height="20" viewBox="0 0 20 20"
                            fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd" clip-rule="evenodd"
                              d="M3.04199 9.37381C3.04199 5.87712 5.87735 3.04218 9.37533 3.04218C12.8733 3.04218 15.7087 5.87712 15.7087 9.37381C15.7087 12.8705 12.8733 15.7055 9.37533 15.7055C5.87735 15.7055 3.04199 12.8705 3.04199 9.37381ZM9.37533 1.54218C5.04926 1.54218 1.54199 5.04835 1.54199 9.37381C1.54199 13.6993 5.04926 17.2055 9.37533 17.2055C11.2676 17.2055 13.0032 16.5346 14.3572 15.4178L17.1773 18.2381C17.4702 18.531 17.945 18.5311 18.2379 18.2382C18.5308 17.9453 18.5309 17.4704 18.238 17.1775L15.4182 14.3575C16.5367 13.0035 17.2087 11.2671 17.2087 9.37381C17.2087 5.04835 13.7014 1.54218 9.37533 1.54218Z"
                              fill=""></path>
                          </svg>
                        </span>
                        <input type="text" placeholder="Search..."
                          class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-10 w-full rounded-lg border border-gray-300 bg-transparent py-2.5 pr-4 pl-[42px] text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden xl:w-[300px] dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30">
                      </div>
                    </form>
                    <div>
                      <button
                        class="text-theme-sm shadow-theme-xs inline-flex h-10 items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 font-medium text-gray-700 hover:bg-gray-50 hover:text-gray-800 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03] dark:hover:text-gray-200">
                        <svg class="stroke-current fill-white dark:fill-gray-800" width="20" height="20"
                          viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                          <path d="M2.29004 5.90393H17.7067" stroke="" stroke-width="1.5" stroke-linecap="round"
                            stroke-linejoin="round"></path>
                          <path d="M17.7075 14.0961H2.29085" stroke="" stroke-width="1.5" stroke-linecap="round"
                            stroke-linejoin="round"></path>
                          <path
                            d="M12.0826 3.33331C13.5024 3.33331 14.6534 4.48431 14.6534 5.90414C14.6534 7.32398 13.5024 8.47498 12.0826 8.47498C10.6627 8.47498 9.51172 7.32398 9.51172 5.90415C9.51172 4.48432 10.6627 3.33331 12.0826 3.33331Z"
                            fill="" stroke="" stroke-width="1.5"></path>
                          <path
                            d="M7.91745 11.525C6.49762 11.525 5.34662 12.676 5.34662 14.0959C5.34661 15.5157 6.49762 16.6667 7.91745 16.6667C9.33728 16.6667 10.4883 15.5157 10.4883 14.0959C10.4883 12.676 9.33728 11.525 7.91745 11.525Z"
                            fill="" stroke="" stroke-width="1.5"></path>
                        </svg>
                        Filter
                      </button>
                    </div>
                  </div>
                </div>
                <div class="max-w-full overflow-x-auto custom-scrollbar">
                  <table class="min-w-full">
                    <thead class="border-gray-100 border-y bg-gray-50 dark:border-gray-800 dark:bg-gray-900">
                      <tr>
                        <th class="px-6 py-3 whitespace-nowrap">
                          <div class="flex items-center">
                            <div x-data="{checked: false}" class="flex items-center gap-3">
                              <div @click="checked = !checked"
                                class="flex h-5 w-5 cursor-pointer items-center justify-center rounded-md border-[1.25px] bg-white dark:bg-white/0 border-gray-300 dark:border-gray-700"
                                :class="checked ? 'border-brand-500 dark:border-brand-500 bg-brand-500' : 'bg-white dark:bg-white/0 border-gray-300 dark:border-gray-700' ">
                                <svg :class="checked ? 'block' : 'hidden'" width="14" height="14" viewBox="0 0 14 14"
                                  fill="none" xmlns="http://www.w3.org/2000/svg" class="hidden">
                                  <path d="M11.6668 3.5L5.25016 9.91667L2.3335 7" stroke="white" stroke-width="1.94437"
                                    stroke-linecap="round" stroke-linejoin="round"></path>
                                </svg>
                              </div>
                              <div>
                                <span class="block font-medium text-gray-500 text-theme-xs dark:text-gray-400">
                                  Deal ID
                                </span>
                              </div>
                            </div>
                          </div>
                        </th>
                        <th class="px-6 py-3 whitespace-nowrap">
                          <div class="flex items-center">
                            <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">
                              Customer
                            </p>
                          </div>
                        </th>
                        <th class="px-6 py-3 whitespace-nowrap">
                          <div class="flex items-center">
                            <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">
                              Product/Service
                            </p>
                          </div>
                        </th>
                        <th class="px-6 py-3 whitespace-nowrap">
                          <div class="flex items-center">
                            <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">
                              Deal Value
                            </p>
                          </div>
                        </th>
                        <th class="px-6 py-3 whitespace-nowrap">
                          <div class="flex items-center">
                            <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">
                              Close Date
                            </p>
                          </div>
                        </th>
                        <th class="px-6 py-3 whitespace-nowrap">
                          <div class="flex items-center">
                            <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">
                              Status
                            </p>
                          </div>
                        </th>
                        <th class="px-6 py-3 whitespace-nowrap">
                          <div class="flex items-center justify-center">
                            <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">
                              Action
                            </p>
                          </div>
                        </th>
                      </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                      <tr>
                        <td class="px-6 py-3 whitespace-nowrap">
                          <div class="flex items-center">
                            <div x-data="{checked: false}" class="flex items-center gap-3">
                              <div @click="checked = !checked"
                                class="flex h-5 w-5 cursor-pointer items-center justify-center rounded-md border-[1.25px] bg-white dark:bg-white/0 border-gray-300 dark:border-gray-700"
                                :class="checked ? 'border-brand-500 dark:border-brand-500 bg-brand-500' : 'bg-white dark:bg-white/0 border-gray-300 dark:border-gray-700' ">
                                <svg :class="checked ? 'block' : 'hidden'" width="14" height="14" viewBox="0 0 14 14"
                                  fill="none" xmlns="http://www.w3.org/2000/svg" class="hidden">
                                  <path d="M11.6668 3.5L5.25016 9.91667L2.3335 7" stroke="white" stroke-width="1.94437"
                                    stroke-linecap="round" stroke-linejoin="round"></path>
                                </svg>
                              </div>
                              <div>
                                <span class="block font-medium text-gray-700 text-theme-sm dark:text-gray-400">
                                  DE124321
                                </span>
                              </div>
                            </div>
                          </div>
                        </td>
                        <td class="px-6 py-3 whitespace-nowrap">
                          <div class="flex items-center">
                            <div class="flex items-center gap-3">
                              <div class="flex items-center justify-center w-10 h-10 rounded-full bg-brand-100">
                                <span class="text-xs font-semibold text-brand-500"> JD </span>
                              </div>
                              <div>
                                <span class="text-theme-sm mb-0.5 block font-medium text-gray-700 dark:text-gray-400">
                                  John Doe
                                </span>
                                <span class="text-gray-500 text-theme-sm dark:text-gray-400">
                                  johndeo@gmail.com
                                </span>
                              </div>
                            </div>
                          </div>
                        </td>
                        <td class="px-6 py-3 whitespace-nowrap">
                          <div class="flex items-center">
                            <p class="text-gray-700 text-theme-sm dark:text-gray-400">
                              Software License
                            </p>
                          </div>
                        </td>
                        <td class="px-6 py-3 whitespace-nowrap">
                          <div class="flex items-center">
                            <p class="text-gray-700 text-theme-sm dark:text-gray-400">
                              $18,50.34
                            </p>
                          </div>
                        </td>
                        <td class="px-6 py-3 whitespace-nowrap">
                          <div class="flex items-center">
                            <p class="text-gray-700 text-theme-sm dark:text-gray-400">
                              2024-06-15
                            </p>
                          </div>
                        </td>
                        <td class="px-6 py-3 whitespace-nowrap">
                          <div class="flex items-center">
                            <p
                              class="bg-success-50 text-theme-xs text-success-600 dark:bg-success-500/15 dark:text-success-500 rounded-full px-2 py-0.5 font-medium">
                              Complete
                            </p>
                          </div>
                        </td>
                        <td class="px-6 py-3 whitespace-nowrap">
                          <div class="flex items-center justify-center">
                            <svg
                              class="cursor-pointer hover:fill-error-500 dark:hover:fill-error-500 fill-gray-700 dark:fill-gray-400"
                              width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                              <path fill-rule="evenodd" clip-rule="evenodd"
                                d="M6.54142 3.7915C6.54142 2.54886 7.54878 1.5415 8.79142 1.5415H11.2081C12.4507 1.5415 13.4581 2.54886 13.4581 3.7915V4.0415H15.6252H16.666C17.0802 4.0415 17.416 4.37729 17.416 4.7915C17.416 5.20572 17.0802 5.5415 16.666 5.5415H16.3752V8.24638V13.2464V16.2082C16.3752 17.4508 15.3678 18.4582 14.1252 18.4582H5.87516C4.63252 18.4582 3.62516 17.4508 3.62516 16.2082V13.2464V8.24638V5.5415H3.3335C2.91928 5.5415 2.5835 5.20572 2.5835 4.7915C2.5835 4.37729 2.91928 4.0415 3.3335 4.0415H4.37516H6.54142V3.7915ZM14.8752 13.2464V8.24638V5.5415H13.4581H12.7081H7.29142H6.54142H5.12516V8.24638V13.2464V16.2082C5.12516 16.6224 5.46095 16.9582 5.87516 16.9582H14.1252C14.5394 16.9582 14.8752 16.6224 14.8752 16.2082V13.2464ZM8.04142 4.0415H11.9581V3.7915C11.9581 3.37729 11.6223 3.0415 11.2081 3.0415H8.79142C8.37721 3.0415 8.04142 3.37729 8.04142 3.7915V4.0415ZM8.3335 7.99984C8.74771 7.99984 9.0835 8.33562 9.0835 8.74984V13.7498C9.0835 14.1641 8.74771 14.4998 8.3335 14.4998C7.91928 14.4998 7.5835 14.1641 7.5835 13.7498V8.74984C7.5835 8.33562 7.91928 7.99984 8.3335 7.99984ZM12.4168 8.74984C12.4168 8.33562 12.081 7.99984 11.6668 7.99984C11.2526 7.99984 10.9168 8.33562 10.9168 8.74984V13.7498C10.9168 14.1641 11.2526 14.4998 11.6668 14.4998C12.081 14.4998 12.4168 14.1641 12.4168 13.7498V8.74984Z"
                                fill=""></path>
                            </svg>
                          </div>
                        </td>
                      </tr>

                      <tr>
                        <td class="px-6 py-3 whitespace-nowrap">
                          <div class="flex items-center">
                            <div x-data="{checked: false}" class="flex items-center gap-3">
                              <div @click="checked = !checked"
                                class="flex h-5 w-5 cursor-pointer items-center justify-center rounded-md border-[1.25px] bg-white dark:bg-white/0 border-gray-300 dark:border-gray-700"
                                :class="checked ? 'border-brand-500 dark:border-brand-500 bg-brand-500' : 'bg-white dark:bg-white/0 border-gray-300 dark:border-gray-700' ">
                                <svg :class="checked ? 'block' : 'hidden'" width="14" height="14" viewBox="0 0 14 14"
                                  fill="none" xmlns="http://www.w3.org/2000/svg" class="hidden">
                                  <path d="M11.6668 3.5L5.25016 9.91667L2.3335 7" stroke="white" stroke-width="1.94437"
                                    stroke-linecap="round" stroke-linejoin="round"></path>
                                </svg>
                              </div>
                              <div>
                                <span class="block font-medium text-gray-700 text-theme-sm dark:text-gray-400">
                                  DE124321
                                </span>
                              </div>
                            </div>
                          </div>
                        </td>
                        <td class="px-6 py-3 whitespace-nowrap">
                          <div class="flex items-center">
                            <div class="flex items-center gap-3">
                              <div class="flex h-10 w-10 items-center justify-center rounded-full bg-[#f0f9ff]">
                                <span class="text-xs font-semibold text-[#0086c9]"> EW </span>
                              </div>
                              <div>
                                <span class="text-theme-sm mb-0.5 block font-medium text-gray-700 dark:text-gray-400">
                                  Emerson Workman
                                </span>
                                <span class="text-gray-500 text-theme-sm dark:text-gray-400">
                                  emerson@gmail.com
                                </span>
                              </div>
                            </div>
                          </div>
                        </td>
                        <td class="px-6 py-3 whitespace-nowrap">
                          <div class="flex items-center">
                            <p class="text-gray-700 text-theme-sm dark:text-gray-400">
                              Software License
                            </p>
                          </div>
                        </td>
                        <td class="px-6 py-3 whitespace-nowrap">
                          <div class="flex items-center">
                            <p class="text-gray-700 text-theme-sm dark:text-gray-400">
                              $18,50.34
                            </p>
                          </div>
                        </td>
                        <td class="px-6 py-3 whitespace-nowrap">
                          <div class="flex items-center">
                            <p class="text-gray-700 text-theme-sm dark:text-gray-400">
                              2024-06-15
                            </p>
                          </div>
                        </td>
                        <td class="px-6 py-3 whitespace-nowrap">
                          <div class="flex items-center">
                            <p
                              class="bg-warning-50 text-theme-xs text-warning-600 dark:bg-warning-500/15 dark:text-warning-400 rounded-full px-2 py-0.5 font-medium">
                              Pending
                            </p>
                          </div>
                        </td>
                        <td class="px-6 py-3 whitespace-nowrap">
                          <div class="flex items-center justify-center">
                            <svg
                              class="cursor-pointer hover:fill-error-500 dark:hover:fill-error-500 fill-gray-700 dark:fill-gray-400"
                              width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                              <path fill-rule="evenodd" clip-rule="evenodd"
                                d="M6.54142 3.7915C6.54142 2.54886 7.54878 1.5415 8.79142 1.5415H11.2081C12.4507 1.5415 13.4581 2.54886 13.4581 3.7915V4.0415H15.6252H16.666C17.0802 4.0415 17.416 4.37729 17.416 4.7915C17.416 5.20572 17.0802 5.5415 16.666 5.5415H16.3752V8.24638V13.2464V16.2082C16.3752 17.4508 15.3678 18.4582 14.1252 18.4582H5.87516C4.63252 18.4582 3.62516 17.4508 3.62516 16.2082V13.2464V8.24638V5.5415H3.3335C2.91928 5.5415 2.5835 5.20572 2.5835 4.7915C2.5835 4.37729 2.91928 4.0415 3.3335 4.0415H4.37516H6.54142V3.7915ZM14.8752 13.2464V8.24638V5.5415H13.4581H12.7081H7.29142H6.54142H5.12516V8.24638V13.2464V16.2082C5.12516 16.6224 5.46095 16.9582 5.87516 16.9582H14.1252C14.5394 16.9582 14.8752 16.6224 14.8752 16.2082V13.2464ZM8.04142 4.0415H11.9581V3.7915C11.9581 3.37729 11.6223 3.0415 11.2081 3.0415H8.79142C8.37721 3.0415 8.04142 3.37729 8.04142 3.7915V4.0415ZM8.3335 7.99984C8.74771 7.99984 9.0835 8.33562 9.0835 8.74984V13.7498C9.0835 14.1641 8.74771 14.4998 8.3335 14.4998C7.91928 14.4998 7.5835 14.1641 7.5835 13.7498V8.74984C7.5835 8.33562 7.91928 7.99984 8.3335 7.99984ZM12.4168 8.74984C12.4168 8.33562 12.081 7.99984 11.6668 7.99984C11.2526 7.99984 10.9168 8.33562 10.9168 8.74984V13.7498C10.9168 14.1641 11.2526 14.4998 11.6668 14.4998C12.081 14.4998 12.4168 14.1641 12.4168 13.7498V8.74984Z"
                                fill=""></path>
                            </svg>
                          </div>
                        </td>
                      </tr>
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
    function showSection(sectionId) {
      const sections = document.querySelectorAll('.content-section');
      sections.forEach(section => {
        section.classList.remove('active');
      });
      document.getElementById(sectionId).classList.add('active');
      const navLinks = document.querySelectorAll('.nav-link');
      navLinks.forEach(link => {
        link.classList.remove('active');
      });
      event.target.classList.add('active');
    }
  </script>

</body>

</html>