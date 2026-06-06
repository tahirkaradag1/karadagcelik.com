# Sonraki Adimlar

## 1. Tasarim Geri Bildirimi

Kullanici prototipi acip su basliklarda geri bildirim verir:

- Ana iki kapi ekrani
- Online teklif sayfasi
- Magaza sayfasi
- Urun kartlari
- Urun detay sayfasi
- Sepet paneli
- Profil ve iletisim sayfalari
- Mobil gorunum

## 2. Hostinger Teknik Kontrol

Kodlamaya gercek backend olarak gecmeden once Hostinger'da sunlar kontrol edilir:

- Node.js kurulum ekrani
- Startup file ayarlari
- Build command / install command alanlari
- Environment variables alani
- MySQL/MariaDB baglanti bilgileri
- SSL ve DNS durumu
- Backup durumu

## 3. Mimari Secim

Muhtemel hedef mimari:

- Next.js
- Node.js backend/API routes
- MySQL/MariaDB
- Google OAuth
- PayTR odeme entegrasyonu
- SMTP/transactional mail
- Admin panel

## 4. Veritabani Taslagi

Ilk tablolar:

- users
- addresses
- products
- product_categories
- cart_items veya orders
- orders
- order_items
- quote_requests
- quote_request_files
- admin_users

## 5. Gercek Online Teklif Akisi

Yapilacaklar:

- Dosya turu sinirlama
- Dosya boyutu limiti
- Guvenli dosya storage
- Form validasyonu
- Mustreriye otomatik mail
- info@karadagcelik.com adresine bildirim
- Admin panelde teklif talebi goruntuleme

## 6. Magaza ve Odeme

Yapilacaklar:

- Urunleri veritabanindan cekme
- Sepet backend'i
- Siparis olusturma
- PayTR test entegrasyonu
- Odeme sonucu callback
- Siparis durum takibi

## 7. Canliya Alma

Yapilacaklar:

- Staging yayin
- Test odeme
- Test mail
- Mobil/desktop QA
- SEO metadata
- Sitemap
- Robots.txt
- Structured data
- Hostinger canli deploy
