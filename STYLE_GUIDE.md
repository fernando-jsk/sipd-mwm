# Pedoman Desain & UI/UX (Style Guide) - SIPD MWM

Dokumen ini adalah acuan utama pengerjaan antarmuka (UI) dan pengalaman pengguna (UX) untuk aplikasi **SIPD MWM**. Aturan ini didasarkan pada identitas logo resmi **RSUD Maria Walanda Maramis**.

---

## 1. Sistem Warna & Tema (Design Tokens)

Sistem warna menggunakan basis warna dari logo resmi: **Soft Coral Pink** (dari tanda palang merah logo) dan **Deep Navy** (dari warna teks utama logo).

### A. Token Warna CSS (Tailwind CSS v4 OKLCH)

Untuk mendapatkan kontras terbaik (WCAG Compliance) dan tampilan modern, berikut adalah warna yang diimplementasikan pada `resources/css/app.css`:

| Peran Warna | Contoh Hex | OKLCH Value | Keterangan |
| :--- | :--- | :--- | :--- |
| **Primary (Pink Coral)** | `#FF8781` | `oklch(0.707 0.174 22.8)` | Warna identitas utama (brand). Digunakan untuk aksen, border aktif, hover state, dan dekorasi brand. |
| **Primary Dark (Text/Button)** | `#E64E47` | `oklch(0.60 0.18 22.0)` | Digunakan untuk tombol utama (dengan teks putih) agar lulus uji kontras aksesibilitas. |
| **Secondary (Deep Navy)** | `#1E293B` | `oklch(0.25 0.02 240)` | Warna teks utama, header, sidebar, dan elemen struktural. |
| **Accent (Soft Green)** | `#4ADE80` | `oklch(0.75 0.15 140)` | Warna sukses, badge status aktif, atau dekorasi alam (terinspirasi dari daun kelapa pada logo). |
| **Background (Light)** | `#F8FAFC` | `oklch(0.98 0.005 240)` | Latar belakang halaman (bersih dan minimalis). |
| **Background (Dark)** | `#09090B` | `oklch(0.145 0 0)` | Latar belakang mode gelap. |
| **Destructive/Error** | `#EF4444` | *Tailwind Default* | Warna peringatan keras, form error, dan tombol hapus data. |
| **Warning/Pending** | `#F59E0B` | *Tailwind Default* | Warna peringatan menengah, status pending, atau info stok menipis. |
| **Muted/Disabled** | `#F1F5F9` | *Tailwind Default* | Background form yang dinonaktifkan (disabled) atau teks sekunder. |

---

## 2. Pedoman Komponen Antarmuka

Semua komponen Vue harus menggunakan utilitas Tailwind yang konsisten. Hindari penggunaan class ad-hoc yang tidak standar.

### A. Tombol (Buttons)
* **Primary Button**: `bg-primary text-primary-foreground hover:bg-primary/90 shadow-sm transition-all duration-200 active:scale-[0.98]`
* **Destructive Button**: `bg-destructive text-destructive-foreground hover:bg-destructive/90`
* **Ghost / Bordered Button**: `border border-input bg-background hover:bg-accent hover:text-accent-foreground`

### B. Kartu & Kontainer (Cards)
* Gunakan padding yang konsisten (`p-6` di desktop, `p-4` di perangkat mobile).
* Radius sudut menggunakan `rounded-xl` (`--radius: 0.75rem`).
* Border tipis menggunakan `border border-border/80` dengan bayangan sangat halus (`shadow-sm`).
* Untuk kartu dalam layout *grid* yang berpotensi memiliki tinggi bervariasi, selalu gunakan class `mt-auto` pada `<CardFooter>` agar posisinya sejajar menempel di dasar kartu.
* > [!WARNING]
  > **ATURAN KERAS (Jarak & Padding)**: Komponen bawaan Shadcn UI (`Card`, `CardHeader`, `CardContent`, `CardFooter`) sudah memiliki struktur *flex* dan *padding* bawaan. **DILARANG KERAS** menambahkan *class padding* eksplisit apa pun (seperti `p-5`, `pt-6`, `pb-6`, `px-4`) pada `<CardContent>`, `<CardHeader>`, atau `<CardFooter>`. Pemaksaan *class padding* akan merusak perhitungan *gap* dan menyebabkan spasi ganda yang berantakan! Biarkan struktur alami komponen yang mengatur jaraknya.
* > [!IMPORTANT]
  > Pastikan struktur HTML `<form>` **membungkus komponen `<Card>` secara penuh dari luar**, bukan diletakkan di dalam `<Card>`. Meletakkan `<form>` di dalam `<Card>` akan merusak struktur `flex gap` bawaannya.

### C. Form & Input
Setiap input harus selalu dibungkus dengan layout grid yang rapi:
```vue
<div class="grid gap-1.5">
  <Label for="email" class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">Alamat Email</Label>
  <Input id="email" type="email" placeholder="nama@email.com" class="focus-visible:ring-primary" />
</div>
```
* **Error State**: Tambahkan `aria-invalid="true"` dan gunakan `border-destructive focus-visible:ring-destructive/20` pada input, serta tambahkan pesan error berukuran `text-[11px] text-destructive` di bawahnya.
* **Disabled State**: Tambahkan atribut `disabled`. Komponen bawaan sudah akan otomatis mengaplikasikan opacity 50% dan `cursor-not-allowed`.

### D. Tabel & Data Display
* **Table Container**: Bungkus tabel dengan `<div class="bg-card border border-border/80 rounded-xl overflow-hidden shadow-sm">`.
* **Table Header**: Gunakan background abu-abu terang (misal: `bg-muted/40`) dengan teks `font-semibold text-xs uppercase tracking-wider text-muted-foreground`.
* **Status Badge**: Untuk status aktif, gunakan kombinasi warna Accent (Soft Green): `bg-[#4ADE80]/10 text-emerald-700`. Untuk status nonaktif, gunakan `bg-muted text-muted-foreground`.

---

## 3. Tipografi (Typography)

* **Font Utama**: Geist Sans / Instrument Sans (`font-sans`).
* **Ukuran Teks**:
  * Heading Utama: `text-2xl font-bold tracking-tight text-secondary`
  * Sub-heading: `text-lg font-semibold text-secondary/90`
  * Teks Utama: `text-sm text-muted-foreground leading-relaxed`
  * Label / Info Kecil: `text-xs font-medium`

---

## 4. Animasi & Transisi (Micro-interactions)

Agar antarmuka terasa hidup dan premium:
* Gunakan transisi halus pada setiap elemen interaktif (hover, active, focus): `transition-all duration-200 ease-out`.
* Tambahkan sedikit efek hover angkat pada kartu informasi utama jika interaktif: `hover:-translate-y-0.5 hover:shadow-md`.

---

## 5. Struktur Layout Halaman (Page Layout)

Agar jarak dan proporsi konten tetap konsisten antar halaman:
* **Main Container**: Selalu bungkus konten utama halaman dengan class pembungkus standar: `max-w-7xl mx-auto p-6 md:p-8 space-y-6` (atau `space-y-8` jika butuh jarak lebih renggang antar section).
* **Gunakan CSS Grid**: Hindari memosisikan layout dasar secara manual dengan pixel atau margin aneh. Manfaatkan grid (contoh: `grid md:grid-cols-2 lg:grid-cols-3 gap-6`) untuk komponen yang berjejer.
