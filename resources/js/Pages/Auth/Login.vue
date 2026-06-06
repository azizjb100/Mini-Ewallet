<script setup>
import Checkbox from "@/Components/Checkbox.vue";
import GuestLayout from "@/Layouts/GuestLayout.vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import { Head, Link, useForm } from "@inertiajs/vue3";

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: "",
    password: "",
    remember: false,
});

const submit = () => {
    form.post(route("login"), {
        onFinish: () => form.reset("password"),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Log in" />

        <div
            class="w-full max-w-md mx-auto bg-sky-50 p-8 rounded-2xl shadow-sm border border-sky-100"
        >
            <div class="text-center mb-8">
                <img
                    src="/images/logo.png"
                    alt="Mini-Ewallet Logo"
                    class="mx-auto h-24 w-auto object-contain mb-2 drop-shadow-sm"
                />
                <p class="text-sm text-gray-500">Silakan masuk ke akun Anda</p>
            </div>

            <div
                v-if="status"
                class="mb-4 p-3 bg-green-50 rounded-lg text-sm font-medium text-green-600 border border-green-100"
            >
                {{ status }}
            </div>

            <form @submit.prevent="submit" class="space-y-5">
                <div>
                    <InputLabel
                        for="email"
                        value="Email"
                        class="text-gray-700 font-medium mb-1.5"
                    />
                    <TextInput
                        id="email"
                        type="email"
                        class="mt-1 block w-full rounded-xl border-gray-200 focus:border-sky-500 focus:ring-sky-500 shadow-sm"
                        v-model="form.email"
                        required
                        autofocus
                        autocomplete="username"
                        placeholder="nama@email.com"
                    />
                    <InputError class="mt-1.5" :message="form.errors.email" />
                </div>

                <div>
                    <div class="flex justify-between items-center mb-1.5">
                        <InputLabel
                            for="password"
                            value="Password"
                            class="text-gray-700 font-medium"
                        />
                        <Link
                            v-if="canResetPassword"
                            :href="route('password.request')"
                            class="text-xs text-sky-600 hover:text-sky-800 hover:underline transition"
                        >
                            Lupa password?
                        </Link>
                    </div>
                    <TextInput
                        id="password"
                        type="password"
                        class="mt-1 block w-full rounded-xl border-gray-200 focus:border-sky-500 focus:ring-sky-500 shadow-sm"
                        v-model="form.password"
                        required
                        autocomplete="current-password"
                        placeholder="••••••••"
                    />
                    <InputError
                        class="mt-1.5"
                        :message="form.errors.password"
                    />
                </div>

                <div class="flex items-center justify-between pt-1">
                    <label class="flex items-center cursor-pointer select-none">
                        <Checkbox
                            name="remember"
                            v-model:checked="form.remember"
                            class="rounded-md text-sky-600 border-gray-300 focus:ring-sky-500"
                        />
                        <span class="ms-2 text-sm text-gray-600"
                            >Ingat saya</span
                        >
                    </label>
                </div>

                <div class="pt-2">
                    <PrimaryButton
                        class="w-full justify-center py-3 bg-sky-600 hover:bg-sky-700 active:bg-sky-800 text-white font-semibold rounded-xl shadow-lg shadow-sky-100 transition-all duration-150 transform active:scale-[0.98]"
                        :class="{
                            'opacity-50 cursor-not-allowed': form.processing,
                        }"
                        :disabled="form.processing"
                    >
                        Masuk
                    </PrimaryButton>
                </div>

                <div class="text-center pt-4 border-t border-gray-200/50 mt-4">
                    <p class="text-sm text-gray-600">
                        Belum punya akun e-wallet?
                        <Link
                            :href="route('register')"
                            class="font-bold text-sky-600 hover:text-sky-800 hover:underline transition"
                        >
                            Daftar Sekarang
                        </Link>
                    </p>
                </div>
            </form>
        </div>
    </GuestLayout>
</template>
