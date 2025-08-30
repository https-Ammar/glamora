<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="../assets/css/main.css">

</head>

<body>
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-800">
            <h2 class="text-xl font-medium text-gray-800 dark:text-white">
                Create Invoice
            </h2>
        </div>
        <div class="border-b border-gray-200 p-4 sm:p-8 dark:border-gray-800">
            <form class="space-y-6">
                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    <div>
                        <label for="invoice-number"
                            class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Invoice
                            Number</label>
                        <input type="text" id="invoice-number"
                            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                            placeholder="WP-3434434">
                    </div>
                    <div>
                        <label for="customer-name"
                            class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Customer
                            Name</label>
                        <input type="text" id="customer-name"
                            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                            placeholder="Jhon Deniyal">
                    </div>
                    <div class="col-span-full">
                        <label for="customer-address"
                            class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Customer
                            Address</label>
                        <input type="file" id="customer-address"
                            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                            placeholder="Enter customer address">
                    </div>


                </div>
            </form>
        </div>
        <div class="border-b border-gray-200 p-4 sm:p-8 dark:border-gray-800" x-data="invoiceProducts()">
            <div class="overflow-hidden rounded-2xl border border-gray-200 dark:border-gray-800">
                <div class="custom-scrollbar overflow-x-auto">
                    <table class="min-w-full text-left text-sm text-gray-700 dark:border-gray-800">
                        <thead class="bg-gray-50 dark:bg-gray-900">
                            <tr class="border-b border-gray-100 whitespace-nowrap dark:border-gray-800">
                                <th
                                    class="px-5 py-4 text-sm font-medium whitespace-nowrap text-gray-700 dark:text-gray-400">
                                    S. No.
                                </th>
                                <th
                                    class="px-5 py-4 text-sm font-medium whitespace-nowrap text-gray-500 dark:text-gray-400">
                                    Products
                                </th>
                                <th
                                    class="px-5 py-4 text-sm font-medium whitespace-nowrap text-gray-700 dark:text-gray-400">
                                    Quantity
                                </th>
                                <th
                                    class="px-5 py-4 text-sm font-medium whitespace-nowrap text-gray-700 dark:text-gray-400">
                                    Unit Cost
                                </th>
                                <th
                                    class="px-5 py-4 text-sm font-medium whitespace-nowrap text-gray-700 dark:text-gray-400">
                                    Discount
                                </th>
                                <th
                                    class="px-5 py-4 text-sm font-medium whitespace-nowrap text-gray-700 dark:text-gray-400">
                                    Total
                                </th>
                                <th
                                    class="relative px-5 py-4 text-sm font-medium whitespace-nowrap text-gray-700 dark:text-gray-400">
                                    <span class="sr-only">Actions</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white dark:divide-gray-800 dark:bg-white/[0.03]">
                            <template x-for="(product, idx) in products" :key="idx">
                                <tr>
                                    <td class="px-5 py-4 text-sm whitespace-nowrap text-gray-500 dark:text-gray-400"
                                        x-text="idx + 1"></td>
                                    <td class="px-5 py-4 text-sm font-medium whitespace-nowrap text-gray-800 dark:text-white/90"
                                        x-text="product.name"></td>
                                    <td class="px-5 py-4 text-sm whitespace-nowrap text-gray-500 dark:text-gray-400"
                                        x-text="product.quantity"></td>
                                    <td class="px-5 py-4 text-sm whitespace-nowrap text-gray-500 dark:text-gray-400"
                                        x-text="'$' + product.price"></td>
                                    <td class="px-5 py-4 text-sm whitespace-nowrap text-gray-500 dark:text-gray-400"
                                        x-text="product.discount + '%' "></td>
                                    <td class="px-5 py-4 text-sm whitespace-nowrap text-gray-500 dark:text-gray-400"
                                        x-text="'$' + product.total"></td>
                                    <td class="px-5 py-4 text-sm whitespace-nowrap text-gray-500 dark:text-gray-400">
                                        <div class="flex items-center justify-center">
                                            <svg class="hover:fill-error-500 dark:hover:fill-error-500 cursor-pointer fill-gray-700 dark:fill-gray-400"
                                                width="20" height="20" viewBox="0 0 20 20" fill="none"
                                                xmlns="http://www.w3.org/2000/svg">
                                                <path fill-rule="evenodd" clip-rule="evenodd"
                                                    d="M6.54142 3.7915C6.54142 2.54886 7.54878 1.5415 8.79142 1.5415H11.2081C12.4507 1.5415 13.4581 2.54886 13.4581 3.7915V4.0415H15.6252H16.666C17.0802 4.0415 17.416 4.37729 17.416 4.7915C17.416 5.20572 17.0802 5.5415 16.666 5.5415H16.3752V8.24638V13.2464V16.2082C16.3752 17.4508 15.3678 18.4582 14.1252 18.4582H5.87516C4.63252 18.4582 3.62516 17.4508 3.62516 16.2082V13.2464V8.24638V5.5415H3.3335C2.91928 5.5415 2.5835 5.20572 2.5835 4.7915C2.5835 4.37729 2.91928 4.0415 3.3335 4.0415H4.37516H6.54142V3.7915ZM14.8752 13.2464V8.24638V5.5415H13.4581H12.7081H7.29142H6.54142H5.12516V8.24638V13.2464V16.2082C5.12516 16.6224 5.46095 16.9582 5.87516 16.9582H14.1252C14.5394 16.9582 14.8752 16.6224 14.8752 16.2082V13.2464ZM8.04142 4.0415H11.9581V3.7915C11.9581 3.37729 11.6223 3.0415 11.2081 3.0415H8.79142C8.37721 3.0415 8.04142 3.37729 8.04142 3.7915V4.0415ZM8.3335 7.99984C8.74771 7.99984 9.0835 8.33562 9.0835 8.74984V13.7498C9.0835 14.1641 8.74771 14.4998 8.3335 14.4998C7.91928 14.4998 7.5835 14.1641 7.5835 13.7498V8.74984C7.5835 8.33562 7.91928 7.99984 8.3335 7.99984ZM12.4168 8.74984C12.4168 8.33562 12.081 7.99984 11.6668 7.99984C11.2526 7.99984 10.9168 8.33562 10.9168 8.74984V13.7498C10.9168 14.1641 11.2526 14.4998 11.6668 14.4998C12.081 14.4998 12.4168 14.1641 12.4168 13.7498V8.74984Z"
                                                    fill=""></path>
                                            </svg>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                            <tr>
                                <td class="px-5 py-4 text-sm whitespace-nowrap text-gray-500 dark:text-gray-400"
                                    x-text="idx + 1">1</td>
                                <td class="px-5 py-4 text-sm font-medium whitespace-nowrap text-gray-800 dark:text-white/90"
                                    x-text="product.name">Macbook pro 13”</td>
                                <td class="px-5 py-4 text-sm whitespace-nowrap text-gray-500 dark:text-gray-400"
                                    x-text="product.quantity">1</td>
                                <td class="px-5 py-4 text-sm whitespace-nowrap text-gray-500 dark:text-gray-400"
                                    x-text="'$' + product.price">$1200</td>
                                <td class="px-5 py-4 text-sm whitespace-nowrap text-gray-500 dark:text-gray-400"
                                    x-text="product.discount + '%' ">0%</td>
                                <td class="px-5 py-4 text-sm whitespace-nowrap text-gray-500 dark:text-gray-400"
                                    x-text="'$' + product.total">$1200.00</td>
                                <td class="px-5 py-4 text-sm whitespace-nowrap text-gray-500 dark:text-gray-400">
                                    <div class="flex items-center justify-center">
                                        <svg class="hover:fill-error-500 dark:hover:fill-error-500 cursor-pointer fill-gray-700 dark:fill-gray-400"
                                            width="20" height="20" viewBox="0 0 20 20" fill="none"
                                            xmlns="http://www.w3.org/2000/svg">
                                            <path fill-rule="evenodd" clip-rule="evenodd"
                                                d="M6.54142 3.7915C6.54142 2.54886 7.54878 1.5415 8.79142 1.5415H11.2081C12.4507 1.5415 13.4581 2.54886 13.4581 3.7915V4.0415H15.6252H16.666C17.0802 4.0415 17.416 4.37729 17.416 4.7915C17.416 5.20572 17.0802 5.5415 16.666 5.5415H16.3752V8.24638V13.2464V16.2082C16.3752 17.4508 15.3678 18.4582 14.1252 18.4582H5.87516C4.63252 18.4582 3.62516 17.4508 3.62516 16.2082V13.2464V8.24638V5.5415H3.3335C2.91928 5.5415 2.5835 5.20572 2.5835 4.7915C2.5835 4.37729 2.91928 4.0415 3.3335 4.0415H4.37516H6.54142V3.7915ZM14.8752 13.2464V8.24638V5.5415H13.4581H12.7081H7.29142H6.54142H5.12516V8.24638V13.2464V16.2082C5.12516 16.6224 5.46095 16.9582 5.87516 16.9582H14.1252C14.5394 16.9582 14.8752 16.6224 14.8752 16.2082V13.2464ZM8.04142 4.0415H11.9581V3.7915C11.9581 3.37729 11.6223 3.0415 11.2081 3.0415H8.79142C8.37721 3.0415 8.04142 3.37729 8.04142 3.7915V4.0415ZM8.3335 7.99984C8.74771 7.99984 9.0835 8.33562 9.0835 8.74984V13.7498C9.0835 14.1641 8.74771 14.4998 8.3335 14.4998C7.91928 14.4998 7.5835 14.1641 7.5835 13.7498V8.74984C7.5835 8.33562 7.91928 7.99984 8.3335 7.99984ZM12.4168 8.74984C12.4168 8.33562 12.081 7.99984 11.6668 7.99984C11.2526 7.99984 10.9168 8.33562 10.9168 8.74984V13.7498C10.9168 14.1641 11.2526 14.4998 11.6668 14.4998C12.081 14.4998 12.4168 14.1641 12.4168 13.7498V8.74984Z"
                                                fill=""></path>
                                        </svg>
                                    </div>
                                </td>
                            </tr>


                        </tbody>
                    </table>
                    <template x-if="products.length === 0">
                        <div class="px-5 py-4 text-center text-gray-400">
                            No products added.
                        </div>
                    </template>
                </div>
            </div>

            <!-- Total Summary -->

            <div class="p-4 sm:p-8">
                <div class="flex flex-col gap-3 sm:flex-row sm:justify-end">
                    <button @click="isModalOpen = !isModalOpen"
                        class="shadow-theme-xs inline-flex items-center justify-center gap-2 rounded-lg bg-white px-4 py-3 text-sm font-medium text-gray-700 ring-1 ring-gray-300 transition hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-400 dark:ring-gray-700 dark:hover:bg-white/[0.03]">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20" fill="none">
                            <path
                                d="M2.46585 10.7925C2.23404 10.2899 2.23404 9.71023 2.46585 9.20764C3.78181 6.35442 6.66064 4.375 10.0003 4.375C13.3399 4.375 16.2187 6.35442 17.5347 9.20765C17.7665 9.71024 17.7665 10.2899 17.5347 10.7925C16.2187 13.6458 13.3399 15.6252 10.0003 15.6252C6.66064 15.6252 3.78181 13.6458 2.46585 10.7925Z"
                                stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            </path>
                            <path
                                d="M13.0212 10C13.0212 11.6684 11.6687 13.0208 10.0003 13.0208C8.33196 13.0208 6.97949 11.6684 6.97949 10C6.97949 8.33164 8.33196 6.97917 10.0003 6.97917C11.6687 6.97917 13.0212 8.33164 13.0212 10Z"
                                stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            </path>
                        </svg>
                        Preview Invoice
                    </button>
                    <button
                        class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 inline-flex items-center justify-center gap-2 rounded-lg px-4 py-3 text-sm font-medium text-white transition">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20" fill="none">
                            <path
                                d="M13.333 16.6666V12.9166C13.333 12.2262 12.7734 11.6666 12.083 11.6666L7.91634 11.6666C7.22599 11.6666 6.66634 12.2262 6.66634 12.9166L6.66635 16.6666M9.99967 5.83325H6.66634M15.4163 16.6666H4.58301C3.89265 16.6666 3.33301 16.1069 3.33301 15.4166V4.58325C3.33301 3.8929 3.89265 3.33325 4.58301 3.33325H12.8171C13.1483 3.33325 13.4659 3.46468 13.7003 3.69869L16.2995 6.29384C16.5343 6.52832 16.6662 6.84655 16.6662 7.17841L16.6663 15.4166C16.6663 16.1069 16.1066 16.6666 15.4163 16.6666Z"
                                stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            </path>
                        </svg>
                        Save Invoice
                    </button>
                </div>
            </div>
        </div>
</body>

</html>