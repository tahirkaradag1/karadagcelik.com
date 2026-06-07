# PayTR kurulum notlari

Karadag Celik magazasi PayTR iFrame API icin hazirdir. PayTR bilgileri
girilene kadar mevcut siparis talebi sistemi calismaya devam eder.

## 1. PayTR basvurusu

PayTR Sanal POS basvurusu sirket sahibi tarafindan tamamlanmalidir:

https://www.paytr.com/virtual-pos

Basvuru sirasinda sirket ve yetkili bilgileri, banka hesabi ve istenen
resmi belgeler PayTR'a iletilir. Bu bilgiler GitHub'a veya sohbet
mesajlarina konulmamalidir.

## 2. Entegrasyon bilgileri

Basvuru onaylandiktan sonra PayTR Magaza Paneli icindeki
`Destek & Kurulum > Entegrasyon Bilgileri` alanindan su degerler alinir:

- Magaza No (`merchant_id`)
- Magaza Parola (`merchant_key`)
- Magaza Gizli Anahtar (`merchant_salt`)

Bu degerler yalnizca Hostinger'daki `kc-test/api/config.php` dosyasina
eklenir.

```php
'paytr' => [
    'merchant_id' => 'PAYTR_MAGAZA_NO',
    'merchant_key' => 'PAYTR_MAGAZA_PAROLA',
    'merchant_salt' => 'PAYTR_GIZLI_ANAHTAR',
    'test_mode' => true,
    'debug_on' => true,
    'no_installment' => false,
    'max_installment' => 0,
    'timeout_limit' => 30,
    'base_url' => '',
],
```

## 3. Bildirim adresi

PayTR Magaza Paneli'ndeki Bildirim URL alani test asamasinda:

```text
https://karadagcelik.com/kc-test/api/paytr-callback.php
```

Canli site kok dizine tasindiginda:

```text
https://karadagcelik.com/api/paytr-callback.php
```

## 4. Test ve canli gecis

Ilk testlerde `test_mode` ve `debug_on` degerleri `true` kalir.
PayTR panelinde test odemesi basarili ve callback durumu tamamlandiktan
sonra:

```php
'test_mode' => false,
'debug_on' => false,
```

Canli moda gecmeden once mesafeli satis, on bilgilendirme, iade/iptal,
KVKK, gizlilik ve sirket iletisim bilgilerinin sitede gercek sirket
bilgileriyle yayinda olmasi gerekir.
