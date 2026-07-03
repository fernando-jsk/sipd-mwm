# Aturan & Pedoman Pengerjaan Proyek

Setiap kali melakukan modifikasi kode, terutama bagian visual (HTML, CSS, Vue, Tailwind CSS), Anda wajib membaca dan mematuhi pedoman UI/UX yang telah disepakati bersama di:
[STYLE_GUIDE.md](file:///c:/laragon/www/sipd-mwm/STYLE_GUIDE.md)

Patuhi aturan warna primary, secondary, spacing, dan pola interaksi yang ada di dalam dokumen tersebut agar desain aplikasi konsisten dan premium.

## Aturan Pencatatan Log Aktivitas (Activity Logging)

Setiap kali Anda membuat fitur yang perlu dicatat aktivitasnya, bedakan antara dua pendekatan pencatatan berikut menggunakan `spatie/laravel-activitylog`:

### 1. Pencatatan Perubahan Data Model (CRUD)
Untuk aktivitas Create, Update, dan Delete pada suatu Model, Anda **wajib** menggunakan *trait* `LogsActivity` di dalam *Class Model* tersebut (jangan memanggil fungsi log secara manual di Controller agar tidak terjadi duplikasi dan agar *old/new properties* otomatis terekam).

**Contoh Penggunaan pada Model:**
```php
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class AccountCode extends Model
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['code', 'name', 'is_active'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('account_code')
            ->setDescriptionForEvent(fn(string $eventName) => "Aksi {$eventName} pada kode rekening {$this->code}");
    }
}
```
*(Catatan: Jangan tambahkan `activity()` di Controller jika Model sudah menggunakan trait ini).*

### 2. Pencatatan Aktivitas Kustom (Non-Model CRUD)
Jika aktivitas tersebut tidak berkaitan langsung dengan CRUD Model tertentu (misalnya: User Login, Mengubah Pengaturan Global secara dinamis), barulah Anda **wajib** menggunakan *helper* global `activity()`.
**Jangan pernah** menggunakan *class* kustom seperti `ActivityLogger`.

**Contoh Penggunaan pada Controller/Service:**
```php
activity('setting')
    ->log('Memperbarui pengaturan sistem secara global');
```
