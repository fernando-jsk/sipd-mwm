<script setup>
import { ref, onMounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Checkbox } from '@/Components/ui/checkbox';
import {
  Card,
  CardHeader,
  CardTitle,
  CardDescription,
  CardContent,
  CardFooter,
  CardAction
} from '@/Components/ui/card';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow
} from '@/Components/ui/table';
import {
  Select,
  SelectTrigger,
  SelectValue,
  SelectContent,
  SelectGroup,
  SelectLabel,
  SelectItem
} from '@/Components/ui/select';
import { 
  Sun, 
  Moon, 
  Layout, 
  Monitor, 
  Sparkles, 
  Check, 
  Trash2, 
  Edit2, 
  ArrowRight, 
  User, 
  Mail, 
  Shield, 
  RefreshCw,
  Plus,
  Eye,
  Settings,
  MoreVertical,
  Activity,
  ChevronRight
} from '@lucide/vue';

// State
const isDarkMode = ref(false);
const showInLayout = ref(true);
const selectValue = ref('');
const checkboxValue = ref(true);
const textInputValue = ref('RSUD Maria Walanda Maramis');
const emailInputValue = ref('admin@mwm.go.id');
const errorInputValue = ref('invalid-value');

// Dummy data for Table
const users = ref([
  { id: 1, name: 'dr. John Doe, Sp.B', email: 'john.doe@mwm.go.id', role: 'Dokter Spesialis', status: 'Aktif', activity: 'Melakukan operasi bedah' },
  { id: 2, name: 'Sarah Connor, A.Md.Kep', email: 'sarah.c@mwm.go.id', role: 'Perawat Utama', status: 'Aktif', activity: 'Mengisi rekam medis' },
  { id: 3, name: 'Fernando JSK', email: 'fernando@mwm.go.id', role: 'Administrator', status: 'Aktif', activity: 'Mengubah pengaturan sistem' },
  { id: 4, name: 'Ahmad Dahlan', email: 'ahmad@mwm.go.id', role: 'Keuangan', status: 'Nonaktif', activity: 'Mencetak laporan bulanan' },
]);

// Theme logic
const toggleDarkMode = () => {
  isDarkMode.value = !isDarkMode.value;
  if (isDarkMode.value) {
    document.documentElement.classList.add('dark');
  } else {
    document.documentElement.classList.remove('dark');
  }
};

onMounted(() => {
  // Sync initial state with document class list
  isDarkMode.value = document.documentElement.classList.contains('dark');
});
</script>

<template>
  <Head title="Dev UI Components Showcase" />

  <!-- Outer wrapper to allow layout toggling -->
  <component :is="showInLayout ? AuthenticatedLayout : 'div'" class="min-h-screen">
    <!-- Inline header slot if using AuthenticatedLayout -->
    <template #header v-if="showInLayout">
      <div class="flex items-center justify-between w-full">
        <div>
          <h2 class="text-xl font-bold tracking-tight text-secondary dark:text-foreground">
            Dev UI Component Showcase
          </h2>
          <p class="text-xs text-muted-foreground mt-0.5">
            Membantu memvisualisasikan standar desain berdasarkan STYLE_GUIDE.md
          </p>
        </div>
        <div class="flex items-center gap-3">
          <Button variant="outline" size="sm" @click="showInLayout = false" class="gap-1.5 shadow-sm">
            <Monitor class="size-4 text-secondary dark:text-foreground" />
            Mode Standalone
          </Button>
          <Button variant="outline" size="sm" @click="toggleDarkMode" class="gap-1.5 shadow-sm">
            <Sun v-if="isDarkMode" class="size-4 text-amber-500" />
            <Moon v-else class="size-4 text-indigo-500" />
            {{ isDarkMode ? 'Light Mode' : 'Dark Mode' }}
          </Button>
        </div>
      </div>
    </template>

    <!-- Main Content Container -->
    <div :class="[
      'p-6 md:p-8 space-y-10 max-w-7xl mx-auto',
      showInLayout ? '' : 'bg-background text-foreground min-h-screen border-t-4 border-primary'
    ]">
      
      <!-- Standalone Header Control (only visible when standalone) -->
      <div v-if="!showInLayout" class="flex flex-col md:flex-row md:items-center justify-between pb-6 border-b border-border/80 gap-4">
        <div>
          <div class="flex items-center gap-2">
            <span class="px-2 py-0.5 bg-primary/10 text-primary text-xs font-semibold rounded-full">Developer Playground</span>
            <span class="text-xs text-muted-foreground">SIPD MWM</span>
          </div>
          <h1 class="text-3xl font-bold tracking-tight text-secondary dark:text-foreground mt-1">
            UI Showcase & Style Guide
          </h1>
          <p class="text-sm text-muted-foreground mt-1 leading-relaxed">
            Menampilkan implementasi dari <code class="bg-muted px-1.5 py-0.5 rounded text-xs">STYLE_GUIDE.md</code> dengan warna brand RSUD Maria Walanda Maramis.
          </p>
        </div>
        <div class="flex items-center gap-3">
          <Button variant="outline" @click="showInLayout = true" class="gap-1.5 shadow-sm">
            <Layout class="size-4" />
            Mode App Layout
          </Button>
          <Button variant="outline" @click="toggleDarkMode" class="gap-1.5 shadow-sm">
            <Sun v-if="isDarkMode" class="size-4 text-amber-500" />
            <Moon v-else class="size-4 text-indigo-500" />
            {{ isDarkMode ? 'Mode Terang' : 'Mode Gelap' }}
          </Button>
        </div>
      </div>

      <!-- SECTION 1: DESIGN TOKENS -->
      <section class="space-y-4">
        <div class="flex items-center gap-2 border-b border-border/60 pb-2">
          <Sparkles class="size-5 text-primary" />
          <h2 class="text-lg font-semibold text-secondary dark:text-foreground">1. Design Tokens & Palet Warna</h2>
        </div>
        
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
          <!-- Primary (Pink Coral) -->
          <div class="bg-card border border-border/80 rounded-xl p-4 shadow-sm flex flex-col justify-between min-h-[140px]">
            <div>
              <div class="size-8 rounded-lg bg-[#FF8781] border border-black/10 mb-2"></div>
              <span class="text-xs font-semibold text-foreground block">Primary (Soft Coral)</span>
              <span class="text-[11px] text-muted-foreground font-mono">#FF8781</span>
            </div>
            <span class="text-[10px] bg-primary/10 text-primary py-0.5 px-1.5 rounded font-mono mt-2 self-start">oklch(0.707 0.174 22.8)</span>
          </div>

          <!-- Primary Dark -->
          <div class="bg-card border border-border/80 rounded-xl p-4 shadow-sm flex flex-col justify-between min-h-[140px]">
            <div>
              <div class="size-8 rounded-lg bg-[#E64E47] border border-black/10 mb-2"></div>
              <span class="text-xs font-semibold text-foreground block">Primary Dark (Buttons)</span>
              <span class="text-[11px] text-muted-foreground font-mono">#E64E47</span>
            </div>
            <span class="text-[10px] bg-primary/10 text-primary py-0.5 px-1.5 rounded font-mono mt-2 self-start">oklch(0.60 0.18 22.0)</span>
          </div>

          <!-- Secondary -->
          <div class="bg-card border border-border/80 rounded-xl p-4 shadow-sm flex flex-col justify-between min-h-[140px]">
            <div>
              <div class="size-8 rounded-lg bg-[#1E293B] border border-black/10 mb-2"></div>
              <span class="text-xs font-semibold text-foreground block">Secondary (Navy)</span>
              <span class="text-[11px] text-muted-foreground font-mono">#1E293B</span>
            </div>
            <span class="text-[10px] bg-secondary/10 text-secondary dark:text-muted-foreground py-0.5 px-1.5 rounded font-mono mt-2 self-start">oklch(0.25 0.02 240)</span>
          </div>

          <!-- Accent -->
          <div class="bg-card border border-border/80 rounded-xl p-4 shadow-sm flex flex-col justify-between min-h-[140px]">
            <div>
              <div class="size-8 rounded-lg bg-[#4ADE80] border border-black/10 mb-2"></div>
              <span class="text-xs font-semibold text-foreground block">Accent (Soft Green)</span>
              <span class="text-[11px] text-muted-foreground font-mono">#4ADE80</span>
            </div>
            <span class="text-[10px] bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 py-0.5 px-1.5 rounded font-mono mt-2 self-start">oklch(0.75 0.15 140)</span>
          </div>

          <!-- Background Light -->
          <div class="bg-card border border-border/80 rounded-xl p-4 shadow-sm flex flex-col justify-between min-h-[140px]">
            <div>
              <div class="size-8 rounded-lg bg-[#F8FAFC] border border-black/10 mb-2"></div>
              <span class="text-xs font-semibold text-foreground block">BG Light</span>
              <span class="text-[11px] text-muted-foreground font-mono">#F8FAFC</span>
            </div>
            <span class="text-[10px] bg-muted text-muted-foreground py-0.5 px-1.5 rounded font-mono mt-2 self-start">oklch(0.98 0.005 240)</span>
          </div>

          <!-- Background Dark -->
          <div class="bg-card border border-border/80 rounded-xl p-4 shadow-sm flex flex-col justify-between min-h-[140px]">
            <div>
              <div class="size-8 rounded-lg bg-[#09090B] border border-white/10 mb-2"></div>
              <span class="text-xs font-semibold text-foreground block">BG Dark</span>
              <span class="text-[11px] text-muted-foreground font-mono">#09090B</span>
            </div>
            <span class="text-[10px] bg-muted text-muted-foreground py-0.5 px-1.5 rounded font-mono mt-2 self-start">oklch(0.145 0 0)</span>
          </div>
        </div>
      </section>

      <!-- SECTION 2: TYPOGRAPHY -->
      <section class="space-y-4">
        <div class="flex items-center gap-2 border-b border-border/60 pb-2">
          <Sparkles class="size-5 text-primary" />
          <h2 class="text-lg font-semibold text-secondary dark:text-foreground">2. Tipografi</h2>
        </div>
        
        <div class="bg-card border border-border/80 rounded-xl p-6 shadow-sm space-y-4">
          <div>
            <span class="text-xs font-semibold text-primary uppercase tracking-widest block mb-1">Heading Utama (text-2xl font-bold tracking-tight text-secondary)</span>
            <h1 class="text-2xl font-bold tracking-tight text-secondary dark:text-foreground">
              Sistem Informasi Pelayanan Daerah - RSUD MWM
            </h1>
          </div>
          <hr class="border-border/60" />
          <div>
            <span class="text-xs font-semibold text-primary uppercase tracking-widest block mb-1">Sub-heading (text-lg font-semibold text-secondary/90)</span>
            <h2 class="text-lg font-semibold text-secondary/90 dark:text-foreground/90">
              Manajemen Data Pengguna & Otoritas Peran
            </h2>
          </div>
          <hr class="border-border/60" />
          <div>
            <span class="text-xs font-semibold text-primary uppercase tracking-widest block mb-1">Teks Utama (text-sm text-muted-foreground leading-relaxed)</span>
            <p class="text-sm text-muted-foreground leading-relaxed max-w-3xl">
              Aplikasi ini mengadopsi standar identitas RSUD Maria Walanda Maramis. Hak akses dan otorisasi dibatasi berdasarkan peran masing-masing pengguna. Segala bentuk aktivitas di dalam sistem akan tercatat dalam log audit secara otomatis demi keamanan data pasien dan rumah sakit.
            </p>
          </div>
          <hr class="border-border/60" />
          <div>
            <span class="text-xs font-semibold text-primary uppercase tracking-widest block mb-1">Label / Info Kecil (text-xs font-medium)</span>
            <span class="text-xs font-medium text-muted-foreground bg-muted py-1 px-2.5 rounded-full inline-block">
              Terakhir diperbarui: 30 Juni 2026 18:00
            </span>
          </div>
        </div>
      </section>

      <!-- SECTION 3: BUTTONS -->
      <section class="space-y-4">
        <div class="flex items-center gap-2 border-b border-border/60 pb-2">
          <Sparkles class="size-5 text-primary" />
          <h2 class="text-lg font-semibold text-secondary dark:text-foreground">3. Tombol (Buttons) & Variasi</h2>
        </div>

        <div class="bg-card border border-border/80 rounded-xl p-6 shadow-sm space-y-6">
          <!-- Button Variants -->
          <div>
            <h3 class="text-sm font-semibold text-secondary dark:text-foreground mb-3">Varian Tombol</h3>
            <div class="flex flex-wrap gap-3">
              <!-- Primary -->
              <Button variant="default">
                Primary Button
              </Button>
              
              <!-- Secondary -->
              <Button variant="secondary">
                Secondary
              </Button>
              
              <!-- Outline -->
              <Button variant="outline">
                Outline / Bordered
              </Button>

              <!-- Ghost -->
              <Button variant="ghost">
                Ghost Button
              </Button>

              <!-- Destructive -->
              <Button variant="destructive">
                <Trash2 class="size-4 mr-1.5" />
                Destructive
              </Button>

              <!-- Link -->
              <Button variant="link">
                Link Button
              </Button>
            </div>
          </div>

          <!-- Button Sizes -->
          <div>
            <h3 class="text-sm font-semibold text-secondary dark:text-foreground mb-3">Ukuran Tombol</h3>
            <div class="flex flex-wrap items-center gap-3">
              <Button size="lg" variant="default">
                Large (lg)
              </Button>
              <Button size="default" variant="default">
                Default
              </Button>
              <Button size="sm" variant="default">
                Small (sm)
              </Button>
              <Button size="xs" variant="default">
                Extra Small (xs)
              </Button>
              <Button size="icon" variant="outline" aria-label="Settings">
                <Settings class="size-4" />
              </Button>
              <Button size="icon-sm" variant="outline" aria-label="Activity">
                <Activity class="size-3.5" />
              </Button>
            </div>
          </div>

          <!-- Micro-interactions visual check -->
          <div>
            <h3 class="text-sm font-semibold text-secondary dark:text-foreground mb-3">Interaksi Mikro (Klik/Tekan & Disabled)</h3>
            <div class="flex flex-wrap gap-4">
              <Button variant="default" class="active:scale-[0.98] transition-all duration-200">
                Tekan Saya (Scale Effect)
              </Button>
              <Button variant="default" disabled>
                Disabled State
              </Button>
              <Button variant="outline" disabled>
                Disabled Outline
              </Button>
            </div>
          </div>
        </div>
      </section>

      <!-- SECTION 4: FORMS AND INPUTS -->
      <section class="space-y-4">
        <div class="flex items-center gap-2 border-b border-border/60 pb-2">
          <Sparkles class="size-5 text-primary" />
          <h2 class="text-lg font-semibold text-secondary dark:text-foreground">4. Komponen Form & Input</h2>
        </div>

        <div class="bg-card border border-border/80 rounded-xl p-6 shadow-sm">
          <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
            
            <!-- Standard Inputs -->
            <div class="space-y-4">
              <h3 class="text-sm font-semibold text-secondary dark:text-foreground">Teks Input & State</h3>
              
              <!-- Normal Input -->
              <div class="grid gap-1.5">
                <Label for="username" class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">Nama Instansi</Label>
                <Input id="username" type="text" v-model="textInputValue" placeholder="Masukkan nama instansi" class="focus-visible:ring-primary" />
                <span class="text-[11px] text-muted-foreground">Fokus untuk melihat ring berwarna Primary Coral.</span>
              </div>

              <!-- Input with email -->
              <div class="grid gap-1.5">
                <Label for="email" class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">Alamat Email</Label>
                <div class="relative flex items-center">
                  <Mail class="absolute left-3 size-4 text-muted-foreground" />
                  <Input id="email" type="email" v-model="emailInputValue" placeholder="nama@email.com" class="pl-9 focus-visible:ring-primary" />
                </div>
              </div>
            </div>

            <!-- Validation/Disabled Inputs -->
            <div class="space-y-4">
              <h3 class="text-sm font-semibold text-secondary dark:text-foreground">Validasi & Disabled</h3>
              
              <!-- Invalid state -->
              <div class="grid gap-1.5">
                <Label for="error-input" class="text-xs font-semibold text-destructive uppercase tracking-wider">Kode Keuangan (Error)</Label>
                <Input id="error-input" type="text" v-model="errorInputValue" aria-invalid="true" class="border-destructive focus-visible:ring-destructive/20" />
                <span class="text-[11px] text-destructive">Format kode keuangan tidak sesuai standar.</span>
              </div>

              <!-- Disabled Input -->
              <div class="grid gap-1.5">
                <Label for="disabled-input" class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">ID Sistem (Terkunci)</Label>
                <Input id="disabled-input" type="text" value="SYS-MWM-9988" disabled />
              </div>
            </div>

            <!-- Select, Checkbox & Controls -->
            <div class="space-y-4">
              <h3 class="text-sm font-semibold text-secondary dark:text-foreground">Kontrol Pilihan (Select & Checkbox)</h3>

              <!-- Select -->
              <div class="grid gap-1.5">
                <Label for="role-select" class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">Pilih Hak Akses (Select)</Label>
                <Select v-model="selectValue">
                  <SelectTrigger id="role-select" class="w-full">
                    <SelectValue placeholder="Pilih tingkat akses..." />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectGroup>
                      <SelectLabel>Tingkatan Otoritas</SelectLabel>
                      <SelectItem value="admin">Administrator (Sistem)</SelectItem>
                      <SelectItem value="dokter">Dokter / Tenaga Medis</SelectItem>
                      <SelectItem value="perawat">Perawat / Kepala Bangsal</SelectItem>
                      <SelectItem value="keuangan">Bagian Keuangan / Kasir</SelectItem>
                    </SelectGroup>
                  </SelectContent>
                </Select>
                <span class="text-[11px] text-muted-foreground" v-if="selectValue">Pilihan aktif: <strong class="text-foreground font-mono">{{ selectValue }}</strong></span>
              </div>

              <!-- Checkbox -->
              <div class="flex items-center gap-2 pt-2">
                <Checkbox id="terms" v-model:checked="checkboxValue" />
                <Label for="terms" class="text-xs font-medium text-muted-foreground cursor-pointer select-none">
                  Setujui syarat pengerjaan sesuai pedoman RSUD MWM
                </Label>
              </div>
              <div class="flex items-center gap-2 opacity-50">
                <Checkbox id="terms-disabled" disabled :checked="true" />
                <Label for="terms-disabled" class="text-xs font-medium text-muted-foreground cursor-not-allowed select-none">
                  Opsi persetujuan audit (Nonaktif)
                </Label>
              </div>
            </div>

          </div>
        </div>
      </section>

      <!-- SECTION 5: CARDS -->
      <section class="space-y-4">
        <div class="flex items-center gap-2 border-b border-border/60 pb-2">
          <Sparkles class="size-5 text-primary" />
          <h2 class="text-lg font-semibold text-secondary dark:text-foreground">5. Desain Kartu (Cards)</h2>
        </div>

        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
          <!-- Card 1: Standard Static Card -->
          <Card class="border border-border/80 shadow-sm">
            <CardHeader class="px-6 pt-6 pb-3">
              <CardTitle class="text-base font-bold text-secondary dark:text-foreground">Informasi Instansi</CardTitle>
              <CardDescription class="text-xs text-muted-foreground mt-0.5">Profil utama rumah sakit saat ini</CardDescription>
            </CardHeader>
            <CardContent class="px-6 py-2 space-y-2">
              <p class="text-sm text-muted-foreground">
                <strong>Nama:</strong> RSUD Maria Walanda Maramis
              </p>
              <p class="text-sm text-muted-foreground">
                <strong>Alamat:</strong> Jl. Ring Road Bypass, Minahasa Utara, Sulawesi Utara.
              </p>
              <p class="text-sm text-muted-foreground">
                <strong>Tipe:</strong> Rumah Sakit Umum Daerah
              </p>
            </CardContent>
            <CardFooter class="px-6 py-4 justify-between mt-auto">
              <span class="text-xs text-muted-foreground font-medium">Terverifikasi</span>
              <Button size="xs" variant="outline">Ubah Profil</Button>
            </CardFooter>
          </Card>

          <!-- Card 2: Premium Interactive Card -->
          <Card class="border border-border/80 shadow-sm hover:-translate-y-0.5 hover:shadow-md transition-all duration-200 ease-out cursor-pointer group/premium">
            <CardHeader class="px-6 pt-6 pb-3">
              <div>
                <CardTitle class="text-base font-bold text-secondary dark:text-foreground">Kartu Interaktif (Hover State)</CardTitle>
                <CardDescription class="text-xs text-muted-foreground mt-0.5">Sentuh/layangkan kursor untuk melihat efek angkat mikro</CardDescription>
              </div>
              <CardAction class="p-0 pt-1">
                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full inline-flex items-center gap-1 bg-[#4ADE80]/15 text-emerald-700 dark:text-emerald-400">
                  <span class="size-1.5 rounded-full bg-[#4ADE80] animate-pulse"></span>
                  Sistem Aktif
                </span>
              </CardAction>
            </CardHeader>
            <CardContent class="px-6 py-2 text-sm text-muted-foreground leading-relaxed">
              Kartu ini mengimplementasikan transisi halus <code class="bg-muted px-1 py-0.5 rounded text-[11px]">hover:-translate-y-0.5 hover:shadow-md</code>. Sangat cocok digunakan untuk tombol jalan pintas cepat pada dasbor.
            </CardContent>
            <CardFooter class="bg-transparent border-t-0 px-6 pb-6 pt-2 mt-auto flex items-center justify-end text-primary font-semibold text-xs gap-1 group-hover/premium:translate-x-1 transition-transform">
              Buka Modul
              <ChevronRight class="size-4" />
            </CardFooter>
          </Card>

          <!-- Card 3: Complex Card with Action slot -->
          <Card class="border border-border/80 shadow-sm">
            <CardHeader class="px-6 pt-6 pb-3">
              <div>
                <CardTitle class="text-base font-bold text-secondary dark:text-foreground">Log Aktivitas Terkini</CardTitle>
                <CardDescription class="text-xs text-muted-foreground mt-0.5">Pemantauan aksi sistem real-time</CardDescription>
              </div>
              <CardAction class="p-0 pt-1">
                <Button size="icon-xs" variant="ghost">
                  <MoreVertical class="size-4 text-muted-foreground" />
                </Button>
              </CardAction>
            </CardHeader>
            <CardContent class="px-6 py-2">
              <ul class="space-y-3">
                <li class="flex items-start gap-2 text-xs text-muted-foreground">
                  <span class="size-1.5 rounded-full bg-primary mt-1.5"></span>
                  <div>
                    <span class="font-medium text-foreground">admin_mwm</span> mengubah hak akses role 'Keuangan'
                    <span class="block text-[10px] text-muted-foreground/80">2 menit yang lalu</span>
                  </div>
                </li>
                <li class="flex items-start gap-2 text-xs text-muted-foreground">
                  <span class="size-1.5 rounded-full bg-primary mt-1.5"></span>
                  <div>
                    <span class="font-medium text-foreground">dr. john_bedah</span> mengunggah dokumen BRM baru
                    <span class="block text-[10px] text-muted-foreground/80">15 menit yang lalu</span>
                  </div>
                </li>
              </ul>
            </CardContent>
            <CardFooter class="px-6 py-4 justify-center mt-auto">
              <Button size="xs" variant="link" class="w-full text-center">Tampilkan Semua Log</Button>
            </CardFooter>
          </Card>
        </div>
      </section>

      <!-- SECTION 6: TABLES -->
      <section class="space-y-4">
        <div class="flex items-center gap-2 border-b border-border/60 pb-2">
          <Sparkles class="size-5 text-primary" />
          <h2 class="text-lg font-semibold text-secondary dark:text-foreground">6. Tabel Data & Badge Status</h2>
        </div>

        <div class="bg-card border border-border/80 rounded-xl overflow-hidden shadow-sm">
          <div class="p-4 border-b border-border/60 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 bg-muted/20">
            <div>
              <h3 class="text-sm font-semibold text-secondary dark:text-foreground">Daftar Staf & Pengguna</h3>
              <p class="text-xs text-muted-foreground mt-0.5">Pengelolaan data personel rumah sakit yang terintegrasi</p>
            </div>
            <div class="flex items-center gap-2">
              <Input type="text" placeholder="Cari nama..." class="max-w-[200px] h-7 text-xs bg-background" />
              <Button size="xs" variant="default" class="gap-1">
                <Plus class="size-3" />
                Tambah
              </Button>
            </div>
          </div>
          
          <Table class="min-w-full">
            <TableHeader class="bg-muted/40">
              <TableRow>
                <TableHead class="font-semibold text-xs uppercase tracking-wider text-muted-foreground py-3">Nama Pengguna</TableHead>
                <TableHead class="font-semibold text-xs uppercase tracking-wider text-muted-foreground py-3">Hak Akses / Peran</TableHead>
                <TableHead class="font-semibold text-xs uppercase tracking-wider text-muted-foreground py-3">Aktivitas Terakhir</TableHead>
                <TableHead class="font-semibold text-xs uppercase tracking-wider text-muted-foreground py-3">Status</TableHead>
                <TableHead class="font-semibold text-xs uppercase tracking-wider text-muted-foreground py-3 text-right">Aksi</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="user in users" :key="user.id">
                <TableCell class="py-3">
                  <div class="flex items-center gap-2.5">
                    <div class="size-8 rounded-full bg-primary/10 text-primary flex items-center justify-center font-bold text-xs">
                      {{ user.name.charAt(0) }}
                    </div>
                    <div>
                      <span class="font-medium text-foreground text-sm block">{{ user.name }}</span>
                      <span class="text-xs text-muted-foreground block">{{ user.email }}</span>
                    </div>
                  </div>
                </TableCell>
                <TableCell class="py-3 text-sm text-foreground/90">
                  <div class="flex items-center gap-1">
                    <Shield class="size-3.5 text-secondary dark:text-muted-foreground" />
                    {{ user.role }}
                  </div>
                </TableCell>
                <TableCell class="py-3 text-xs text-muted-foreground max-w-[200px] truncate">
                  {{ user.activity }}
                </TableCell>
                <TableCell class="py-3">
                  <!-- Custom Badge matching Accent (Soft Green) or Warning/Muted states -->
                  <span :class="[
                    'text-[10px] font-bold px-2 py-0.5 rounded-full inline-flex items-center gap-1',
                    user.status === 'Aktif' 
                      ? 'bg-[#4ADE80]/10 text-emerald-700 dark:text-emerald-400' 
                      : 'bg-muted text-muted-foreground'
                  ]">
                    <span :class="['size-1.5 rounded-full', user.status === 'Aktif' ? 'bg-[#4ADE80]' : 'bg-muted-foreground']"></span>
                    {{ user.status }}
                  </span>
                </TableCell>
                <TableCell class="py-3 text-right">
                  <div class="inline-flex gap-1.5">
                    <Button size="icon-xs" variant="outline" aria-label="Lihat">
                      <Eye class="size-3.5" />
                    </Button>
                    <Button size="icon-xs" variant="outline" aria-label="Ubah">
                      <Edit2 class="size-3.5 text-indigo-500" />
                    </Button>
                  </div>
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>
          
          <div class="p-3 border-t border-border/60 flex items-center justify-between text-xs text-muted-foreground bg-muted/10">
            <span>Menampilkan 4 dari 4 staf terdaftar</span>
            <div class="flex items-center gap-2">
              <Button size="xs" variant="outline" disabled>Sebelumnya</Button>
              <Button size="xs" variant="outline" disabled>Selanjutnya</Button>
            </div>
          </div>
        </div>
      </section>

    </div>
  </component>
</template>
