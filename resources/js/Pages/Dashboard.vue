<script setup lang="ts">
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, useForm, usePage, Link, router } from "@inertiajs/vue3";
import { computed, ref, watch } from "vue";
import axios from "axios"; // PERBAIKAN 1: Wajib import axios untuk cek nomor wallet ke server

const props = defineProps({
    usersList: Array,
    transactions: Object,
    filters: Object,
});

const page = usePage();
const user = computed(() => page.props.auth.user);
const flashSuccess = computed(() => page.props.flash?.success);
const flashError = computed(() => page.props.flash?.error);

// State untuk mengontrol Modal Pop-up (OVO/DANA Style)
const isTransferModalOpen = ref(false);
const isTopupModalOpen = ref(false);
const isNotificationOpen = ref(false);
const receiptDataRaw = ref(null);
const receiptData = computed(
    () => receiptDataRaw.value || page.props.flash?.receipt,
);

// PERBAIKAN 2: Deklarasikan state penampung alur Multi-step transfer dana
const currentStep = ref(1);
const walletCodeInput = ref("");
const walletError = ref("");
const detectedTargetName = ref("");
const pinInput = ref("");
const isSearching = ref(false);

const filterForm = ref({
    type: props.filters?.type || "all",
    start_date: props.filters?.start_date || "",
    end_date: props.filters?.end_date || "",
});

const applyFilter = () => {
    router.get(
        route("dashboard"),
        {
            type: filterForm.value.type,
            start_date: filterForm.value.start_date,
            end_date: filterForm.value.end_date,
        },
        {
            preserveState: true,
            replace: true,
        },
    );
};

// Pasang WATCH dengan deep: true agar rentang tanggal otomatis melakukan auto-submit
watch(
    () => filterForm.value,
    () => {
        applyFilter();
    },
    { deep: true },
);

// Fungsi menghapus semua filter (Reset data awal)
const resetFilter = () => {
    filterForm.value = { type: "all", start_date: "", end_date: "" };
};

// Form Transfer
const transferForm = useForm({
    ewallet_number: "",
    amount: "",
    pin: "",
});

const isSaldoKurang = computed(() => {
    if (!transferForm.amount) return false;
    return parseFloat(transferForm.amount) > parseFloat(user.value.balance);
});

// Form Top Up
const topupForm = useForm({
    amount: "",
    payment_method: "va_bank",
});

const verifyWalletAndAmount = async () => {
    walletError.value = "";
    detectedTargetName.value = "";

    if (!walletCodeInput.value || walletCodeInput.value.length !== 12) {
        walletError.value = "Nomor e-wallet tujuan harus tepat 12 digit.";
        return;
    }

    if (!transferForm.amount || parseFloat(transferForm.amount) <= 0) {
        walletError.value = "Nominal transfer harus lebih besar dari Rp0.";
        return;
    }

    if (isSaldoKurang.value) {
        walletError.value =
            "Saldo Anda tidak mencukupi untuk melakukan transaksi ini.";
        return;
    }

    // Aktifkan animasi loading sedang berjalan
    isSearching.value = true;

    try {
        // Simulasi loading berjalan selama 1.5 detik agar UX terlihat memproses data nyata
        await new Promise((resolve) => setTimeout(resolve, 1500));

        const response = await axios.post(route("wallet.check"), {
            wallet_code: walletCodeInput.value,
        });

        if (response.data.success) {
            transferForm.ewallet_number = walletCodeInput.value;
            detectedTargetName.value = response.data.name;
            currentStep.value = 2; // Pindah ke layar konfirmasi & validasi nama
        } else {
            walletError.value = response.data.message;
        }
    } catch (err) {
        walletError.value = "Gagal memproses pencarian dompet.";
    } finally {
        isSearching.value = false; // Matikan animasi loading
    }
};

const proceedToPin = () => {
    currentStep.value = 3;
};

const submitTransfer = () => {
    if (pinInput.value.length < 6) {
        alert("PIN validasi wajib 6 digit lengkap.");
        return;
    }

    transferForm.pin = pinInput.value;

    transferForm.post(route("transfer"), {
        onSuccess: () => {
            transferForm.reset();
            walletCodeInput.value = "";
            pinInput.value = "";
            detectedTargetName.value = "";
            currentStep.value = 1;
            isTransferModalOpen.value = false;
        },
        onError: () => {
            currentStep.value = 3;
        },
    });
};

const closeTransferModal = () => {
    isTransferModalOpen.value = false;
    currentStep.value = 1;
    walletCodeInput.value = "";
    pinInput.value = "";
    walletError.value = "";
    detectedTargetName.value = "";
    isSearching.value = false;
    transferForm.reset();
};

const submitTopup = () => {
    topupForm.post(route("wallet.topup"), {
        onSuccess: () => {
            isTopupModalOpen.value = false;
            topupForm.reset("amount");
        },
        onError: (errors) => {
            console.error("Topup Gagal:", errors);
        },
    });
};

const viewOldReceipt = (trx) => {
    const isOut = trx.type === "transfer_out";

    receiptDataRaw.value = {
        reference_number: trx.reference_number
            .replace("-OUT", "")
            .replace("-IN", ""),
        amount: parseFloat(trx.amount),
        receiver_name: isOut
            ? trx.description.replace("Transfer ke ", "")
            : trx.description.replace("Terima transfer dari ", ""),
        receiver_email: isOut ? "Penerima Dana" : "Penerima Transfer",
        date: new Date(trx.created_at).toLocaleDateString("id-ID", {
            day: "2-digit",
            month: "short",
            year: "numeric",
            hour: "2-digit",
            minute: "2-digit",
        }),
    };
};

const closeReceipt = () => {
    receiptDataRaw.value = null;
    if (page.props.flash) {
        page.props.flash.receipt = null;
    }
};

const printReceipt = () => {
    window.print();
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat("id-ID").format(value);
};
</script>

<template>
    <Head title="Dashboard E-Wallet" />

    <AuthenticatedLayout>
        <div class="py-8 bg-gray-50 min-h-screen">
            <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                <div
                    v-if="flashSuccess"
                    class="mb-4 p-4 bg-emerald-50 text-emerald-800 text-sm rounded-xl border border-emerald-200 shadow-sm flex items-center justify-between"
                >
                    <span>✨ {{ flashSuccess }}</span>
                </div>
                <div
                    v-if="flashError"
                    class="mb-4 p-4 bg-red-50 text-red-700 text-sm rounded-xl border border-red-200 shadow-sm"
                >
                    ⚠️ {{ flashError }}
                </div>

                <div
                    class="bg-indigo-900 text-white p-6 rounded-3xl shadow-xl relative overflow-hidden mb-6"
                >
                    <div
                        class="absolute -right-10 -top-10 w-40 h-40 bg-indigo-800 rounded-full opacity-50 blur-xl"
                    ></div>

                    <div class="flex justify-between items-start relative z-10">
                        <div>
                            <p
                                class="text-xs text-indigo-200 font-semibold uppercase tracking-wider"
                            >
                                Active Wallet
                            </p>
                            <h3 class="text-xl font-bold mt-1">
                                {{ user.name }}
                            </h3>
                            <p class="text-xs text-indigo-300 mt-0.5">
                                {{ user.email }}
                            </p>
                        </div>

                        <div class="relative">
                            <button
                                @click="
                                    isNotificationOpen = !isNotificationOpen
                                "
                                class="p-2 bg-indigo-800 hover:bg-indigo-700 rounded-full transition relative"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    class="h-6 w-6 text-indigo-100"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"
                                    />
                                </svg>
                                <span
                                    v-if="transactions.data.length > 0"
                                    class="absolute top-1 right-1 flex h-2 w-2 rounded-full bg-red-500"
                                ></span>
                            </button>

                            <div
                                v-if="isNotificationOpen"
                                class="absolute right-0 mt-2 w-72 bg-white rounded-2xl shadow-2xl py-3 text-gray-800 z-50 border border-gray-100 animate-fade-in"
                            >
                                <h4
                                    class="font-bold px-4 pb-2 border-b text-sm text-gray-700"
                                >
                                    Notifikasi Terbaru
                                </h4>
                                <div
                                    class="max-h-60 overflow-y-auto divide-y divide-gray-50"
                                >
                                    <div
                                        v-for="trx in transactions.data.slice(
                                            0,
                                            3,
                                        )"
                                        :key="trx.id"
                                        class="p-3 text-xs hover:bg-gray-50"
                                    >
                                        <p class="font-semibold text-gray-800">
                                            {{ trx.description }}
                                        </p>
                                        <p
                                            class="text-gray-400 mt-0.5 font-mono"
                                        >
                                            {{ trx.reference_number }}
                                        </p>
                                    </div>
                                    <div
                                        v-if="transactions.data.length === 0"
                                        class="p-4 text-center text-gray-400 text-xs"
                                    >
                                        Tidak ada notifikasi baru.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-8 relative z-10">
                        <p
                            class="text-xs text-indigo-200 uppercase font-medium tracking-wide"
                        >
                            Total Saldo Cash
                        </p>
                        <div class="flex items-baseline gap-1 mt-1">
                            <span class="text-xl font-bold text-indigo-300"
                                >Rp</span
                            >
                            <span class="text-4xl font-black tracking-tight">{{
                                formatCurrency(user.balance)
                            }}</span>
                        </div>
                    </div>

                    <div
                        class="grid grid-cols-2 gap-4 mt-8 pt-4 border-t border-indigo-800/60 relative z-10"
                    >
                        <button
                            @click="isTopupModalOpen = true"
                            class="flex flex-col items-center justify-center gap-1.5 bg-white/10 hover:bg-white/20 active:scale-95 text-white font-semibold py-3 px-4 rounded-2xl transition-all shadow-sm border border-white/10"
                        >
                            <div
                                class="p-2.5 bg-white/20 rounded-full text-white shadow-inner"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    class="h-5 w-5"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"
                                    />
                                </svg>
                            </div>
                            <span class="text-xs tracking-wide"
                                >Top Up Saldo</span
                            >
                        </button>
                        <button
                            @click="isTransferModalOpen = true"
                            class="flex flex-col items-center justify-center gap-1.5 bg-white/10 hover:bg-white/20 active:scale-95 text-white font-semibold py-3 px-4 rounded-2xl transition-all shadow-sm border border-white/10"
                        >
                            <div
                                class="p-2.5 bg-white/20 rounded-full text-white shadow-inner"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    class="h-5 w-5"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"
                                    />
                                </svg>
                            </div>
                            <span class="text-xs tracking-wide"
                                >Transfer Dana</span
                            >
                        </button>
                    </div>
                </div>

                <div
                    class="bg-white p-6 shadow-sm rounded-3xl border border-gray-100"
                >
                    <div class="flex justify-between items-center mb-4">
                        <h4 class="text-base font-bold text-gray-800">
                            Riwayat Transaksi
                        </h4>
                        <span
                            class="text-xs font-semibold text-indigo-600 bg-indigo-50 px-3 py-1 rounded-full"
                            >Urutan Terbaru</span
                        >
                    </div>

                    <div
                        class="bg-gray-50 p-4 rounded-2xl mb-6 grid grid-cols-1 sm:grid-cols-4 gap-3 border border-gray-100 items-end no-print"
                    >
                        <div>
                            <label
                                class="block text-[10px] font-bold text-gray-400 uppercase mb-1"
                                >Jenis Mutasi</label
                            >
                            <select
                                v-model="filterForm.type"
                                class="w-full rounded-xl border-gray-200 text-xs font-semibold text-gray-700 bg-white focus:ring-indigo-500 focus:border-indigo-500"
                            >
                                <option value="all">🌐 Semua Transaksi</option>
                                <option value="transfer_in">
                                    📥 Uang Masuk (In)
                                </option>
                                <option value="transfer_out">
                                    📤 Uang Keluar (Out)
                                </option>
                            </select>
                        </div>
                        <div>
                            <label
                                class="block text-[10px] font-bold text-gray-400 uppercase mb-1"
                                >Dari Tanggal</label
                            >
                            <input
                                type="date"
                                v-model="filterForm.start_date"
                                class="w-full rounded-xl border-gray-200 text-xs font-semibold text-gray-700 bg-white focus:ring-indigo-500 focus:border-indigo-500"
                            />
                        </div>
                        <div>
                            <label
                                class="block text-[10px] font-bold text-gray-400 uppercase mb-1"
                                >Sampai Tanggal</label
                            >
                            <input
                                type="date"
                                v-model="filterForm.end_date"
                                class="w-full rounded-xl border-gray-200 text-xs font-semibold text-gray-700 bg-white focus:ring-indigo-500 focus:border-indigo-500"
                            />
                        </div>
                        <div>
                            <button
                                @click="resetFilter"
                                class="w-full bg-gray-200 hover:bg-gray-300 text-gray-700 text-xs font-bold py-2.5 px-4 rounded-xl transition"
                            >
                                🔄 Reset Filter
                            </button>
                        </div>
                    </div>

                    <div class="divide-y divide-gray-100">
                        <div
                            v-for="trx in transactions.data"
                            :key="trx.id"
                            class="py-3 flex justify-between items-center hover:bg-gray-50/50 px-2 rounded-xl transition"
                        >
                            <div class="flex items-center gap-3">
                                <div
                                    :class="
                                        trx.type === 'transfer_out'
                                            ? 'bg-red-50 text-red-500'
                                            : 'bg-emerald-50 text-emerald-500'
                                    "
                                    class="p-2.5 rounded-2xl"
                                >
                                    <svg
                                        v-if="trx.type === 'transfer_out'"
                                        xmlns="http://www.w3.org/2000/svg"
                                        class="h-5 w-5"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M16 17l-4 4m0 0l-4-4m4 4V3"
                                        />
                                    </svg>
                                    <svg
                                        v-else
                                        xmlns="http://www.w3.org/2000/svg"
                                        class="h-5 w-5"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M8 7l4-4m0 0l4 4m-4-4v18"
                                        />
                                    </svg>
                                </div>
                                <div>
                                    <p class="font-bold text-gray-800 text-sm">
                                        {{ trx.description }}
                                    </p>
                                    <p
                                        class="text-[10px] font-mono text-gray-400 mt-0.5"
                                    >
                                        {{ trx.reference_number }} •
                                        {{
                                            new Date(
                                                trx.created_at,
                                            ).toLocaleDateString("id-ID", {
                                                day: "2-digit",
                                                month: "short",
                                                hour: "2-digit",
                                                minute: "2-digit",
                                            })
                                        }}
                                    </p>
                                </div>
                            </div>

                            <div
                                class="text-right flex items-center gap-2 justify-end"
                            >
                                <p
                                    class="text-sm font-black whitespace-nowrap"
                                    :class="
                                        trx.type === 'transfer_out'
                                            ? 'text-gray-800'
                                            : 'text-emerald-600'
                                    "
                                >
                                    {{
                                        trx.type === "transfer_out" ? "-" : "+"
                                    }}Rp{{ formatCurrency(trx.amount) }}
                                </p>
                                <button
                                    @click="viewOldReceipt(trx)"
                                    title="Cetak Struk"
                                    class="p-1.5 text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition no-print"
                                >
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        class="h-4 w-4"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"
                                        />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <div
                            v-if="transactions.data.length === 0"
                            class="py-8 text-center text-gray-400 text-sm"
                        >
                            Belum ada aktivitas transaksi terdaftar.
                        </div>
                    </div>

                    <div
                        class="mt-6 flex justify-between items-center text-xs text-gray-400 pt-4 border-t border-gray-100"
                    >
                        <p>Total data: {{ transactions.total }}</p>
                        <div class="flex gap-1">
                            <Link
                                v-for="(link, idx) in transactions.links"
                                :key="idx"
                                :href="link.url || '#'"
                                v-html="link.label"
                                class="px-3 py-1.5 border rounded-xl transition font-medium"
                                :class="{
                                    'bg-indigo-900 text-white border-indigo-900':
                                        link.active,
                                    'text-gray-300 pointer-events-none':
                                        !link.url,
                                    'text-gray-600 hover:bg-gray-100':
                                        link.url && !link.active,
                                }"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div
            v-if="isTopupModalOpen"
            class="fixed inset-0 bg-black/50 flex items-center justify-center p-4 z-50 animate-fade-in"
        >
            <div
                class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-gray-100"
            >
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-lg font-black text-gray-800">
                        Top Up Saldo E-Wallet
                    </h3>
                    <button
                        @click="isTopupModalOpen = false"
                        class="text-gray-400 hover:text-gray-600 font-bold"
                    >
                        ✕
                    </button>
                </div>
                <form @submit.prevent="submitTopup">
                    <div class="mb-5">
                        <label
                            class="block text-xs font-bold text-gray-400 uppercase tracking-wide mb-1.5"
                            >Nominal Pengisian (Min. Rp10.000)</label
                        >
                        <div class="relative">
                            <span
                                class="absolute left-3 top-2.5 text-gray-400 font-bold text-sm"
                                >Rp</span
                            >
                            <input
                                type="number"
                                v-model="topupForm.amount"
                                placeholder="0"
                                class="w-full pl-9 rounded-xl border-gray-200 text-sm font-semibold focus:ring-indigo-500 focus:border-indigo-500"
                            />
                        </div>
                        <p
                            v-if="topupForm.errors.amount"
                            class="mt-1 text-xs text-red-500"
                        >
                            {{ topupForm.errors.amount }}
                        </p>
                    </div>

                    <div class="mb-6">
                        <label
                            class="block text-xs font-bold text-gray-400 uppercase tracking-wide mb-2"
                            >Pilih Metode Pembayaran</label
                        >
                        <div class="space-y-2.5">
                            <label
                                class="flex items-center justify-between p-3.5 border rounded-2xl cursor-pointer transition select-none"
                                :class="
                                    topupForm.payment_method === 'va_bank'
                                        ? 'border-indigo-600 bg-indigo-50/40 ring-1 ring-indigo-600'
                                        : 'border-gray-200 hover:bg-gray-50'
                                "
                            >
                                <div class="flex items-center gap-3">
                                    <div
                                        class="p-2 bg-indigo-100 text-indigo-700 rounded-xl"
                                    >
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            class="h-5 w-5"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"
                                            />
                                        </svg>
                                    </div>
                                    <div>
                                        <p
                                            class="text-sm font-bold text-gray-800"
                                        >
                                            Virtual Account Bank
                                        </p>
                                        <p class="text-[11px] text-gray-400">
                                            Transfer via Mandiri, BCA, BRI, BNI
                                        </p>
                                    </div>
                                </div>
                                <input
                                    type="radio"
                                    value="va_bank"
                                    v-model="topupForm.payment_method"
                                    class="text-indigo-600 focus:ring-indigo-500 h-4 w-4 border-gray-300"
                                />
                            </label>

                            <label
                                class="flex items-center justify-between p-3.5 border rounded-2xl cursor-pointer transition select-none"
                                :class="
                                    topupForm.payment_method === 'qris'
                                        ? 'border-indigo-600 bg-indigo-50/40 ring-1 ring-indigo-600'
                                        : 'border-gray-200 hover:bg-gray-50'
                                "
                            >
                                <div class="flex items-center gap-3">
                                    <div
                                        class="p-2 bg-emerald-100 text-emerald-700 rounded-xl"
                                    >
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            class="h-5 w-5"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M12 4v1m0 11v1m4-6h1m-11 0h1m5 10H8a3 3 0 01-3-3v-1m14-4h-4a1 1 0 00-1 1v4a1 1 0 001 1h4a1 1 0 001-1v-4a1 1 0 00-1-1zM7 7H4a1 1 0 00-1 1v3a1 1 0 001 1h3a1 1 0 001-1V8a1 1 0 00-1-1z"
                                            />
                                        </svg>
                                    </div>
                                    <div>
                                        <p
                                            class="text-sm font-bold text-gray-800"
                                        >
                                            QRIS Instant
                                        </p>
                                        <p class="text-[11px] text-gray-400">
                                            Scan QR via GoPay, DANA, LinkAja,
                                            ShopeePay
                                        </p>
                                    </div>
                                </div>
                                <input
                                    type="radio"
                                    value="qris"
                                    v-model="topupForm.payment_method"
                                    class="text-indigo-600 focus:ring-indigo-500 h-4 w-4 border-gray-300"
                                />
                            </label>
                        </div>
                        <p
                            v-if="topupForm.errors.payment_method"
                            class="mt-1 text-xs text-red-500"
                        >
                            {{ topupForm.errors.payment_method }}
                        </p>
                    </div>

                    <button
                        type="submit"
                        :disabled="topupForm.processing"
                        class="w-full bg-indigo-900 hover:bg-indigo-800 text-white font-bold py-3 rounded-xl shadow-md transition disabled:opacity-50 text-sm"
                    >
                        {{
                            topupForm.processing
                                ? "Memproses..."
                                : "Konfirmasi Top Up"
                        }}
                    </button>
                </form>
            </div>
        </div>
        <div
            v-if="isTransferModalOpen"
            class="fixed inset-0 bg-black/50 flex items-center justify-center p-4 z-50 animate-fade-in"
        >
            <div
                class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-gray-100 relative overflow-hidden"
            >
                <div
                    v-if="isSearching"
                    class="absolute inset-0 bg-white/90 z-50 flex flex-col items-center justify-center animate-fade-in"
                >
                    <div
                        class="w-12 h-12 border-4 border-indigo-200 border-t-indigo-900 rounded-full animate-spin mb-3"
                    ></div>
                    <p class="text-sm font-bold text-gray-700">
                        Memverifikasi Data Akun...
                    </p>
                    <p class="text-xs text-gray-400 mt-0.5">
                        Mohon tunggu sebentar
                    </p>
                </div>

                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h3 class="text-lg font-black text-gray-800">
                            Transfer Dana Aman
                        </h3>
                        <p class="text-xs text-gray-400 mt-0.5">
                            Langkah {{ currentStep }} dari 3
                        </p>
                    </div>
                    <button
                        @click="closeTransferModal"
                        class="text-gray-400 hover:text-gray-600 font-bold"
                    >
                        ✕
                    </button>
                </div>

                <div v-if="currentStep === 1" class="space-y-4">
                    <div>
                        <label
                            class="block text-xs font-bold text-gray-400 uppercase tracking-wide mb-1.5"
                            >Masukkan Nomor / Kode Wallet Tujuan</label
                        >
                        <input
                            type="text"
                            maxLength="12"
                            v-model="walletCodeInput"
                            placeholder="Masukkan 12 digit nomor e-wallet"
                            class="w-full rounded-xl border-gray-200 text-sm font-semibold focus:ring-indigo-500 focus:border-indigo-500"
                        />
                    </div>

                    <div>
                        <label
                            class="block text-xs font-bold text-gray-400 uppercase tracking-wide mb-1.5"
                            >Nominal Transfer</label
                        >
                        <div class="relative">
                            <span
                                class="absolute left-3 top-2.5 text-gray-400 font-bold text-sm"
                                >Rp</span
                            >
                            <input
                                type="number"
                                v-model="transferForm.amount"
                                placeholder="0"
                                class="w-full pl-9 rounded-xl border-gray-200 text-sm font-semibold focus:ring-indigo-500 focus:border-indigo-500"
                                :class="{
                                    'border-red-500 focus:ring-red-500 focus:border-red-500':
                                        isSaldoKurang,
                                }"
                            />
                        </div>
                    </div>

                    <p
                        v-if="walletError"
                        class="text-xs text-red-500 font-medium bg-red-50 p-2.5 rounded-xl border border-red-100 flex items-center gap-1.5"
                    >
                        ⚠️ {{ walletError }}
                    </p>

                    <button
                        @click="verifyWalletAndAmount"
                        :disabled="!walletCodeInput || !transferForm.amount"
                        type="button"
                        class="w-full bg-indigo-900 hover:bg-indigo-800 text-white font-bold py-3 rounded-xl shadow-md transition text-sm disabled:opacity-50"
                    >
                        Periksa & Lanjutkan
                    </button>
                </div>

                <div v-if="currentStep === 2" class="space-y-5">
                    <div
                        class="text-center bg-gray-50 p-4 rounded-2xl border border-gray-100"
                    >
                        <p
                            class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3"
                        >
                            Detail Konfirmasi Transaksi
                        </p>

                        <div
                            class="w-12 h-12 bg-indigo-600 text-white font-black text-base rounded-full flex items-center justify-center uppercase shadow-sm mx-auto mb-2"
                        >
                            {{ detectedTargetName.charAt(0) }}
                        </div>

                        <p class="text-sm font-black text-gray-800">
                            {{ detectedTargetName }}
                        </p>
                        <p class="text-[11px] font-mono text-gray-400 mt-0.5">
                            {{ walletCodeInput }}
                        </p>

                        <div
                            class="border-t border-dashed my-3 border-gray-200"
                        ></div>

                        <p class="text-xs text-gray-400 font-medium">
                            Jumlah Uang Dikirim
                        </p>
                        <p class="text-xl font-black text-indigo-950 mt-0.5">
                            Rp{{ formatCurrency(transferForm.amount) }}
                        </p>
                    </div>

                    <div class="flex gap-2">
                        <button
                            @click="currentStep = 1"
                            type="button"
                            class="w-1/3 bg-gray-100 hover:bg-gray-200 text-gray-600 font-bold py-3 rounded-xl transition text-sm"
                        >
                            Ubah Data
                        </button>
                        <button
                            @click="proceedToPin"
                            type="button"
                            class="w-2/3 bg-emerald-600 hover:bg-emerald-500 text-white font-bold py-3 rounded-xl shadow-md transition text-sm"
                        >
                            Konfirmasi & Lanjut
                        </button>
                    </div>
                </div>

                <div v-if="currentStep === 3" class="text-center">
                    <p class="text-sm font-bold text-gray-700 mb-2">
                        Masukkan 6 Digit PIN Keamanan
                    </p>
                    <p class="text-xs text-gray-400 mb-6">
                        Mengamankan transfer ke akun
                        <span class="font-black text-gray-700">{{
                            detectedTargetName
                        }}</span>
                    </p>

                    <div class="mb-6 flex justify-center">
                        <input
                            type="password"
                            maxLength="6"
                            v-model="pinInput"
                            placeholder="******"
                            class="w-36 text-center text-2xl tracking-[0.5em] font-mono rounded-xl border-gray-200 focus:ring-indigo-500 focus:border-indigo-500"
                        />
                    </div>
                    <p
                        v-if="transferForm.errors.pin"
                        class="mb-3 text-xs text-red-500 font-medium"
                    >
                        ❌ {{ transferForm.errors.pin }}
                    </p>

                    <div class="flex gap-2">
                        <button
                            @click="currentStep = 2"
                            :disabled="transferForm.processing"
                            type="button"
                            class="w-1/3 bg-gray-100 hover:bg-gray-200 text-gray-600 font-bold py-3 rounded-xl transition text-sm disabled:opacity-50"
                        >
                            Batal
                        </button>
                        <button
                            @click="submitTransfer"
                            :disabled="
                                transferForm.processing || pinInput.length < 6
                            "
                            type="button"
                            class="w-2/3 bg-indigo-900 hover:bg-indigo-800 text-white font-bold py-3 rounded-xl shadow-md transition text-sm disabled:opacity-50"
                        >
                            {{
                                transferForm.processing
                                    ? "Memverifikasi PIN..."
                                    : "Kirim Transfer Sekarang"
                            }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div
            v-if="receiptData"
            class="fixed inset-0 bg-black/60 flex items-center justify-center p-4 z-50 animate-fade-in"
        >
            <div
                class="bg-white rounded-3xl max-w-sm w-full shadow-2xl overflow-hidden border border-gray-100"
            >
                <div id="receipt-print-area" class="bg-white p-6 relative">
                    <div
                        class="text-center pb-4 border-b border-dashed border-gray-200"
                    >
                        <div
                            class="flex justify-center items-center gap-1.5 mb-2"
                        >
                            <div
                                class="w-7 h-7 bg-indigo-600 rounded-lg flex items-center justify-center text-white font-black text-xs tracking-wider"
                            >
                                eW
                            </div>
                            <span
                                class="text-lg font-black text-indigo-900 tracking-tight"
                                >MINI E-WALLET</span
                            >
                        </div>
                        <p class="text-xs text-gray-400 font-medium">
                            {{ receiptData.date }}
                        </p>
                    </div>

                    <div class="text-center my-5">
                        <div
                            class="inline-flex items-center gap-1.5 bg-emerald-50 text-emerald-700 font-bold text-xs px-3 py-1.5 rounded-full mb-3"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="h-4 w-4"
                                viewBox="0 0 20 20"
                                fill="currentColor"
                            >
                                <path
                                    fill-rule="evenodd"
                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                    clip-rule="evenodd"
                                />
                            </svg>
                            Transaksi Berhasil!
                        </div>
                        <h4 class="text-sm font-bold text-gray-700">
                            <span
                                v-if="
                                    receiptData.receiver_email ===
                                    'Penerima Transfer'
                                "
                                >Terima Uang dari
                                {{ receiptData.receiver_name }}</span
                            >
                            <span v-else
                                >Transfer ke
                                {{ receiptData.receiver_name }}</span
                            >
                        </h4>
                        <p class="text-[11px] text-gray-400 font-medium mt-0.5">
                            {{ receiptData.receiver_email }}
                        </p>
                        <div
                            class="mt-2 bg-indigo-50/50 inline-block px-3 py-0.5 rounded text-[10px] text-indigo-700 font-bold uppercase tracking-wider"
                        >
                            {{
                                receiptData.receiver_email ===
                                "Penerima Transfer"
                                    ? "Uang Masuk"
                                    : "Kirim Uang"
                            }}
                        </div>
                    </div>

                    <div
                        class="bg-gray-50/80 rounded-2xl p-4 mb-4 border border-gray-100"
                    >
                        <div class="flex justify-between items-center mb-1">
                            <span class="text-sm font-black text-gray-800">
                                {{
                                    receiptData.receiver_email ===
                                    "Penerima Transfer"
                                        ? "Total Terima"
                                        : "Total Bayar"
                                }}
                            </span>
                            <span class="text-base font-black text-gray-900"
                                >Rp{{
                                    formatCurrency(receiptData.amount)
                                }}</span
                            >
                        </div>
                    </div>

                    <div
                        class="space-y-2 text-xs text-gray-500 border-b pb-4 mb-4"
                    >
                        <div class="flex justify-between">
                            <span>Biaya Admin</span>
                            <span class="font-bold text-gray-800">Rp0</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Metode Pembayaran</span>
                            <span class="font-bold text-gray-800"
                                >Saldo E-Wallet</span
                            >
                        </div>
                        <div class="flex justify-between">
                            <span>Nomor Referensi</span>
                            <span
                                class="font-mono text-gray-700 font-semibold"
                                >{{ receiptData.reference_number }}</span
                            >
                        </div>
                    </div>

                    <div
                        class="absolute bottom-0 left-0 right-0 flex justify-between px-2 overflow-hidden h-2 select-none pointer-events-none opacity-20"
                    >
                        <span
                            v-for="i in 20"
                            :key="i"
                            class="w-3 h-3 bg-gray-400 rounded-full -mb-1.5 flex-shrink-0"
                        ></span>
                    </div>
                </div>

                <div
                    class="p-4 bg-gray-50 border-t border-gray-100 flex gap-3 no-print"
                >
                    <button
                        @click="printReceipt"
                        class="flex-1 bg-indigo-900 hover:bg-indigo-800 text-white font-bold py-3 rounded-xl shadow-md transition text-sm flex items-center justify-center gap-1.5"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-4 w-4"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"
                            />
                        </svg>
                        Cetak Struk
                    </button>
                    <button
                        @click="closeReceipt"
                        class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-700 font-bold py-3 rounded-xl transition text-sm text-center"
                    >
                        Selesai
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style>
@media print {
    body * {
        visibility: hidden;
    }
    #receipt-print-area,
    #receipt-print-area * {
        visibility: visible;
    }
    #receipt-print-area {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        max-width: 400px;
        margin: 0 auto;
        border: none;
        box-shadow: none;
    }
    .no-print {
        display: none !important;
    }
}
</style>
