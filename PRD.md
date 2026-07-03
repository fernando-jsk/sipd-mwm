# Product Requirements Document (PRD): SIPD MWM

## 1. Latar Belakang & Visi Produk
**SIPD MWM** (Sistem Informasi Pemerintah Daerah - Rumah Sakit Daerah BLUD Maria Walanda Maramis) adalah sebuah sistem informasi berbasis web yang dirancang khusus untuk mendigitalisasi dan mengelola alur keuangan rumah sakit secara terintegrasi. 

**Tujuan Utama:** 
Menyediakan *platform* tersentralisasi untuk perencanaan keuangan, pencatatan transaksi (penerimaan & pengeluaran), fungsi akuntansi, serta menyajikan visualisasi data yang komprehensif (Dashboard) bagi jajaran Direksi untuk kebutuhan presentasi, *meeting*, dan pengambilan keputusan strategis.

## 2. Aktor & Target Pengguna (*User Personas*)
Sistem ini akan memiliki beberapa kelompok pengguna dengan hak akses (*roles*) yang berbeda-beda:
1. **Super Admin / IT**: Mengelola pengaturan sistem inti (User, Role, Hak Akses, Log).
2. **Tim Perencanaan**: Bertanggung jawab untuk melakukan *input* dan mengelola data Perencanaan Anggaran Tahunan.
3. **Bendahara Penerimaan**: Bertanggung jawab mencatat aliran dana masuk (penerimaan kas) dari operasional rumah sakit (mungkin secara harian).
4. **Bendahara Pengeluaran**: Bertanggung jawab mencatat aliran dana keluar (pengeluaran kas) sesuai dengan anggaran yang ada.
5. **Tim Akuntansi**: Melakukan penjurnalan, penyesuaian, dan penyusunan laporan keuangan (kebutuhan spesifik akan didefinisikan lebih lanjut).
6. **Direksi / Eksekutif**: Mengakses *Dashboard* untuk memantau metrik keuangan dan performa anggaran secara *real-time* lewat visualisasi grafik yang interaktif.

## 3. Modul yang Telah Selesai (Fase Fondasi)
Fondasi sistem keamanan dan pengaturan aplikasi telah berhasil dibangun dengan UI/UX yang premium:
- [x] **Autentikasi**: *Login*, *logout*, dan manajemen sesi.
- [x] **Manajemen Role & Hak Akses (RBAC)**: Pembuatan dan pengaturan izin secara dinamis.
- [x] **Manajemen User**: Registrasi, *edit*, dan pengelolaan akun pegawai.
- [x] **Log Aktivitas (Audit Trail)**: Perekaman historis aktivitas pengguna dengan indikator warna (*badges*), pemformatan waktu, dan fitur *search/filter*.
- [x] **Arsitektur UI/UX**: *Layouting* responsif dengan *sticky sidebar*, implementasi komponen *Shadcn Vue* (Card, Dialog, Breadcrumbs, dll).

## 4. Roadmap Pengembangan (Modul Selanjutnya)
Berikut adalah urutan prioritas pengerjaan berdasarkan *flow* data keuangan:

### **Fase 1: Master Data & Pengaturan Sistem (Target Saat Ini)**
- **Master Kode Rekening**: Modul CRUD (Create, Read, Update, Delete) untuk mengelola data master kode rekening (akun) standar akuntansi.
- **Tahun Anggaran**: Fitur "Tahun Anggaran Aktif" berupa *dropdown* (pemilih) global yang ditempatkan di **Sidebar** (dekat dengan profil *user*). Ini memungkinkan *user* berganti tahun anggaran secara instan kapan saja tanpa perlu *logout*.
- **Pengaturan Sistem (System Settings)**: Modul khusus Admin berbasis *Key-Value* agar dinamis dan bisa ditambah seiring berkembangnya sistem. Pengaturan pertama yang dimasukkan adalah **Validasi Pagu**:
  - *Strict/Block*: Memblokir transaksi jika melebihi anggaran (Error).
  - *Warning*: Mengizinkan transaksi dengan memunculkan peringatan (Warning).
- Pembaruan menu *Sidebar* menyesuaikan dengan penambahan modul-modul ini.

### **Fase 2: Modul Perencanaan Anggaran**
- Form *input* Rencana Anggaran (RBA/RKA) tahunan.
- Tabel daftar anggaran beserta plafon nilainya (terikat dengan Tahun Anggaran dan Kode Rekening).

### **Fase 3: Modul Bendahara (Penerimaan & Pengeluaran)**
- **Penerimaan**: Form *input* kas masuk dengan filter tanggal & kategori rekening.
- **Pengeluaran**: Form *input* kas keluar. 
- *Fitur Kunci*: Validasi sisa anggaran (mencegah pengeluaran melebihi plafon berdasarkan pengaturan Validasi Pagu di Fase 1).

### **Fase 4: Modul Akuntansi**
- Modul ini akan dibangun secara iteratif. Kebutuhan akan di-*explore* lebih lanjut seiring dengan berjalannya proses bisnis (misal: Jurnal Umum, Buku Besar, Laporan LRA).

### **Fase 5: Executive Dashboard (Direksi)**
- Pembuatan visualisasi data (Grafik/Chart) yang memanjakan mata.
- Metrik utama: Total Penerimaan vs Pengeluaran, Realisasi vs Target Anggaran, *Burn Rate*, dll.

---
*Dokumen ini akan terus diperbarui seiring dengan berjalannya proses pengembangan SIPD MWM.*
