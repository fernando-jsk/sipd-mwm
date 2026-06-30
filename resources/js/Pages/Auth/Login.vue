<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { Card, CardHeader, CardTitle, CardDescription, CardContent, CardFooter } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';

const form = useForm({
    username: '',
    password: '',
});

const submit = () => {
    form.post('/login', {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head title="Log in" />
    
    <div class="min-h-screen flex items-center justify-center bg-zinc-50 dark:bg-zinc-950 p-4 relative overflow-hidden">
        <!-- Background aesthetic -->
        <div class="absolute inset-0 z-0 bg-[radial-gradient(ellipse_at_top_right,_var(--tw-gradient-stops))] from-blue-100 via-zinc-50 to-zinc-50 dark:from-blue-950 dark:via-zinc-950 dark:to-zinc-950 opacity-70"></div>
        
        <Card class="w-full max-w-md z-10 shadow-xl border-zinc-200/60 dark:border-zinc-800 bg-white/80 dark:bg-zinc-950/80 backdrop-blur-sm">
            <CardHeader class="space-y-2 text-center pb-6">
                <div class="mb-4 flex justify-center">
                    <img src="/images/logo-mwm.png" alt="SIPD-MWM Logo" class="h-16 object-contain" />
                </div>
                <CardTitle class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-50">SIPD MWM</CardTitle>
                <CardDescription class="text-zinc-500">
                    Sistem Informasi Manajemen Keuangan
                </CardDescription>
            </CardHeader>
            <form @submit.prevent="submit">
                <CardContent class="space-y-5">
                    <div class="space-y-2">
                        <Label for="username" class="text-zinc-700 dark:text-zinc-300">Username</Label>
                        <Input 
                            id="username" 
                            type="text" 
                            placeholder="Masukkan username Anda..." 
                            v-model="form.username" 
                            :class="{'border-red-500 focus-visible:ring-red-500': form.errors.username}"
                            required 
                            autofocus 
                        />
                        <p v-if="form.errors.username" class="text-sm text-red-500 mt-1">{{ form.errors.username }}</p>
                    </div>
                    
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <Label for="password" class="text-zinc-700 dark:text-zinc-300">Password</Label>
                        </div>
                        <Input 
                            id="password" 
                            type="password" 
                            placeholder="••••••••" 
                            v-model="form.password" 
                            :class="{'border-red-500 focus-visible:ring-red-500': form.errors.password}"
                            required 
                        />
                        <p v-if="form.errors.password" class="text-sm text-red-500 mt-1">{{ form.errors.password }}</p>
                    </div>
                </CardContent>
                
                <CardFooter class="pt-2">
                    <Button class="w-full bg-blue-600 hover:bg-blue-700 text-white shadow-md transition-all" type="submit" :disabled="form.processing">
                        <template v-if="form.processing">
                            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Memproses...
                        </template>
                        <template v-else>
                            Masuk Ke Sistem
                        </template>
                    </Button>
                </CardFooter>
            </form>
        </Card>
    </div>
</template>
