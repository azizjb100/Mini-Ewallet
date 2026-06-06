<script setup>
import GuestLayout from "@/Layouts/GuestLayout.vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import { Head, Link, useForm } from "@inertiajs/vue3";

const form = useForm({
    name: "",
    email: "",
    password: "",
    password_confirmation: "",
    pin: "", // TAMBAHAN: State penampung data PIN saat registrasi
});

const submit = () => {
    form.post(route("register"), {
        onFinish: () => form.reset("password", "password_confirmation", "pin"),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Pendaftaran Akun E-Wallet" />

        <div
            class="w-full max-w-md mx-auto bg-sky-50 p-8 rounded-2xl shadow-sm border border-sky-100"
        >
            <div class="text-center mb-6">
                <img
                    src="/images/logo.png"
                    alt="Mini-Ewallet Logo"
                    class="mx-auto h-20 w-auto object-contain mb-2 drop-shadow-sm"
                />
                <h3 class="text-lg font-black text-gray-800 tracking-tight">
                    Daftar Akun Baru
                </h3>
                <p class="text-xs text-gray-500 mt-0.5">
                    Dapatkan otomatis 12 digit nomor akun e-wallet
                </p>
            </div>

            <form @submit.prevent="submit" class="space-y-4">
                <div>
                    <InputLabel
                        for="name"
                        value="Nama Lengkap"
                        class="text-gray-700 font-medium mb-1"
                    />
                    <TextInput
                        id="name"
                        type="text"
                        class="mt-1 block w-full rounded-xl border-gray-200 focus:border-sky-500 focus:ring-sky-500 shadow-sm text-sm"
                        v-model="form.name"
                        required
                        autofocus
                        autocomplete="name"
                        placeholder="Masukkan nama lengkap Anda"
                    />
                    <InputError class="mt-1" :message="form.errors.name" />
                </div>

                <div>
                    <InputLabel
                        for="email"
                        value="Alamat Email"
                        class="text-gray-700 font-medium mb-1"
                    />
                    <TextInput
                        id="email"
                        type="email"
                        class="mt-1 block w-full rounded-xl border-gray-200 focus:border-sky-500 focus:ring-sky-500 shadow-sm text-sm"
                        v-model="form.email"
                        required
                        autocomplete="username"
                        placeholder="nama@email.com"
                    />
                    <InputError class="mt-1" :message="form.errors.email" />
                </div>

                <div>
                    <InputLabel
                        for="pin"
                        value="Buat PIN Keamanan (6 Digit Angka)"
                        class="text-gray-700 font-medium mb-1"
                    />
                    <TextInput
                        id="pin"
                        type="password"
                        maxLength="6"
                        class="mt-1 block w-full rounded-xl border-gray-200 focus:border-sky-500 focus:ring-sky-500 shadow-sm text-sm font-mono tracking-[0.2em]"
                        v-model="form.pin"
                        required
                        placeholder="******"
                    />
                    <p class="text-[10px] text-gray-400 mt-1">
                        Digunakan untuk memvalidasi setiap pengiriman saldo
                        dana.
                    </p>
                    <InputError class="mt-1" :message="form.errors.pin" />
                </div>

                <div>
                    <InputLabel
                        for="password"
                        value="Password Akun"
                        class="text-gray-700 font-medium mb-1"
                    />
                    <TextInput
                        id="password"
                        type="password"
                        class="mt-1 block w-full rounded-xl border-gray-200 focus:border-sky-500 focus:ring-sky-500 shadow-sm text-sm"
                        v-model="form.password"
                        required
                        autocomplete="new-password"
                        placeholder="Minimal 8 karakter"
                    />
                    <InputError class="mt-1" :message="form.errors.password" />
                </div>

                <div>
                    <InputLabel
                        for="password_confirmation"
                        value="Konfirmasi Password"
                        class="text-gray-700 font-medium mb-1"
                    />
                    <TextInput
                        id="password_confirmation"
                        type="password"
                        class="mt-1 block w-full rounded-xl border-gray-200 focus:border-sky-500 focus:ring-sky-500 shadow-sm text-sm"
                        v-model="form.password_confirmation"
                        required
                        autocomplete="new-password"
                        placeholder="Ulangi password Anda"
                    />
                    <InputError
                        class="mt-1"
                        :message="form.errors.password_confirmation"
                    />
                </div>

                <div class="pt-2 space-y-3">
                    <PrimaryButton
                        class="w-full justify-center py-3 bg-sky-600 hover:bg-sky-700 active:bg-sky-800 text-white font-semibold rounded-xl shadow-lg shadow-sky-100 transition-all duration-150 transform active:scale-[0.98]"
                        :class="{
                            'opacity-50 cursor-not-allowed': form.processing,
                        }"
                        :disabled="form.processing"
                    >
                        Mendaftar Akun
                    </PrimaryButton>

                    <div class="text-center pt-2 border-t border-gray-200/50">
                        <p class="text-xs text-gray-600">
                            Sudah memiliki akun?
                            <Link
                                :href="route('login')"
                                class="font-bold text-sky-600 hover:text-sky-800 hover:underline transition"
                            >
                                Masuk Disini
                            </Link>
                        </p>
                    </div>
                </div>
            </form>
        </div>
    </GuestLayout>
</template>
