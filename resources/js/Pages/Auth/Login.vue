<script setup>
import { onMounted, onUnmounted } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import { Card, CardHeader, CardTitle, CardDescription, CardContent, CardFooter } from '@/Components/ui/card';
import { Label } from '@/Components/ui/label';
import { Input } from '@/Components/ui/input';
import { Button } from '@/Components/ui/button';

const form = useForm({
    username: '',
    password: '',
});

const submit = () => {
    form.post('/login', {
        onFinish: () => form.reset('password'),
    });
};

// Deteksi jika tab login ditinggal lama saat tidak aktif di browser
let lastActiveTime = Date.now();

const handleVisibilityChange = () => {
    if (document.visibilityState === 'visible') {
        const idleMinutes = (Date.now() - lastActiveTime) / 1000 / 60;
        // Jika ditinggal lebih dari 45 menit dan form belum diisi, reload agar CSRF token tetap segar
        if (idleMinutes > 45 && !form.username && !form.password && !form.processing) {
            window.location.reload();
        }
    } else {
        lastActiveTime = Date.now();
    }
};

onMounted(() => {
    document.addEventListener('visibilitychange', handleVisibilityChange);
});

onUnmounted(() => {
    document.removeEventListener('visibilitychange', handleVisibilityChange);
});
</script>

<template>
    <Head title="Log in" />
    
    <div class="min-h-screen flex items-center justify-center bg-background p-4 relative overflow-hidden transition-colors duration-300">
        <!-- Modern Decorative Glowing Orbs (Premium Aesthetic) -->
        <div class="absolute top-[-10%] right-[-10%] w-[40vw] h-[40vw] rounded-full bg-primary/10 blur-[80px] pointer-events-none animate-pulse duration-[6000ms]"></div>
        <div class="absolute bottom-[-10%] left-[-10%] w-[35vw] h-[35vw] rounded-full bg-primary/5 blur-[70px] pointer-events-none"></div>
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[60vw] h-[60vw] bg-[radial-gradient(circle_at_center,_var(--color-primary)_0%,_transparent_60%)] opacity-3 pointer-events-none"></div>
        
        <!-- Form membungkus Card secara penuh sesuai STYLE_GUIDE.md -->
        <form @submit.prevent="submit" class="w-full max-w-md z-10">
            <Card class="w-full shadow-2xl border border-border bg-card/75 backdrop-blur-md rounded-2xl overflow-hidden transition-all duration-300 hover:shadow-[0_20px_50px_rgba(230,78,71,0.1)]">
                <!-- Top brand Accent Bar -->
                <div class="h-1.5 w-full bg-gradient-to-r from-primary to-rose-400"></div>

                <CardHeader class="space-y-1 text-center pt-8 pb-6">
                    <!-- Logo Container with Shadow and Ring -->
                    <div class="mb-4 flex justify-center">
                        <div class="p-3 bg-white dark:bg-zinc-900 rounded-full shadow-md border border-border/50 transition-all duration-300 hover:scale-105">
                            <img src="/images/logo-mwm.png" alt="SIPD-MWM Logo" class="h-16 w-16 object-contain" />
                        </div>
                    </div>
                    
                    <CardTitle class="text-2xl font-bold tracking-tight text-foreground">
                        SIPD MWM
                    </CardTitle>
                    <CardDescription class="text-xs font-semibold text-muted-foreground uppercase tracking-widest mt-1">
                        RSUD Maria Walanda Maramis
                    </CardDescription>
                    <div class="h-px w-16 bg-border/80 mx-auto my-3"></div>
                    <p class="text-xs text-muted-foreground/80">
                        Sistem Informasi Manajemen Keuangan
                    </p>
                </CardHeader>

                <CardContent class="space-y-4 px-6 pb-6">
                    <!-- Flash Error Notification (misal 419 session expired) -->
                    <div 
                        v-if="$page.props.flash?.error" 
                        class="flex items-start gap-2.5 p-3 rounded-xl bg-destructive/10 border border-destructive/20 text-destructive text-xs font-medium animate-in fade-in duration-200"
                    >
                        <svg class="w-4 h-4 shrink-0 mt-0.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10" />
                            <line x1="12" y1="8" x2="12" y2="12" />
                            <line x1="12" y1="16" x2="12.01" y2="16" />
                        </svg>
                        <span class="leading-relaxed">{{ $page.props.flash.error }}</span>
                    </div>

                    <!-- Flash Message Notification -->
                    <div 
                        v-if="$page.props.flash?.message" 
                        class="flex items-start gap-2.5 p-3 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-600 dark:text-emerald-400 text-xs font-medium animate-in fade-in duration-200"
                    >
                        <svg class="w-4 h-4 shrink-0 mt-0.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 6 9 17l-5-5"/>
                        </svg>
                        <span class="leading-relaxed">{{ $page.props.flash.message }}</span>
                    </div>

                    <!-- Username Field -->
                    <div class="grid gap-1.5">
                        <Label for="username" class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">
                            Username
                        </Label>
                        <Input 
                            id="username" 
                            type="text" 
                            placeholder="Masukkan username Anda" 
                            v-model="form.username" 
                            :class="{'border-destructive focus-visible:ring-destructive': form.errors.username, 'focus-visible:ring-primary': !form.errors.username}"
                            required 
                            autofocus 
                            class="bg-background/50 h-10 transition-all duration-200"
                        />
                        <p v-if="form.errors.username" class="text-xs text-destructive mt-1 font-medium">{{ form.errors.username }}</p>
                    </div>
                    
                    <!-- Password Field -->
                    <div class="grid gap-1.5">
                        <div class="flex items-center justify-between">
                            <Label for="password" class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">
                                Password
                            </Label>
                        </div>
                        <Input 
                            id="password" 
                            type="password" 
                            placeholder="••••••••" 
                            v-model="form.password" 
                            :class="{'border-destructive focus-visible:ring-destructive': form.errors.password, 'focus-visible:ring-primary': !form.errors.password}"
                            required 
                            class="bg-background/50 h-10 transition-all duration-200"
                        />
                        <p v-if="form.errors.password" class="text-xs text-destructive mt-1 font-medium">{{ form.errors.password }}</p>
                    </div>
                </CardContent>
                
                <CardFooter class="px-6 pb-8 flex flex-col gap-4">
                    <Button 
                        class="w-full bg-primary text-primary-foreground hover:bg-primary/95 shadow-md hover:shadow-lg transition-all duration-200 active:scale-[0.98] font-medium h-10 cursor-pointer" 
                        type="submit" 
                        :disabled="form.processing"
                    >
                        <template v-if="form.processing">
                            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-primary-foreground" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Memproses...
                        </template>
                        <template v-else>
                            Masuk ke Sistem
                        </template>
                    </Button>

                    <div class="text-center">
                        <span class="text-[10px] font-medium text-muted-foreground/60 uppercase tracking-widest">
                            Kabupaten Minahasa Utara
                        </span>
                    </div>
                </CardFooter>
            </Card>
        </form>
    </div>
</template>

