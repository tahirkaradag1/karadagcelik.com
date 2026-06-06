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
