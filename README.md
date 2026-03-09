# POS Flex (PHP + MySQL)

Aplikasi Point of Sales universal untuk coffee shop, minimarket, kantin sekolah, dan retail lain dengan konsep modular seperti CMS ringan.

## Fitur Inti
- Login admin.
- Dashboard ringkas (produk, stok rendah, omzet harian, AI task).
- Manajemen produk (tambah/hapus, harga, stok, kategori, unit).
- Inventory barang masuk/keluar.
- Penjualan cepat dan pengurangan stok otomatis.
- Pengaturan bisnis + aktivasi modul.
- **AI Trigger Hook**: centang `Trigger AI review/optimization/audit` di form untuk membuat task otomatis pada tabel `ai_tasks`.
- Webhook callback AI provider (`index.php?page=ai-webhook`) untuk menyimpan hasil rekomendasi AI di `ai_suggestions`.

## Struktur Folder
- `public/` : entry point + assets.
- `app/` : class dan helper inti.
- `config/` : konfigurasi app + database.
- `templates/` : layout UI.
- `sql/` : skema database siap import.

## Cara Menjalankan
1. Buat database dari `sql/pos_flex.sql`.
2. Ubah `config/database.php` sesuai credential MySQL.
3. Jalankan PHP built-in server:
   ```bash
   php -S 0.0.0.0:8000 -t public
   ```
4. Login default:
   - Email: `admin@posflex.local`
   - Password: `admin123`

## Contoh Integrasi AI Trigger
1. Sistem membuat task ke `ai_tasks` saat checkbox AI dipilih.
2. Worker eksternal (script AI apapun) membaca task status `pending`.
3. Setelah diproses, worker kirim POST ke:
   - `POST /index.php?page=ai-webhook`
   - Header: `X-POS-AI-SECRET: <webhook_secret>`
   - Body JSON:
   ```json
   {
     "task_id": 10,
     "provider": "gpt|claude|gemini|custom",
     "suggestion": "Naikkan reorder point SKU KOPI-01 dari 10 ke 20"
   }
   ```

Dengan pola ini, Anda bisa mengganti provider AI tanpa mengubah core sistem POS.
