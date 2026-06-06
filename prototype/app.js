const fallbackProducts = [
  {
    id: "makine-baglanti-plakasi",
    group: "industrial",
    name: "Makine Bağlantı Plakası",
    tag: "Endüstriyel",
    price: 1480,
    type: "plate",
    summary: "Lazer kesim ve hassas delik toleransı ile montaja hazır bağlantı plakası.",
    detail:
      "Makine gövdeleri, konveyör sistemleri ve üretim hatlarında kullanılabilecek çok amaçlı metal bağlantı parçası.",
  },
  {
    id: "disli-flans",
    group: "industrial",
    name: "Dişli Flanş Parça",
    tag: "Endüstriyel",
    price: 2350,
    type: "ring",
    summary: "Kalıp, makine ve aktarım sistemleri için yuvarlak kesim flanş parçası.",
    detail: "Çelik, paslanmaz veya alüminyum malzeme seçenekleriyle üretime uygun teknik flanş altyapısı.",
  },
  {
    id: "gunes-paneli-ayagi",
    group: "industrial",
    name: "Güneş Paneli Ayağı",
    tag: "Enerji",
    price: 890,
    type: "bracket",
    summary: "Güneş enerji sistemleri için bükümlü ve delikli montaj ayağı.",
    detail: "Saha montajında hız, dayanıklılık ve ölçülü kurulum gerektiren panel taşıyıcı parçası.",
  },
  {
    id: "metal-kablo-kanali",
    group: "industrial",
    name: "Metal Kablo Kanalı",
    tag: "Tesisat",
    price: 760,
    type: "rail",
    summary: "Endüstriyel tesisat ve pano hatları için bükümlü kablo taşıma kanalı.",
    detail: "Elektrik ve otomasyon projelerinde kablo geçişlerini düzenli tutmak için tasarlandı.",
  },
  {
    id: "dekoratif-metal-raf",
    group: "retail",
    name: "Dekoratif Metal Raf",
    tag: "Ürün",
    price: 1250,
    type: "bracket",
    summary: "Minimal iç mekanlar için lazer kesim metal raf ve taşıyıcı set.",
    detail: "Mağaza, ofis ve ev kullanımı için sade görünümlü, dayanıklı metal raf ürünü.",
  },
  {
    id: "bahce-paneli",
    group: "retail",
    name: "Lazer Kesim Bahçe Paneli",
    tag: "Ürün",
    price: 3150,
    type: "plate",
    summary: "Dış mekanlarda dekoratif bölme veya cephe etkisi için metal panel.",
    detail: "Desenli panel altyapısı ileride gerçek modeller, ölçüler ve kaplama seçenekleriyle doldurulabilir.",
  },
  {
    id: "masa-ayagi-seti",
    group: "retail",
    name: "Metal Masa Ayağı Seti",
    tag: "Ürün",
    price: 1980,
    type: "rail",
    summary: "Ahşap veya kompozit tablalar için sabit fiyatlı metal masa ayağı.",
    detail: "Lazer kesim ve bükümle üretilen masa ayağı setleri için satın alma sayfası örneği.",
  },
  {
    id: "duvar-logo-panelleri",
    group: "retail",
    name: "Duvar Logo Paneli",
    tag: "Ürün",
    price: 2250,
    type: "plate",
    summary: "İşletmeler için metal logo, tabela veya dekoratif duvar paneli.",
    detail: "Marka uygulamalarında kullanılabilecek lazer kesim dekoratif metal panel altyapısı.",
  },
];

let products = [...fallbackProducts];

const state = {
  cart: JSON.parse(localStorage.getItem("kc_cart") || "[]"),
  activeProductId: products[0].id,
  detailQty: 1,
  checkoutOpen: false,
};

const views = [...document.querySelectorAll(".view")];
const dock = document.getElementById("dock");
const toast = document.getElementById("toast");
const cartBadge = document.getElementById("cartBadge");
const cartDrawer = document.getElementById("cartDrawer");
const drawerScrim = document.getElementById("drawerScrim");
const cartItems = document.getElementById("cartItems");
const cartTotal = document.getElementById("cartTotal");
const profilePanel = document.getElementById("profilePanel");

function formatPrice(value) {
  return new Intl.NumberFormat("tr-TR", {
    style: "currency",
    currency: "TRY",
    maximumFractionDigits: 0,
  }).format(value);
}

function showToast(message) {
  toast.textContent = message;
  toast.classList.remove("visible");
  window.requestAnimationFrame(() => toast.classList.add("visible"));
}

function isLocalPreview() {
  return window.location.protocol === "file:";
}

async function postForm(endpoint, formData) {
  const response = await fetch(endpoint, {
    method: "POST",
    body: formData,
  });
  const payload = await response.json().catch(() => ({}));
  if (!response.ok || payload.ok === false) {
    throw new Error(payload.message || "İşlem tamamlanamadı.");
  }
  return payload;
}

function escapeHtml(value) {
  return String(value ?? "")
    .replaceAll("&", "&amp;")
    .replaceAll("<", "&lt;")
    .replaceAll(">", "&gt;")
    .replaceAll('"', "&quot;")
    .replaceAll("'", "&#039;");
}

function safeVisualType(value) {
  return ["plate", "ring", "bracket", "rail"].includes(value) ? value : "plate";
}

async function loadProducts() {
  if (isLocalPreview()) return;
  try {
    const response = await fetch("api/products.php", { headers: { Accept: "application/json" } });
    const payload = await response.json();
    if (response.ok && payload.ok && Array.isArray(payload.products) && payload.products.length) {
      products = payload.products;
      state.activeProductId = products[0].id;
      state.cart = state.cart.filter((line) => products.some((product) => product.id === line.id));
      saveCart();
    }
  } catch {
    products = [...fallbackProducts];
  }
}

function setHash(view, productId) {
  const nextHash = productId ? `#${view}/${productId}` : `#${view}`;
  if (window.location.hash !== nextHash) {
    window.location.hash = nextHash;
  }
}

function navigate(view, productId, pushHash = true) {
  views.forEach((section) => {
    section.classList.toggle("active", section.dataset.view === view);
  });

  dock.classList.toggle("visible", view !== "gate");
  document.querySelectorAll("[data-nav-view]").forEach((button) => {
    button.classList.toggle("active", button.dataset.navView === view || (view === "product" && button.dataset.navView === "store"));
  });

  if (view === "product") {
    renderProductDetail(productId || state.activeProductId);
  }

  if (pushHash) {
    setHash(view, productId);
  }

  window.scrollTo({ top: 0, behavior: "smooth" });
}

function routeFromHash() {
  const hash = window.location.hash.replace("#", "");
  if (!hash) {
    navigate("gate", null, false);
    return;
  }
  const [view, productId] = hash.split("/");
  const knownViews = ["gate", "quote", "store", "contact", "profile", "product"];
  navigate(knownViews.includes(view) ? view : "gate", productId, false);
}

function productCard(product) {
  const id = escapeHtml(product.id);
  const name = escapeHtml(product.name);
  const media = product.imageUrl
    ? `<img src="${escapeHtml(product.imageUrl)}" alt="${name}" loading="lazy">`
    : "<span></span>";
  return `
    <article class="product-card">
      <button class="product-media ${safeVisualType(product.type)} ${product.imageUrl ? "has-image" : ""}" type="button" data-product-open="${id}" aria-label="${name} detay">
        ${media}
      </button>
      <div class="product-body">
        <div class="product-meta">
          <span>${escapeHtml(product.tag)}</span>
          <strong>${formatPrice(product.price)}</strong>
        </div>
        <h3>${name}</h3>
        <p>${escapeHtml(product.summary)}</p>
        <div class="product-actions">
          <button class="text-button" type="button" data-product-open="${id}">Detaya bak</button>
          <button class="round-action" type="button" data-add-cart="${id}" aria-label="${name} sepete ekle">
            <svg><use href="#icon-plus"></use></svg>
          </button>
        </div>
      </div>
    </article>
  `;
}

function renderProducts() {
  document.getElementById("industrialProducts").innerHTML = products
    .filter((product) => product.group === "industrial")
    .map(productCard)
    .join("");
  document.getElementById("retailProducts").innerHTML = products
    .filter((product) => product.group === "retail")
    .map(productCard)
    .join("");
}

function renderProductDetail(productId) {
  const product = products.find((item) => item.id === productId) || products[0];
  if (!product) return;
  state.activeProductId = product.id;
  state.detailQty = 1;
  const name = escapeHtml(product.name);
  const media = product.imageUrl
    ? `<img src="${escapeHtml(product.imageUrl)}" alt="${name}">`
    : "<span></span>";
  document.getElementById("productDetail").innerHTML = `
    <div class="product-hero">
      <div class="product-stage">
        <div class="product-media ${safeVisualType(product.type)} ${product.imageUrl ? "has-image" : ""}">${media}</div>
      </div>
      <div class="product-info">
        <span class="eyebrow">${escapeHtml(product.tag)}</span>
        <h1>${name}</h1>
        <p>${escapeHtml(product.detail)}</p>
        <strong class="product-price">${formatPrice(product.price)}</strong>
        <div>
          <span class="quantity-control" aria-label="Adet seçimi">
            <button type="button" data-detail-qty="-1" aria-label="Adet azalt"><svg><use href="#icon-minus"></use></svg></button>
            <span id="detailQty">1</span>
            <button type="button" data-detail-qty="1" aria-label="Adet artir"><svg><use href="#icon-plus"></use></svg></button>
          </span>
          <button class="primary-action" type="button" data-detail-add="${escapeHtml(product.id)}">Sepete ekle</button>
        </div>
      </div>
    </div>
    <div class="section-heading">
      <span class="eyebrow">Ürün bilgisi</span>
      <h2>${name} icin bilgi ve kaynak alani.</h2>
      <p>Bu sayfa yapısı, ileride ürüne özel teknik bilgi, kullanım senaryoları ve soru cevap içerikleriyle genişletilmeye hazır.</p>
    </div>
    <div class="knowledge-columns">
      <article class="knowledge-card">
        <h3>Kullanım Alanları</h3>
        <p>${escapeHtml(product.knowledgeUsage || "Ürünün kullanıldığı sektörler ve montaj senaryoları burada anlatılabilir.")}</p>
      </article>
      <article class="knowledge-card">
        <h3>Malzeme ve Ölçü</h3>
        <p>${escapeHtml(product.knowledgeMaterial || "Malzeme, kalınlık, yüzey işlemi ve tolerans bilgileri burada tutulabilir.")}</p>
      </article>
      <article class="knowledge-card">
        <h3>Sık Sorular</h3>
        <p>${escapeHtml(product.knowledgeFaq || "Ürüne özel sık sorulan sorular ve net cevaplar burada yayımlanabilir.")}</p>
      </article>
    </div>
  `;
}

function saveCart() {
  localStorage.setItem("kc_cart", JSON.stringify(state.cart));
}

function cartCount() {
  return state.cart.reduce((total, item) => total + item.qty, 0);
}

function updateCartBadge() {
  const count = cartCount();
  cartBadge.textContent = count;
  cartBadge.classList.toggle("visible", count > 0);
}

function addToCart(productId, qty = 1) {
  const product = products.find((item) => item.id === productId);
  if (!product) return;
  const line = state.cart.find((item) => item.id === productId);
  if (line) {
    line.qty += qty;
  } else {
    state.cart.push({ id: productId, qty });
  }
  saveCart();
  renderCart();
    showToast(`${product.name} sepete eklendi.`);
}

function updateCartLine(productId, delta) {
  const line = state.cart.find((item) => item.id === productId);
  if (!line) return;
  line.qty += delta;
  if (line.qty <= 0) {
    state.cart = state.cart.filter((item) => item.id !== productId);
  }
  saveCart();
  renderCart();
}

function removeCartLine(productId) {
  state.cart = state.cart.filter((item) => item.id !== productId);
  if (!state.cart.length) {
    state.checkoutOpen = false;
  }
  saveCart();
  renderCart();
}

function calculateCartTotal() {
  return state.cart.reduce((total, line) => {
    const product = products.find((item) => item.id === line.id);
    return product ? total + product.price * line.qty : total;
  }, 0);
}

function cartOrderItems() {
  return state.cart
    .map((line) => {
      const product = products.find((item) => item.id === line.id);
      if (!product) return null;
      return {
        id: product.id,
        qty: line.qty,
      };
    })
    .filter(Boolean);
}

function checkoutFormHtml() {
  return `
    <form class="checkout-form" id="orderForm">
      <div>
        <span class="eyebrow">Sipariş bilgileri</span>
        <h3>Mağaza sipariş talebi</h3>
        <p>Canlı ödeme bağlanana kadar bu form sipariş talebini ekibe iletir.</p>
      </div>
      <label>
        Ad soyad
        <input name="name" required autocomplete="name" placeholder="Adınız ve soyadınız" />
      </label>
      <label>
        Telefon
        <input name="phone" required autocomplete="tel" placeholder="+90 5.." />
      </label>
      <label>
        E-posta
        <input name="email" required type="email" autocomplete="email" placeholder="ornek@firma.com" />
      </label>
      <label>
        Teslimat adresi
        <textarea name="address" required rows="3" placeholder="İl, ilçe, açık adres"></textarea>
      </label>
      <button class="primary-action full" type="submit">Sipariş talebini gönder</button>
    </form>
  `;
}

function renderCart() {
  updateCartBadge();
  if (!state.cart.length) {
    cartItems.innerHTML = `<div class="cart-empty"><p>Sepetiniz şu an boş.</p></div>`;
    cartTotal.textContent = formatPrice(0);
    document.getElementById("checkoutButton").disabled = false;
    document.getElementById("checkoutButton").innerHTML = `<svg><use href="#icon-credit" /></svg> Ödeme adımına geç`;
    return;
  }

  let total = 0;
  cartItems.innerHTML = state.cart
    .map((line) => {
      const product = products.find((item) => item.id === line.id);
      if (!product) return "";
      total += product.price * line.qty;
      return `
        <article class="cart-line">
          <span class="cart-thumb"></span>
          <div>
            <h3>${product.name}</h3>
            <p>${formatPrice(product.price)} x ${line.qty}</p>
          </div>
          <div class="cart-line-actions">
            <span class="tiny-qty">
            <button type="button" data-cart-delta="-1" data-cart-id="${line.id}" aria-label="Adet azalt">
                <svg><use href="#icon-minus"></use></svg>
              </button>
              <span>${line.qty}</span>
            <button type="button" data-cart-delta="1" data-cart-id="${line.id}" aria-label="Adet artır">
                <svg><use href="#icon-plus"></use></svg>
              </button>
            </span>
            <button class="remove-line" type="button" data-cart-remove="${line.id}" aria-label="Ürünü kaldır">
              <svg><use href="#icon-trash"></use></svg>
            </button>
          </div>
        </article>
      `;
    })
    .join("");

  if (state.checkoutOpen) {
    cartItems.insertAdjacentHTML("beforeend", checkoutFormHtml());
  }

  cartTotal.textContent = formatPrice(total);
  const checkoutButton = document.getElementById("checkoutButton");
  checkoutButton.disabled = state.checkoutOpen;
  checkoutButton.innerHTML = state.checkoutOpen
    ? `<svg><use href="#icon-credit" /></svg> Formu doldurun`
    : `<svg><use href="#icon-credit" /></svg> Ödeme adımına geç`;
}

function openCart() {
  cartDrawer.classList.add("open");
  cartDrawer.setAttribute("aria-hidden", "false");
  drawerScrim.classList.add("visible");
}

function closeCart() {
  cartDrawer.classList.remove("open");
  cartDrawer.setAttribute("aria-hidden", "true");
  drawerScrim.classList.remove("visible");
}

function accountStatusLabel(status) {
  return {
    new: "Yeni",
    in_review: "İnceleniyor",
    quoted: "Teklif verildi",
    confirmed: "Onaylandı",
    preparing: "Hazırlanıyor",
    shipped: "Kargolandı",
    completed: "Tamamlandı",
    cancelled: "İptal edildi",
  }[status] || status;
}

function accountDate(value) {
  return new Intl.DateTimeFormat("tr-TR", { dateStyle: "medium" }).format(new Date(value.replace(" ", "T")));
}

async function renderProfile() {
  profilePanel.innerHTML = `<div class="profile-loading">Hesap bilgileri yükleniyor...</div>`;
  if (isLocalPreview()) {
    profilePanel.innerHTML = `
      <div class="google-card">
        <div><h2>Google ile bağlan</h2><p>Gerçek müşteri hesabı canlı sunucuda Google bağlantısı tamamlandığında açılacak.</p></div>
        <button class="google-button" type="button" disabled>Google bağlantısı hazırlanıyor</button>
      </div>`;
    return;
  }

  try {
    const response = await fetch("api/account.php", { headers: { Accept: "application/json" } });
    const payload = await response.json();
    if (!payload.logged_in) {
      profilePanel.innerHTML = `
        <div class="google-card">
          <div>
            <h2>Google ile bağlan</h2>
            <p>Karadağ Çelik hesabınız yalnızca Google ile açılır. Ayrı bir üyelik parolası oluşturmanız gerekmez.</p>
          </div>
          ${payload.google_configured
            ? `<a class="google-button" href="${escapeHtml(payload.login_url || "account/google-start.php")}">Google ile devam et</a>`
            : `<button class="google-button" type="button" disabled>Google bağlantısı hazırlanıyor</button>`}
        </div>`;
      return;
    }

    const customer = payload.customer;
    const addresses = payload.addresses || [];
    const orders = payload.orders || [];
    const quotes = payload.quotes || [];
    profilePanel.innerHTML = `
      <div class="profile-dashboard">
        <div class="profile-account">
          ${customer.avatar ? `<img src="${escapeHtml(customer.avatar)}" alt="">` : `<span class="profile-avatar">${escapeHtml(customer.name.charAt(0))}</span>`}
          <div><h2>${escapeHtml(customer.name)}</h2><p>${escapeHtml(customer.email)}</p></div>
          <a class="secondary-action" href="account/logout.php">Oturumu kapat</a>
        </div>
        <div class="account-stat-grid">
          <div><strong>${quotes.length}</strong><span>Teklif talebi</span></div>
          <div><strong>${orders.length}</strong><span>Sipariş</span></div>
          <div><strong>${addresses.length}</strong><span>Kayıtlı adres</span></div>
        </div>
        <section class="account-section">
          <div class="account-heading"><div><span class="eyebrow">Teslimat</span><h3>Kayıtlı adresler</h3></div></div>
          <div class="address-list">
            ${addresses.length
              ? addresses.map((address) => `<article><strong>${escapeHtml(address.label)}</strong><p>${escapeHtml(address.recipient_name)} · ${escapeHtml(address.phone)}</p><p>${escapeHtml(address.address_line)}, ${escapeHtml(address.district)}/${escapeHtml(address.city)}</p></article>`).join("")
              : `<p class="account-empty">Henüz kayıtlı adresiniz yok.</p>`}
          </div>
          <details class="address-create">
            <summary>Yeni adres ekle</summary>
            <form id="addressForm" class="account-form">
              <input type="hidden" name="csrf" value="${escapeHtml(payload.csrf)}">
              <div class="account-form-grid">
                <label>Adres etiketi<input name="label" placeholder="Ev, iş, fabrika"></label>
                <label>Alıcı adı<input name="recipient_name" value="${escapeHtml(customer.name)}" required></label>
                <label>Telefon<input name="phone" value="${escapeHtml(customer.phone || "")}" required></label>
                <label>Şehir<input name="city" required></label>
                <label>İlçe<input name="district" required></label>
                <label>Posta kodu<input name="postal_code"></label>
              </div>
              <label>Açık adres<textarea name="address_line" rows="3" required></textarea></label>
              <button class="primary-action" type="submit">Adresi kaydet</button>
            </form>
          </details>
        </section>
        <section class="account-section">
          <div class="account-heading"><div><span class="eyebrow">Üretim talepleri</span><h3>Teklif geçmişi</h3></div></div>
          <div class="account-records">
            ${quotes.length
              ? quotes.map((quote) => `<div><span><strong>${escapeHtml(quote.code)}</strong><small>${escapeHtml([quote.material, quote.thickness].filter(Boolean).join(" · ") || "Proje talebi")}</small></span><span><b>${escapeHtml(accountStatusLabel(quote.status))}</b><small>${accountDate(quote.created_at)}</small></span></div>`).join("")
              : `<p class="account-empty">Henüz teklif talebiniz yok.</p>`}
          </div>
        </section>
        <section class="account-section">
          <div class="account-heading"><div><span class="eyebrow">Mağaza</span><h3>Sipariş geçmişi</h3></div></div>
          <div class="account-records">
            ${orders.length
              ? orders.map((order) => `<div><span><strong>${escapeHtml(order.code)}</strong><small>${escapeHtml(order.total_text)}</small></span><span><b>${escapeHtml(accountStatusLabel(order.status))}</b><small>${accountDate(order.created_at)}</small></span></div>`).join("")
              : `<p class="account-empty">Henüz mağaza siparişiniz yok.</p>`}
          </div>
        </section>
      </div>`;
  } catch {
    profilePanel.innerHTML = `<div class="account-empty">Hesap bilgileri şu anda yüklenemiyor.</div>`;
  }
}

function bindEvents() {
  document.querySelectorAll("[data-target-view]").forEach((button) => {
    button.addEventListener("click", () => navigate(button.dataset.targetView));
  });

  document.querySelectorAll("[data-nav-view]").forEach((button) => {
    button.addEventListener("click", () => navigate(button.dataset.navView));
  });

  document.body.addEventListener("click", (event) => {
    const openButton = event.target.closest("[data-product-open]");
    if (openButton) {
      navigate("product", openButton.dataset.productOpen);
    }

    const addButton = event.target.closest("[data-add-cart]");
    if (addButton) {
      addToCart(addButton.dataset.addCart);
    }

    const detailAdd = event.target.closest("[data-detail-add]");
    if (detailAdd) {
      addToCart(detailAdd.dataset.detailAdd, state.detailQty);
    }

    const detailQty = event.target.closest("[data-detail-qty]");
    if (detailQty) {
      const nextQty = Math.max(1, state.detailQty + Number(detailQty.dataset.detailQty));
      state.detailQty = nextQty;
      document.getElementById("detailQty").textContent = nextQty;
    }

    const cartDelta = event.target.closest("[data-cart-delta]");
    if (cartDelta) {
      updateCartLine(cartDelta.dataset.cartId, Number(cartDelta.dataset.cartDelta));
    }

    const cartRemove = event.target.closest("[data-cart-remove]");
    if (cartRemove) {
      removeCartLine(cartRemove.dataset.cartRemove);
    }

  });

  document.getElementById("cartTrigger").addEventListener("click", openCart);
  document.getElementById("closeCart").addEventListener("click", closeCart);
  drawerScrim.addEventListener("click", closeCart);

  document.getElementById("checkoutButton").addEventListener("click", () => {
    if (!state.cart.length) {
      showToast("Ödeme için önce sepete ürün ekleyin.");
      return;
    }
    state.checkoutOpen = true;
    renderCart();
    window.requestAnimationFrame(() => {
      const form = document.getElementById("orderForm");
      if (form) form.scrollIntoView({ behavior: "smooth", block: "nearest" });
    });
  });

  document.getElementById("quoteFiles").addEventListener("change", (event) => {
    const files = [...event.target.files];
    document.getElementById("fileSummary").textContent = files.length
      ? `${files.length} dosya seçildi: ${files.map((file) => file.name).slice(0, 2).join(", ")}`
      : "Dosya seçilmedi";
  });

  document.getElementById("quoteForm").addEventListener("submit", async (event) => {
    event.preventDefault();
    const form = event.currentTarget;
    const button = form.querySelector("button[type='submit']");
    const originalText = button.textContent;

    if (isLocalPreview()) {
      form.reset();
      document.getElementById("fileSummary").textContent = "Dosya seçilmedi";
      showToast("Form hazır. Hostinger'a yüklendiğinde e-posta ve dosya kaydı çalışacak.");
      return;
    }

    button.textContent = "Gönderiliyor...";
    button.disabled = true;
    try {
      const payload = await postForm("api/quote.php", new FormData(form));
      form.reset();
      document.getElementById("fileSummary").textContent = "Dosya seçilmedi";
      showToast(`Teklif talebiniz alındı. Talep no: ${payload.request_id}`);
    } catch (error) {
      showToast(error.message);
    } finally {
      button.textContent = originalText;
      button.disabled = false;
    }
  });

  document.getElementById("contactForm").addEventListener("submit", async (event) => {
    event.preventDefault();
    const form = event.currentTarget;
    const button = form.querySelector("button[type='submit']");
    const originalText = button.textContent;

    if (isLocalPreview()) {
      form.reset();
      showToast("Mesaj formu hazır. Hostinger'a yüklendiğinde e-posta gönderimi çalışacak.");
      return;
    }

    button.textContent = "Gönderiliyor...";
    button.disabled = true;
    try {
      const payload = await postForm("api/contact.php", new FormData(form));
      form.reset();
      showToast(`Mesajınız alındı. Mesaj no: ${payload.request_id}`);
    } catch (error) {
      showToast(error.message);
    } finally {
      button.textContent = originalText;
      button.disabled = false;
    }
  });

  document.body.addEventListener("submit", async (event) => {
    if (event.target.id === "addressForm") {
      event.preventDefault();
      const form = event.target;
      const button = form.querySelector("button[type='submit']");
      button.disabled = true;
      try {
        const payload = await postForm("api/address.php", new FormData(form));
        showToast(payload.message || "Adres kaydedildi.");
        await renderProfile();
      } catch (error) {
        showToast(error.message);
      } finally {
        button.disabled = false;
      }
      return;
    }

    if (event.target.id !== "orderForm") return;
    event.preventDefault();

    const form = event.target;
    const button = form.querySelector("button[type='submit']");
    const originalText = button.textContent;

    if (isLocalPreview()) {
      showToast("Sipariş formu hazır. Hostinger'a yüklendiğinde e-posta bildirimi çalışacak.");
      return;
    }

    const formData = new FormData(form);
    formData.append("items", JSON.stringify(cartOrderItems()));
    formData.append("total", formatPrice(calculateCartTotal()));

    button.textContent = "Gönderiliyor...";
    button.disabled = true;
    try {
      const payload = await postForm("api/order.php", formData);
      state.cart = [];
      state.checkoutOpen = false;
      saveCart();
      renderCart();
      showToast(`Sipariş talebiniz alındı. Sipariş no: ${payload.request_id}`);
    } catch (error) {
      showToast(error.message);
    } finally {
      button.textContent = originalText;
      button.disabled = false;
    }
  });

  window.addEventListener("hashchange", routeFromHash);
}

async function initialize() {
  await loadProducts();
  renderProducts();
  renderCart();
  await renderProfile();
  bindEvents();
  routeFromHash();
}

initialize();
