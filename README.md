# Karadag Celik Web Platform

Karadag Celik icin WordPress/WooCommerce sinirlarindan cikarak ozel tasarimli online teklif ve magaza sistemi kurma projesi.

## Mevcut Durum

Bu repo su anda ilk MVP prototipini icerir. Prototip HTML/CSS/JS ile hazirlandi; Hostinger uzerinde PHP endpointleri ile teklif, iletisim ve magaza siparis talebi akislari canliya baglanabilir.

Ana prototip:

```text
prototype/index.html
```

## Vizyon

Sitenin ana deneyimi iki kapi uzerine kuruldu:

- Online Teklif Olustur: Musteri CAD/teknik cizim dosyasini yukler, iletisim bilgilerini girer, Karadag Celik ekibi manuel teklif hazirlar.
- Magaza: Sabit fiyatli endustriyel parcalar ve urunler sergilenir, sepet ve odeme akisiyle satin alma hedeflenir.

## Prototipte Olanlar

- Iki kapili ana giris ekrani
- Apple esintili premium arayuz dili
- Alt dock navigasyon
- Online teklif formu arayuzu
- Dosya yukleme arayuzu
- Magaza ve urun kartlari
- Urun detay sayfasi altyapisi
- Urun bilgi/ansiklopedi bolumu altyapisi
- Sepete ekleme
- Sagdan acilan sepet paneli
- Profil ve Google giris prototipi
- Iletisim sayfasi
- PHP endpointleri:
  - `prototype/api/quote.php`
  - `prototype/api/contact.php`
  - `prototype/api/order.php`

## Sonraki Evre

Bu statik prototip, sonraki evrede gercek backend sistemine tasinacak:

- Ilk canli evre icin Hostinger PHP
- Sonraki evre icin Next.js veya uygun Node.js mimarisi
- MySQL/MariaDB veritabani
- Google ile giris
- Online teklif dosya yukleme ve e-posta bildirimi
- Admin paneli
- Magaza, siparis ve sepet backend'i
- PayTR veya iyzico odeme entegrasyonu
- Hostinger uzerinde staging/canli yayin

## Dokumanlar

- `docs/PROJECT_BRIEF.md`
- `docs/MVP_SCOPE.md`
- `docs/NEXT_STEPS.md`
- `docs/HOSTINGER_DEPLOYMENT.md`

## Guvenlik

Bu repoya asagidaki dosya ve bilgiler eklenmemelidir:

- `.env` dosyalari
- API anahtarlari
- PayTR/iyzico anahtarlari
- Hostinger sifreleri veya FTP bilgileri
- Database sifreleri
- Codex/ChatGPT auth dosyalari
