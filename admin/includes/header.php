<aside :class="sidebarToggle ? 'translate-x-0 xl:w-[90px]' : '-translate-x-full'"
    class="sidebar fixed top-0 left-0 z-9999 flex h-screen w-[290px] flex-col overflow-y-auto border-r border-gray-200 bg-white px-5 transition-all duration-300 xl:static xl:translate-x-0 dark:border-gray-800 dark:bg-black"
    @click.outside="sidebarToggle = false">
    
    <div class="no-scrollbar flex flex-col overflow-y-auto duration-300 ease-linear mt-8">
        <nav x-data="{selected: $persist('admin')}">
            <div>
                <h3 class="mb-4 text-xs leading-[20px] text-gray-400 uppercase">
                    <span class="menu-group-title" :class="sidebarToggle ? 'xl:hidden' : ''">
                        MAIN MENU
                    </span>
                    <i :class="sidebarToggle ? 'xl:block hidden bi bi-three-dots' : 'hidden'"></i>
                </h3>

                <ul class="mb-6 flex flex-col gap-1">
                    <li>
                        <a href="#" @click.prevent="selected = (selected === 'admin' ? '':'admin')"
                            class="menu-item group"
                            :class=" (selected === 'admin') || (page === 'ecommerce' || page === 'analytics' || page === 'marketing' || page === 'crm' || page === 'stocks' || page === 'saas' || page === 'logistics') ? 'menu-item-active' : 'menu-item-inactive'">
                            
                            <i
                                :class="(selected === 'admin') || (page === 'ecommerce' || page === 'analytics' || page === 'marketing' || page === 'crm' || page === 'stocks') ? 'menu-item-icon-active bi bi-grid-fill' :'menu-item-icon-inactive bi bi-grid-fill'"></i>

                            <span class="menu-item-text" :class="sidebarToggle ? 'xl:hidden' : ''">
                                Administration
                            </span>

                            <i class="menu-item-arrow bi bi-chevron-down"
                                :class="[(selected === 'admin') ? 'menu-item-arrow-active' : 'menu-item-arrow-inactive', sidebarToggle ? 'xl:hidden' : '' ]"></i>
                        </a>

                        <div class="translate transform overflow-hidden"
                            :class="(selected === 'admin') ? 'block' :'hidden'">
                            <ul :class="sidebarToggle ? 'xl:hidden' : 'flex'"
                                class="menu-dropdown mt-2 flex flex-col gap-1 pl-9">
                                <li>
                                    <a href="./pages/Products.php" class="menu-dropdown-item group"
                                        :class="page === 'analytics' ? 'menu-dropdown-item-active' : 'menu-dropdown-item-inactive'">
                                        <i class="bi bi-box"></i> Products
                                    </a>
                                </li>
                                <li>
                                    <a class="menu-dropdown-item group" href="./pages/add_product.php"
                                        :class="page === 'marketing' ? 'menu-dropdown-item-active' : 'menu-dropdown-item-inactive'">
                                        <i class="bi bi-plus-square"></i> Add Product
                                    </a>
                                </li>
                                <li>
                                    <a href="./pages/coupon.php" class="menu-dropdown-item group"
                                        :class="page === 'crm' ? 'menu-dropdown-item-active' : 'menu-dropdown-item-inactive'">
                                        <i class="bi bi-tags"></i> Coupons
                                    </a>
                                </li>
                                <li>
                                    <a href="./customers/customers.php" class="menu-dropdown-item group"
                                        :class="page === 'stocks' ? 'menu-dropdown-item-active' : 'menu-dropdown-item-inactive'">
                                        <i class="bi bi-people"></i> Customers
                                    </a>
                                </li>
                                <li>
                                    <a href="./pages/ads.php" class="menu-dropdown-item group"
                                        :class="page === 'stocks' ? 'menu-dropdown-item-active' : 'menu-dropdown-item-inactive'">
                                        <i class="bi bi-megaphone"></i> Advertisements
                                    </a>
                                </li>

                                <li>
                                    <a href="./pages/categories.php" class="menu-dropdown-item group"
                                        :class="page === 'stocks' ? 'menu-dropdown-item-active' : 'menu-dropdown-item-inactive'">
                                        <i class="bi bi-list-nested"></i> Categories
                                    </a>
                                </li>

                                <li>
                                    <a href="./orders/order.php" class="menu-dropdown-item group"
                                        :class="page === 'stocks' ? 'menu-dropdown-item-active' : 'menu-dropdown-item-inactive'">
                                        <i class="bi bi-cart-check"></i> Order Management
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </li>
                </ul>
            </div>

            <div :class="sidebarToggle ? 'xl:hidden' : ''" class="mx-auto mb-10 w-full max-w-60 rounded-2xl bg-gray-50 px-4 py-5 text-center dark:bg-white/[0.03]">
                <p class="text-theme-sm mb-4 text-center text-gray-500 dark:text-gray-400">
                    <span class="font-medium text-primary-600 dark:text-primary-400">Eng - Ammar Ahmed</span> ©
                    <span x-text="new Date().getFullYear()">2025</span>
                </p>
                <a href="../auth/logout.php" target="_blank" rel="nofollow" class="bg-brand-500 text-theme-sm hover:bg-brand-600 flex items-center justify-center gap-2 rounded-lg p-3 font-medium text-white">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </a>
            </div>
        </nav>
    </div>
</aside>