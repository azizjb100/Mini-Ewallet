<script setup lang="ts">
import { useForm, usePage } from "@inertiajs/vue3";
import { computed, ref } from "vue";

const page = usePage();
const user = computed(() => page.props.auth.user);
const hasPin = computed(() => !!user.value.has_pin);

const form = useForm({
    current_pin: "",
    pin: "",
    pin_confirmation: "",
});

const statusMessage = ref("");

const updatePin = () => {
    // Keamanan Frontend: Pastikan input PIN baru berupa angka murni sebelum ditembak ke server
    if (
        isNaN(Number(form.pin)) ||
        (form.current_pin && isNaN(Number(form.current_pin)))
    ) {
        form.errors.pin = "PIN harus berupa angka murni!";
        return;
    }

    form.put(route("profile.pin.update"), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            statusMessage.value = "PIN Keamanan berhasil diperbarui!";
            setTimeout(() => (statusMessage.value = ""), 4000);
        },
        onError: (errors) => {
            console.error("Gagal mengupdate PIN:", errors);
        },
    });
};
</script>

<template>
    <section
        class="max-w-xl bg-white p-6 rounded-3xl border border-gray-100 shadow-sm"
    >
        <header>
            <h2 class="text-lg font-black text-gray-800">
                {{
                    hasPin
                        ? "Perbarui PIN Transaksi"
                        : "Buat PIN Transaksi Keamanan"
                }}
            </h2>
            <p class="mt-1 text-sm text-gray-400">
                PIN 6 digit digunakan untuk memvalidasi setiap transaksi
                pengiriman dana e-wallet Anda agar tetap aman.
            </p>
        </header>

        <form @submit.prevent="updatePin" class="mt-6 space-y-6">
            <div v-if="hasPin">
                <label
                    class="block text-xs font-bold text-gray-400 uppercase tracking-wide mb-1.5"
                    >PIN Lama Saat Ini</label
                >
                <input
                    type="password"
                    maxLength="6"
                    v-model="form.current_pin"
                    placeholder="******"
                    class="w-full font-mono text-sm rounded-xl border-gray-200 focus:ring-indigo-500 focus:border-indigo-500"
                />
                <p
                    v-if="form.errors.current_pin"
                    class="mt-1.5 text-xs text-red-500 font-medium"
                >
                    ❌ {{ form.errors.current_pin }}
                </p>
            </div>

            <div>
                <label
                    class="block text-xs font-bold text-gray-400 uppercase tracking-wide mb-1.5"
                    >PIN Baru (6 Digit Angka)</label
                >
                <input
                    type="password"
                    maxLength="6"
                    v-model="form.pin"
                    placeholder="******"
                    class="w-full font-mono text-sm rounded-xl border-gray-200 focus:ring-indigo-500 focus:border-indigo-500"
                />
                <p
                    v-if="form.errors.pin"
                    class="mt-1.5 text-xs text-red-500 font-medium"
                >
                    ❌ {{ form.errors.pin }}
                </p>
            </div>

            <div>
                <label
                    class="block text-xs font-bold text-gray-400 uppercase tracking-wide mb-1.5"
                    >Konfirmasi PIN Baru Anda</label
                >
                <input
                    type="password"
                    maxLength="6"
                    v-model="form.pin_confirmation"
                    placeholder="******"
                    class="w-full font-mono text-sm rounded-xl border-gray-200 focus:ring-indigo-500 focus:border-indigo-500"
                />
                <p
                    v-if="form.errors.pin_confirmation"
                    class="mt-1.5 text-xs text-red-500 font-medium"
                >
                    ❌ {{ form.errors.pin_confirmation }}
                </p>
            </div>

            <div class="flex items-center gap-4">
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="bg-indigo-900 hover:bg-indigo-800 text-white font-bold py-2.5 px-6 rounded-xl shadow-md transition text-xs disabled:opacity-50"
                >
                    {{ form.processing ? "Menyimpan..." : "Simpan PIN Baru" }}
                </button>

                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0"
                >
                    <p
                        v-if="statusMessage"
                        class="text-sm text-emerald-600 font-semibold"
                    >
                        ✨ {{ statusMessage }}
                    </p>
                </Transition>
            </div>
        </form>
    </section>
</template>
