# Hostinger Deployment Notlari

## Hedef

Ilk canli evrede mevcut prototip, Hostinger uzerinde WordPress yerine calisan statik/PHP destekli bir site olarak yayinlanabilir.

Bu evrede:

- Online teklif formu POST ile `api/quote.php` dosyasina gider.
- Yuklenen dosyalar sunucuda ozel klasore kaydedilir.
- Karadag Celik ekibine e-posta bildirimi gonderilir.
- Musteriye otomatik alindi e-postasi gonderilmeye calisilir.
- Iletisim formu `api/contact.php` uzerinden e-posta bildirimi gonderir.
- Sepet checkout formu `api/order.php` uzerinden magaza siparis talebi olusturur.

## Canliya Almadan Once

Hostinger panelinden kontrol edilecekler:

- Manuel backup olusturuldu mu?
- `public_html` icinde mevcut WordPress dosyalari yedeklendi mi?
- SSL aktif mi?
- PHP surumu aktif mi?
- `mail()` fonksiyonu calisiyor mu?
- Dosya yukleme limiti yeterli mi?
- `info@karadagcelik.com` mail kutusu aktif mi?

## Dosya Yapisi

Canliya alinacak klasor:

```text
prototype/
```

Hostinger `public_html` icine su dosyalar yuklenir:

```text
index.html
styles.css
app.js
api/
```

Preview PNG dosyalari canli site icin sart degildir; GitHub dokumantasyonu icin tutulabilir.

## Config

Sunucuda:

```text
api/config.example.php
```

dosyasi kopyalanip:

```text
api/config.php
```

olarak kaydedilebilir.

`config.php` GitHub'a yuklenmemelidir.

Ornek notification email listesi:

```php
'notification_emails' => [
    'info@karadagcelik.com',
    'sirket-gmail-adresi@example.com',
],
```

Gerekirse test asamasinda kisisel test e-posta adresi de yalnizca sunucudaki `config.php` dosyasina eklenebilir. Bu adresler GitHub'a yuklenmemelidir.

## WordPress'i Hemen Silme

En guvenli yol:

1. Hostinger'da manuel backup al.
2. Mevcut `public_html` WordPress dosyalarini yedek klasore tasi veya indir.
3. Yeni siteyi `public_html` icine yukle.
4. Formlari test et.
5. Sorun olursa backup'tan geri don.

## Sonraki Evre

PHP destekli bu ilk canli surum, hizli yayina cikmak icindir.

Gercek tam sistem icin sonraki evrede:

- Next.js/Node.js
- MySQL/MariaDB
- Admin panel
- Google OAuth
- PayTR/iyzico odeme
- Siparis ve teklif takip paneli

baglanacaktir.

## MySQL ve Yonetim Paneli Kurulumu

WordPress veritabanini kullanmak yerine Karadag Celik platformu icin ayri bir
MySQL veritabani ve kullanicisi olusturulmalidir.

1. Hostinger `Databases -> MySQL Databases` ekraninda yeni veritabani olustur.
2. Guclu ve benzersiz bir veritabani parolasi belirle.
3. Guncel `api/` ve `admin/` klasorlerini test sitesine yukle.
4. Gizli kurulum anahtariyla `admin/install.php` sayfasini ac.
5. MySQL bilgilerini ve ilk yonetici hesabini kurulum formuna gir.
6. Kurulumdan sonra `admin/login.php` uzerinden giris yap.

Kurulum sihirbazi:

- Gerekli tablolari otomatik olusturur.
- Veritabani parolasini GitHub'a girmeyen `api/database.local.php` dosyasinda saklar.
- Ilk yonetici parolasini geri dondurulemez bir parola ozeti olarak saklar.
- Kurulum tamamlandiktan sonra ikinci bir yonetici olusturulmasina izin vermez.

Yonetim panelinde:

- Online teklif talepleri ve yuklenen dosyalar
- Magaza siparis talepleri ve urun kalemleri
- Iletisim mesajlari
- Kayit durumlari

goruntulenebilir.
